<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use ReflectionProperty;
use Simply_Static\Admin_Rest;
use Simply_Static\Admin_Settings;
use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Upgrade_Handler;

final class AdminControllerWpdb {

	/** @var array<int,array<string,mixed>> */
	public $page_rows = array();

	/** @var array<int,array{status:string,count:int}> */
	public $status_rows = array();

	/** @var int */
	public $count_value = 0;

	/** @var string[] */
	public $queries = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4';
	}

	/** @return array<int,array<string,mixed>> */
	public function get_results( string $query, $output = null ): array {
		$this->queries[] = $query;
		if ( false !== strpos( $query, 'LEFT(http_status_code, 1)' ) ) {
			return $this->status_rows;
		}

		return $this->page_rows;
	}

	/** @return null */
	public function get_row( string $query, $output = null ) {
		$this->queries[] = $query;
		return null;
	}

	public function get_var( string $query ): int {
		$this->queries[] = $query;
		return $this->count_value;
	}

	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	public function prepare( string $query, ...$arguments ): string {
		foreach ( $arguments as $argument ) {
			$position = strpos( $query, '%s' );
			if ( false === $position ) {
				break;
			}
			$replacement = "'" . str_replace( "'", "''", (string) $argument ) . "'";
			$query = substr_replace( $query, $replacement, $position, 2 );
		}

		return $query;
	}
}

final class AdminControllerCoverageTest extends UnitTestCase {

	/** @var Admin_Rest */
	private $rest;

	/** @var AdminControllerWpdb */
	private $wpdb;

	/** @var mixed */
	private $previous_wpdb;

	/** @var mixed */
	private $previous_plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-upgrade-handler.php' );
		$this->requireSource( 'src/admin/inc/class-ss-admin-meta.php' );
		$this->requireSource( 'src/admin/inc/class-ss-admin-settings.php' );
		$this->requireSource( 'src/admin/inc/class-ss-admin-rest.php' );

		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		wp_mkdir_p( dirname( $upgrade_file ) );
		if ( ! file_exists( $upgrade_file ) ) {
			file_put_contents( $upgrade_file, "<?php\n" );
		}

		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->wpdb = new AdminControllerWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;

		$instance_property = $this->pluginInstanceProperty();
		$this->previous_plugin = $instance_property->getValue();

		Options::reinstance();
		$this->rest = new Admin_Rest();
	}

	protected function tearDown(): void {
		$this->pluginInstanceProperty()->setValue( null, $this->previous_plugin );
		if ( null === $this->previous_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->previous_wpdb;
		}

		parent::tearDown();
	}

	public function test_rest_routes_register_expected_callbacks_and_capability_contexts(): void {
		$this->rest->rest_api_init();
		self::assertCount( 37, WpEnv::$routes );

		$expected_callbacks = array(
			array( '/settings', 'GET', 'get_settings' ),
			array( '/settings', 'POST', 'save_settings' ),
			array( '/settings/reset', 'POST', 'reset_settings' ),
			array( '/reset-diagnostics', 'POST', 'reset_diagnostics' ),
			array( '/system-status', 'GET', 'get_system_status' ),
			array( '/activity-log', 'GET', 'get_activity_log' ),
			array( '/export-log', 'GET', 'get_export_log' ),
			array( '/start-export', 'POST', 'start_export' ),
			array( '/cancel-export', 'POST', 'cancel_export' ),
			array( '/pause-export', 'POST', 'pause_export' ),
			array( '/resume-export', 'POST', 'resume_export' ),
			array( '/is-running', 'GET', 'is_running' ),
		);

		foreach ( $expected_callbacks as $expected ) {
			$route = $this->registeredRoute( $expected[0], $expected[1] );
			self::assertSame( 'simplystatic/v1', $route['namespace'] );
			self::assertSame( $this->rest, $route['args']['callback'][0] );
			self::assertSame( $expected[2], $route['args']['callback'][1] );
		}

		self::assertFalse( $this->hasRegisteredRoute( '/sites', 'GET' ) );

		add_filter(
			'ss_user_capability',
			static function ( string $default, string $context ): string {
				return 'test_' . $context;
			},
			10,
			2
		);

		self::assertFalse( $this->routePermission( '/settings', 'GET' ) );
		WpEnv::$capabilities['test_settings'] = true;
		self::assertTrue( $this->routePermission( '/settings', 'GET' ) );
		self::assertTrue( $this->routePermission( '/settings', 'POST' ) );

		self::assertFalse( $this->routePermission( '/system-status', 'GET' ) );
		self::assertFalse( $this->routePermission( '/reset-diagnostics', 'POST' ) );
		WpEnv::$capabilities['test_diagnostics'] = true;
		self::assertTrue( $this->routePermission( '/system-status', 'GET' ) );
		self::assertTrue( $this->routePermission( '/reset-diagnostics', 'POST' ) );

		self::assertFalse( $this->routePermission( '/activity-log', 'GET' ) );
		WpEnv::$capabilities['test_activity-log'] = true;
		self::assertTrue( $this->routePermission( '/activity-log', 'GET' ) );

		self::assertFalse( $this->routePermission( '/start-export', 'POST' ) );
		WpEnv::$capabilities['test_generate'] = true;
		self::assertTrue( $this->routePermission( '/start-export', 'POST' ) );
		self::assertTrue( $this->routePermission( '/export-404', 'POST' ) );

		self::assertFalse( $this->routePermission( '/update-from-network', 'POST' ) );
		WpEnv::$capabilities['manage_network_options'] = true;
		self::assertTrue( $this->routePermission( '/update-from-network', 'POST' ) );

		self::assertFalse( $this->routePermission( '/install-studio-migrate', 'POST' ) );
		WpEnv::$capabilities['install_plugins'] = true;
		self::assertFalse( $this->routePermission( '/install-studio-migrate', 'POST' ) );
		WpEnv::$capabilities['activate_plugins'] = true;
		self::assertTrue( $this->routePermission( '/install-studio-migrate', 'POST' ) );
	}

	public function test_multisite_routes_require_network_capabilities(): void {
		WpEnv::$multisite = true;
		$this->rest->rest_api_init();
		self::assertCount( 41, WpEnv::$routes );

		self::assertTrue( $this->hasRegisteredRoute( '/sites', 'GET' ) );
		self::assertTrue( $this->hasRegisteredRoute( '/trigger-cron', 'POST' ) );
		self::assertTrue( $this->hasRegisteredRoute( '/reset-export-lock', 'POST' ) );

		WpEnv::$capabilities['manage_options'] = true;
		self::assertFalse( $this->routePermission( '/sites', 'GET' ) );
		self::assertFalse( $this->routePermission( '/reset-export-lock', 'POST' ) );
		self::assertFalse( $this->routePermission( '/trigger-cron', 'POST' ) );

		WpEnv::$capabilities['manage_network_options'] = true;
		self::assertTrue( $this->routePermission( '/sites', 'GET' ) );
		self::assertTrue( $this->routePermission( '/reset-export-lock', 'POST' ) );
		self::assertFalse( $this->routePermission( '/trigger-cron', 'POST' ) );

		WpEnv::$capabilities['manage_network'] = true;
		self::assertTrue( $this->routePermission( '/trigger-cron', 'POST' ) );
	}

	public function test_legacy_admin_settings_route_registration_delegates_once_to_canonical_controller(): void {
		$instance_property = new ReflectionProperty( Admin_Rest::class, 'instance' );
		$instance_property->setAccessible( true );
		$previous_instance = $instance_property->getValue();
		$instance_property->setValue( null, null );
		WpEnv::$routes = array();

		try {
			$settings = Admin_Settings::get_instance();
			$settings->rest_api_init();
			self::assertCount( 37, WpEnv::$routes );

			$settings->rest_api_init();
			self::assertCount( 37, WpEnv::$routes );
		} finally {
			$instance_property->setValue( null, $previous_instance );
		}

		foreach ( WpEnv::$filters['rest_api_init'][10] ?? array() as $registered ) {
			self::assertIsArray( $registered['callback'] );
			self::assertInstanceOf( Admin_Rest::class, $registered['callback'][0] );
		}
	}

	public function test_reset_settings_persists_and_returns_the_canonical_filtered_defaults(): void {
		$plugin = new class() extends Plugin {
			public function get_archive_creation_job() {
				return new class() {
					public function is_active(): bool {
						return false;
					}
				};
			}
		};
		$this->pluginInstanceProperty()->setValue( null, $plugin );
		WpEnv::$options['simply-static'] = array(
			'delivery_method' => 'sftp',
			'legacy_only'     => 'remove me',
		);
		Options::reinstance();
		add_filter(
			'ss_default_options',
			static function ( array $defaults ): array {
				$defaults['extension_setting'] = 'extension-default';
				return $defaults;
			}
		);
		$expected = Upgrade_Handler::get_default_options();

		$response = json_decode(
			(string) $this->rest->reset_settings( new \WP_REST_Request() ),
			true
		);

		self::assertSame( 200, $response['status'] ?? null );
		self::assertSame( 'Ok', $response['message'] ?? null );
		self::assertSame( $expected, $response['data'] ?? null );
		self::assertSame( $expected, WpEnv::$options['simply-static'] );
		self::assertSame( 'extension-default', Options::instance()->get( 'extension_setting' ) );
		self::assertNull( Options::instance()->get( 'legacy_only' ) );
		self::assertArrayHasKey( 'ss_webhook_enabled_types', $response['data'] );
		self::assertArrayHasKey( 'critical_css_custom', $response['data'] );
		self::assertArrayHasKey( 'deploy_manifest_schema_version', $response['data'] );
	}

	public function test_diagnostics_endpoints_return_cached_results_pass_state_and_reset_transients(): void {
		$passing_checks = array(
			'filesystem' => array(
				array( 'name' => 'Permalinks', 'test' => true ),
				array( 'name' => 'Temporary directory', 'test' => true ),
			),
		);
		WpEnv::$transients['simply_static_checks'] = $passing_checks;
		WpEnv::$transients['simply_static_failed_tests'] = 2;

		self::assertSame( $passing_checks, $this->rest->get_system_status() );
		self::assertSame(
			array( 'status' => 200, 'passed' => 'yes' ),
			json_decode( (string) $this->rest->check_system_status_passed(), true )
		);

		WpEnv::$transients['simply_static_checks']['filesystem'][] = array(
			'name' => 'WordPress cron',
			'test' => false,
		);
		self::assertSame(
			array( 'status' => 200, 'passed' => 'no' ),
			json_decode( (string) $this->rest->check_system_status_passed(), true )
		);

		self::assertSame(
			array( 'status' => 200 ),
			json_decode( (string) $this->rest->reset_diagnostics(), true )
		);
		self::assertArrayNotHasKey( 'simply_static_checks', WpEnv::$transients );
		self::assertArrayNotHasKey( 'simply_static_failed_tests', WpEnv::$transients );
	}

	public function test_post_type_provider_excludes_internal_types_and_formats_public_types(): void {
		WpEnv::$post_types = array(
			'post'              => (object) array( 'name' => 'post', 'label' => 'Posts' ),
			'page'              => (object) array( 'name' => 'page', 'label' => 'Pages' ),
			'product'           => (object) array( 'name' => 'product', 'label' => 'Products' ),
			'attachment'        => (object) array( 'name' => 'attachment', 'label' => 'Media' ),
			'elementor_library' => (object) array( 'name' => 'elementor_library', 'label' => 'Templates' ),
			'ssp-form'          => (object) array( 'name' => 'ssp-form', 'label' => 'Forms' ),
		);

		self::assertSame(
			array(
				'status' => 200,
				'data'   => array(
					array( 'name' => 'post', 'label' => 'Posts' ),
					array( 'name' => 'page', 'label' => 'Pages' ),
					array( 'name' => 'product', 'label' => 'Products' ),
				),
			),
			json_decode( (string) $this->rest->get_post_types(), true )
		);

		WpEnv::$post_types = array();
		self::assertSame(
			array( 'status' => 200, 'data' => array() ),
			json_decode( (string) $this->rest->get_post_types(), true )
		);
	}

	public function test_taxonomy_provider_uses_labels_and_slug_fallbacks(): void {
		WpEnv::$taxonomies = array(
			'category' => (object) array( 'labels' => (object) array( 'name' => 'Categories' ) ),
			'topic'    => (object) array(),
		);

		self::assertSame(
			array(
				array( 'label' => 'Categories', 'value' => 'category' ),
				array( 'label' => 'topic', 'value' => 'topic' ),
			),
			$this->rest->get_taxonomies()
		);

		WpEnv::$taxonomies = false;
		self::assertSame( array(), $this->rest->get_taxonomies() );
	}

	public function test_activity_log_sanitizes_messages_dates_and_invalid_entries(): void {
		WpEnv::$options['simply-static'] = array(
			'archive_status_messages' => array(
				'fetch'   => array(
					'message'  => '<strong>Ready</strong><script>alert(1)</script><a href="javascript:alert(2)" onclick="bad()">link</a>',
					'datetime' => "<b>2026-07-12 10:00</b>\n forged",
				),
				'invalid' => 'not an entry',
			),
		);
		$options = Options::reinstance();
		$plugin = ( new ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();
		$this->setPluginProperty( $plugin, 'options', $options );
		$this->setPluginProperty( $plugin, 'archive_creation_job', new \stdClass() );

		$log = $plugin->get_activity_log( 12 );

		self::assertSame(
			array(
				'fetch' => array(
					'message'  => '<strong>Ready</strong><a href="">link</a>',
					'datetime' => '2026-07-12 10:00 forged',
				),
			),
			$log
		);
		self::assertContains( 'ss_before_render_activity_log', WpEnv::$action_log );
		self::assertContains( 'ss_after_render_activity_log', WpEnv::$action_log );
	}

	public function test_log_endpoints_normalize_request_values_and_response_shapes(): void {
		$job = new class() {
			public function is_running(): bool {
				return true;
			}
		};
		$plugin = new class( $job ) extends Plugin {
			/** @var mixed */
			private $test_job;

			/** @var int[] */
			public $activity_blog_ids = array();

			/** @var array<int,array{int,int,int,string}> */
			public $export_calls = array();

			/** @param mixed $job */
			public function __construct( $job ) {
				$this->test_job = $job;
			}

			public function get_activity_log( $blog_id = 0 ) {
				$this->activity_blog_ids[] = $blog_id;
				return array( 'fetch' => array( 'message' => 'Ready', 'datetime' => 'Now' ) );
			}

			public function get_export_log( $per_page, $current_page = 1, $blog_id = 0, $search = '' ) {
				$this->export_calls[] = array( $per_page, $current_page, $blog_id, $search );
				return array( 'static_pages' => array(), 'total_static_pages' => 0 );
			}

			public function get_archive_creation_job() {
				return $this->test_job;
			}
		};
		$this->pluginInstanceProperty()->setValue( null, $plugin );

		$activity = json_decode(
			(string) $this->rest->get_activity_log( new \WP_REST_Request( array( 'blog_id' => '-9' ) ) ),
			true
		);
		self::assertSame( array( 9 ), $plugin->activity_blog_ids );
		self::assertSame( 200, $activity['status'] ?? null );
		self::assertTrue( $activity['running'] ?? false );
		self::assertSame( 'Ready', $activity['data']['fetch']['message'] ?? null );

		$export = json_decode(
			(string) $this->rest->get_export_log(
				new \WP_REST_Request(
					array(
						'blog_id'  => '-3',
						'per_page' => 9999,
						'page'     => 0,
						'search'   => "<b> needle </b>\nvalue",
					)
				)
			),
			true
		);
		self::assertSame( array( 200, 1, 3, 'needle value' ), $plugin->export_calls[0] );
		self::assertSame( 200, $export['status'] ?? null );
		self::assertSame( array(), $export['data']['static_pages'] ?? null );

		$this->rest->get_export_log( new \WP_REST_Request() );
		self::assertSame( array( 25, 1, 0, '' ), $plugin->export_calls[1] );
	}

	/**
	 * @dataProvider throwingLogEndpointProvider
	 */
	public function test_multisite_log_context_is_restored_when_plugin_callback_throws( string $endpoint ): void {
		WpEnv::$multisite = true;
		WpEnv::$current_blog_id = 1;
		$job = new class() {
			/** @var int */
			public $set_options_calls = 0;

			public function set_options( Options $options ): void {
				++$this->set_options_calls;
			}
		};
		$plugin = new class( $job ) extends Plugin {
			/** @var mixed */
			private $test_job;

			/** @param mixed $job */
			public function __construct( $job ) {
				$this->test_job = $job;
			}

			public function get_activity_log( $blog_id = 0 ) {
				throw new \RuntimeException( 'Activity log failed.' );
			}

			public function get_export_log( $per_page, $current_page = 1, $blog_id = 0, $search = '' ) {
				throw new \RuntimeException( 'Export log failed.' );
			}

			public function get_archive_creation_job() {
				return $this->test_job;
			}
		};
		$this->pluginInstanceProperty()->setValue( null, $plugin );

		try {
			$this->rest->{$endpoint}( new \WP_REST_Request( array( 'blog_id' => 2 ) ) );
			self::fail( 'Expected the log callback to throw.' );
		} catch ( \RuntimeException $error ) {
			self::assertStringContainsString( 'log failed', $error->getMessage() );
		}

		self::assertSame( 1, WpEnv::$current_blog_id );
		self::assertSame( array(), WpEnv::$blog_stack );
		self::assertSame( 2, $job->set_options_calls );
	}

	/** @return array<string,array{string}> */
	public function throwingLogEndpointProvider(): array {
		return array(
			'activity log' => array( 'get_activity_log' ),
			'export log'   => array( 'get_export_log' ),
		);
	}

	public function test_multisite_context_is_restored_when_option_refresh_throws_during_switch(): void {
		WpEnv::$multisite = true;
		WpEnv::$current_blog_id = 1;
		$job = new class() {
			/** @var int */
			public $set_options_calls = 0;

			public function set_options( Options $options ): void {
				++$this->set_options_calls;
				if ( 1 === $this->set_options_calls ) {
					throw new \RuntimeException( 'Option refresh failed.' );
				}
			}
		};
		$plugin = new class( $job ) extends Plugin {
			/** @var mixed */
			private $test_job;

			/** @param mixed $job */
			public function __construct( $job ) {
				$this->test_job = $job;
			}

			public function get_archive_creation_job() {
				return $this->test_job;
			}
		};
		$this->pluginInstanceProperty()->setValue( null, $plugin );

		try {
			$this->rest->get_activity_log( new \WP_REST_Request( array( 'blog_id' => 2 ) ) );
			self::fail( 'Expected option refresh to throw.' );
		} catch ( \RuntimeException $error ) {
			self::assertSame( 'Option refresh failed.', $error->getMessage() );
		}

		self::assertSame( 1, WpEnv::$current_blog_id );
		self::assertSame( array(), WpEnv::$blog_stack );
		self::assertSame( 2, $job->set_options_calls );
	}

	public function test_export_log_clamps_pagination_and_sanitizes_formatted_rows(): void {
		WpEnv::$options['simply-static'] = array(
			'generate_type'      => 'export',
			'archive_start_time' => null,
		);
		$options = Options::reinstance();
		$plugin = ( new ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();
		$this->setPluginProperty( $plugin, 'options', $options );
		$this->setPluginProperty( $plugin, 'archive_creation_job', new \stdClass() );

		$this->wpdb->page_rows = array(
			array(
				'id'               => 41,
				'found_on_id'      => null,
				'url'              => 'https://example.test/exported/?item=1',
				'http_status_code' => 200,
				'status_message'   => '<strong>Kept</strong><script>bad()</script><span onclick="bad()">Note</span>',
				'error_message'    => "<b>Error</b>\n detail",
			),
		);
		$this->wpdb->status_rows = array(
			array( 'status' => '2', 'count' => 400 ),
			array( 'status' => '5', 'count' => 1 ),
		);
		$this->wpdb->count_value = 401;

		$log = $plugin->get_export_log( 500, 2, 0, 'needle_%' );

		self::assertSame( 401, $log['total_static_pages'] );
		self::assertSame( 3.0, $log['total_pages'] );
		self::assertSame( 400, $log['status_codes']['2'] );
		self::assertSame( 1, $log['status_codes']['5'] );
		self::assertSame(
			array(
				'id'          => 41,
				'url'         => 'https://example.test/exported/?item=1',
				'processable' => true,
				'code'        => 200,
				'notes'       => '<strong>Kept</strong><span>Note</span>',
				'error'       => 'Error detail',
			),
			$log['static_pages'][0]
		);

		$queries = implode( "\n", $this->wpdb->queries );
		self::assertStringContainsString( 'LIMIT 200 OFFSET 200', $queries );
		self::assertStringContainsString( 'status_message LIKE', $queries );
		self::assertStringContainsString( 'needle\\_\\%', $queries );
		self::assertContains( 'ss_before_render_export_log', WpEnv::$action_log );
		self::assertContains( 'ss_after_render_export_log', WpEnv::$action_log );
	}

	/** @return array<string,mixed> */
	private function registeredRoute( string $route, string $method ): array {
		foreach ( WpEnv::$routes as $registered ) {
			if ( $route === $registered['route'] && $method === $registered['args']['methods'] ) {
				return $registered;
			}
		}

		self::fail( sprintf( 'REST route %s %s was not registered.', $method, $route ) );
	}

	private function hasRegisteredRoute( string $route, string $method ): bool {
		foreach ( WpEnv::$routes as $registered ) {
			if ( $route === $registered['route'] && $method === $registered['args']['methods'] ) {
				return true;
			}
		}

		return false;
	}

	private function routePermission( string $route, string $method ): bool {
		$registered = $this->registeredRoute( $route, $method );
		return (bool) call_user_func(
			$registered['args']['permission_callback'],
			new \WP_REST_Request()
		);
	}

	private function pluginInstanceProperty(): ReflectionProperty {
		$property = new ReflectionProperty( Plugin::class, 'instance' );
		$property->setAccessible( true );
		return $property;
	}

	/** @param mixed $plugin @param mixed $value */
	private function setPluginProperty( $plugin, string $property_name, $value ): void {
		$property = new ReflectionProperty( Plugin::class, $property_name );
		$property->setAccessible( true );
		$property->setValue( $plugin, $value );
	}
}
