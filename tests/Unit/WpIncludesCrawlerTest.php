<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Crawler\Wp_Includes_Crawler;
use Simply_Static\Tests\Support\UnitTestCase;

final class WpIncludesCrawlerTest extends UnitTestCase {

	/** @var string */
	private $includes_dir;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-wp-includes-crawler.php' );

		$this->includes_dir = ABSPATH . 'wp-includes/js/';
		wp_mkdir_p( $this->includes_dir . 'swfupload' );
	}

	protected function tearDown(): void {
		foreach (
			array(
				'supported.js',
				'swfobject.js',
				'swfupload/handlers.js',
				'swfupload/handlers.min.js',
				'swfupload/swfupload.js',
			) as $file
		) {
			@unlink( $this->includes_dir . $file );
		}

		@rmdir( $this->includes_dir . 'swfupload' );
		@rmdir( $this->includes_dir );
		@rmdir( dirname( $this->includes_dir ) );
		@rmdir( ABSPATH );

		parent::tearDown();
	}

	public function test_detect_skips_empty_deprecated_assets(): void {
		file_put_contents( $this->includes_dir . 'supported.js', 'window.supported = true;' );
		file_put_contents( $this->includes_dir . 'swfobject.js', '' );
		file_put_contents( $this->includes_dir . 'swfupload/handlers.js', '' );
		file_put_contents( $this->includes_dir . 'swfupload/handlers.min.js', '' );
		file_put_contents( $this->includes_dir . 'swfupload/swfupload.js', '' );

		$urls = ( new Wp_Includes_Crawler() )->detect();

		self::assertContains( 'https://example.test/wp-includes/js/supported.js', $urls );
		self::assertNotContains( 'https://example.test/wp-includes/js/swfobject.js', $urls );
		self::assertNotContains( 'https://example.test/wp-includes/js/swfupload/handlers.js', $urls );
		self::assertNotContains( 'https://example.test/wp-includes/js/swfupload/handlers.min.js', $urls );
		self::assertNotContains( 'https://example.test/wp-includes/js/swfupload/swfupload.js', $urls );
	}

	public function test_detect_keeps_non_empty_legacy_assets(): void {
		file_put_contents( $this->includes_dir . 'swfobject.js', 'window.swfobject = {};' );

		$urls = ( new Wp_Includes_Crawler() )->detect();

		self::assertContains( 'https://example.test/wp-includes/js/swfobject.js', $urls );
	}
}
