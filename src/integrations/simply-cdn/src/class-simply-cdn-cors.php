<?php

namespace Simply_Static;

/**
 * Class to handle settings for cors.
 */
class Simply_CDN_CORS {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_CORS.
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
	 * Constructor for Simply_CDN_CORS.
	 */
	public function __construct() {
		if ( ! class_exists( '\simply_static_pro\CORS' ) ) {
			add_filter( 'allowed_http_origins', array( $this, 'add_allowed_origins' ) );
			add_action( 'init', array( $this, 'set_cors_headers' ) );
		}
	}

	/**
	 * Add static URL to allowed origins.
	 *
	 * @param array $origins list of allowed origins.
	 *
	 * @return array
	 */
	public function add_allowed_origins( $origins ) {
		$static_url = get_option( 'sch_static_url' );

		if ( ! empty( $static_url ) ) {
			$origins[] = $static_url;
		}

		return $origins;
	}

	/**
	 * Handle CORS on init.
	 *
	 * @return void
	 */
	public function set_cors_headers() {
		$origin     = get_http_origin();
		$static_url = untrailingslashit( get_option( 'sch_static_url' ) );

		// If it's a temporary URL allow all CORS requests.
		$temporary_url = strpos( $static_url, '.b-cdn.net' );

		if ( false !== $temporary_url ) {
			$static_url = '*';
		}

		if ( ! empty( $static_url ) ) {
			if ( $origin === $static_url || false !== $temporary_url ) {
				if ( ! headers_sent() ) {
					header( 'Access-Control-Allow-Origin: ' . $static_url );
					header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
					header( 'Access-Control-Allow-Credentials: true' );
					header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization ' );

					if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
						status_header( 200 );
						exit();
					}
				}
			}
		}
	}
}