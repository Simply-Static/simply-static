<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Crawler\Text_File_Crawler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class TextFileCrawlerTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-text-file-crawler.php' );
	}

	public function test_it_discovers_a_live_dynamic_llms_file_with_a_bounded_request(): void {
		$this->setResponse( 200, "# Example\n\n- [Docs](https://example.test/docs/)" );

		self::assertSame(
			array( 'https://example.test/llms.txt' ),
			( new Text_File_Crawler() )->detect()
		);

		self::assertCount( 1, WpEnv::$remote_requests );
		$request = WpEnv::$remote_requests[0];
		self::assertSame( 'GET', $request['method'] );
		self::assertSame( 'https://example.test/llms.txt', $request['url'] );
		self::assertSame( 5.0, $request['args']['timeout'] );
		self::assertSame( 0, $request['args']['redirection'] );
		self::assertTrue( $request['args']['sslverify'] );
		self::assertSame( 4096, $request['args']['limit_response_size'] );
	}

	/**
	 * @dataProvider invalidResponseProvider
	 */
	public function test_it_does_not_queue_missing_empty_or_html_responses( int $status, string $body ): void {
		$this->setResponse( $status, $body );

		self::assertSame( array(), ( new Text_File_Crawler() )->detect() );
	}

	/** @return array<string,array{int,string}> */
	public function invalidResponseProvider(): array {
		return array(
			'not found' => array( 404, 'Not found' ),
			'empty'     => array( 200, '' ),
			'soft 404'  => array( 200, '<!doctype html><html><body>Not found</body></html>' ),
		);
	}

	public function test_it_respects_the_existing_llms_exclusion_filter_without_a_request(): void {
		add_filter( 'ss_include_llms_txt_in_export', static function () {
			return false;
		} );

		self::assertSame( array(), ( new Text_File_Crawler() )->detect() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_it_rejects_a_filtered_external_llms_endpoint_before_requesting_it(): void {
		add_filter( 'simply_static_llms_txt_url', static function () {
			return 'https://attacker.test/llms.txt';
		} );

		self::assertSame( array(), ( new Text_File_Crawler() )->detect() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_it_rejects_an_alternate_port_before_requesting_it(): void {
		add_filter( 'simply_static_llms_txt_url', static function () {
			return 'https://example.test:8443/llms.txt';
		} );

		self::assertSame( array(), ( new Text_File_Crawler() )->detect() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_it_assigns_the_text_file_handler_to_discovered_pages(): void {
		$page   = new \stdClass();
		$method = new ReflectionMethod( Text_File_Crawler::class, 'configure_static_page' );
		$method->setAccessible( true );
		$method->invoke( new Text_File_Crawler(), $page, 'https://example.test/llms.txt' );

		self::assertSame( 'Simply_Static\\Text_File_Handler', $page->handler );
	}

	private function setResponse( int $status, string $body ): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => $status ),
			'headers'  => array(),
			'body'     => $body,
		);
	}
}
