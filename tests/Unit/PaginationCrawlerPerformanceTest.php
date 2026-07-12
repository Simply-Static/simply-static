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
}

namespace Simply_Static\Tests\Unit {

	use ReflectionMethod;
	use Simply_Static\Crawler\Pagination_Crawler;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class PaginationCrawlerPerformanceTest extends UnitTestCase {

		protected function setUp(): void {
			parent::setUp();
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
	}
}
