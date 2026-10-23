<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Crawler\Crawler;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/crawler/class-ss-crawler.php';
require_once dirname( __DIR__, 2 ) . '/src/crawler/class-ss-home-crawler.php';

final class CrawlerQueueWpdb {

	/** @var array<int,array<string,mixed>> */
	public $inserts = array();

	/** @var int */
	public $insert_id = 1;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @return null */
	public function get_row( string $query, $output = null ) {
		return null;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return int
	 */
	public function insert( string $table, array $data ): int {
		$this->inserts[] = array(
			'table' => $table,
			'data'  => $data,
		);
		++$this->insert_id;

		return 1;
	}
}

final class FragmentCrawler extends Crawler {

	/** @var string[] */
	private $urls;

	/** @param string[] $urls */
	public function __construct( array $urls ) {
		$this->name = 'Fragment test';
		$this->urls = $urls;
	}

	/** @return string[] */
	public function detect(): array {
		return $this->urls;
	}

	/** @param string[] $urls @return string[] */
	public function expandWpmlUrls( array $urls ): array {
		return $this->expand_wpml_urls( $urls );
	}
}

final class CrawlerQueueTest extends UnitTestCase {

	/** @var CrawlerQueueWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		$this->wpdb       = new CrawlerQueueWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	public function test_fragment_routes_are_not_added_as_separate_pages(): void {
		$crawler = new FragmentCrawler(
			array(
				'#',
				'#/',
				'#/page/2/',
				'https://example.test/#/page/3/',
				'https://example.test/article/#section',
				'https://example.test/actual/',
			)
		);

		$added = $crawler->add_urls_to_queue();

		self::assertSame( 3, $added );
		self::assertSame(
			array(
				'https://example.test/',
				'https://example.test/article/',
				'https://example.test/actual/',
			),
			array_column( array_column( $this->wpdb->inserts, 'data' ), 'url' )
		);
	}

	public function test_crawlers_complete_in_one_call_by_default(): void {
		self::assertTrue( ( new FragmentCrawler( array() ) )->is_complete() );
		self::assertSame( array(), ( new FragmentCrawler( array() ) )->get_progress() );
	}

	public function test_wpml_urls_are_unchanged_when_wpml_is_not_active(): void {
		$urls = array( 'https://example.test/', 'https://example.test/category/news/' );

		self::assertSame( $urls, ( new FragmentCrawler( array() ) )->expandWpmlUrls( $urls ) );
	}

	public function test_wpml_urls_are_expanded_for_each_active_language(): void {
		$calls = array();
		add_filter(
			'wpml_active_languages',
			static function ( $languages, $args ) {
				self::assertSame( array( 'skip_missing' => 0 ), $args );

				return array(
					'english-key' => array( 'language_code' => 'en' ),
					'spanish-key' => array( 'language_code' => 'es' ),
				);
			},
			10,
			2
		);
		add_filter(
			'wpml_permalink',
			static function ( $url, $language_code ) use ( &$calls ) {
				$calls[] = array( $url, $language_code );

				return 'es' === $language_code
					? str_replace( 'https://example.test/', 'https://example.test/es/', $url )
					: $url;
			},
			10,
			2
		);

		$urls = ( new FragmentCrawler( array() ) )->expandWpmlUrls(
			array( 'https://example.test/', 'https://example.test/category/news/' )
		);

		self::assertSame(
			array(
				'https://example.test/',
				'https://example.test/category/news/',
				'https://example.test/es/',
				'https://example.test/es/category/news/',
			),
			$urls
		);
		self::assertSame(
			array(
				array( 'https://example.test/', 'en' ),
				array( 'https://example.test/', 'es' ),
				array( 'https://example.test/category/news/', 'en' ),
				array( 'https://example.test/category/news/', 'es' ),
			),
			$calls
		);
	}

	public function test_home_crawler_detects_each_wpml_language_homepage(): void {
		add_filter(
			'wpml_active_languages',
			static function () {
				return array(
					'en' => array( 'language_code' => 'en' ),
					'es' => array( 'language_code' => 'es' ),
				);
			}
		);
		add_filter(
			'wpml_permalink',
			static function ( $url, $language_code ) {
				return 'es' === $language_code ? 'https://example.test/es/' : $url;
			},
			10,
			2
		);

		$crawler = new \Simply_Static\Crawler\Home_Crawler();
		self::assertSame(
			array( 'https://example.test/', 'https://example.test/es/' ),
			$crawler->detect()
		);
		self::assertSame( 2, $crawler->add_urls_to_queue() );
		self::assertSame(
			array( 'https://example.test/', 'https://example.test/es/' ),
			array_column( array_column( $this->wpdb->inserts, 'data' ), 'url' )
		);
	}
}
