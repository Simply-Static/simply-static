<?php
/**
 * @package Simply_Static\Unit_tests
 */
class Simply_Static_Url_Extractor_Test extends WP_UnitTestCase {

	/**
	 * Set the test domain
	 */
	const DOMAIN = 'http://www.origin.test/';

	/**
	 * Set the test URL
	 */
	const URL = 'http://www.origin.test/blog/my-first-blog-post';

	/**
	 * Helper function for creating extractors
	 */
	function build_extractor( $content_type, $body, $url = self::URL ) {
		return Simply_Static_Url_Extractor_Factory::build( $content_type, $body, $url );
	}

	/**
	 * Placeholder test to prevent PHPUnit from throwing errors
	 */
	public function test_class_is_tested() {
		$this->assertTrue( true );
	}

	public function test_extract_urls() {
		$body = "<a href='/one.htm'>one</a>";

		$extractor = $this->build_extractor( 'html', $body );

		// TODO
	}
}
