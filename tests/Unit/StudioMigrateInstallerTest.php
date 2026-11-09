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

	if ( ! function_exists( 'activate_plugin' ) ) {
		function activate_plugin( $plugin ) {
			WpEnv::$options['activated_plugin'] = $plugin;
			$result = WpEnv::$options['activate_plugin_result'] ?? null;
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			WpEnv::$options['active_plugins'][] = $plugin;

			return null;
		}
	}

	if ( ! function_exists( 'plugins_api' ) ) {
		function plugins_api( $action, $args = array() ) {
			WpEnv::$options['plugins_api_call'] = array(
				'action' => $action,
				'args'   => $args,
			);

			return WpEnv::$options['plugins_api_result'] ?? (object) array(
				'download_link' => 'https://downloads.wordpress.org/plugin/simply-static-studio-backup-migrate.latest-stable.zip',
			);
		}
	}

	if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
		class WP_Ajax_Upgrader_Skin {}
	}

	if ( ! class_exists( 'Plugin_Upgrader' ) ) {
		class Plugin_Upgrader {
			/** @param mixed $skin */
			public function __construct( $skin = null ) {}

			/** @return bool|WP_Error */
			public function install( $package ) {
				WpEnv::$options['installed_package'] = $package;

				return WpEnv::$options['plugin_install_result'] ?? true;
			}
		}
	}
}

namespace Simply_Static\Tests\Unit {
	use Simply_Static\Admin_Rest;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class StudioMigrateInstallerTest extends UnitTestCase {
		private const PLUGIN_FILE = 'simply-static-studio-backup-migrate/simply-static-studio-backup-migrate.php';

		/** @var Admin_Rest */
		private $rest;

		protected function setUp(): void {
			parent::setUp();

			foreach ( array( 'file.php', 'plugin-install.php', 'class-wp-upgrader.php', 'plugin.php' ) as $include ) {
				$path = ABSPATH . 'wp-admin/includes/' . $include;
				wp_mkdir_p( dirname( $path ) );
				if ( ! file_exists( $path ) ) {
					file_put_contents( $path, "<?php\n" );
				}
			}

			$plugin_file = WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE;
			if ( file_exists( $plugin_file ) ) {
				unlink( $plugin_file );
			}

			$this->requireSource( 'src/admin/inc/class-ss-admin-rest.php' );
			$this->rest = new Admin_Rest();
		}

		public function test_installs_wordpress_org_package_and_activates_plugin(): void {
			$download_link = 'https://downloads.wordpress.org/plugin/simply-static-studio-backup-migrate.1.0.0.zip';
			WpEnv::$options['plugins_api_result'] = (object) array(
				'download_link' => $download_link,
			);

			$response = $this->rest->install_studio_migrate();

			self::assertSame( 200, $response->get_status() );
			self::assertSame(
				array(
					'success'  => true,
					'redirect' => 'https://example.test/wp-admin/tools.php?page=studio-backup',
				),
				$response->get_data()
			);
			self::assertSame( 'plugin_information', WpEnv::$options['plugins_api_call']['action'] );
			self::assertSame( 'simply-static-studio-backup-migrate', WpEnv::$options['plugins_api_call']['args']['slug'] );
			self::assertFalse( WpEnv::$options['plugins_api_call']['args']['fields']['sections'] );
			self::assertSame( $download_link, WpEnv::$options['installed_package'] );
			self::assertSame( self::PLUGIN_FILE, WpEnv::$options['activated_plugin'] );
		}

		public function test_returns_wordpress_org_api_error_without_installing(): void {
			WpEnv::$options['plugins_api_result'] = new \WP_Error( 'plugins_api_failed', 'Directory unavailable.' );

			$response = $this->rest->install_studio_migrate();

			self::assertSame( 502, $response->get_status() );
			self::assertSame(
				array(
					'success' => false,
					'message' => 'Directory unavailable.',
				),
				$response->get_data()
			);
			self::assertArrayNotHasKey( 'installed_package', WpEnv::$options );
			self::assertArrayNotHasKey( 'activated_plugin', WpEnv::$options );
		}

		public function test_requires_wordpress_org_to_return_a_package_url(): void {
			WpEnv::$options['plugins_api_result'] = (object) array();

			$response = $this->rest->install_studio_migrate();

			self::assertSame( 502, $response->get_status() );
			self::assertSame(
				array(
					'success' => false,
					'message' => 'WordPress.org did not provide a migration plugin package.',
				),
				$response->get_data()
			);
			self::assertArrayNotHasKey( 'installed_package', WpEnv::$options );
			self::assertArrayNotHasKey( 'activated_plugin', WpEnv::$options );
		}
	}
}
