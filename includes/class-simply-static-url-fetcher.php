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
			return new WP_Error( 'remote_url', sprintf( __( "Attempting to fetch remote URL: %s", 'simply-static' ), $url ) );
		}

		$temp_filename = $filename . '.tmp';

		$response = wp_remote_get( $url, array(
			'timeout' => self::TIMEOUT,
			'sslverify' => false, // not verifying SSL because all calls are local
			'redirection' => 0, // disable redirection
			'blocking' => true, // do not execute code until this call is complete
			'stream' => true, // stream body content to a file
			'filename' => $temp_filename
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {

			// If we got a 200, make the file permanent
			if ( $response['response']['code'] == 200 ) {
				rename( $temp_filename, $filename );
				$response['filename'] = $filename;
			} else {
				// Not a 200? Delete the file
				unlink( $temp_filename );
				$response['filename'] = null;
			}

			return new Simply_Static_Url_Response( $url, $response );
		}
	}

}
