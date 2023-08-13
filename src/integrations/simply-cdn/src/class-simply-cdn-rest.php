<?php

namespace Simply_Static;

/**
 * Class to handle Rest API.
 */
class Simply_CDN_Rest {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Rest.
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
	 * Constructor for Simply_CDN_Rest.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}


	/**
	 * Setup Rest API endpoints.
	 *
	 * @return void
	 */
	public function rest_api_init() {
		register_rest_route( 'simplystatic/v1', '/clear-ssh-cache', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'clear_cache' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	/**
	 * Clear cache via rest API.
	 *
	 * @return false|string
	 */
	public function clear_cache() {
		Simply_CDN_Api::clear_cache();

		return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
	}
}



