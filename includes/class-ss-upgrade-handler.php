<?php
namespace Simply_Static;

// Exit if accessed directly
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
	 * Singleton instance
	 * @var Simply_Static\Upgrade_Handler
	 */
	protected static $instance = null;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * Default options to set for the plugin
	 * @var array
	 */
	protected $default_options = null;

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Return an instance of Simply_Static\Sql_Permissions
	 * @return Simply_Static\Sql_Permissions
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->options = Options::instance();

			self::$instance->default_options = array(
				'destination_scheme' => sist_origin_scheme(),
				'destination_host' => sist_origin_host(),
				'temp_files_dir' => trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' ),
				'additional_urls' => '',
				'additional_files' => '',
				'delivery_method' => 'zip',
				'local_dir' => '',
				'delete_temp_files' => '1',
				'relative_path' => '',
				'destination_url_type' => 'relative',
				'archive_status_messages' => array(),
				'archive_name' => null,
				'archive_start_time' => null,
				'archive_end_time' => null,

			);
		}

		return self::$instance;
	}


	/**
	 * Create settings and setup database
	 * @return void
	 */
	public function run() {
		$save_changes = false;
		$version = $this->options->get( 'version' );

		// Never installed or options key changed
		if ( null === $version ) {
			$save_changes = true;

			// checking for legacy options key
			$old_ss_options = get_option( 'simply_static' );

			if ( $old_ss_options ) { // options key changed
				update_option( 'simply-static', $old_ss_options );
				delete_option( 'simply_static' );

				// update Simply_Static\Options again to pull in updated data
				$this->options = new Options();
			}
		}

		// sync the database on any install/upgrade/downgrade
		if ( version_compare( $version, Plugin::VERSION, '!=' ) ) {
			$save_changes = true;

			Page::create_or_update_table();
			$this->set_default_options();

			// perform migrations if our saved version # is older than
			// the current version
			if ( version_compare( $version, Plugin::VERSION, '<' ) ) {

				if ( version_compare( $version, '1.4.0', '<' ) ) {
					// check for, and add, the WP emoji url if it's missing
					$emoji_url = includes_url( 'js/wp-emoji-release.min.js' );
					$additional_urls = $this->options->get( 'additional_urls' );
					$urls_array = sist_string_to_array( $additional_urls );

					if ( ! in_array( $emoji_url, $urls_array ) ) {
						$additional_urls = $additional_urls . "\n"  . $emoji_url;
						$this->options->set( 'additional_urls', $additional_urls );
					}
				}

				if ( version_compare( $version, '1.7.0', '<' ) ) {
					$scheme = $this->options->get( 'destination_scheme' );
					if ( strpos( $scheme, '://' ) === false ) {
						$scheme = $scheme . '://';
						$this->options->set( 'destination_scheme', $scheme );
					}

					$host = $this->options->get( 'destination_host' );
					if ( $host == sist_origin_host() ) {
						$this->options->set( 'destination_url_type', 'relative' );
					} else {
						$this->options->set( 'destination_url_type', 'absolute' );
					}
				}

				if ( version_compare( $version, '1.7.1', '<' ) ) {
					// check for, and add, the WP uploads dir if it's missing
					$upload_dir = wp_upload_dir();
					if ( isset( $upload_dir['basedir'] ) ) {
						$upload_dir = trailingslashit( $upload_dir['basedir'] );

						$additional_files = $this->options->get( 'additional_files' );
						$files_array = sist_string_to_array( $additional_files );

						if ( ! in_array( $upload_dir, $files_array ) ) {
							$additional_files = $additional_files . "\n" . $upload_dir;
							$this->options->set( 'additional_files', $additional_files );
						}
					}
				}
			}

			$this->remove_old_options();
		}

		if ( $save_changes ) {
			// update the version and save options
			$this->options
				->set( 'version', Plugin::VERSION )
				->save();
		}
	}

	/**
	 * Add default options where they don't exist
	 * @return void
	 */
	protected function set_default_options() {
		foreach ( $this->default_options as $option_key => $option_value ) {
			if ( $this->options->get( $option_key ) === null ) {
				$this->options->set( $option_key, $option_value );
			}
		}
	}

	/**
	 * Remove any unused (old) options
	 * @return void
	 */
	protected function remove_old_options() {
		$all_options = $this->options->get_as_array();

		foreach ( $all_options as $option_key => $option_value ) {
			if ( ! array_key_exists( $option_key, $this->default_options ) ) {
				$this->options->destroy( $option_key );
			}
		}
	}
}
