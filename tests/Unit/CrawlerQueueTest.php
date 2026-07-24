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
}
