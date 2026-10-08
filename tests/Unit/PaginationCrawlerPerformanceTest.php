<?php

declare(strict_types=1);

namespace Simply_Static\Crawler {
	/** @param array<string,mixed> $args */
	function get_posts( $args = array() ) {
		$GLOBALS['simply_static_pagination_queries'][] = $args;
		$posts  = isset( $GLOBALS['simply_static_pagination_posts'] ) ? $GLOBALS['simply_static_pagination_posts'] : array();
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$limit  = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : count( $posts );

		return array_slice( $posts, $offset, $limit );
	}

	function get_permalink( $post_id ) {
		return 'https://example.test/post-' . (int) $post_id . '/';
	}

	function get_year_link( $year ) {
		return 'https://example.test/' . (int) $year . '/';
	}

	function get_month_link( $year, $month ) {
		return sprintf( 'https://example.test/%d/%02d/', (int) $year, (int) $month );
	}

	function get_day_link( $year, $month, $day ) {
		return sprintf( 'https://example.test/%d/%02d/%02d/', (int) $year, (int) $month, (int) $day );
	}

	function get_categories( $args = array() ) {
		return array();
	}

	function get_tags( $args = array() ) {
		return array();
	}

	function get_users( $args = array() ) {
		return array();
	}
}

namespace Simply_Static\Tests\Unit {

	use ReflectionMethod;
	use Simply_Static\Crawler\Pagination_Crawler;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class PaginationCrawlerWpdb {

		/** @var string */
		public $posts = 'wp_posts';

		/** @var string[] */
		public $queries = array();

		/** @var array<string,object[]> */
		private $archives;

		/** @param array<string,object[]> $archives */
		public function __construct( array $archives ) {
			$this->archives = $archives;
		}

		public function prepare( string $query, int $limit ): string {
			return str_replace( '%d', (string) $limit, $query );
		}

		/** @return object[] */
		public function get_results( string $query ): array {
			$this->queries[] = $query;

			if ( false !== strpos( $query, 'DAYOFMONTH(post_date) AS day' ) ) {
				return $this->archives['daily'];
			}

			if ( false !== strpos( $query, 'MONTH(post_date) AS month' ) ) {
				return $this->archives['monthly'];
			}

			return $this->archives['yearly'];
		}
	}

	final class PaginationCrawlerPerformanceTest extends UnitTestCase {

		/** @var mixed */
		private $previous_wpdb;

		protected function setUp(): void {
			parent::setUp();
			$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
			$this->requireSource( 'src/class-ss-plugin.php' );
			$this->requireSource( 'src/class-ss-options.php' );
			$this->requireSource( 'src/class-ss-util.php' );
			$this->requireSource( 'src/crawler/class-ss-crawler.php' );
			$this->requireSource( 'src/crawler/class-ss-pagination-crawler.php' );

			$GLOBALS['simply_static_pagination_queries'] = array();
			$GLOBALS['simply_static_pagination_posts']   = array();
			for ( $id = 1; $id <= 25; ++$id ) {
				$GLOBALS['simply_static_pagination_posts'][] = (object) array(
					'ID'           => $id,
					'post_content' => 'First<!--nextpage-->Second<!--nextpage-->Third',
				);
			}
			WpEnv::$options['simply-static'] = array(
				'post_types'            => array( 'post' ),
				'post_types_configured' => true,
			);
		}

		protected function tearDown(): void {
			unset( $GLOBALS['simply_static_pagination_queries'], $GLOBALS['simply_static_pagination_posts'] );
			if ( null === $this->previous_wpdb ) {
				unset( $GLOBALS['wpdb'] );
			} else {
				$GLOBALS['wpdb'] = $this->previous_wpdb;
			}
			parent::tearDown();
		}

		public function test_nextpage_candidate_query_is_batched_and_hard_limited(): void {
			add_filter(
				'simply_static_pagination_post_query_batch_size',
				static function (): int {
					return 10;
				}
			);
			add_filter(
				'simply_static_pagination_max_posts_to_scan',
				static function (): int {
					return 12;
				}
			);

			$crawler = new Pagination_Crawler();
			$method  = new ReflectionMethod( Pagination_Crawler::class, 'get_post_pagination' );
			$method->setAccessible( true );
			$urls = $method->invoke( $crawler );

			self::assertCount( 24, $urls );
			self::assertSame( 'https://example.test/post-1/2/', $urls[0] );
			self::assertSame( 'https://example.test/post-12/3/', $urls[23] );
			self::assertCount( 2, $GLOBALS['simply_static_pagination_queries'] );
			self::assertSame( 0, $GLOBALS['simply_static_pagination_queries'][0]['offset'] );
			self::assertSame( 10, $GLOBALS['simply_static_pagination_queries'][0]['posts_per_page'] );
			self::assertSame( 10, $GLOBALS['simply_static_pagination_queries'][1]['offset'] );
			self::assertSame( 2, $GLOBALS['simply_static_pagination_queries'][1]['posts_per_page'] );
			self::assertTrue( $GLOBALS['simply_static_pagination_queries'][0]['no_found_rows'] );
			self::assertFalse( $GLOBALS['simply_static_pagination_queries'][0]['update_post_meta_cache'] );
		}

		public function test_public_cpt_archive_pagination_is_discovered_by_default(): void {
			WpEnv::$post_types = array(
				'post'               => 'post',
				'knowledge_articles' => 'knowledge_articles',
			);
			WpEnv::$post_type_archives = array(
				'knowledge_articles' => 'https://example.test/knowledge-articles/',
			);
			WpEnv::$post_type_counts = array(
				'post'               => 0,
				'knowledge_articles' => 15,
			);
			WpEnv::$options['posts_per_page'] = 10;
			WpEnv::$options['simply-static'] = array(
				'post_types'            => array( 'knowledge_articles' ),
				'post_types_configured' => true,
			);

			$crawler = new Pagination_Crawler();
			$method  = new ReflectionMethod( Pagination_Crawler::class, 'get_archive_pagination' );
			$method->setAccessible( true );
			$urls = $method->invoke( $crawler );

			self::assertSame(
				array( 'https://example.test/knowledge-articles/page/2/' ),
				$urls
			);
		}

		/**
		 * @see https://github.com/Simply-Static/simply-static/issues/470
		 */
		public function test_date_archive_pagination_is_discovered(): void {
			$database = new PaginationCrawlerWpdb(
				array(
					'yearly' => array(
						(object) array( 'year' => 2018, 'posts' => 21 ),
					),
					'monthly' => array(
						(object) array( 'year' => 2018, 'month' => 11, 'posts' => 11 ),
						(object) array( 'year' => 2018, 'month' => 10, 'posts' => 10 ),
					),
					'daily' => array(
						(object) array( 'year' => 2018, 'month' => 11, 'day' => 1, 'posts' => 25 ),
					),
				)
			);
			$GLOBALS['wpdb'] = $database;
			WpEnv::$options['posts_per_page'] = 10;
			WpEnv::$options['simply-static'] = array(
				'post_types'            => array( 'post' ),
				'post_types_configured' => true,
			);
			WpEnv::$post_type_counts['post'] = 0;

			$crawler = new Pagination_Crawler();
			$method  = new ReflectionMethod( Pagination_Crawler::class, 'get_archive_pagination' );
			$method->setAccessible( true );
			$urls = $method->invoke( $crawler );

			self::assertSame(
				array(
					'https://example.test/2018/page/2/',
					'https://example.test/2018/page/3/',
					'https://example.test/2018/11/page/2/',
					'https://example.test/2018/11/01/page/2/',
					'https://example.test/2018/11/01/page/3/',
				),
				$urls
			);
			self::assertCount( 3, $database->queries );
			self::assertStringContainsString( "post_type = 'post' AND post_status = 'publish'", $database->queries[0] );
			self::assertStringContainsString( 'LIMIT 5000', $database->queries[0] );
		}
	}
}
