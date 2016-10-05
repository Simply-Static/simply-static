<?php
/**
 * @package Simply_Static\Unit_tests
 */

/**
 * Returns a faked URL Extractor
 */
class Simply_Static_Url_Extractor_Factory extends WP_UnitTestCase {

	public static function build( $content_type, $body, $destination_url_type, $url, $relative_path = '' ) {
		$response = Simply_Static_Url_Response_Factory::build( $content_type, $body, $url );

		return new Simply_Static_Url_Extractor( $response, $destination_url_type, $relative_path );
	}

	public static function build_from_response( $response, $destination_url_type, $relative_path = '' ) {
		return new Simply_Static_Url_Extractor( $response, $destination_url_type, $relative_path );
	}
}
