<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Upgrade Handler class
 *
 * Used for handling upgrades/downgrades of Simply Static
 */
class Upgrade_Handler {

	/**
	 * An instance of the options structure containing all options for this plugin
	 *
	 * @var Simply_Static\Options
	 */
	protected static $options = null;

	/**
	 * Default options to set for the plugin
	 *
	 * @var array
	 */
	protected static $default_options = null;

	/**
	 * Disable usage of "new"
	 *
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Disable cloning of the class
	 *
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Create settings and setup database
	 * @return void
	 */
	public static function run() {
		self::$options = Options::instance();

		// Check if directory exists, if not, create it.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';

		// Check if directory exists.
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		self::$default_options = array(
			'destination_scheme'      => 'https://',
			'destination_host'        => '',
			'temp_files_dir'          => trailingslashit( $temp_dir ),
			'additional_urls'         => '',
			'additional_files'        => '',
			'urls_to_exclude'         => array(),
			'delivery_method'         => 'zip',
			'local_dir'               => '',
			'relative_path'           => '',
			'destination_url_type'    => 'relative',
			'archive_status_messages' => array(),
			'pages_status'            => array(),
			'archive_name'            => null,
			'archive_start_time'      => null,
			'archive_end_time'        => null,
			'debugging_mode'          => false,
			'http_basic_auth_digest'  => null,
		);

		$save_changes = false;
		$version      = self::$options->get( 'version' );

		// New installation, set default options.
		if ( null === $version ) {
			Page::create_or_update_table();
			self::set_default_options();
		}

		if ( version_compare( $version, SIMPLY_STATIC_VERSION, '!=' ) ) {
			// Sync database.
			Page::create_or_update_table();

			if ( floatval( $version ) < floatval( '3.0.4' ) ) {
				// Migrate settings.
				Migrate_Settings::migrate();
			}

			// Update version.
			self::$options
				->set( 'version', SIMPLY_STATIC_VERSION )
				->save();
		}
	}

	/**
	 * Add default options where they don't exist
	 *
	 * @return void
	 */
	protected static function set_default_options() {
		foreach ( self::$default_options as $option_key => $option_value ) {
			if ( self::$options->get( $option_key ) === null ) {
				self::$options->set( $option_key, $option_value );
			}
		}
	}
}
