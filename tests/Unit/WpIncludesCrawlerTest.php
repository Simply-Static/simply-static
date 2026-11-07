<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Crawler\Wp_Includes_Crawler;
use Simply_Static\Options;
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

	/**
	 * @dataProvider crawlerSelectionProvider
	 * @param array|null $selection Saved crawler IDs, or null for defaults.
	 */
	public function test_activation_respects_crawler_selection( bool $smart_crawl, ?array $selection, bool $expected ): void {
		Options::instance()->set( 'smart_crawl', $smart_crawl )->set( 'crawlers', $selection )->save();
		Options::reinstance();

		$crawler = new Wp_Includes_Crawler();

		self::assertSame( $expected, $crawler->is_active() );
		self::assertSame( $expected, $crawler->js_object()['active'] );
	}

	/** @return array<string,array{bool,?array,bool}> */
	public function crawlerSelectionProvider(): array {
		return array(
			'enhanced crawl with includes removed'  => array( true, array( 'home', 'post_type' ), false ),
			'enhanced crawl with no crawlers'       => array( true, array(), false ),
			'enhanced crawl with includes selected' => array( true, array( 'wp_includes' ), true ),
			'enhanced crawl with defaults'          => array( true, null, true ),
			'legacy crawl with includes removed'    => array( false, array( 'home', 'post_type' ), false ),
			'legacy crawl with no crawlers'         => array( false, array(), false ),
			'legacy crawl with includes selected'   => array( false, array( 'wp_includes' ), true ),
			'legacy crawl with defaults'            => array( false, null, true ),
		);
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
