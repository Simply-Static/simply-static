<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Config Files Crawler class
 *
 * This crawler detects JSON configuration files in the Simply Static Pro configs directory.
 */
class Config_Files_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'config_files';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Config Files', 'simply-static' );
		$this->description = __( 'Detects JSON configuration files in the Simply Static Pro configs directory.', 'simply-static' );
	}

	/**
	 * Check if the crawler is active.
	 * This crawler is only active when Simply Static Pro is activated.
	 *
	 * @return boolean
	 */
	public function is_active() {
		// Only active if Simply Static Pro is activated
		if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
			return false;
		}

		// Otherwise, use the default active check from the parent class
		return parent::is_active();
	}

	/**
	 * Detect config file URLs.
	 *
	 * @return array List of config file URLs
	 */
	public function detect() : array {
		$config_urls = [];

		// Path to the configs directory
		$configs_dir = WP_CONTENT_DIR . '/uploads/simply-static/configs/';

		// URL to the configs directory
		$configs_url = content_url( '/uploads/simply-static/configs/' );

		// Check if the directory exists
		if ( ! is_dir( $configs_dir ) ) {
			return $config_urls;
		}

		$max_files = max( 1, min( 10000, (int) apply_filters( 'simply_static_config_files_max_files', 1000 ) ) );
		try {
			$files = new \FilesystemIterator( $configs_dir, \FilesystemIterator::SKIP_DOTS );
			foreach ( $files as $file ) {
				if ( count( $config_urls ) >= $max_files ) {
					\Simply_Static\Util::debug_log( sprintf( 'Config files crawler stopped at its %d-file safety limit.', $max_files ) );
					break;
				}
				if ( ! $file->isFile() || $file->isLink() || ! in_array( strtolower( $file->getExtension() ), array( 'json', 'html' ), true ) ) {
					continue;
				}
				$config_urls[] = $configs_url . $file->getFilename();
			}
		} catch ( \UnexpectedValueException $exception ) {
			\Simply_Static\Util::debug_log( 'Config files crawler could not read its directory: ' . $exception->getMessage() );
		}

		return $config_urls;
	}
}
