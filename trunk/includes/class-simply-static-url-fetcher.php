<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL fetcher class
 *
 * @package Simply_Static
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
		$response = wp_remote_get( $url, array(
			'timeout' => self::TIMEOUT,
			'redirection' => 0 // disable redirection
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return new Simply_Static_Url_Response( $url, $response );
		}
	}

}
