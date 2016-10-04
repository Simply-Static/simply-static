<?php
/**
 * @package Simply_Static\Unit_tests
 */
class Simply_Static_Url_Response_Test extends WP_UnitTestCase {

	/**
	 * Set the test domain
	 */
	const DOMAIN = 'http://example.org';

	/**
	 * Set the test URL
	 */
	const URL = 'http://example.org/blog/my-first-blog-post';

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
		$response = Simply_Static_Url_Response_Factory::build( 'html', '', self::URL );

		$this->assertTrue( $response->is_html() );
	}

	/**
	 * @covers Simply_Static_Url_Response::is_css
	 */
	public function test_page_is_css() {
		$response = Simply_Static_Url_Response_Factory::build( 'css', '', self::URL );

		$this->assertTrue( $response->is_css() );
	}

	/**
	 * @covers Simply_Static_Url_Response::replace_urls
	 */
	public function test_replace_urls() {
		$destination_scheme = 'http://';
		$destination_host = 'www.destination.test';

		$content = self::DOMAIN;

		$response = Simply_Static_Url_Response_Factory::build( 'html', $content, self::URL );
		$response->replace_urls( $destination_scheme, $destination_host );

		$this->assertEquals( $destination_scheme . $destination_host, $response->get_body() );
	}

}
