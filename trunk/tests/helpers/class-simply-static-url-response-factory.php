<?php
/**
 * @package Simply_Static\Unit_tests
 */

/**
 * Returns a faked URL Response
 */
class Simply_Static_Url_Response_Factory extends WP_UnitTestCase {

	public static function build( $content_type, $body = '', $url = '' ) {
		return new Simply_Static_Url_Response( $url, array(
			'headers' => array (
				'content-type' => 'text/' . $content_type . '; charset=UTF-8'
			),
			'body' => $body,
			'response' => array (
				'code' => 200,
				'message' => 'OK'
			)
		) );
	}
}
