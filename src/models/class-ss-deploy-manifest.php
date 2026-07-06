<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stored deploy manifest metadata.
 */
class Deploy_Manifest extends Model {

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	protected static $table_name = 'deploy_manifests';

	/**
	 * Table columns.
	 *
	 * @var array
	 */
	protected static $columns = array(
		'id'                => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'deploy_id'         => 'VARCHAR(80) NOT NULL',
		'site_id'           => 'BIGINT(20) UNSIGNED NULL',
		'manifest_version'  => 'VARCHAR(20) NOT NULL',
		'status'            => 'VARCHAR(30) NOT NULL',
		'domain'            => 'VARCHAR(255) NULL',
		'mount_path'        => 'VARCHAR(255) NULL',
		'started_at'        => "DATETIME NULL DEFAULT '0000-00-00 00:00:00'",
		'finished_at'       => "DATETIME NULL DEFAULT '0000-00-00 00:00:00'",
		'duration_seconds'  => 'INT(11) NULL',
		'plugin_version'    => 'VARCHAR(30) NULL',
		'wp_version'        => 'VARCHAR(30) NULL',
		'php_version'       => 'VARCHAR(30) NULL',
		'generate_type'     => 'VARCHAR(30) NULL',
		'url_counts'        => 'LONGTEXT NULL',
		'root_files'        => 'LONGTEXT NULL',
		'warnings'          => 'LONGTEXT NULL',
		'errors'            => 'LONGTEXT NULL',
		'manifest'          => 'LONGTEXT NULL',
		'created_at'        => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'        => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	/**
	 * Indexes for columns.
	 *
	 * @var array
	 */
	protected static $indexes = array(
		'PRIMARY KEY  (id)',
		'UNIQUE KEY deploy_id (deploy_id)',
		'KEY site_id (site_id)',
		'KEY status (status)',
		'KEY finished_at (finished_at)'
	);

	/**
	 * Primary key.
	 *
	 * @var string
	 */
	protected static $primary_key = 'id';
}
