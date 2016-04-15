<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static Page class, for tracking the status of pages / static files
 * @package Simply_Static
 */
class Simply_Static_Page extends Simply_Static_Model {

	/** @const */
	protected static $table_name = 'pages';

	/** @const */
	protected static $columns = array(
		'id'                  => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'found_on_id'         => 'BIGINT(20) UNSIGNED NULL',
		'url'                 => 'VARCHAR(255) NOT NULL',
		'file_path'           => 'VARCHAR(255) NULL',
		'http_status_code'    => 'SMALLINT(20) NULL',
		'content_hash'        => 'BINARY(20) NULL',
		'error_message'       => 'VARCHAR(255) NULL',
		'last_checked_at'     => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_modified_at'    => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_transferred_at' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'created_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'          => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	/** @const */
	protected static $indexes = array(
		'PRIMARY KEY  (id)',
		'KEY url (url)',
		'KEY last_checked_at (last_checked_at)',
		'KEY last_modified_at (last_modified_at)',
		'KEY last_transferred_at (last_transferred_at)'
	);

	/** @const */
	protected static $primary_key = 'id';

	/**
	 * Check if the hash for the content matches the prior hash for the page
	 * @param  string  $content The content of the page/file
	 * @return boolean          Is the hash a match?
	 */
	public function is_content_identical( $content ) {
		$hash = sha1( $content, true );
		return $hash === $this->content_hash;
	}

	/**
	 * Set the hash for the content and update the last_modified_at value
	 * @param string $content The content of the page/file
	 */
	public function set_content_hash( $content ) {
		$hash = sha1( $content, true );
		$this->content_hash = $hash;
		$this->last_modified_at = sist_formatted_datetime();
	}

	/**
	 * Set an error message if an error message hasn't already been set
	 *
	 * This ensures that we only display the first error message encountered
	 * instead of the last.
	 * @param string $message The error message
	 */
	public function set_error_message( $message ) {
		if ( ! $this->error_message ) {
			$this->error_message = $message;
		}
	}
}
