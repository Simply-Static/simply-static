<?php

namespace Simply_Static;

use simply_static_pro\Helper;

class Complianz_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'complianz';

	/**
	 * @var null|Url_Extractor
	 */
	protected $extractor = null;

	/**
	 * Can this integration run?
	 *
	 * @return bool
	 */
	public function can_run() {
		return class_exists( 'COMPLIANZ' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		define( 'SS_COMPLIANZ_URL', plugin_dir_url( __FILE__ ) );

		add_filter( 'script_loader_src', [ $this, 'change_script' ], 20, 2 );
	}

	/**
	 * Save Cookie Data to a file.
	 *
	 * @return string
	 */
	public function save_cookie_data() {
		$cookie_data = $this->get_cookie_data();

		$filesystem = Util::get_file_system();

		// Get config file path.
		$upload_dir  = wp_upload_dir();
		$config_dir  = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR;
		$config_file = $config_dir . 'complianz-cookie-data.json';

		// Delete old data.
		if ( file_exists( $config_file ) ) {
			wp_delete_file( $config_file );
		}

		// Check if directory exists.
		if ( ! is_dir( $config_dir ) ) {
			wp_mkdir_p( $config_dir );
		}

		$filesystem->put_contents( $config_file, $cookie_data );

		return $config_file;
	}

	/**
	 * Return the cookie data that Complianz sends through REST API.
	 *
	 * @return false|string
	 */
	public function get_cookie_data() {
		$cookie_blocker = \COMPLIANZ::$cookie_blocker;
		$cookie_blocker->load_cookie_data();
		$response = wp_json_encode( $cookie_blocker->cookie_list );
		return $response;
	}

	/**
	 * Change the Complianz script to be loaded from our plugin.
	 * We use a modofied script to work with static sites.
	 *
	 * @param string $src URL to the script.
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	public function change_script( $src, $handle ) {
		if ( 'cmplz-cookiebanner' !== $handle ) {
			return $src;
		}

		return trailingslashit( SS_COMPLIANZ_URL ) . 'complianz/complianz.js';
	}
}