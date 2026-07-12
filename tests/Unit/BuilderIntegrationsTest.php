<?php

declare(strict_types=1);

namespace {
	if ( ! function_exists( 'wp_get_theme' ) ) {
		/** @return mixed */
		function wp_get_theme() {
			return isset( $GLOBALS['simply_static_test_theme'] ) ? $GLOBALS['simply_static_test_theme'] : null;
		}
	}

	if ( ! function_exists( 'get_template' ) ) {
		function get_template() {
			return isset( $GLOBALS['simply_static_test_template'] ) ? $GLOBALS['simply_static_test_template'] : '';
		}
	}

	$simply_static_root = dirname( __DIR__, 2 );
	require_once $simply_static_root . '/src/class-ss-plugin.php';
	require_once $simply_static_root . '/src/class-ss-options.php';
	require_once $simply_static_root . '/src/class-ss-util.php';
	require_once $simply_static_root . '/src/integrations/class-ss-integration.php';
	require_once $simply_static_root . '/src/crawler/class-ss-crawler.php';
	require_once $simply_static_root . '/src/integrations/class-ss-divi-integration.php';
	require_once $simply_static_root . '/src/crawler/class-ss-divi-crawler.php';
	require_once $simply_static_root . '/src/integrations/class-ss-elementor-integration.php';
	require_once $simply_static_root . '/src/crawler/class-ss-elementor-crawler.php';
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Crawler\Divi_Crawler;
	use Simply_Static\Crawler\Elementor_Crawler;
	use Simply_Static\Divi_Integration;
	use Simply_Static\Elementor_Integration;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class BuilderIntegrationsTest extends UnitTestCase {

		protected function setUp(): void {
			parent::setUp();
			$GLOBALS['simply_static_test_theme']    = null;
			$GLOBALS['simply_static_test_template'] = '';
		}

		protected function tearDown(): void {
			unset( $GLOBALS['simply_static_test_theme'], $GLOBALS['simply_static_test_template'], $GLOBALS['wpdb'] );
			parent::tearDown();
		}

		public function test_divi_detection_requires_an_exact_theme_identity(): void {
			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'individual', 'Individual' );

			self::assertFalse( ( new Divi_Integration() )->dependency_active() );
			self::assertFalse( ( new Divi_Crawler() )->dependency_active() );

			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'custom-theme', 'My Divi Toolkit' );

			self::assertFalse( ( new Divi_Integration() )->dependency_active() );
			self::assertFalse( ( new Divi_Crawler() )->dependency_active() );

			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'DIVI', 'Divi' );

			self::assertTrue( ( new Divi_Integration() )->dependency_active() );
			self::assertTrue( ( new Divi_Crawler() )->dependency_active() );
		}

		public function test_divi_detection_supports_an_exact_parent_theme(): void {
			$parent = new Builder_Test_Theme( 'Divi', 'Divi', 'Divi' );
			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'custom-child', 'Custom Child', 'custom-child', $parent );

			self::assertTrue( ( new Divi_Integration() )->dependency_active() );
			self::assertTrue( ( new Divi_Crawler() )->dependency_active() );
		}

		public function test_divi_options_are_only_changed_during_an_export_and_then_restored(): void {
			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'Divi', 'Divi' );
			$original_divi = array(
				'minify_combine_js'   => 'on',
				'enable_dynamic_css'  => true,
				'unrelated_preference' => 'keep',
			);
			$original_core = array(
				'defer_css' => 1,
				'other'     => 42,
			);
			WpEnv::$options['et_divi']         = $original_divi;
			WpEnv::$options['et_core_options'] = $original_core;

			$integration = new Divi_Integration();
			$integration->run();

			self::assertSame( $original_divi, WpEnv::$options['et_divi'] );
			self::assertSame( $original_core, WpEnv::$options['et_core_options'] );
			self::assertArrayNotHasKey( Divi_Integration::PERFORMANCE_OPTIONS_BACKUP, WpEnv::$options );

			do_action( 'ss_before_static_export', 1, 'export' );

			self::assertSame( 'off', WpEnv::$options['et_divi']['minify_combine_js'] );
			self::assertFalse( WpEnv::$options['et_divi']['enable_dynamic_css'] );
			self::assertSame( 'keep', WpEnv::$options['et_divi']['unrelated_preference'] );
			self::assertSame( 0, WpEnv::$options['et_core_options']['defer_css'] );
			self::assertSame( 42, WpEnv::$options['et_core_options']['other'] );
			self::assertSame(
				array(
					'et_divi' => array(
						'original' => array(
							'minify_combine_js'  => 'on',
							'enable_dynamic_css' => true,
						),
						'temporary' => array(
							'minify_combine_js'  => 'off',
							'enable_dynamic_css' => false,
						),
					),
					'et_core_options' => array(
						'original'  => array( 'defer_css' => 1 ),
						'temporary' => array( 'defer_css' => 0 ),
					),
				),
				WpEnv::$options[ Divi_Integration::PERFORMANCE_OPTIONS_BACKUP ]
			);

			WpEnv::$options['et_divi']['unrelated_preference'] = 'changed-during-export';

			do_action( 'ss_after_cleanup' );

			$expected_divi = $original_divi;
			$expected_divi['unrelated_preference'] = 'changed-during-export';
			self::assertSame( $expected_divi, WpEnv::$options['et_divi'] );
			self::assertSame( $original_core, WpEnv::$options['et_core_options'] );
			self::assertArrayNotHasKey( Divi_Integration::PERFORMANCE_OPTIONS_BACKUP, WpEnv::$options );
		}

		/** @dataProvider diviRecoveryHookProvider */
		public function test_divi_options_are_restored_on_aborted_lifecycles( string $hook ): void {
			$GLOBALS['simply_static_test_theme'] = new Builder_Test_Theme( 'Divi', 'Divi' );
			WpEnv::$options['et_divi'] = array( 'minify_combine_js' => 'on' );
			$integration = new Divi_Integration();
			$integration->run();

			do_action( 'ss_before_static_export' );
			self::assertSame( 'off', WpEnv::$options['et_divi']['minify_combine_js'] );
			do_action( $hook );

			self::assertSame( 'on', WpEnv::$options['et_divi']['minify_combine_js'] );
			self::assertArrayNotHasKey( Divi_Integration::PERFORMANCE_OPTIONS_BACKUP, WpEnv::$options );
		}

		/** @return array<string,array{string}> */
		public function diviRecoveryHookProvider(): array {
			return array(
				'start failure' => array( 'ss_archive_creation_job_start_failed' ),
				'queue reset'   => array( 'ss_after_background_queue_reset' ),
				'deactivation'  => array( 'simply_static_deactivated' ),
			);
		}

		public function test_elementor_json_extraction_ignores_invalid_and_incomplete_values(): void {
			$integration = new Testable_Elementor_Integration();
			$crawler     = new Testable_Elementor_Crawler();

			self::assertSame( array(), $integration->extract_lottie_urls( '{invalid json' ) );
			self::assertSame( array(), $crawler->extract_lottie_urls( '{invalid json' ) );
			self::assertSame(
				array(),
				$integration->extract_lottie_urls(
					(string) json_encode(
						array(
							array(
								'widgetType' => 'lottie',
								'settings'   => array( 'source_json' => array( 'source' => 'library' ) ),
							),
						)
					)
				)
			);
		}

		public function test_elementor_flattening_is_linear_and_does_not_embed_prior_results(): void {
			$depth = 1500;
			$tree  = array(
				'id'         => 'leaf',
				'widgetType' => 'lottie',
				'settings'   => array(
					'source_json' => array(
						'source' => 'library',
						'url'    => 'https://example.test/animation.json',
					),
				),
			);

			for ( $index = 0; $index < $depth; $index ++ ) {
				$tree = array(
					'id'       => 'container-' . $index,
					'elements' => array( $tree ),
				);
			}

			$integration_flat = ( new Testable_Elementor_Integration() )->flatten( $tree );
			$crawler_flat     = ( new Testable_Elementor_Crawler() )->flatten( $tree );

			self::assertCount( $depth + 1, $integration_flat );
			self::assertSame( $integration_flat, $crawler_flat );
			self::assertSame( 'leaf', $integration_flat[0]['id'] );

			foreach ( $integration_flat as $item ) {
				self::assertArrayNotHasKey( 'elements', $item );
				self::assertArrayNotHasKey( 0, $item );
			}
		}

		public function test_elementor_lottie_queries_are_batched_with_a_keyset(): void {
			add_filter(
				'simply_static_elementor_meta_batch_size',
				static function () {
					return 2;
				}
			);

			$rows = array(
				array( 'meta_id' => 2, 'meta_value' => '{not-json' ),
				array( 'meta_id' => 7, 'meta_value' => $this->lottieJson( 'https://example.test/first.json' ) ),
				array( 'meta_id' => 11, 'meta_value' => $this->lottieJson( 'https://example.test/second.json' ) ),
			);

			$integration_db  = new Builder_Test_Wpdb( $rows );
			$GLOBALS['wpdb'] = $integration_db;
			$integration_urls = ( new Testable_Elementor_Integration() )->lottie_file_urls();

			self::assertSame(
				array( 'https://example.test/first.json', 'https://example.test/second.json' ),
				$integration_urls
			);
			$this->assertKeysetQueries( $integration_db );

			$crawler_db       = new Builder_Test_Wpdb( $rows );
			$GLOBALS['wpdb']  = $crawler_db;
			$crawler_urls     = ( new Testable_Elementor_Crawler() )->lottie_file_urls();

			self::assertSame( $integration_urls, $crawler_urls );
			$this->assertKeysetQueries( $crawler_db );
		}

		private function lottieJson( string $url ): string {
			return (string) json_encode(
				array(
					array(
						'id'         => 'lottie-widget',
						'widgetType' => 'lottie',
						'settings'   => array(
							'source_json' => array(
								'source' => 'library',
								'url'    => $url,
							),
						),
					),
				)
			);
		}

		private function assertKeysetQueries( Builder_Test_Wpdb $database ): void {
			self::assertCount( 2, $database->queries );
			self::assertStringContainsString( 'meta_id > 0', $database->queries[0] );
			self::assertStringContainsString( 'meta_id > 7', $database->queries[1] );
			self::assertStringNotContainsString( 'OFFSET', implode( ' ', $database->queries ) );
		}
	}

	final class Builder_Test_Theme {
		/** @var string */
		private $template;

		/** @var string */
		private $name;

		/** @var string */
		private $stylesheet;

		/** @var null|self */
		private $parent_theme;

		public function __construct( string $template, string $name, string $stylesheet = '', ?self $parent_theme = null ) {
			$this->template    = $template;
			$this->name        = $name;
			$this->stylesheet  = $stylesheet ?: $template;
			$this->parent_theme = $parent_theme;
		}

		public function get_template(): string {
			return $this->template;
		}

		public function get( string $field ): string {
			return 'Name' === $field ? $this->name : '';
		}

		public function get_stylesheet(): string {
			return $this->stylesheet;
		}

		public function parent(): ?self {
			return $this->parent_theme;
		}
	}

	final class Builder_Test_Wpdb {
		/** @var string */
		public $postmeta = 'wp_postmeta';

		/** @var array<int,array<string,mixed>> */
		private $rows;

		/** @var array<int,string> */
		public $queries = array();

		/** @param array<int,array<string,mixed>> $rows */
		public function __construct( array $rows ) {
			$this->rows = $rows;
		}

		/** @param mixed ...$arguments */
		public function prepare( string $query, ...$arguments ): string {
			return vsprintf( $query, $arguments );
		}

		/** @return array<int,array<string,mixed>> */
		public function get_results( string $query, string $output ): array {
			$this->queries[] = $query;
			preg_match( '/meta_id > (\d+).*LIMIT (\d+)/', $query, $matches );
			$last_meta_id = isset( $matches[1] ) ? (int) $matches[1] : 0;
			$limit        = isset( $matches[2] ) ? (int) $matches[2] : 100;
			$rows         = array_values(
				array_filter(
					$this->rows,
					static function ( array $row ) use ( $last_meta_id ): bool {
						return (int) $row['meta_id'] > $last_meta_id;
					}
				)
			);
			usort(
				$rows,
				static function ( array $left, array $right ): int {
					return (int) $left['meta_id'] <=> (int) $right['meta_id'];
				}
			);

			return array_slice( $rows, 0, $limit );
		}

		public function flush(): void {
		}
	}

	final class Testable_Elementor_Integration extends Elementor_Integration {
		/** @return array<int,array<string,mixed>> */
		public function flatten( array $data ): array {
			return $this->flatten_data( $data );
		}

		/** @return array<int,string> */
		public function extract_lottie_urls( string $json ): array {
			return $this->extract_lottie_urls_from_json( $json );
		}

		/** @return array<int,string> */
		public function lottie_file_urls(): array {
			return $this->get_lottie_file_urls();
		}
	}

	final class Testable_Elementor_Crawler extends Elementor_Crawler {
		/** @return array<int,array<string,mixed>> */
		public function flatten( array $data ): array {
			$method = new \ReflectionMethod( Elementor_Crawler::class, 'flatten_data' );
			$method->setAccessible( true );

			return $method->invoke( $this, $data );
		}

		/** @return array<int,string> */
		public function extract_lottie_urls( string $json ): array {
			$method = new \ReflectionMethod( Elementor_Crawler::class, 'extract_lottie_urls_from_json' );
			$method->setAccessible( true );

			return $method->invoke( $this, $json );
		}

		/** @return array<int,string> */
		public function lottie_file_urls(): array {
			$method = new \ReflectionMethod( Elementor_Crawler::class, 'detect_lottie_files' );
			$method->setAccessible( true );

			return $method->invoke( $this );
		}
	}
}
