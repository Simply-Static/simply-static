<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stored URL/file records for a deploy manifest.
 */
class Deploy_Manifest_Url extends Model {

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	protected static $table_name = 'deploy_manifest_urls';

	/**
	 * Table columns.
	 *
	 * @var array
	 */
	protected static $columns = array(
		'id'                 => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'deploy_id'          => 'VARCHAR(80) NOT NULL',
		'site_id'            => 'BIGINT(20) UNSIGNED NULL',
		'url'                => 'TEXT NOT NULL',
		'source_url'         => 'TEXT NULL',
		'static_path'        => 'TEXT NULL',
		'type'               => 'VARCHAR(30) NOT NULL',
		'status_code'        => 'SMALLINT(20) NULL',
		'content_hash'       => 'VARCHAR(96) NULL',
		'file_size'          => 'BIGINT(20) NULL',
		'redirect_target'    => 'TEXT NULL',
		'found_on'           => 'LONGTEXT NULL',
		'in_sitemap'         => 'TINYINT(1) NULL',
		'markdown_generated' => 'TINYINT(1) NULL',
		'warnings'           => 'LONGTEXT NULL',
		'errors'             => 'LONGTEXT NULL',
		'created_at'         => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'updated_at'         => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);

	/**
	 * Indexes for columns.
	 *
	 * @var array
	 */
	protected static $indexes = array(
		'PRIMARY KEY  (id)',
		'KEY deploy_id (deploy_id)',
		'KEY site_id (site_id)',
		'KEY type (type)',
		'KEY status_code (status_code)'
	);

	/**
	 * Primary key.
	 *
	 * @var string
	 */
	protected static $primary_key = 'id';
}
