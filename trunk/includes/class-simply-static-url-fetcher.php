<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static URL fetcher class
 */
class Simply_Static_Url_Fetcher {

	/**
	 * Timeout for fetching URLs
	 * @var string
	 */
	const TIMEOUT = 300;

    /**
	 * Fetch the URL and return a WP_Error if we get one, otherwise a Response class.
	 * @return WP_Error|Simply_Static_Url_Response
	 */
	public static function fetch( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => self::TIMEOUT ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return new Simply_Static_Url_Response( $url, $response );
		}
	}

}
