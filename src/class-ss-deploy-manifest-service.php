<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds, stores, and exposes deploy manifests from Simply Static export data.
 */
class Deploy_Manifest_Service {

	const MANIFEST_VERSION = '1.0';
	const SCHEMA_VERSION   = '1';

	/**
	 * Singleton instance.
	 *
	 * @var Deploy_Manifest_Service|null
	 */
	protected static $instance = null;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * Return singleton instance.
	 *
	 * @return Deploy_Manifest_Service
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register export hooks.
	 */
	protected function __construct() {
		$this->options = Options::instance();
		add_action( 'ss_completed', array( $this, 'store_current_manifest' ), 20, 2 );
	}

	/**
	 * Ensure manifest tables exist.
	 *
	 * @return void
	 */
	public static function create_or_update_tables() {
		Deploy_Manifest::create_or_update_table();
		Deploy_Manifest_Url::create_or_update_table();
	}

	/**
	 * Store a manifest for the current export.
	 *
	 * @param string $result Export result from ss_completed.
	 * @param string $message Optional failure message.
	 *
	 * @return array|null
	 */
	public function store_current_manifest( $result = 'success', $message = '' ) {
		try {
			$manifest = $this->build_current_manifest( $result, $message );
			$this->persist_manifest( $manifest );

			return $manifest;
		} catch ( \Throwable $e ) {
			Util::debug_log( 'Unable to store deploy manifest: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Build the current manifest from Simply Static page records.
	 *
	 * @param string $result Export result.
	 * @param string $message Optional failure message.
	 *
	 * @return array
	 */
	public function build_current_manifest( $result = 'success', $message = '' ) {
		$deploy_id = $this->options->get( 'archive_deploy_id' );

		if ( empty( $deploy_id ) ) {
			$deploy_id = $this->new_deploy_id();
			$this->options->set( 'archive_deploy_id', $deploy_id )->save();
		}

		$started_at  = $this->options->get( 'archive_start_time' );
		$finished_at = $this->options->get( 'archive_end_time' );
		$status      = $this->normalize_status( $result );
		$records     = $this->build_url_records();
		$counts      = $this->count_url_records( $records );
		$root_files  = array_values( array_filter( $records, array( $this, 'is_root_record' ) ) );
		$warnings    = $this->collect_manifest_messages( $records, 'warnings' );
		$errors      = $this->collect_manifest_messages( $records, 'errors' );

		if ( ! empty( $message ) && 'success' !== $status ) {
			$errors[] = array(
				'code'    => $status,
				'message' => wp_strip_all_tags( (string) $message ),
			);
		}

		$duration = null;
		if ( ! empty( $started_at ) && ! empty( $finished_at ) ) {
			$duration = max( 0, strtotime( $finished_at ) - strtotime( $started_at ) );
		}

		$manifest = array(
			'manifest_version' => self::MANIFEST_VERSION,
			'deploy_id'        => $deploy_id,
			'site_id'          => (string) get_current_blog_id(),
			'environment_id'   => null,
			'domain'           => $this->get_domain(),
			'mount_path'       => $this->get_mount_path(),
			'started_at'       => $started_at,
			'finished_at'      => $finished_at,
			'duration_seconds' => $duration,
			'status'           => $status,
			'plugin_version'   => defined( 'SIMPLY_STATIC_VERSION' ) ? SIMPLY_STATIC_VERSION : null,
			'studio_version'   => defined( 'SSS_VERSION' ) ? SSS_VERSION : null,
			'wp_version'       => get_bloginfo( 'version' ),
			'php_version'      => PHP_VERSION,
			'theme'            => $this->get_theme(),
			'detected_builders'=> $this->get_detected_builders(),
			'export_config_hash' => $this->get_export_config_hash(),
			'generate_type'    => $this->options->get( 'generate_type' ),
			'url_counts'       => $counts,
			'warnings'         => $warnings,
			'errors'           => $errors,
			'root_files'       => $this->summarize_root_files( $root_files ),
			'snapshot_id'      => null,
			'urls'             => $records,
		);

		return apply_filters( 'ss_deploy_manifest', $manifest, $records, $this );
	}

	/**
	 * Return manifests for REST lists.
	 *
	 * @param int $page Current page.
	 * @param int $per_page Per-page count.
	 *
	 * @return array
	 */
	public function get_exports( $page = 1, $per_page = 20 ) {
		global $wpdb;

		$page     = max( 1, (int) $page );
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = Deploy_Manifest::table_name();
		$site_id  = (int) get_current_blog_id();
		$total    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE site_id = %d", $site_id ) );
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT deploy_id, manifest_version, status, domain, mount_path, started_at, finished_at, duration_seconds, plugin_version, wp_version, php_version, generate_type, url_counts, root_files, warnings, errors FROM `{$table}` WHERE site_id = %d ORDER BY finished_at DESC, id DESC LIMIT %d OFFSET %d",
				$site_id,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return array(
			'exports'     => array_map( array( $this, 'format_manifest_summary_row' ), $rows ?: array() ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Return latest stored manifest.
	 *
	 * @return array|null
	 */
	public function get_latest_manifest() {
		global $wpdb;

		$table   = Deploy_Manifest::table_name();
		$site_id = (int) get_current_blog_id();
		$row     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE site_id = %d ORDER BY finished_at DESC, id DESC LIMIT 1",
				$site_id
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		return $this->hydrate_manifest_row( $row );
	}

	/**
	 * Return stored manifest by deploy ID.
	 *
	 * @param string $deploy_id Deploy ID.
	 *
	 * @return array|null
	 */
	public function get_manifest( $deploy_id ) {
		global $wpdb;

		$table   = Deploy_Manifest::table_name();
		$site_id = (int) get_current_blog_id();
		$row     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE site_id = %d AND deploy_id = %s LIMIT 1",
				$site_id,
				$deploy_id
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		return $this->hydrate_manifest_row( $row );
	}

	/**
	 * Return paginated URL records for a deploy.
	 *
	 * @param string $deploy_id Deploy ID.
	 * @param array  $args Query args.
	 *
	 * @return array
	 */
	public function get_manifest_urls( $deploy_id, $args = array() ) {
		global $wpdb;

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 500, (int) ( $args['per_page'] ?? 100 ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = Deploy_Manifest_Url::table_name();
		$site_id  = (int) get_current_blog_id();
		$where      = array( 'site_id = %d', 'deploy_id = %s' );
		$where_args = array( $site_id, $deploy_id );

		if ( ! empty( $args['status'] ) ) {
			$status = sanitize_text_field( $args['status'] );
			if ( is_numeric( $status ) ) {
				$where[] = 'status_code = %d';
				$where_args[] = (int) $status;
			} elseif ( 'warning' === $status ) {
				$where[] = "(warnings IS NOT NULL AND warnings != '' AND warnings != '[]')";
			} elseif ( 'failed' === $status ) {
				$where[] = "(errors IS NOT NULL AND errors != '' AND errors != '[]')";
			}
		}

		if ( ! empty( $args['type'] ) ) {
			$where[] = 'type = %s';
			$where_args[] = sanitize_text_field( $args['type'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[] = '(url LIKE %s OR static_path LIKE %s)';
			$where_args[] = $like;
			$where_args[] = $like;
		}

		$where_sql = 'WHERE ' . implode( ' AND ', $where );
		$total     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` {$where_sql}", $where_args ) );
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` {$where_sql} ORDER BY status_code DESC, id ASC LIMIT %d OFFSET %d",
				array_merge( $where_args, array( $per_page, $offset ) )
			),
			ARRAY_A
		);

		return array(
			'urls'        => array_map( array( $this, 'format_url_row' ), $rows ?: array() ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Return root/static file records for a deploy.
	 *
	 * @param string $deploy_id Deploy ID.
	 *
	 * @return array
	 */
	public function get_manifest_files( $deploy_id ) {
		global $wpdb;

		$table   = Deploy_Manifest_Url::table_name();
		$site_id = (int) get_current_blog_id();
		$types   = array( 'root_file', 'sitemap', 'markdown', 'file_override' );
		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$query_args   = array_merge( array( $site_id, $deploy_id ), $types );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE site_id = %d AND deploy_id = %s AND type IN ({$placeholders}) ORDER BY status_code DESC, id ASC",
				$query_args
			),
			ARRAY_A
		);
		$files = array_map( array( $this, 'format_url_row' ), $rows ?: array() );

		return array(
			'files' => $files,
			'total' => count( $files ),
		);
	}

	/**
	 * Return warnings for a deploy.
	 *
	 * @param string $deploy_id Deploy ID.
	 *
	 * @return array
	 */
	public function get_manifest_warnings( $deploy_id ) {
		$manifest = $this->get_manifest( $deploy_id );

		if ( empty( $manifest ) ) {
			return array( 'warnings' => array(), 'errors' => array() );
		}

		return array(
			'warnings' => $manifest['warnings'],
			'errors'   => $manifest['errors'],
		);
	}

	/**
	 * Persist manifest metadata and URL records.
	 *
	 * @param array $manifest Manifest.
	 *
	 * @return void
	 */
	protected function persist_manifest( $manifest ) {
		global $wpdb;

		$manifest_table = Deploy_Manifest::table_name();
		$url_table      = Deploy_Manifest_Url::table_name();
		$deploy_id      = $manifest['deploy_id'];
		$site_id        = (int) get_current_blog_id();
		$manifest_copy  = $manifest;
		$urls           = $manifest_copy['urls'];
		$now            = Util::formatted_datetime();
		unset( $manifest_copy['urls'] );

		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			throw new \RuntimeException( 'Unable to start a deploy manifest transaction.' );
		}

		try {
			if ( false === $wpdb->delete( $manifest_table, array( 'deploy_id' => $deploy_id, 'site_id' => $site_id ) ) ) {
				throw new \RuntimeException( 'Unable to remove the previous deploy manifest.' );
			}

			if ( false === $wpdb->delete( $url_table, array( 'deploy_id' => $deploy_id, 'site_id' => $site_id ) ) ) {
				throw new \RuntimeException( 'Unable to remove the previous deploy manifest URLs.' );
			}

			$inserted = $wpdb->insert(
				$manifest_table,
				array(
					'deploy_id'        => $deploy_id,
					'site_id'          => $site_id,
					'manifest_version' => $manifest['manifest_version'],
					'status'           => $manifest['status'],
					'domain'           => $manifest['domain'],
					'mount_path'       => $manifest['mount_path'],
					'started_at'       => $manifest['started_at'],
					'finished_at'      => $manifest['finished_at'],
					'duration_seconds' => $manifest['duration_seconds'],
					'plugin_version'   => $manifest['plugin_version'],
					'wp_version'       => $manifest['wp_version'],
					'php_version'      => $manifest['php_version'],
					'generate_type'    => $manifest['generate_type'],
					'url_counts'       => wp_json_encode( $manifest['url_counts'] ),
					'root_files'       => wp_json_encode( $manifest['root_files'] ),
					'warnings'         => wp_json_encode( $manifest['warnings'] ),
					'errors'           => wp_json_encode( $manifest['errors'] ),
					'manifest'         => wp_json_encode( $manifest_copy ),
					'created_at'       => $now,
					'updated_at'       => $now,
				)
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Unable to store the deploy manifest.' );
			}

			$this->persist_manifest_urls( $url_table, $deploy_id, $site_id, $urls, $now );

			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new \RuntimeException( 'Unable to commit the deploy manifest.' );
			}
		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Persist URL records in bounded multi-row inserts.
	 *
	 * @param string $table     URL table name.
	 * @param string $deploy_id Deploy ID.
	 * @param int    $site_id   Site ID.
	 * @param array  $records   URL records.
	 * @param string $now       Shared timestamp.
	 *
	 * @return void
	 */
	protected function persist_manifest_urls( $table, $deploy_id, $site_id, $records, $now ) {
		global $wpdb;

		if ( empty( $records ) ) {
			return;
		}

		$columns = array(
			'deploy_id',
			'site_id',
			'url',
			'source_url',
			'static_path',
			'type',
			'status_code',
			'content_hash',
			'file_size',
			'redirect_target',
			'found_on',
			'in_sitemap',
			'markdown_generated',
			'warnings',
			'errors',
			'created_at',
			'updated_at',
		);
		$column_sql = '`' . implode( '`, `', $columns ) . '`';
		$batch_size = max( 1, min( 500, (int) apply_filters( 'ss_deploy_manifest_insert_batch_size', 200 ) ) );

		foreach ( array_chunk( $records, $batch_size ) as $batch ) {
			$value_sql = array();
			$args      = array();

			foreach ( $batch as $record ) {
				$values = array(
					array( $deploy_id, '%s' ),
					array( $site_id, '%d' ),
					array( $record['url'] ?? '', '%s' ),
					array( $record['source_url'] ?? null, '%s' ),
					array( $record['static_path'] ?? null, '%s' ),
					array( $record['type'] ?? 'other', '%s' ),
					array( $record['status_code'] ?? null, '%d' ),
					array( $record['content_hash'] ?? null, '%s' ),
					array( $record['file_size'] ?? null, '%d' ),
					array( $record['redirect_target'] ?? null, '%s' ),
					array( wp_json_encode( $record['found_on'] ?? array() ), '%s' ),
					array( $this->nullable_bool_to_db( $record['in_sitemap'] ?? null ), '%d' ),
					array( $this->nullable_bool_to_db( $record['markdown_generated'] ?? null ), '%d' ),
					array( wp_json_encode( $record['warnings'] ?? array() ), '%s' ),
					array( wp_json_encode( $record['errors'] ?? array() ), '%s' ),
					array( $now, '%s' ),
					array( $now, '%s' ),
				);
				$placeholders = array();

				foreach ( $values as $value ) {
					if ( null === $value[0] ) {
						$placeholders[] = 'NULL';
						continue;
					}

					$placeholders[] = $value[1];
					$args[]          = $value[0];
				}

				$value_sql[] = '(' . implode( ', ', $placeholders ) . ')';
			}

			$sql      = "INSERT INTO `{$table}` ({$column_sql}) VALUES " . implode( ', ', $value_sql );
			$prepared = $wpdb->prepare( $sql, $args );

			if ( ! is_string( $prepared ) || false === $wpdb->query( $prepared ) ) {
				throw new \RuntimeException( 'Unable to store deploy manifest URLs.' );
			}
		}
	}

	/**
	 * Build URL records from Page rows.
	 *
	 * @return array
	 */
	protected function build_url_records() {
		$query = Page::query()->order( 'url ASC' );
		$scope = $this->get_export_scope();
		$this->apply_scope( $query, $scope );
		$pages = $query->find();

		if ( empty( $pages ) ) {
			return array();
		}

		$records     = array();
		$parent_urls = $this->get_parent_url_map( $pages );

		foreach ( $pages as $page ) {
			$parent_id = (int) $page->found_on_id;
			$records[] = $this->format_page_record( $page, $parent_urls[ $parent_id ] ?? null );
		}

		return $records;
	}

	/**
	 * Format a Page model as a manifest URL record.
	 *
	 * @param Page        $page       Page model.
	 * @param string|null $parent_url Parent URL, when known.
	 *
	 * @return array
	 */
	protected function format_page_record( $page, $parent_url = null ) {
		$found_on = array();

		if ( ! empty( $parent_url ) ) {
			$found_on[] = $parent_url;
		}

		$warnings = $this->parse_messages( $page->status_message );
		$errors   = $this->parse_messages( $page->error_message );
		$type     = $this->detect_record_type( $page );

		return array(
			'url'                => $page->url,
			'source_url'         => null,
			'static_path'        => $page->file_path,
			'type'               => $type,
			'status_code'        => null === $page->http_status_code ? null : (int) $page->http_status_code,
			'content_hash'       => $this->format_content_hash( $page->content_hash ),
			'file_size'          => $this->get_file_size( $page->file_path ),
			'redirect_target'    => $page->redirect_url,
			'found_on'           => $found_on,
			'in_sitemap'         => null,
			'markdown_generated' => 'markdown' === $type ? true : null,
			'warnings'           => $warnings,
			'errors'             => $errors,
		);
	}

	/**
	 * Resolve parent URLs in batches instead of issuing one query per page.
	 *
	 * @param Page[] $pages Page models.
	 *
	 * @return array<int,string>
	 */
	protected function get_parent_url_map( $pages ) {
		$parent_ids = array();
		$url_map    = array();

		foreach ( $pages as $page ) {
			$page_id   = (int) $page->id;
			$parent_id = (int) $page->found_on_id;

			if ( $page_id > 0 && ! empty( $page->url ) ) {
				$url_map[ $page_id ] = (string) $page->url;
			}

			if ( $parent_id > 0 ) {
				$parent_ids[ $parent_id ] = $parent_id;
			}
		}

		$missing_ids = array_diff_key( $parent_ids, $url_map );

		foreach ( array_chunk( array_values( $missing_ids ), 1000 ) as $id_batch ) {
			$parents = Page::query()
				->where( 'id IN (' . implode( ',', array_map( 'intval', $id_batch ) ) . ')' )
				->find();

			foreach ( $parents ?: array() as $parent ) {
				if ( (int) $parent->id > 0 && ! empty( $parent->url ) ) {
					$url_map[ (int) $parent->id ] = (string) $parent->url;
				}
			}
		}

		return $url_map;
	}

	/**
	 * Detect manifest type for a page row.
	 *
	 * @param Page $page Page model.
	 *
	 * @return string
	 */
	protected function detect_record_type( $page ) {
		$path = ltrim( (string) $page->file_path, '/' );
		$url_path = trim( (string) wp_parse_url( $page->url, PHP_URL_PATH ), '/' );
		$status = (int) $page->http_status_code;

		if ( in_array( $status, array( 301, 302, 303, 307, 308 ), true ) || ! empty( $page->redirect_url ) ) {
			return 'redirect';
		}

		if ( preg_match( '/\.md$/i', $path ) || preg_match( '/\.md$/i', $url_path ) ) {
			return 'markdown';
		}

		if ( $this->is_sitemap_path( $path ) || $this->is_sitemap_path( $url_path ) ) {
			return 'sitemap';
		}

		if ( $this->is_root_path( $path ) || $this->is_root_path( $url_path ) ) {
			return 'root_file';
		}

		if ( $page->is_type( 'text/html' ) || '' === $path || preg_match( '#(^|/)index\.html?$#i', $path ) ) {
			return 'page';
		}

		if ( $page->is_binary_file() || preg_match( '/\.(css|js|png|jpe?g|gif|webp|svg|ico|avif|pdf|woff2?|ttf|eot|mp4|webm|mp3|wav)$/i', $path ) ) {
			return 'asset';
		}

		if ( false !== strpos( $url_path, 'wp-json/' ) ) {
			return 'api';
		}

		return 'other';
	}

	/**
	 * Count URL records by status/type.
	 *
	 * @param array $records URL records.
	 *
	 * @return array
	 */
	protected function count_url_records( $records ) {
		$counts = array(
			'total'          => count( $records ),
			'successful'     => 0,
			'redirects'      => 0,
			'not_found'      => 0,
			'failed'         => 0,
			'skipped'        => 0,
			'assets'         => 0,
			'root_files'     => 0,
			'markdown_files' => 0,
			'warnings'       => 0,
			'errors'         => 0,
		);

		foreach ( $records as $record ) {
			$status = (int) $record['status_code'];

			if ( $status >= 200 && $status < 300 ) {
				$counts['successful']++;
			} elseif ( $status >= 300 && $status < 400 ) {
				$counts['redirects']++;
			} elseif ( 404 === $status ) {
				$counts['not_found']++;
			} elseif ( $status >= 400 || ! empty( $record['errors'] ) ) {
				$counts['failed']++;
			}

			if ( 'asset' === $record['type'] ) {
				$counts['assets']++;
			}

			if ( in_array( $record['type'], array( 'root_file', 'sitemap' ), true ) ) {
				$counts['root_files']++;
			}

			if ( 'markdown' === $record['type'] ) {
				$counts['markdown_files']++;
			}

			if ( ! empty( $record['warnings'] ) ) {
				$counts['warnings']++;
			}

			if ( ! empty( $record['errors'] ) ) {
				$counts['errors']++;
			}
		}

		return $counts;
	}

	/**
	 * Get current export scope.
	 *
	 * @return array
	 */
	protected function get_export_scope() {
		$use_single         = get_option( 'simply-static-use-single' );
		$use_build          = get_option( 'simply-static-use-build' );
		$generate_type      = $this->options->get( 'generate_type' );
		$archive_start_time = $this->options->get( 'archive_start_time' );

		if ( ! empty( $use_single ) ) {
			$ids = array_values( array_filter( array_map( 'intval', explode( ',', $use_single ) ) ) );

			if ( ! empty( $ids ) ) {
				return array(
					'type' => 'single',
					'ids'  => $ids,
				);
			}
		}

		if ( 'update' === $generate_type && empty( $use_build ) && ! empty( $archive_start_time ) ) {
			return array(
				'type'               => 'update',
				'archive_start_time' => $archive_start_time,
			);
		}

		return array();
	}

	/**
	 * Apply export scope to a query.
	 *
	 * @param Query $query Query object.
	 * @param array $scope Scope.
	 *
	 * @return void
	 */
	protected function apply_scope( $query, $scope ) {
		if ( empty( $scope ) || empty( $scope['type'] ) ) {
			return;
		}

		if ( 'single' === $scope['type'] && ! empty( $scope['ids'] ) ) {
			$ids = array_map( 'intval', $scope['ids'] );

			if ( count( $ids ) === 1 ) {
				$query->where( 'post_id = ?', $ids[0] );
			} else {
				$query->where( 'post_id IN (' . implode( ',', $ids ) . ')' );
			}
		}

		if ( 'update' === $scope['type'] && ! empty( $scope['archive_start_time'] ) ) {
			$query->where( 'last_checked_at >= ?', $scope['archive_start_time'] );
			$query->where( 'updated_at >= ?', $scope['archive_start_time'] );
		}
	}

	/**
	 * Create a deploy id.
	 *
	 * @return string
	 */
	protected function new_deploy_id() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'deploy_', true );
	}

	/**
	 * Normalize export status.
	 *
	 * @param string $result Result.
	 *
	 * @return string
	 */
	protected function normalize_status( $result ) {
		if ( 'success' === $result ) {
			return 'success';
		}

		if ( 'cancel' === $result || 'cancelled' === $result ) {
			return 'cancelled';
		}

		return 'failed';
	}

	/**
	 * Parse semicolon-separated messages.
	 *
	 * @param string|null $messages Messages.
	 *
	 * @return array
	 */
	protected function parse_messages( $messages ) {
		if ( empty( $messages ) ) {
			return array();
		}

		$parts = array_filter( array_map( 'trim', explode( ';', wp_strip_all_tags( (string) $messages ) ) ) );

		return array_values(
			array_map(
				function ( $message ) {
					return array(
						'code'    => sanitize_key( substr( $message, 0, 40 ) ),
						'message' => $message,
					);
				},
				array_unique( $parts )
			)
		);
	}

	/**
	 * Collect global messages from URL records.
	 *
	 * @param array  $records URL records.
	 * @param string $key warnings or errors.
	 *
	 * @return array
	 */
	protected function collect_manifest_messages( $records, $key ) {
		$messages = array();

		foreach ( $records as $record ) {
			if ( empty( $record[ $key ] ) ) {
				continue;
			}

			foreach ( $record[ $key ] as $message ) {
				$message['url'] = $record['url'];
				$messages[] = $message;
			}
		}

		return $messages;
	}

	/**
	 * Is a root/static file record.
	 *
	 * @param array $record URL record.
	 *
	 * @return bool
	 */
	protected function is_root_record( $record ) {
		return in_array( $record['type'], array( 'root_file', 'sitemap' ), true );
	}

	/**
	 * Summarize root files.
	 *
	 * @param array $records Root records.
	 *
	 * @return array
	 */
	protected function summarize_root_files( $records ) {
		return array_map(
			function ( $record ) {
				return array(
					'url'          => $record['url'],
					'static_path'  => $record['static_path'],
					'type'         => $record['type'],
					'status_code'  => $record['status_code'],
					'content_hash' => $record['content_hash'],
					'file_size'    => $record['file_size'],
					'warnings'     => $record['warnings'],
					'errors'       => $record['errors'],
				);
			},
			$records
		);
	}

	/**
	 * Is sitemap path.
	 *
	 * @param string $path Path.
	 *
	 * @return bool
	 */
	protected function is_sitemap_path( $path ) {
		return (bool) preg_match( '#(^|/)sitemap[^/]*\.xml$#i', (string) $path );
	}

	/**
	 * Is supported root file path.
	 *
	 * @param string $path Path.
	 *
	 * @return bool
	 */
	protected function is_root_path( $path ) {
		$path = ltrim( (string) $path, '/' );

		return (bool) preg_match( '#^(robots|llms|ads|security|humans)\.txt$#i', $path )
			|| (bool) preg_match( '#^\.well-known/#i', $path );
	}

	/**
	 * Get final file size if available.
	 *
	 * @param string|null $file_path Static path.
	 *
	 * @return int|null
	 */
	protected function get_file_size( $file_path ) {
		if ( empty( $file_path ) ) {
			return null;
		}

		$archive_dir = $this->options->get_archive_dir();
		$full_path   = trailingslashit( $archive_dir ) . ltrim( $file_path, '/\\' );

		return file_exists( $full_path ) ? filesize( $full_path ) : null;
	}

	/**
	 * Normalize content hash.
	 *
	 * @param string|null $hash Hash.
	 *
	 * @return string|null
	 */
	protected function format_content_hash( $hash ) {
		if ( empty( $hash ) ) {
			return null;
		}

		if ( preg_match( '/^[a-f0-9]{40,64}$/i', $hash ) ) {
			return 'sha1:' . strtolower( $hash );
		}

		return 'sha1:' . bin2hex( $hash );
	}

	/**
	 * Get public domain.
	 *
	 * @return string
	 */
	protected function get_domain() {
		$destination = $this->options->get_destination_url();

		if ( empty( $destination ) || './' === $destination ) {
			$destination = home_url();
		}

		return untrailingslashit( $destination );
	}

	/**
	 * Get mount path when Studio stores it in options.
	 *
	 * @return string
	 */
	protected function get_mount_path() {
		$mount_path = $this->options->get( 'sss_mount_path' );

		if ( empty( $mount_path ) ) {
			$mount_path = $this->options->get( 'cdn_directory' );
		}

		$mount_path = trim( (string) $mount_path );
		if ( '' === $mount_path || '/' === $mount_path ) {
			return '';
		}

		return '/' . trim( $mount_path, '/' );
	}

	/**
	 * Get active theme metadata.
	 *
	 * @return array
	 */
	protected function get_theme() {
		$theme = wp_get_theme();

		return array(
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
		);
	}

	/**
	 * Detect common builders from active plugins.
	 *
	 * @return array
	 */
	protected function get_detected_builders() {
		$builders = array();

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$builders[] = 'Elementor';
		}

		if ( class_exists( 'FLBuilder' ) ) {
			$builders[] = 'Beaver Builder';
		}

		if ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) || function_exists( 'et_setup_theme' ) ) {
			$builders[] = 'Divi';
		}

		if ( defined( 'BRICKS_VERSION' ) ) {
			$builders[] = 'Bricks';
		}

		if ( defined( 'CT_VERSION' ) || defined( 'OXYGEN_VERSION' ) ) {
			$builders[] = 'Oxygen';
		}

		return array_values( array_unique( apply_filters( 'ss_deploy_manifest_detected_builders', $builders ) ) );
	}

	/**
	 * Hash export settings relevant to the manifest.
	 *
	 * @return string
	 */
	protected function get_export_config_hash() {
		$options = $this->options->get_as_array();
		$keys    = array(
			'destination_url_type',
			'destination_scheme',
			'destination_host',
			'relative_path',
			'delivery_method',
			'cdn_directory',
			'sss_mount_path',
			'generate_type',
			'additional_urls',
			'additional_files',
			'urls_to_exclude',
			'smart_crawl',
		);
		$config = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $options ) ) {
				$config[ $key ] = $options[ $key ];
			}
		}

		return 'sha256:' . hash( 'sha256', wp_json_encode( $config ) );
	}

	/**
	 * Format a stored manifest row.
	 *
	 * @param array $row Row.
	 *
	 * @return array
	 */
	protected function hydrate_manifest_row( $row ) {
		$manifest = json_decode( $row['manifest'], true );
		if ( ! is_array( $manifest ) ) {
			$manifest = array();
		}

		$manifest['urls'] = $this->get_manifest_urls(
			$row['deploy_id'],
			array(
				'page'     => 1,
				'per_page' => 200,
			)
		);

		return $manifest;
	}

	/**
	 * Format a summary row.
	 *
	 * @param array $row Row.
	 *
	 * @return array
	 */
	protected function format_manifest_summary_row( $row ) {
		$row['url_counts'] = $this->decode_json_field( $row['url_counts'] );
		$row['root_files'] = $this->decode_json_field( $row['root_files'] );
		$row['warnings']   = $this->decode_json_field( $row['warnings'] );
		$row['errors']     = $this->decode_json_field( $row['errors'] );

		return $row;
	}

	/**
	 * Format a URL row.
	 *
	 * @param array $row Row.
	 *
	 * @return array
	 */
	protected function format_url_row( $row ) {
		$row['status_code']        = null === $row['status_code'] ? null : (int) $row['status_code'];
		$row['file_size']          = null === $row['file_size'] ? null : (int) $row['file_size'];
		$row['found_on']           = $this->decode_json_field( $row['found_on'] );
		$row['in_sitemap']         = $this->db_to_nullable_bool( $row['in_sitemap'] );
		$row['markdown_generated'] = $this->db_to_nullable_bool( $row['markdown_generated'] );
		$row['warnings']           = $this->decode_json_field( $row['warnings'] );
		$row['errors']             = $this->decode_json_field( $row['errors'] );

		unset( $row['id'], $row['site_id'], $row['created_at'], $row['updated_at'] );

		return $row;
	}

	/**
	 * Decode JSON field.
	 *
	 * @param string|null $value JSON value.
	 *
	 * @return array
	 */
	protected function decode_json_field( $value ) {
		if ( empty( $value ) ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Convert nullable bool to DB value.
	 *
	 * @param bool|null $value Value.
	 *
	 * @return int|null
	 */
	protected function nullable_bool_to_db( $value ) {
		if ( null === $value ) {
			return null;
		}

		return $value ? 1 : 0;
	}

	/**
	 * Convert DB value to nullable bool.
	 *
	 * @param mixed $value Value.
	 *
	 * @return bool|null
	 */
	protected function db_to_nullable_bool( $value ) {
		if ( null === $value ) {
			return null;
		}

		return (bool) $value;
	}
}
