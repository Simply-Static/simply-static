<?php

declare(strict_types=1);

namespace Simply_Static\Crawler {
	function get_terms( $args = array() ) {
		$fixture = $GLOBALS['simply_static_taxonomy_fixture'] ?? null;
		if ( ! $fixture ) {
			return array();
		}
		$fixture->queries[] = array( 'language' => $fixture->language, 'args' => $args );
		$terms = $fixture->terms[ $fixture->language ][ $args['taxonomy'] ] ?? array();
		$terms = array_slice( $terms, (int) ( $args['offset'] ?? 0 ), (int) ( $args['number'] ?? count( $terms ) ) );

		return 'ids' === ( $args['fields'] ?? '' ) ? array_column( $terms, 'term_id' ) : $terms;
	}

	function get_term_link( $term, $taxonomy = '' ) {
		$fixture = $GLOBALS['simply_static_taxonomy_fixture'] ?? null;
		$id = is_object( $term ) ? $term->term_id : (int) $term;
		foreach ( $fixture->terms[ $fixture->language ][ $taxonomy ] ?? array() as $candidate ) {
			if ( $candidate->term_id === $id ) {
				return $candidate->url;
			}
		}

		return new \WP_Error( 'invalid_term', 'Invalid term' );
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Crawler\Taxonomy_Crawler;
	use Simply_Static\Options;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class TaxonomyLanguageFixture {
		/** @var string */
		public $language = 'en';
		/** @var array<string,array<string,object[]>> */
		public $terms = array();
		/** @var array */
		public $queries = array();
		/** @var array */
		public $switches = array();
		/** @var array */
		public $permalink_calls = array();
	}

	final class TaxonomyCrawlerLanguagesWpdb {
		public $insert_id = 1;
		public $urls = array();

		public function get_blog_prefix() : string {
			return 'wp_';
		}

		public function get_row( $query, $output = null ) {
			return null;
		}

		public function insert( $table, $data ) : int {
			$this->urls[] = $data['url'];
			$this->insert_id++;

			return 1;
		}
	}

	final class TaxonomyCrawlerLanguagesTest extends UnitTestCase {
		/** @var TaxonomyLanguageFixture */
		private $fixture;
		/** @var TaxonomyCrawlerLanguagesWpdb */
		private $database;
		private $previous_wpdb;

		protected function setUp() : void {
			parent::setUp();
			foreach ( array(
				'src/class-ss-plugin.php', 'src/class-ss-options.php', 'src/class-ss-util.php',
				'src/class-ss-query.php', 'src/models/class-ss-model.php', 'src/models/class-ss-page.php',
				'src/crawler/class-ss-crawler.php', 'src/crawler/class-ss-taxonomy-crawler.php',
			) as $source ) {
				$this->requireSource( $source );
			}
			$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
			$this->database = new TaxonomyCrawlerLanguagesWpdb();
			$GLOBALS['wpdb'] = $this->database;
			$this->fixture = new TaxonomyLanguageFixture();
			$GLOBALS['simply_static_taxonomy_fixture'] = $this->fixture;
			WpEnv::$taxonomies = array( 'plugin_tag' => 'plugin_tag' );
			Options::instance()->set( 'archive_start_time', '2026-11-04 09:00:00' )->save();
		}

		protected function tearDown() : void {
			unset( $GLOBALS['simply_static_taxonomy_fixture'] );
			$GLOBALS['wpdb'] = $this->previous_wpdb;
			parent::tearDown();
		}

		private function multilingual() : void {
			add_filter( 'wpml_active_languages', static function () {
				return array( 'en' => array( 'language_code' => 'en' ), 'de' => array( 'language_code' => 'de' ) );
			} );
			add_filter( 'wpml_current_language', function () {
				return $this->fixture->language;
			} );
			add_action( 'wpml_switch_language', function ( $language ) {
				$this->fixture->switches[] = $language;
				$this->fixture->language = $language;
			} );
			add_filter( 'wpml_permalink', function ( $url, $language, $full_resolution ) {
				$this->fixture->permalink_calls[] = array( $url, $language, $full_resolution );
				return $url;
			}, 10, 3 );
		}

		private function term( int $id, string $path ) : object {
			return (object) array( 'term_id' => $id, 'url' => 'https://example.test/' . $path . '/' );
		}

		public function test_queries_actual_terms_in_each_language_without_synthetic_aliases() : void {
			$this->multilingual();
			$this->fixture->terms = array(
				'en' => array( 'plugin_tag' => array(
					$this->term( 1, 'tutorials/plugin-tag/security' ),
					$this->term( 2, 'tutorials/plugin-tag/english-only' ),
				) ),
				'de' => array( 'plugin_tag' => array(
					$this->term( 10, 'de/tutorials/plugin-tag/sicherheit' ),
					$this->term( 20, 'de/tutorials/plugin-tag/german-only' ),
				) ),
			);

			$crawler = new Taxonomy_Crawler();
			self::assertSame( 4, $crawler->add_urls_to_queue() );
			self::assertTrue( $crawler->is_complete() );
			self::assertSame( array(
				'https://example.test/tutorials/plugin-tag/security/',
				'https://example.test/tutorials/plugin-tag/english-only/',
				'https://example.test/de/tutorials/plugin-tag/sicherheit/',
				'https://example.test/de/tutorials/plugin-tag/german-only/',
			), $this->database->urls );
			self::assertSame( array( 'en', 'de' ), array_column( $this->fixture->queries, 'language' ) );
			self::assertSame( 'en', $this->fixture->language );
			self::assertSame( 4, count( $this->fixture->permalink_calls ) );
			self::assertSame( array_fill( 0, 4, true ), array_column( $this->fixture->permalink_calls, 2 ) );
		}

		public function test_resumes_each_language_with_its_own_offset_and_restores_context() : void {
			$this->multilingual();
			$this->fixture->language = 'fr';
			$this->fixture->terms = array(
				'en' => array( 'plugin_tag' => array( $this->term( 1, 'one' ), $this->term( 2, 'two' ), $this->term( 3, 'three' ) ) ),
				'de' => array( 'plugin_tag' => array( $this->term( 10, 'de/eins' ), $this->term( 20, 'de/zwei' ), $this->term( 30, 'de/drei' ) ) ),
			);
			add_filter( 'simply_static_taxonomy_crawler_batch_size', static function () { return 2; } );

			$total = 0;
			for ( $i = 0; $i < 10; $i++ ) {
				$crawler = new Taxonomy_Crawler();
				$total += $crawler->add_urls_to_queue();
				self::assertSame( 'fr', $this->fixture->language );
				if ( $crawler->is_complete() ) {
					break;
				}
			}
			self::assertTrue( $crawler->is_complete() );
			self::assertSame( 6, $total );
			self::assertCount( 6, array_unique( $this->database->urls ) );
			self::assertSame( array( 'en', 'en', 'de', 'de' ), array_column( $this->fixture->queries, 'language' ) );
			self::assertSame( array( 0, 2, 0, 2 ), array_column( array_column( $this->fixture->queries, 'args' ), 'offset' ) );
			self::assertNull( Options::instance()->get( 'taxonomy_crawler_state' ) );
		}

		public function test_detection_keeps_real_translation_with_a_different_slug() : void {
			$this->multilingual();
			$this->fixture->terms = array(
				'en' => array( 'plugin_tag' => array( $this->term( 1, 'category/news' ) ) ),
				'de' => array( 'plugin_tag' => array( $this->term( 10, 'de/kategorie/nachrichten' ) ) ),
			);

			self::assertSame( array( 'https://example.test/category/news/', 'https://example.test/de/kategorie/nachrichten/' ), ( new Taxonomy_Crawler() )->detect() );
			self::assertSame( 'en', $this->fixture->language );
		}

		public function test_without_wpml_keeps_the_existing_single_language_query() : void {
			$this->fixture->terms = array( 'en' => array( 'plugin_tag' => array( $this->term( 1, 'one' ) ) ) );

			self::assertSame( 1, ( new Taxonomy_Crawler() )->add_urls_to_queue() );
			self::assertSame( array(), $this->fixture->switches );
			self::assertSame( array(), $this->fixture->permalink_calls );
		}

		public function test_nontranslatable_taxonomy_is_queried_once_without_language_prefixes() : void {
			$this->multilingual();
			add_filter( 'wpml_is_translated_taxonomy', static function () { return false; } );
			add_filter( 'wpml_default_language', static function () { return 'en'; } );
			$this->fixture->terms = array(
				'en' => array( 'plugin_tag' => array( $this->term( 1, 'faq-topic/benefits' ) ) ),
				'de' => array( 'plugin_tag' => array( $this->term( 1, 'faq-topic/benefits' ) ) ),
			);

			self::assertSame( 1, ( new Taxonomy_Crawler() )->add_urls_to_queue() );
			self::assertSame( array( 'https://example.test/faq-topic/benefits/' ), $this->database->urls );
			self::assertSame( array( 'en' ), array_column( $this->fixture->queries, 'language' ) );
			self::assertSame( array(), $this->fixture->permalink_calls );
			self::assertSame( 'en', $this->fixture->language );
		}

		public function test_exception_during_permalink_resolution_restores_the_original_language() : void {
			$this->multilingual();
			$this->fixture->language = 'fr';
			$this->fixture->terms = array( 'en' => array( 'plugin_tag' => array( $this->term( 1, 'one' ) ) ) );
			add_filter( 'wpml_permalink', static function () {
				throw new \RuntimeException( 'Resolver unavailable' );
			} );

			try {
				( new Taxonomy_Crawler() )->add_urls_to_queue();
				self::fail( 'Expected the resolver to throw.' );
			} catch ( \RuntimeException $error ) {
				self::assertSame( 'Resolver unavailable', $error->getMessage() );
			}
			self::assertSame( 'fr', $this->fixture->language );
		}
	}
}
