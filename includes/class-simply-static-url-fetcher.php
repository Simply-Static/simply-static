<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL fetcher class
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
	public static function fetch( $url, $filename ) {
		// Don't process URLs that don't match the URL of this WordPress installation
		if ( ! sist_is_local_url( $url ) ) {
			return new WP_Error( 'remote_url', sprintf( __( "Attempting to fetch remote URL: %s", Simply_Static::SLUG ), $url ) );
		}

		$response = wp_remote_get( $url, array(
			'timeout' => self::TIMEOUT,
			'sslverify' => false, // not verifying SSL because all calls are local
			'redirection' => 0, // disable redirection
			'blocking' => true, // do not execute code until this call is complete
			'stream' => true, // stream body content to a file
			'filename' => $filename
		) );

		// Don't save a file for anything that isn't a 200 response
		if ( $response['response']['code'] !== 200 ) {
			unlink($filename);
			$response['filename'] = null;
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return new Simply_Static_Url_Response( $url, $response );
		}
	}

}
