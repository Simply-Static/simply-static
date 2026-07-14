<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page_Handlers;
use Simply_Static\Plugin;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-page-handlers.php';

final class ExportLifecycleArchiveJob {

	/** @var bool */
	public $running = false;

	/** @var bool */
	public $paused = false;

	/** @var bool */
	public $queued = false;

	/** @var bool */
	public $start_result = true;

	/** @var array<int,array{int,string}> */
	public $starts = array();

	/** @var array<int,array{string,string}> */
	public $status_messages = array();

	public function is_running(): bool {
		return $this->running;
	}

	public function is_paused(): bool {
		return $this->paused;
	}

	public function is_queued(): bool {
		return $this->queued;
	}

	public function get_current_task(): string {
		return 'setup';
	}

	public function is_job_done(): bool {
		return ! $this->running;
	}

	public function start( int $blog_id, string $type ): bool {
		$this->starts[] = array( $blog_id, $type );
		return $this->start_result;
	}

	public function save_status_message( string $message, string $key ): void {
		$this->status_messages[] = array( $key, $message );
	}
}

final class OrderedPageHandlers extends Page_Handlers {

	/** @var string[] */
	public static $events = array();

	public function includes() {
		// Handler files are irrelevant to this hook-order regression test.
	}

	public function run_page_handlers_from_request() {
		self::$events[] = 'page-handlers';
	}
}

final class ExportLifecycleTest extends UnitTestCase {

	/** @var Plugin */
	private $plugin;

	/** @var ExportLifecycleArchiveJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		WpEnv::$options['simply-static'] = array( 'debugging_mode' => false );
		Options::reinstance();

		$this->job    = new ExportLifecycleArchiveJob();
		$this->plugin = ( new \ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();

		$job_property = new \ReflectionProperty( Plugin::class, 'archive_creation_job' );
		$job_property->setAccessible( true );
		$job_property->setValue( $this->plugin, $this->job );
	}

	/**
	 * @dataProvider incompleteCredentialsProvider
	 * @param mixed $username
	 * @param mixed $password
	 */
	public function test_basic_auth_preflight_requires_both_credentials_before_start( $username, $password ): void {
		$_SERVER['PHP_AUTH_USER'] = 'protected-site-user';
		WpEnv::$options['simply-static']['http_basic_auth_username'] = $username;
		WpEnv::$options['simply-static']['http_basic_auth_password'] = $password;
		WpEnv::$transients['simply_static_checks'] = 'must-survive-preflight';
		$before_export_calls = 0;
		add_action(
			'ss_before_static_export',
			static function () use ( &$before_export_calls ): void {
				++$before_export_calls;
			}
		);

		self::assertFalse( $this->plugin->run_static_export( 1, 'export' ) );
		self::assertSame( array(), $this->job->starts );
		self::assertSame( 0, $before_export_calls );
		self::assertSame( 'must-survive-preflight', WpEnv::$transients['simply_static_checks'] );
		self::assertCount( 1, $this->job->status_messages );
		self::assertSame( 'error', $this->job->status_messages[0][0] );
	}

	/** @return array<string,array{mixed,mixed}> */
	public function incompleteCredentialsProvider(): array {
		return array(
			'both missing'     => array( '', '' ),
			'username missing' => array( '', 'secret' ),
			'password missing' => array( 'crawler', '' ),
			'non-string user'  => array( array( 'crawler' ), 'secret' ),
		);
	}

	public function test_complete_credentials_allow_start_and_zero_values_are_not_treated_as_empty(): void {
		$_SERVER['REMOTE_USER'] = 'protected-site-user';
		WpEnv::$options['simply-static']['http_basic_auth_username'] = '0';
		WpEnv::$options['simply-static']['http_basic_auth_password'] = '0';

		self::assertTrue( $this->plugin->run_static_export( 4, 'update' ) );
		self::assertSame( array( array( 4, 'update' ) ), $this->job->starts );
		self::assertSame( array(), $this->job->status_messages );
	}

	public function test_missing_server_variables_are_safe_and_do_not_enable_basic_auth_preflight(): void {
		unset(
			$_SERVER['SERVER_SOFTWARE'],
			$_SERVER['PHP_AUTH_USER'],
			$_SERVER['REMOTE_USER'],
			$_SERVER['AUTH_USER']
		);

		self::assertTrue( $this->plugin->run_static_export( 2, 'export' ) );
		self::assertSame( array( array( 2, 'export' ) ), $this->job->starts );
	}

	public function test_non_basic_remote_user_does_not_trigger_basic_auth_preflight(): void {
		$_SERVER['REMOTE_USER'] = 'sso-user';

		self::assertTrue( $this->plugin->run_static_export( 2, 'export' ) );
		self::assertSame( array( array( 2, 'export' ) ), $this->job->starts );
	}

	public function test_job_start_failure_is_propagated_to_the_caller(): void {
		$this->job->start_result = false;

		self::assertFalse( $this->plugin->run_static_export( 3, 'export' ) );
		self::assertSame( array( array( 3, 'export' ) ), $this->job->starts );
	}

	public function test_contended_start_gate_blocks_preparation_without_mutating_scope(): void {
		WpEnv::$options[ Plugin::EXPORT_START_LOCK_OPTION ] = array(
			'token'      => 'another-request',
			'created_at' => time(),
		);
		WpEnv::$options['simply-static-use-build'] = 17;
		$prepare_calls = 0;

		$started = $this->plugin->run_static_export(
			1,
			'export',
			static function () use ( &$prepare_calls ) {
				++$prepare_calls;
				update_option( 'simply-static-use-build', 29, false );
				return 'export';
			}
		);

		self::assertFalse( $started );
		self::assertSame( 0, $prepare_calls );
		self::assertSame( 17, WpEnv::$options['simply-static-use-build'] );
		self::assertSame( array(), $this->job->starts );
	}

	public function test_active_or_paused_jobs_block_preparation_and_release_the_gate(): void {
		$prepare_calls = 0;
		$prepare = static function () use ( &$prepare_calls ) {
			++$prepare_calls;
			return 'export';
		};

		$this->job->running = true;
		self::assertFalse( $this->plugin->run_static_export( 1, 'export', $prepare ) );
		$this->job->running = false;
		$this->job->paused  = true;
		self::assertFalse( $this->plugin->run_static_export( 1, 'export', $prepare ) );

		self::assertSame( 0, $prepare_calls );
		self::assertArrayNotHasKey( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
	}

	public function test_preparation_can_atomically_select_type_and_success_releases_gate(): void {
		$gate_seen = false;
		$started = $this->plugin->run_static_export(
			8,
			'42',
			static function () use ( &$gate_seen ) {
				$gate_seen = array_key_exists( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
				update_option( 'simply-static-use-build', 42, false );
				return 'export';
			}
		);

		self::assertTrue( $started );
		self::assertTrue( $gate_seen );
		self::assertSame( array( array( 8, 'export' ) ), $this->job->starts );
		self::assertSame( 42, WpEnv::$options['simply-static-use-build'] );
		self::assertArrayNotHasKey( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
	}

	public function test_failed_start_rolls_back_prepared_scope_before_releasing_gate(): void {
		$this->job->start_result = false;
		WpEnv::$options['simply-static-use-single'] = '11';
		$rollback_saw_gate = false;

		$started = $this->plugin->run_static_export(
			3,
			'export',
			static function () {
				update_option( 'simply-static-use-single', '22', false );
				return 'export';
			},
			static function () use ( &$rollback_saw_gate ) {
				$rollback_saw_gate = array_key_exists( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
				update_option( 'simply-static-use-single', '11', false );
			}
		);

		self::assertFalse( $started );
		self::assertTrue( $rollback_saw_gate );
		self::assertSame( '11', WpEnv::$options['simply-static-use-single'] );
		self::assertArrayNotHasKey( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
	}

	public function test_abandoned_or_malformed_start_gate_is_recovered(): void {
		foreach (
			array(
				array( 'token' => 'stale', 'created_at' => time() - 301 ),
				'corrupt',
			) as $stale_lock
		) {
			WpEnv::$options[ Plugin::EXPORT_START_LOCK_OPTION ] = $stale_lock;
			self::assertTrue( $this->plugin->run_static_export( 2, 'export' ) );
			self::assertArrayNotHasKey( Plugin::EXPORT_START_LOCK_OPTION, WpEnv::$options );
		}
	}

	public function test_nested_start_in_same_request_is_rejected_before_its_preparation(): void {
		$nested_prepare_calls = 0;
		$outer_started = $this->plugin->run_static_export(
			5,
			'export',
			function () use ( &$nested_prepare_calls ) {
				$nested_started = $this->plugin->run_static_export(
					5,
					'export',
					static function () use ( &$nested_prepare_calls ) {
						++$nested_prepare_calls;
						return 'export';
					}
				);
				self::assertFalse( $nested_started );
				return 'export';
			}
		);

		self::assertTrue( $outer_started );
		self::assertSame( 0, $nested_prepare_calls );
		self::assertCount( 1, $this->job->starts );
	}

	public function test_page_handlers_register_before_init_and_run_after_integration_bootstrap(): void {
		OrderedPageHandlers::$events = array();
		add_action(
			'init',
			static function (): void {
				OrderedPageHandlers::$events[] = 'regular-init';
			},
			10
		);

		new OrderedPageHandlers();

		self::assertArrayHasKey( Page_Handlers::INIT_PRIORITY, WpEnv::$filters['init'] );
		do_action( 'init' );
		self::assertSame( array( 'regular-init', 'page-handlers' ), OrderedPageHandlers::$events );
	}
}
