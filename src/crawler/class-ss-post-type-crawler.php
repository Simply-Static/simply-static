<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Post Type Crawler class
 *
 * This crawler detects URLs for all public post types.
 */
class Post_Type_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'post_type';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Post Type URLs', 'simply-static' );
		$this->description = __( 'Detects URLs for all public post types (posts, pages, etc.).', 'simply-static' );
	}

	/**
	 * Detect post type URLs.
	 *
	 * @return array List of post type URLs
	 */
	public function detect(): array {
		$post_urls = [];
		$max_posts = max( 1, min( 100000, (int) apply_filters( 'simply_static_post_type_detection_limit', 1000 ) ) );

		// Get all public post types
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		// Filter post types to allow exclusion of specific post types
		$post_types = apply_filters( 'simply_static_post_types_to_crawl', $post_types );

		// Exclude Elementor's element_library post type
		if ( isset( $post_types['elementor_library'] ) ) {
			unset( $post_types['elementor_library'] );
		}

		// Exclude ssp-form post type
		if ( isset( $post_types['ssp-form'] ) ) {
			unset( $post_types['ssp-form'] );
		}

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		if ( $has_post_type_selection ) {
			// Filter post types to only include those selected in settings
			$post_types = array_intersect( $post_types, $options['post_types'] );
		}

		foreach ( $post_types as $post_type ) {
			if ( count( $post_urls ) >= $max_posts ) {
				break;
			}
			// Skip attachments as they're handled differently
			if ( $post_type === 'attachment' ) {
				continue;
			}

			// Get all published posts of this type
			$posts = get_posts( [
				'post_type'      => $post_type,
				'posts_per_page' => $max_posts - count( $post_urls ),
				'post_status'    => 'publish',
			] );

			foreach ( $posts as $post ) {
				$permalink = get_permalink( $post->ID );

				if ( ! is_string( $permalink ) ) {
					continue;
				}

				$post_urls[] = $permalink;
			}
		}

		return $post_urls;
	}

	/**
	 * Traverse public posts and stale-page cleanup across bounded requests.
	 *
	 * @return int Number of URLs added by this invocation.
	 */
	public function add_urls_to_queue() : int {
		global $wpdb;

		$post_types = $this->get_crawl_post_types();
		$signature  = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'post_types'         => $post_types,
		) ) );
		$state       = $this->load_post_type_state( $signature );
		$batch_size  = max( 1, min( 500, (int) apply_filters( 'simply_static_post_type_crawler_batch_size', 100 ) ) );
		$entry_limit = max( $batch_size, min( 2000, (int) apply_filters( 'simply_static_post_type_crawler_max_entries_per_batch', 500 ) ) );
		$seconds     = (float) apply_filters( 'simply_static_post_type_crawler_max_batch_seconds', 10 );
		$deadline    = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$processed   = 0;
		$added_now   = 0;
		$this->complete = false;

		while ( 'posts' === $state['stage'] && $state['post_type_index'] < count( $post_types ) ) {
			$post_type   = $post_types[ $state['post_type_index'] ];
			$query_limit = min( $batch_size, $entry_limit - $processed );
			$ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND ID > %d ORDER BY ID ASC LIMIT %d",
				$post_type,
				$state['last_post_id'],
				$query_limit
			) );
			$ids = is_array( $ids ) ? array_map( 'intval', $ids ) : array();
			$urls = array();
			foreach ( $ids as $post_id ) {
				$state['last_post_id'] = max( $state['last_post_id'], $post_id );
				$url = get_permalink( $post_id );
				if ( is_string( $url ) ) {
					$urls[] = $url;
				}
			}
			$added             = $this->enqueue_urls( $urls );
			$added_now        += $added;
			$state['added']   += $added;
			$state['scanned'] += count( $ids );
			$processed        += count( $ids );

			if ( count( $ids ) < $query_limit ) {
				$state['post_type_index']++;
				$state['last_post_id'] = 0;
			}
			if ( $processed >= $entry_limit || microtime( true ) >= $deadline ) {
				return $this->checkpoint_post_type_crawler( $state, $added_now );
			}
		}

		if ( 'posts' === $state['stage'] ) {
			$state['stage'] = 'cleanup';
		}

		if ( 'cleanup' === $state['stage'] ) {
			$table = $wpdb->prefix . 'simply_static_pages';
			$remaining = max( 1, $entry_limit - $processed );
			$stale_rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT p.id, p.post_id, p.file_path, p.url
				 FROM {$table} p
				 LEFT JOIN {$wpdb->posts} wp ON p.post_id = wp.ID
				 WHERE p.id > %d AND p.post_id IS NOT NULL AND p.post_id > 0
				   AND ( wp.ID IS NULL OR wp.post_status IN ('trash','draft','pending','private','auto-draft') )
				 ORDER BY p.id ASC
				 LIMIT %d",
				$state['last_stale_id'],
				min( $batch_size, $remaining )
			) );
			$stale_rows = is_array( $stale_rows ) ? $stale_rows : array();
			if ( ! empty( $stale_rows ) ) {
				$this->queue_stale_pages_for_deletion( $stale_rows );
				$stale_ids = array_map( static function ( $row ) {
					return (int) $row->id;
				}, $stale_rows );
				$state['last_stale_id'] = max( $stale_ids );
				$state['scanned']      += count( $stale_ids );
				$wpdb->query( "DELETE FROM {$table} WHERE id IN (" . implode( ',', $stale_ids ) . ')' );
			}
			if ( count( $stale_rows ) >= min( $batch_size, $remaining ) ) {
				return $this->checkpoint_post_type_crawler( $state, $added_now );
			}
		}

		$this->complete = true;
		$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		$this->clear_crawler_state( 'post_type_crawler_state' );

		return $added_now;
	}

	/** @return string[] */
	private function get_crawl_post_types() : array {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( 'simply_static_post_types_to_crawl', $post_types );
		$post_types = array_values( array_diff( (array) $post_types, array( 'attachment', 'elementor_library', 'ssp-form' ) ) );
		$options = get_option( 'simply-static' );
		$has_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		if ( $has_selection ) {
			$post_types = array_values( array_intersect( $post_types, $options['post_types'] ) );
		}

		return array_values( array_filter( $post_types, 'is_string' ) );
	}

	/** @return array<string,mixed> */
	private function load_post_type_state( $signature ) : array {
		$state = \Simply_Static\Options::instance()->get( 'post_type_crawler_state' );
		if ( ! is_array( $state )
			|| 1 !== ( $state['version'] ?? null )
			|| $signature !== ( $state['signature'] ?? null )
			|| ! in_array( $state['stage'] ?? null, array( 'posts', 'cleanup' ), true )
			|| ! is_int( $state['post_type_index'] ?? null )
			|| $state['post_type_index'] < 0
			|| ! is_int( $state['last_post_id'] ?? null )
			|| $state['last_post_id'] < 0
			|| ! is_int( $state['last_stale_id'] ?? null )
			|| $state['last_stale_id'] < 0
			|| ! is_int( $state['added'] ?? null )
			|| $state['added'] < 0
			|| ! is_int( $state['scanned'] ?? null )
			|| $state['scanned'] < 0
		) {
			return array(
				'version'         => 1,
				'signature'       => $signature,
				'stage'           => 'posts',
				'post_type_index' => 0,
				'last_post_id'    => 0,
				'last_stale_id'   => 0,
				'added'           => 0,
				'scanned'         => 0,
			);
		}

		return $state;
	}

	/** @param array<string,mixed> $state */
	private function checkpoint_post_type_crawler( array $state, $added_now ) : int {
		$this->complete = false;
		$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		$this->save_crawler_state( 'post_type_crawler_state', $state );

		return (int) $added_now;
	}

	/**
	 * Insert stale pages into the Pro deletion-tracker table so the
	 * Delete_Tracked_Pages_Task can remove them from any delivery destination.
	 *
	 * Also fires the `ss_cleanup_stale_static_page` action per row so
	 * third-party code can react.
	 *
	 * @param array $stale_rows Rows from the pages table to process.
	 */
	private function queue_stale_pages_for_deletion( array $stale_rows ): void {
		global $wpdb;

		// Check if the Pro deletion-tracker table exists (single query, cached for the loop).
		$tracker_table  = $wpdb->prefix . 'simply_static_delete_pages';
		$tracker_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tracker_table ) ) === $tracker_table );

		foreach ( $stale_rows as $row ) {
			$url       = ! empty( $row->url ) ? $row->url : '';
			$file_path = ! empty( $row->file_path ) ? ltrim( $row->file_path, '/' ) : '';
			$post_id   = ! empty( $row->post_id ) ? (int) $row->post_id : 0;

			// Skip rows without actionable data.
			if ( '' === $url && '' === $file_path ) {
				continue;
			}

			\Simply_Static\Util::debug_log( sprintf( 'Queueing stale page for deletion: %s (post %d)', $url ?: $file_path, $post_id ) );

			if ( $tracker_exists ) {
				$post         = $post_id > 0 ? get_post( $post_id ) : null;
				$content_type = $post ? (string) $post->post_type : '';
				$unique_hash  = md5( implode( '|', [
					(string) get_current_blog_id(),
					$url,
					$file_path,
					$content_type
				] ) );

				$wpdb->replace(
					$tracker_table,
					[
						'old_url'      => $url,
						'file_path'    => $file_path,
						'content_type' => $content_type,
						'object_id'    => $post_id > 0 ? $post_id : null,
						'object_type'  => $post_id > 0 ? 'post' : '',
						'site_id'      => get_current_blog_id(),
						'deleted_at'   => gmdate( 'Y-m-d H:i:s' ),
						'source'       => 'crawler_cleanup',
						'meta'         => null,
						'unique_hash'  => $unique_hash,
					],
					[ '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ]
				);
			}

			do_action( 'ss_cleanup_stale_static_page', $url, $file_path, $post_id );
		}
	}
}
