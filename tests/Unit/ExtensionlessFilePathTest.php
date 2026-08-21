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

	private function fetcher(): Url_Fetcher {
		$reflection = new ReflectionClass( Url_Fetcher::class );

		return $reflection->newInstanceWithoutConstructor();
	}
}
