<?php
/**
 * @package Simply_Static\Unit_tests
 */

/**
 * Returns a faked URL Response
 */
class Simply_Static_Url_Response_Factory extends WP_UnitTestCase {

	public static function build( $content_type, $body, $url ) {
		$filename = tempnam( get_temp_dir(), 'sst' );
		file_put_contents( $filename, $body );

		return new Simply_Static_Url_Response( $url, array(
			'headers' => array (
				'content-type' => 'text/' . $content_type . '; charset=UTF-8'
			),
			'filename' => $filename,
			'response' => array (
				'code' => 200,
				'message' => 'OK'
			)
		) );
	}
}
