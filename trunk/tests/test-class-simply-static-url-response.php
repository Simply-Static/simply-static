<?php
/**
 * @package Simply_Static\Unit_tests
 */
class Simply_Static_Url_Response_Test extends WP_UnitTestCase {

	/**
	 * Placeholder test to prevent PHPUnit from throwing errors
	 */
	public function test_class_is_tested() {
		$this->assertTrue( true );
	}

	/**
	 * @covers Simply_Static_Url_Response::is_html
	 */
	public function test_page_is_html() {
		$response = Simply_Static_Url_Response_Factory::build( 'html' );

		$this->assertTrue( $response->is_html() );
	}

	/**
	 * @covers Simply_Static_Url_Response::is_css
	 */
	public function test_page_is_css() {
		$response = Simply_Static_Url_Response_Factory::build( 'css' );

		$this->assertTrue( $response->is_css() );
	}

	/**
	 * @covers Simply_Static_Url_Response::replace_url
	 */
	public function test_replace_url() {
		$origin_url = 'www.origin.test';
		$destination_url = 'www.destination.test';

		$content = $origin_url;

		$response = Simply_Static_Url_Response_Factory::build( 'html', $content );
		$response->replace_url( $origin_url, $destination_url );

		$this->assertEquals( $destination_url, $response->body );
	}

}
