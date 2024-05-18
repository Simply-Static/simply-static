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
		'url'                 => 'VARCHAR(255) NOT NULL',
		'redirect_url'        => 'TEXT NULL',
		'file_path'           => 'VARCHAR(255) NULL',
		'http_status_code'    => 'SMALLINT(20) NULL',
		'content_type'        => 'VARCHAR(255) NULL',
		'content_hash'        => 'BINARY(20) NULL',
		'error_message'       => 'VARCHAR(255) NULL',
		'status_message'      => 'VARCHAR(255) NULL',
		'handler'             => 'VARCHAR(255) NULL',
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
		'KEY url (url)',
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
	 * Check if the hash for the content matches the prior hash for the page
	 *
	 * @param string $sha1 The content of the page/file.
	 *
	 * @return boolean          Is the hash a match?
	 */
	public function is_content_identical( $sha1 ) {
		$hash = $this->content_hash ?? '';

		Util::debug_log( 'Checking Content Identical:' . $sha1 . '===' . $hash . '. Value: ' . ( $hash && strpos( $sha1, $hash ) === 0 ? 'TRUE' : 'FALSE' ) );

		return $hash && strpos( $sha1, $hash ) === 0;
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
		if ( $this->status_message ) {
			$this->status_message = $this->status_message . '; ' . $message;
		} else {
			$this->status_message = $message;
		}
	}

	/**
	 * Check the content type.
	 *
	 * @param string $content_type given content type.
	 *
	 * @return boolean
	 */
	public function is_type( $content_type ) {
		return stripos( $this->content_type, $content_type ) !== false;
	}

	/**
	 * Return if it's a binary file.
	 *
	 * @return bool
	 */
	public function is_binary_file() {
		return $this->is_type( 'application/octet-stream' ) || $this->is_type( 'image' );
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
}
