<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Page class, for tracking the status of pages / static files
 */
class Page extends Model {

	const SCHEMA_VERSION = '1';
	const SCHEMA_VERSION_OPTION = 'simply_static_pages_schema_version';

	/**
	 * Protected status codes.
	 *
	 * @var array
	 */
	public static $processable_status_codes = array( 200, 301, 302, 303, 307, 308 );

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	protected static $table_name = 'pages';

	/**
	 * Table columns.
	 *
	 * @var array
	 */
	protected static $columns = array(
		'id'                  => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'build_id'            => 'BIGINT(20) UNSIGNED NULL',
		'post_id'             => 'BIGINT(20) UNSIGNED NULL',
		'found_on_id'         => 'BIGINT(20) UNSIGNED NULL',
		'site_id'             => 'BIGINT(20) UNSIGNED NULL',
		'url'                 => 'TEXT NOT NULL',
		'redirect_url'        => 'TEXT NULL',
		'file_path'           => 'TEXT NULL',
		'http_status_code'    => 'SMALLINT(20) NULL',
		'content_type'        => 'VARCHAR(255) NULL',
		'content_hash'        => 'BINARY(20) NULL',
		'error_message'       => 'VARCHAR(255) NULL',
		'status_message'      => 'VARCHAR(255) NULL',
		'handler'             => 'VARCHAR(255) NULL',
		'json'                => 'TEXT NULL',
		'fetch_attempts'      => 'INT(11) NOT NULL DEFAULT 0',
		'last_checked_at'     => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_modified_at'    => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_transferred_at' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'created_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	/**
	 * Indexes for columns.
	 *
	 * @var array
	 */
	protected static $indexes = array(
		'PRIMARY KEY  (id)',
		'KEY url (url(191))',
		'KEY last_checked_at (last_checked_at)',
		'KEY last_modified_at (last_modified_at)',
		'KEY last_transferred_at (last_transferred_at)'
	);

	/**
	 * Primary key.
	 *
	 * @var string
	 */
	protected static $primary_key = 'id';

	/**
	 * Upgrade the current site's table independently of the plugin version.
	 *
	 * Use a blog option rather than network settings: each multisite blog owns
	 * its own pages table and must complete its own migration.
	 *
	 * @return bool Whether the pages schema is ready.
	 */
	public static function maybe_upgrade_schema() {
		if ( self::SCHEMA_VERSION === get_option( self::SCHEMA_VERSION_OPTION ) ) {
			return true;
		}

		return static::create_or_update_table();
	}

	/**
	 * Prepare a switched multisite blog before a lookup primes wpdb metadata.
	 * Pro can prepare builds before the setup task or archive start hook runs.
	 *
	 * @return Query
	 */
	public static function query() {
		static::maybe_upgrade_schema();
		return parent::query();
	}

	/**
	 * Create the pages table or widen existing URL storage without losing data.
	 *
	 * dbDelta ignores index prefix changes and can reset a column's charset.
	 * Convert the URL, path and index together, preserving each collation,
	 * before dbDelta synchronizes the remaining table definition.
	 *
	 * @return bool Whether the URL/path columns and URL index are ready.
	 */
	public static function create_or_update_table() {
		global $wpdb;

		delete_option( self::SCHEMA_VERSION_OPTION );
		$table = self::table_name();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		if ( $exists ) {
			$changes = array();
			foreach ( array( 'url' => 'NOT NULL', 'file_path' => 'NULL' ) as $name => $nullable ) {
				$column = $wpdb->get_row( "SHOW FULL COLUMNS FROM `{$table}` WHERE Field = '{$name}'", ARRAY_A );
				if ( ! $column || ! empty( $wpdb->last_error ) ) {
					return false;
				}
				if ( ! self::is_text_column_ready( $column ) ) {
					$collation = $column['Collation'];
					if ( ! is_string( $collation ) || ! preg_match( '/\A[a-z0-9]+_[a-z0-9_]+\z/i', $collation ) ) {
						return false;
					}
					$charset = explode( '_', $collation )[0];
					$changes[] = "MODIFY `{$name}` TEXT CHARACTER SET {$charset} COLLATE {$collation} {$nullable}";
				}
			}

			$indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Key_name = 'url'", ARRAY_A );
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}

			if ( ! self::is_url_index_ready( $indexes ) ) {
				if ( $indexes ) {
					$changes[] = 'DROP INDEX `url`';
				}
				$changes[] = 'ADD KEY `url` (`url`(191))';
			}
			if ( $changes ) {
				$updated = $wpdb->query( "ALTER TABLE `{$table}` " . implode( ', ', $changes ) );
				if ( false === $updated ) {
					return false;
				}
			}
		} elseif ( ! empty( $wpdb->last_error ) ) {
			return false;
		}

		parent::create_or_update_table();

		// dbDelta reports intended changes even if SQL fails. Verify the schema
		// before marking this blog complete so a later request can retry.
		foreach ( array( 'url', 'file_path' ) as $name ) {
			$column = $wpdb->get_row( "SHOW COLUMNS FROM `{$table}` WHERE Field = '{$name}'", ARRAY_A );
			if ( ! self::is_text_column_ready( $column ) || ! empty( $wpdb->last_error ) ) {
				return false;
			}
		}
		$indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Key_name = 'url'", ARRAY_A );
		if ( ! self::is_url_index_ready( $indexes ) ) {
			return false;
		}

		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false );
		return true;
	}

	/** @return bool Whether the column can store at least a full TEXT value. */
	private static function is_text_column_ready( $column ) {
		return is_array( $column ) && isset( $column['Type'] )
			&& in_array( strtolower( $column['Type'] ), array( 'text', 'mediumtext', 'longtext' ), true );
	}

	/**
	 * Check that the URL lookup index is non-unique and fits utf8mb4 key limits.
	 *
	 * @param array|null $indexes Rows returned by SHOW INDEX.
	 * @return bool Whether the expected URL prefix index exists.
	 */
	private static function is_url_index_ready( $indexes ) {
		return is_array( $indexes ) && 1 === count( $indexes )
			&& 'url' === $indexes[0]['Column_name']
			&& 191 === (int) $indexes[0]['Sub_part']
			&& 1 === (int) $indexes[0]['Non_unique'];
	}

	/**
	 * Persist a page without silently discarding URLs or database errors.
	 *
	 * Reporting here also covers integrations that do not inspect save()'s
	 * return value. Keep dirty fields for retries. Under WP-CLI, throw
	 * after reporting so the command can clean up and exit with a failure code.
	 *
	 * @return bool Whether the page was saved.
	 * @throws \RuntimeException When a page write fails under WP-CLI.
	 */
	public function save() {
		if ( ! static::maybe_upgrade_schema() ) {
			return $this->report_save_failure(
				__( 'Unable to upgrade the pages database table. Check the database permissions and try again.', 'simply-static' )
			);
		}

		// MySQL TEXT is limited by bytes, including for multibyte URLs. Sites
		// that already use a wider TEXT type can retain their larger URLs.
		$url_bytes = strlen( (string) $this->url );
		if ( $url_bytes > 65535 ) {
			global $wpdb;
			$limit = 65535;
			if ( method_exists( $wpdb, 'get_col_length' ) ) {
				$length = $wpdb->get_col_length( static::table_name(), 'url' );
				if ( is_array( $length ) && 'byte' === ( $length['type'] ?? null ) ) {
					$limit = max( $limit, (int) ( $length['length'] ?? 0 ) );
				}
			}
			if ( $url_bytes > $limit ) {
				return $this->report_save_failure(
					sprintf(
						/* translators: %s: maximum URL length in bytes. */
						__( 'The URL exceeds the storage limit of %s bytes.', 'simply-static' ),
						number_format( $limit )
					)
				);
			}
		}

		if ( ! parent::save() ) {
			global $wpdb;
			return $this->report_save_failure( (string) ( $wpdb->last_error ?? '' ) );
		}

		return true;
	}

	/**
	 * Make a failed page write visible even when the page row does not exist.
	 *
	 * @param string $reason Database or validation error captured before logging.
	 * @return false
	 */
	protected function report_save_failure( $reason ) {
		if ( '' === trim( $reason ) ) {
			$reason = __( 'The database rejected the write. A field may be too long or contain invalid data; check the database schema and permissions.', 'simply-static' );
		}

		$message = sprintf(
			/* translators: 1: page URL, 2: database table, 3: error reason. */
			__( 'Unable to save URL "%1$s" to %2$s: %3$s', 'simply-static' ),
			(string) $this->url,
			self::table_name(),
			$reason
		);
		// Do not let URL or database text inject extra log lines or CLI controls.
		$message = preg_replace( '/[\x00-\x1F\x7F]/', ' ', $message );
		Util::debug_log( $message );

		$options = Options::instance();
		$key     = 'page_write_error_' . md5( (string) $this->url );
		$messages = Util::add_archive_status_message(
			$options->get( 'archive_status_messages' ),
			$key,
			esc_html( $message )
		);
		$options->set( 'archive_status_messages', $messages );

		// The job checks this at task boundaries before starting delivery, so an
		// incomplete queue cannot be reported as a successful export.
		$is_exporting = null !== $options->get( 'archive_start_time' ) && null === $options->get( 'archive_end_time' );
		if ( $is_exporting && ! $options->get( 'archive_page_write_error' ) ) {
			$options->set( 'archive_page_write_error', $message );
		}
		$options->save();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::warning( $message );
			// Build/single preparation may write pages before the archive starts.
			throw new \RuntimeException( $message );
		}

		return false;
	}

	/**
	 * Claim a pending page and persist its retry attempt in the same write.
	 *
	 * Fetch, Markdown and delivery tasks all run this for every page. Keeping
	 * the claim and retry counter together avoids a second model update per
	 * artifact, while still recording the attempt before external processing.
	 *
	 * @param string $column Processing timestamp column.
	 * @param string $start_time Current export timestamp.
	 * @return bool Whether this worker claimed the page.
	 */
	public function claim_for_processing( $column, $start_time ) : bool {
		if ( ! in_array( $column, array( 'last_checked_at', 'last_transferred_at' ), true ) ) {
			throw new \InvalidArgumentException( 'A valid page processing column is required.' );
		}

		global $wpdb;
		$table = self::table_name();
		$now = Util::formatted_datetime();
		$attempts = (int) $this->fetch_attempts;
		$claimed = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET {$column} = %s, fetch_attempts = %d, updated_at = %s WHERE id = %d AND ( {$column} IS NULL OR {$column} < %s ) AND fetch_attempts = %d AND fetch_attempts < 3",
			$now,
			$attempts + 1,
			$now,
			$this->id,
			$start_time,
			$attempts
		) );
		if ( false === $claimed ) {
			return $this->report_save_failure( (string) ( $wpdb->last_error ?? '' ) );
		}
		if ( ! $claimed ) {
			return false;
		}

		$persisted = array( $column => $now, 'fetch_attempts' => $attempts + 1, 'updated_at' => $now );
		$this->attributes( $persisted );
		$this->mark_fields_clean( $persisted );

		return true;
	}

	/**
	 * Get the number of pages for each group of status codes, e.g. 1xx, 2xx, 3xx
	 *
	 * @return array Assoc. array of status code to number of pages, e.g. '2' => 183
	 */
	public static function get_http_status_codes_summary() {
		global $wpdb;

		$query = 'SELECT LEFT(http_status_code, 1) AS status, COUNT(*) AS count';
		$query .= ' FROM ' . self::table_name();
		$query .= ' GROUP BY LEFT(http_status_code, 1)';
		$query .= ' ORDER BY status';

		$rows = $wpdb->get_results( $query, ARRAY_A );

		$http_codes = array( '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0 );
		foreach ( $rows as $row ) {
			$http_codes[ $row['status'] ] = $row['count'];
		}

		return $http_codes;
	}

	/**
	 * Return the static page that this page belongs to (if any)
	 *
	 * @return Page The parent Page
	 */
	public function parent_static_page() {
		return self::query()->find_by( 'id', $this->found_on_id );
	}

	/**
	 * Delete this page.
	 *
	 * @return int|null
	 */
	public function delete() {
		return self::query()->delete_by_id( $this->id );
	}

	/**
	 * Check if the hash for the content matches the prior hash for the page
	 *
	 * @param string $sha1 The content of the page/file.
	 *
	 * @return boolean          Is the hash a match?
	 */
	public function is_content_identical( $sha1 ) {
		$hash = $this->content_hash ?? '';
		$identical = is_string( $sha1 )
			&& is_string( $hash )
			&& 20 === strlen( $sha1 )
			&& 20 === strlen( $hash )
			&& hash_equals( $hash, $sha1 );

		Util::debug_log( 'Checking content hash equality. Value: ' . ( $identical ? 'TRUE' : 'FALSE' ) );

		return $identical;
	}

	/**
	 * Set the hash for the content and update the last_modified_at value
	 *
	 * @param string $sha1 The content of the page/file.
	 */
	public function set_content_hash( $sha1 ) {
		$this->content_hash     = $sha1;
		$this->last_modified_at = Util::formatted_datetime();
	}

	/**
	 * Set an error message
	 *
	 * An error indicates that something bad happened when fetching the page, or
	 * saving the page, or during some other activity related to the page.
	 *
	 * @param string $message The error message.
	 */
	public function set_error_message( $message ) {
		// Already has the same message.
		if ( $this->has_error_message( $message ) ) {
			return;
		}

		if ( $this->error_message ) {
			$this->error_message = $this->error_message . '; ' . $message;
		} else {
			$this->error_message = $message;
		}
	}

	/**
	 * Clear errors left by an earlier processing attempt.
	 *
	 * Page records are retained for single and incremental exports. A later
	 * successful fetch should therefore replace, rather than continue to show,
	 * an error from an older attempt.
	 */
	public function clear_error_message() {
		$this->error_message = null;
	}

	protected function has_error_message( $message ) {
		if ( ! $this->error_message ) {
			return false;
		}

		$errors = explode( '; ', $this->error_message );
		$index  = array_search( $message, $errors, true );

		return false !== $index && $index >= 0;
	}

	/**
	 * Set a status message
	 *
	 * A status message is used to indicate things that happened to the page
	 * that weren't errors, such as not following links or not saving the page.
	 *
	 * @param string $message The status message.
	 */
	public function set_status_message( $message ) {
		// Already has the same message.
		if ( $this->has_status_message( $message ) ) {
			return;
		}

		if ( $this->status_message ) {
			$this->status_message = $this->status_message . '; ' . $message;
		} else {
			$this->status_message = $message;
		}
	}

	/**
	 * Check if the page already has a specific status message
	 *
	 * @param string $message The status message to check for.
	 * @return boolean Whether the page already has the status message.
	 */
	protected function has_status_message( $message ) {
		if ( ! $this->status_message ) {
			return false;
		}

		$statuses = explode( '; ', $this->status_message );
		$index  = array_search( $message, $statuses, true );

		return false !== $index && $index >= 0;
	}

	/**
	 * Check the content type.
	 *
	 * @param string $content_type given content type.
	 *
	 * @return boolean
	 */
	public function is_type( $content_type ) {
		if ( ! is_null( $this->content_type ) ) {
			return stripos( $this->content_type, $content_type ) !== false;
		}

		return false;
	}

	/**
	 * Return if it's a binary file.
	 *
	 * @return bool
	 */
	public function is_binary_file() {
		if ( ! is_null( $this->content_type ) ) {
			$binary_types = array(
				'application/gzip',
				'application/octet-stream',
				'application/pdf',
				'application/vnd.ms-fontobject',
				'application/vnd.rar',
				'application/wasm',
				'application/x-7z-compressed',
				'application/x-gzip',
				'application/x-rar-compressed',
				'application/x-tar',
				'application/x-zip-compressed',
				'application/zip',
				'audio/',
				'font/',
				'image/',
				'video/',
			);

			foreach ( $binary_types as $binary_type ) {
				if ( $this->is_type( $binary_type ) ) {
					return true;
				}
			}
		}

		if ( isset( $this->file_path ) ) {
			$extension = strtolower( pathinfo( (string) $this->file_path, PATHINFO_EXTENSION ) );
			if ( in_array( $extension, array(
				'7z',
				'aac',
				'avif',
				'bmp',
				'eot',
				'gif',
				'gz',
				'heic',
				'ico',
				'jpeg',
				'jpg',
				'm4a',
				'm4v',
				'mov',
				'mp3',
				'mp4',
				'oga',
				'ogg',
				'ogv',
				'otf',
				'pdf',
				'png',
				'rar',
				'tar',
				'tif',
				'tiff',
				'ttf',
				'wav',
				'webm',
				'webp',
				'woff',
				'woff2',
				'zip',
			), true ) ) {
				return true;
			}
		}

		if ( $this->is_type( 'image' ) ) {
			return true;
		}

		if ( null === $this->content_type && $this->get_handler_class() === Additional_File_Handler::class ) {
			return true;
		}

		return false;
	}

	public function get_handler_class() {
		$handler = $this->handler ?? Page_Handler::class;

		if ( ! class_exists( $handler ) ) {
			$handler = '\Simply_Static\\' . $handler;
		}

		if ( ! class_exists( $handler ) ) {
			$handler = Page_Handler::class;
		}

		return $handler;
	}

	/**
	 * Get the Page Handler based on the column saved.
	 *
	 * @return Page_Handler
	 */
	public function get_handler() {
		$handler_class = $this->get_handler_class();

		return new $handler_class( $this );
	}

	/**
	 * Set the attributes of the model
	 *
	 * @param array $attributes Array of attributes to set.
	 *
	 * @return static            An instance of the class.
	 */
	public function attributes( $attributes ) {
		if ( empty( $attributes['site_id'] ) ) {
			$attributes['site_id'] = get_current_blog_id();
		}

		return parent::attributes( $attributes );
	}

	/**
	 * Get JSON
	 * @return mixed
	 */
	public function get_json() {
		return json_decode( $this->json ?? '', true );
	}

	/**
	 * Set JSON
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function set_json( $data ) {
		$this->json = json_encode( $data );
	}

	/**
	 * Get the JSON data by a key.
	 *
	 * @param string $key Key in JSON.
	 *
	 * @return mixed|null
	 */
	public function get_json_data_by_key( $key ) {
		$json = $this->get_json();

		if ( ! is_array( $json ) ) {
			return null;
		}

		if ( ! array_key_exists( $key, $json ) ) {
			return null;
		}

		return $json[ $key ];
	}

	/**
	 * Set the JSON data for a key.
	 *
	 * @param string $key Key under which sets the data.
	 * @param mixed $data Mixed data.
	 *
	 * @return void
	 */
	public function set_json_data_by_key( $key, $data ) {
		$json = $this->get_json();

		if ( ! $json ) {
			$json = [];
		}

		$json[ $key ] = $data;

		$this->set_json( $json );
	}
}
