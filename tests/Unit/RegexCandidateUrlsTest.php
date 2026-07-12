<?php

declare(strict_types=1);

namespace Simply_Static {
	if ( ! function_exists( __NAMESPACE__ . '\\get_post_types' ) ) {
		function get_post_types( $args = array(), $output = 'names' ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getPostTypes() : \get_post_types( $args, $output );
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_posts' ) ) {
		function get_posts( $args = array() ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getPosts( $args ) : array();
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_permalink' ) ) {
		function get_permalink( $post_id ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getPermalink( (int) $post_id ) : false;
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_taxonomies' ) ) {
		function get_taxonomies( $args = array(), $output = 'names' ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getTaxonomies() : \get_taxonomies( $args, $output );
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_terms' ) ) {
		function get_terms( $args = array() ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getTerms( $args ) : array();
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_term_link' ) ) {
		function get_term_link( $term ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getTermLink( $term ) : false;
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_users' ) ) {
		function get_users( $args = array() ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getUsers( $args ) : array();
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\get_author_posts_url' ) ) {
		function get_author_posts_url( $user_id ) {
			$provider = isset( $GLOBALS['simply_static_regex_candidate_provider'] ) ? $GLOBALS['simply_static_regex_candidate_provider'] : null;

			return $provider ? $provider->getAuthorUrl( (int) $user_id ) : false;
		}
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Util;

	final class RegexCandidateUrlsTest extends UnitTestCase {

		/** @var Regex_Candidate_Provider */
		private $provider;

		protected function setUp(): void {
			parent::setUp();
			$this->requireSource( 'src/class-ss-plugin.php' );
			$this->requireSource( 'src/class-ss-options.php' );
			$this->requireSource( 'src/class-ss-util.php' );

			$this->provider = new Regex_Candidate_Provider();
			$GLOBALS['simply_static_regex_candidate_provider'] = $this->provider;
		}

		protected function tearDown(): void {
			unset( $GLOBALS['simply_static_regex_candidate_provider'] );
			parent::tearDown();
		}

		public function test_it_paginates_each_provider_and_honors_the_global_limit(): void {
			$this->setLimits( 7, 2 );
			$this->provider->posts = array( 1, 2, 3 );
			$this->provider->terms['category'] = array(
				(object) array( 'term_id' => 10, 'url' => 'https://example.test/category/one/' ),
				(object) array( 'term_id' => 11, 'url' => 'https://example.test/category/two/' ),
			);
			$this->provider->users = array( (object) array( 'ID' => 100 ) );

			self::assertSame(
				array(
					'https://example.test/',
					'https://example.test/post/1/',
					'https://example.test/post/2/',
					'https://example.test/post/3/',
					'https://example.test/category/one/',
					'https://example.test/category/two/',
					'https://example.test/author/100/',
				),
				Util::candidate_urls_for_regex()
			);

			self::assertSame( array( 2, 2 ), array_column( $this->provider->post_queries, 'posts_per_page' ) );
			self::assertSame( array( 0, 2 ), array_column( $this->provider->post_queries, 'offset' ) );
			self::assertSame( array( 2, 1 ), array_column( $this->provider->term_queries, 'number' ) );
			self::assertSame( array( 0, 2 ), array_column( $this->provider->term_queries, 'offset' ) );
			self::assertSame( array( 1 ), array_column( $this->provider->user_queries, 'number' ) );
			self::assertSame( array( 0 ), array_column( $this->provider->user_queries, 'offset' ) );
			self::assertSame( 'ID', $this->provider->post_queries[0]['orderby'] );
			self::assertSame( 'ASC', $this->provider->post_queries[0]['order'] );
			self::assertSame( 'term_id', $this->provider->term_queries[0]['orderby'] );
			self::assertSame( 'ASC', $this->provider->term_queries[0]['order'] );
			self::assertSame( 'ID', $this->provider->user_queries[0]['orderby'] );
			self::assertSame( 'ASC', $this->provider->user_queries[0]['order'] );
		}

		public function test_duplicate_or_invalid_rows_cannot_trigger_an_unbounded_post_scan(): void {
			$this->setLimits( 4, 2 );
			$this->provider->posts = range( 1, 100 );
			$this->provider->permalinks = array(
				1 => 'https://example.test/',
				2 => false,
				3 => 'https://example.test/post/3/',
			);
			$this->provider->terms['category'] = array(
				(object) array( 'term_id' => 10, 'url' => 'https://example.test/category/one/' ),
			);
			$this->provider->users = array( (object) array( 'ID' => 100 ) );

			self::assertSame(
				array(
					'https://example.test/',
					'https://example.test/post/3/',
					'https://example.test/category/one/',
					'https://example.test/author/100/',
				),
				Util::candidate_urls_for_regex()
			);
			self::assertSame( array( 2, 1 ), array_column( $this->provider->post_queries, 'posts_per_page' ) );
			self::assertSame( array( 0, 2 ), array_column( $this->provider->post_queries, 'offset' ) );
			self::assertSame( array( 2 ), array_column( $this->provider->term_queries, 'number' ) );
			self::assertSame( array( 1 ), array_column( $this->provider->user_queries, 'number' ) );
		}

		public function test_zero_limit_short_circuits_without_querying_providers(): void {
			$this->setLimits( 0, 2 );
			$this->provider->posts = range( 1, 10 );

			self::assertSame( array(), Util::candidate_urls_for_regex() );
			self::assertSame( array(), $this->provider->post_queries );
			self::assertSame( array(), $this->provider->term_queries );
			self::assertSame( array(), $this->provider->user_queries );
		}

		private function setLimits( int $limit, int $batch_size ): void {
			add_filter(
				'ss_regex_candidate_url_limit',
				static function () use ( $limit ) {
					return $limit;
				}
			);
			add_filter(
				'ss_regex_candidate_query_batch_size',
				static function () use ( $batch_size ) {
					return $batch_size;
				}
			);
		}
	}

	final class Regex_Candidate_Provider {
		/** @var int[] */
		public $posts = array();

		/** @var array<int,string|false> */
		public $permalinks = array();

		/** @var array<string,array<int,object>> */
		public $terms = array( 'category' => array() );

		/** @var object[] */
		public $users = array();

		/** @var array<int,array<string,mixed>> */
		public $post_queries = array();

		/** @var array<int,array<string,mixed>> */
		public $term_queries = array();

		/** @var array<int,array<string,mixed>> */
		public $user_queries = array();

		/** @return array<string,string> */
		public function getPostTypes(): array {
			return array( 'post' => 'post', 'attachment' => 'attachment' );
		}

		/** @param array<string,mixed> $args @return int[] */
		public function getPosts( array $args ): array {
			$this->post_queries[] = $args;

			return array_slice( $this->posts, (int) $args['offset'], (int) $args['posts_per_page'] );
		}

		/** @return string|false */
		public function getPermalink( int $post_id ) {
			if ( array_key_exists( $post_id, $this->permalinks ) ) {
				return $this->permalinks[ $post_id ];
			}

			return 'https://example.test/post/' . $post_id . '/';
		}

		/** @return string[] */
		public function getTaxonomies(): array {
			return array_keys( $this->terms );
		}

		/** @param array<string,mixed> $args @return object[] */
		public function getTerms( array $args ): array {
			$this->term_queries[] = $args;
			$terms = isset( $this->terms[ $args['taxonomy'] ] ) ? $this->terms[ $args['taxonomy'] ] : array();

			return array_slice( $terms, (int) $args['offset'], (int) $args['number'] );
		}

		/** @return string|false */
		public function getTermLink( $term ) {
			return is_object( $term ) && isset( $term->url ) ? $term->url : false;
		}

		/** @param array<string,mixed> $args @return object[] */
		public function getUsers( array $args ): array {
			$this->user_queries[] = $args;

			return array_slice( $this->users, (int) $args['offset'], (int) $args['number'] );
		}

		public function getAuthorUrl( int $user_id ): string {
			return 'https://example.test/author/' . $user_id . '/';
		}
	}
}
