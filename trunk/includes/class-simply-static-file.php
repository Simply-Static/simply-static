<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static File class, for tracking the state of static files
 * @package Simply_Static
 */
class Simply_Static_File extends Simply_Static_Model {

	protected static $table_name = 'files';

	protected static $columns = array(
		'id'                => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
		'found_on_id'       => 'BIGINT(20) UNSIGNED NULL',
		'url'               => 'VARCHAR(255) NOT NULL',
		'file_path'         => 'VARCHAR(255) NOT NULL',
		'http_status_code'  => 'SMALLINT(20) NOT NULL',
		'content_hash'      => 'BINARY(20) NOT NULL',
		'last_checked_at'   => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_modified_at'  => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'last_uploaded_at'  => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'created_at'        => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'        => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	protected static $indexes = array(
		'KEY url (url)',
		'KEY last_checked_at (last_checked_at)',
		'KEY last_modified_at (last_modified_at)',
		'KEY last_uploaded_at (last_uploaded_at)'
	);

	protected static $primary_key = 'id';
}
