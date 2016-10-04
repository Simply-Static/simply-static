<?php
/**
 * @package Simply_Static\Unit_tests
 */

/**
 * Returns a faked URL Extractor
 */
class Simply_Static_Url_Extractor_Factory extends WP_UnitTestCase {

	public static function build( $content_type, $body, $url ) {
		$response = Simply_Static_Url_Response_Factory::build( $content_type, $body, $url );

		return new Simply_Static_Url_Extractor( $response, 'absolute' );
	}

	public static function build_from_response( $response ) {
		return new Simply_Static_Url_Extractor( $response, 'absolute' );
	}
}
