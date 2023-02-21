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
		$this->include_files();
	}

	/**
	 * Include files for the SimplyCDN integration.
	 *
	 * @return void
	 */
	public function include_files() {
		$path = SIMPLY_STATIC_PATH . 'src/integrations/simply-cdn/src/';

		// Admin settings.
		require_once $path . 'class-simply-cdn-admin.php';
		Simply_CDN_Admin::get_instance();

		// Api.
		require_once $path . 'class-simply-cdn-api.php';
		Simply_CDN_Api::get_instance();

		// Cors.
		require_once $path . 'class-simply-cdn-cors.php';
		Simply_CDN_CORS::get_instance();

		// Webhook.
		require_once $path . 'class-simply-cdn-webhook.php';
		Simply_CDN_Webhook::get_instance();

		// Exports.
		require_once $path . 'class-simply-cdn-export.php';
		Simply_CDN_Export::get_instance();

		// CDN.
		require_once $path . 'class-simply-cdn-handler.php';
	}
}
