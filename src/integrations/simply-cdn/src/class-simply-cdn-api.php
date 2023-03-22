<?php

namespace Simply_Static;

use stdClass;

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
	 * @param string $token Given security token for authentication.
	 *
	 * @return object|bool
	 */
	public static function get_data( $token ) {
		// Maybe use constant instead of options.
		if ( defined( 'SIMPLYCDN' ) ) {
			$connection = SIMPLYCDN;

			$response                             = new StdClass();
			$response->data->cdn->sub_directory   = $connection['sub-directory'];
			$response->data->cdn->access_key      = $connection['access-key'];
			$response->data->cdn->pull_zone->name = $connection['pull-zone'];

			return $response;
		}

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
