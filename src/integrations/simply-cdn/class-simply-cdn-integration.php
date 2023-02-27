<?php

namespace Simply_Static;

/**
 * Class to handle the Simply CDN integration.
 */
class Simply_CDN_Integration {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Integration.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Simply_CDN_Integration.
	 *
	 * @return void
	 */
	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		
		if ( ! is_plugin_active( 'simply-cdn-helper/simply-cdn-helper.php' ) ) {
			$this->include_files();
		}
	}

	/**
	 * Include files for the SimplyCDN integration.
	 *
	 * @return void
	 */
	public function include_files() {
		$path = SIMPLY_STATIC_PATH . 'src/integrations/simply-cdn/src/';

		// CDN.
		require_once $path . 'class-simply-cdn-handler.php';

		// Api.
		require_once $path . 'class-simply-cdn-api.php';
		Simply_CDN_Api::get_instance();

		// Admin settings.
		require_once $path . 'class-simply-cdn-admin.php';
		Simply_CDN_Admin::get_instance();

		// Include only if connected.
		$token = get_option( 'sch_token' );

		if ( ! empty( $token ) ) {
			$data = Simply_CDN_Api::get_data( $token );

			if ( $data && ! empty( $data->cdn->url ) ) {
				// Cors.
				require_once $path . 'class-simply-cdn-cors.php';
				Simply_CDN_CORS::get_instance();

				// Webhook.
				require_once $path . 'class-simply-cdn-webhook.php';
				Simply_CDN_Webhook::get_instance();

				// Exports.
				require_once $path . 'class-simply-cdn-export.php';
				Simply_CDN_Export::get_instance();

			}
		}
	}
}
