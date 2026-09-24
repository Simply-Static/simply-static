<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Additional_File_Handler;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Text_File_Handler;
use Simply_Static\Url_Fetcher;

final class ExtensionlessFilePathTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/handlers/class-ss-additional-file-handler.php' );
		$this->requireSource( 'src/handlers/class-ss-text-file-handler.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/class-ss-url-fetcher.php' );

		Options::reinstance();
	}

	/**
	 * @dataProvider extensionlessFileProvider
	 */
	public function test_file_handlers_preserve_extensionless_paths( string $url, string $handler, string $expected ): void {
		$page = Page::initialize(
			array(
				'url'           => $url,
				'content_type'  => 'text/plain',
				'handler'       => $handler,
			)
		);

		self::assertSame( $expected, $this->fetcher()->get_expected_file_path_for_static_page( $page ) );
	}

	/** @return array<string,array{string,string,string}> */
	public function extensionlessFileProvider(): array {
		return array(
			'headers rule file' => array(
				'https://example.test/_headers',
				Text_File_Handler::class,
				'_headers',
			),
			'redirects rule file' => array(
				'https://example.test/_redirects',
				Text_File_Handler::class,
				'_redirects',
			),
			'nested rule file' => array(
				'https://example.test/config/_headers',
				Text_File_Handler::class,
				'config/_headers',
			),
			'custom additional file' => array(
				'https://example.test/CNAME',
				Additional_File_Handler::class,
				'CNAME',
			),
		);
	}

	public function test_regular_extensionless_pages_still_use_directory_indexes(): void {
		$page = Page::initialize(
			array(
				'url'          => 'https://example.test/about',
				'content_type' => 'text/html; charset=UTF-8',
			)
		);

		self::assertSame( 'about/index.html', $this->fetcher()->get_expected_file_path_for_static_page( $page ) );
	}

	/**
	 * @dataProvider feedPathProvider
	 */
	public function test_feeds_use_stable_non_colliding_xml_paths( string $url, string $expected ): void {
		$page = Page::initialize(
			array(
				'url'          => $url,
				'content_type' => 'application/rss+xml; charset=UTF-8',
			)
		);

		self::assertSame( $expected, $this->fetcher()->get_expected_file_path_for_static_page( $page ) );
	}

	/** @return array<string,array{string,string}> */
	public function feedPathProvider(): array {
		return array(
			'main rss2 feed' => array(
				'https://example.test/?feed=rss2',
				'feed/index.xml',
			),
			'atom feed' => array(
				'https://example.test/?feed=atom',
				'feed/atom/index.xml',
			),
			'comments feed' => array(
				'https://example.test/?feed=comments-rss2',
				'comments/feed/index.xml',
			),
			'custom post type feed' => array(
				'https://example.test/?post_type=book&feed=rss2',
				'feed/rss2/__qs/' . substr( md5( 'feed=rss2&post_type=book' ), 0, 12 ) . '/index.xml',
			),
			'pretty custom post type feed' => array(
				'https://example.test/feed/?post_type=book',
				'feed/__qs/' . substr( md5( 'post_type=book' ), 0, 12 ) . '/index.xml',
			),
		);
	}

	private function fetcher(): Url_Fetcher {
		$reflection = new ReflectionClass( Url_Fetcher::class );

		return $reflection->newInstanceWithoutConstructor();
	}
}
