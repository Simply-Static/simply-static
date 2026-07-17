<?php

declare(strict_types=1);

namespace {
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	if ( ! function_exists( 'is_plugin_active' ) ) {
		function is_plugin_active( $plugin ) {
			$active = WpEnv::$options['active_plugins'] ?? array();

			return is_array( $active ) && in_array( $plugin, $active, true );
		}
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		function is_plugin_active_for_network( $plugin ) {
			$active = WpEnv::$site_options['active_sitewide_plugins'] ?? array();

			return is_array( $active ) && array_key_exists( $plugin, $active );
		}
	}

	if ( ! function_exists( 'ssp_run_plugin' ) ) {
		function ssp_run_plugin() {}
	}

	if ( ! function_exists( 'ssp_register_crawler' ) ) {
		function ssp_register_crawler( $crawlers ) {
			return $crawlers;
		}
	}

	if ( ! function_exists( 'ssp_rest_api_init' ) ) {
		function ssp_rest_api_init() {}
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Pro_Compatibility;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	require_once dirname( __DIR__, 2 ) . '/src/class-ss-pro-compatibility.php';

	final class BootstrapCompatibilityTest extends UnitTestCase {

		private const PRO_BASENAME = 'simply-static-pro/simply-static-pro.php';

		public function test_release_compatibility_map_uses_the_new_symmetric_boundary(): void {
			self::assertSame( '2.0.1', Pro_Compatibility::required_pro_version( '3.7.9' ) );
			self::assertSame( '2.5.0', Pro_Compatibility::required_pro_version( '3.8.0' ) );
			self::assertSame( '2.5.0', Pro_Compatibility::required_pro_version( '3.8.1' ) );
			self::assertSame( '2.5.0', Pro_Compatibility::required_pro_version( '3.8.2' ) );
			self::assertSame( '2.5.0', Pro_Compatibility::required_pro_version( '3.8.3' ) );
		}

		public function test_outdated_active_pro_is_left_active_but_all_runtime_hooks_are_blocked(): void {
			WpEnv::$options['active_plugins'] = array( self::PRO_BASENAME );
			WpEnv::$options['simply-static']   = array( 'sentinel' => 'preserved' );
			$this->registerProRuntimeHooks();

			self::assertFalse( Pro_Compatibility::enforce( '3.8.0', '2.4.9' ) );

			self::assertFalse( $this->hasHook( 'plugins_loaded', 'ssp_run_plugin' ) );
			self::assertFalse( $this->hasHook( 'simply_static_crawlers', 'ssp_register_crawler' ) );
			self::assertFalse( $this->hasHook( 'rest_api_init', 'ssp_rest_api_init' ) );
			self::assertTrue( $this->hasHook( 'admin_notices', array( Pro_Compatibility::class, 'admin_notice' ) ) );
			self::assertTrue( $this->hasHook( 'network_admin_notices', array( Pro_Compatibility::class, 'network_admin_notice' ) ) );
			self::assertSame( array( self::PRO_BASENAME ), WpEnv::$options['active_plugins'] );
			self::assertSame( array( 'sentinel' => 'preserved' ), WpEnv::$options['simply-static'] );
		}

		public function test_compatible_pro_keeps_its_runtime_hooks(): void {
			WpEnv::$options['active_plugins'] = array( self::PRO_BASENAME );
			$this->registerProRuntimeHooks();

			self::assertTrue( Pro_Compatibility::enforce( '3.8.0', '2.5.0' ) );

			self::assertTrue( $this->hasHook( 'plugins_loaded', 'ssp_run_plugin' ) );
			self::assertTrue( $this->hasHook( 'simply_static_crawlers', 'ssp_register_crawler' ) );
			self::assertTrue( $this->hasHook( 'rest_api_init', 'ssp_rest_api_init' ) );
			self::assertFalse( $this->hasHook( 'admin_notices', array( Pro_Compatibility::class, 'admin_notice' ) ) );
		}

		public function test_network_active_outdated_pro_is_detected_without_deactivation(): void {
			WpEnv::$site_options['active_sitewide_plugins'] = array( self::PRO_BASENAME => time() );
			$this->registerProRuntimeHooks();

			self::assertFalse( Pro_Compatibility::enforce( '3.8.0', '2.4.9' ) );
			self::assertArrayHasKey( self::PRO_BASENAME, WpEnv::$site_options['active_sitewide_plugins'] );
			self::assertFalse( $this->hasHook( 'plugins_loaded', 'ssp_run_plugin' ) );
			self::assertTrue( $this->hasHook( 'network_admin_notices', array( Pro_Compatibility::class, 'network_admin_notice' ) ) );
		}

		public function test_release_metadata_and_bootstrap_hook_are_aligned(): void {
			$root      = dirname( __DIR__, 2 );
			$bootstrap = (string) file_get_contents( $root . '/simply-static.php' );
			$readme    = (string) file_get_contents( $root . '/readme.txt' );
			$package   = json_decode( (string) file_get_contents( $root . '/src/admin/package.json' ), true );
			$lock      = json_decode( (string) file_get_contents( $root . '/src/admin/package-lock.json' ), true );

			self::assertMatchesRegularExpression( '/^ \* Version:\s+3\.8\.3$/m', $bootstrap );
			self::assertMatchesRegularExpression( '/^ \* Requires at least:\s+6\.6$/m', $bootstrap );
			self::assertStringContainsString( "define( 'SIMPLY_STATIC_VERSION', '3.8.3' );", $bootstrap );
			self::assertStringContainsString( "version_compare( get_bloginfo( 'version' ), '6.6', '<' )", $bootstrap );
			self::assertStringContainsString( 'Simply Static requires WordPress 6.6 or higher.', $bootstrap );
			self::assertStringContainsString( "require_once SIMPLY_STATIC_PATH . 'src/class-ss-pro-compatibility.php';", $bootstrap );
			self::assertStringContainsString( "add_action( 'plugins_loaded', array( 'Simply_Static\\Pro_Compatibility', 'enforce' ), 1 );", $bootstrap );
			self::assertStringNotContainsString( 'deactivate_plugins( $pro_basename', $bootstrap );
			self::assertMatchesRegularExpression( '/^Stable tag:\s+3\.8\.3$/m', $readme );
			self::assertMatchesRegularExpression( '/^Requires at least:\s+6\.6$/m', $readme );
			self::assertStringContainsString( '= 3.8.3 =', $readme );
			self::assertSame( '3.8.3', $package['version'] ?? null );
			self::assertSame( '3.8.3', $lock['version'] ?? null );
			self::assertSame( '3.8.3', $lock['packages']['']['version'] ?? null );
		}

		private function registerProRuntimeHooks(): void {
			add_action( 'plugins_loaded', 'ssp_run_plugin' );
			add_filter( 'simply_static_crawlers', 'ssp_register_crawler' );
			add_action( 'rest_api_init', 'ssp_rest_api_init' );
		}

		/**
		 * @param callable|string|array{class-string,string} $callback
		 */
		private function hasHook( string $hook, $callback ): bool {
			foreach ( WpEnv::$filters[ $hook ] ?? array() as $registered_at_priority ) {
				foreach ( $registered_at_priority as $registered ) {
					if ( $registered['callback'] === $callback ) {
						return true;
					}
				}
			}

			return false;
		}
	}
}
