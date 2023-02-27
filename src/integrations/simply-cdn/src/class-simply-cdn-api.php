<?php

namespace Simply_Static;

/**
 * Class to handle Api settings
 */
class Simply_CDN_Api {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Api.
	 *
	 * @return object|null
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get site data
	 *
	 * @return object|bool
	 */
	public static function get_data( $token ) {
		$response = wp_remote_get( 'https://simplycdn.io?security-token=' . $token, array() );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( $response['body'] );
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Clear cache
	 *
	 * @return object|bool
	 */
	public static function clear_cache() {
		$token    = get_option( 'sch_token' );
		$response = wp_remote_get( 'https://simplycdn.io?security-token=' . $token . '&clear-cache=true', array() );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
