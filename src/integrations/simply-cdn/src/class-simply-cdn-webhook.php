<?php

namespace Simply_Static;

/**
 * Class to handle form webhooks.
 */
class Simply_CDN_Webhook {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Webhook.
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
	 * Constructor for Simply_CDN_Webhook.
	 *
	 * @return void
	 */
	public function __construct() {
		$use_forms = get_option( 'sch_use_forms' );

		if ( $use_forms && ! class_exists( '\simply_static_pro\Form_Webhook' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_webhook_scripts' ) );
			add_filter( 'wpcf7_load_js', '__return_false' );
			add_filter( 'gform_form_args', array( $this, 'disable_ajax' ) );
		}
	}

	/**
	 * Enqueue scripts for webhook.
	 *
	 * @return void
	 */
	public function add_webhook_scripts() {
		wp_enqueue_script( 'sch-form-webhook-js', SIMPLY_STATIC_URL . '/src/integrations/simply-cdn/assets/sch-form-webhook.js', array(), Plugin::VERSION, true );
	}

	/**
	 * Disable ajax in Gravity Forms.
	 *
	 * @param array $args given list or arguments.
	 *
	 * @return mixed
	 */
	public function disable_ajax( $args ) {
		$args['ajax'] = false;

		return $args;
	}
}
