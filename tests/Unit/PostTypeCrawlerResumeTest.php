<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Crawler\Post_Type_Crawler;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class PostTypeCrawlerResumeWpdb {

	/** @var string */
	public $prefix = 'wp_';

	/** @var string */
	public $posts = 'wp_posts';

	/** @var int */
	public $insert_id = 1;

	/** @var array<int,array<string,mixed>> */
	public $inserts = array();

	/** @var string[] */
	public $queries = array();

	/** @var array<string,int[]> */
	private $post_ids;

	/** @param array<string,int[]> $post_ids */
	public function __construct( array $post_ids ) {
		$this->post_ids = $post_ids;
	}

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @param mixed ...$arguments */
	public function prepare( string $query, ...$arguments ): string {
		$values = array_map( static function ( $value ) {
			return is_string( $value ) ? "'" . addslashes( $value ) . "'" : $value;
		}, $arguments );

		return vsprintf( $query, $values );
	}

	/** @return null */
	public function get_row( string $query, $output = null ) {
		return null;
	}

	/** @return int[] */
	public function get_col( string $query ): array {
		$this->queries[] = $query;
		preg_match( "/post_type = '([^']+)'.*ID > (\d+).*LIMIT (\d+)/", $query, $matches );
		$post_type = $matches[1] ?? '';
		$last_id   = isset( $matches[2] ) ? (int) $matches[2] : 0;
		$limit     = isset( $matches[3] ) ? (int) $matches[3] : 100;
		$ids       = array_values( array_filter( $this->post_ids[ $post_type ] ?? array(), static function ( int $id ) use ( $last_id ): bool {
			return $id > $last_id;
		} ) );

		return array_slice( $ids, 0, $limit );
	}

	/** @return array<int,object> */
	public function get_results( string $query ): array {
		$this->queries[] = $query;
		return array();
	}

	/** @param array<string,mixed> $data */
	public function insert( string $table, array $data ): int {
		$this->inserts[] = array( 'table' => $table, 'data' => $data );
		++$this->insert_id;

		return 1;
	}

	public function query( string $query ): int {
		$this->queries[] = $query;
		return 0;
	}
}

final class PostTypeCrawlerResumeTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-post-type-crawler.php' );

		WpEnv::$post_types = array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' );
		WpEnv::$options['simply-static'] = array(
			'post_types'            => array( 'post', 'page' ),
			'post_types_configured' => true,
		);
		Options::instance()->set( 'archive_start_time', '2026-08-19 12:00:00' )->save();
	}

	protected function tearDown(): void {
		WpEnv::$post_types = null;
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	public function test_public_posts_resume_with_a_keyset_and_bounded_queries(): void {
		$database = new PostTypeCrawlerResumeWpdb( array(
			'post' => array( 2, 7, 11 ),
			'page' => array( 3, 9 ),
		) );
		$GLOBALS['wpdb'] = $database;
		add_filter( 'simply_static_post_type_crawler_batch_size', static function (): int {
			return 2;
		} );
		add_filter( 'simply_static_post_type_crawler_max_entries_per_batch', static function (): int {
			return 2;
		} );

		$total_added = 0;
		$complete    = false;
		$progress    = array();
		for ( $attempt = 0; $attempt < 10; ++$attempt ) {
			$crawler      = new Post_Type_Crawler();
			$total_added += $crawler->add_urls_to_queue();
			$complete     = $crawler->is_complete();
			$progress     = $crawler->get_progress();
			if ( $complete ) {
				break;
			}
			self::assertIsArray( Options::instance()->get( 'post_type_crawler_state' ) );
		}

		self::assertTrue( $complete );
		self::assertSame( 5, $total_added );
		self::assertSame( 5, $progress['added'] );
		self::assertSame( 5, $progress['scanned'] );
		self::assertNull( Options::instance()->get( 'post_type_crawler_state' ) );
		self::assertStringContainsString( 'ID > 0', $database->queries[0] );
		self::assertStringContainsString( 'ID > 7', $database->queries[1] );
		self::assertStringNotContainsString( 'OFFSET', implode( ' ', $database->queries ) );

		$urls = array_column( array_column( $database->inserts, 'data' ), 'url' );
		self::assertSame(
			array(
				'https://example.test/post-2/',
				'https://example.test/post-7/',
				'https://example.test/post-11/',
				'https://example.test/post-3/',
				'https://example.test/post-9/',
			),
			$urls
		);
	}
}
