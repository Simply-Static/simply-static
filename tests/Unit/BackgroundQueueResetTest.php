<?php

declare(strict_types=1);

namespace {
	if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
		/**
		 * @param mixed[] $args
		 * @return int
		 */
		function wp_clear_scheduled_hook( $hook, $args = array(), $wp_error = false ) {
			$events = isset( $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] )
				? $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ]
				: array();
			unset( $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] );
			return count( $events );
		}
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Admin_Rest;
	use Simply_Static\Background_Process;
	use Simply_Static\Options;
	use Simply_Static\Plugin;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
	require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
	require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
	require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-async-request.php';
	require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-background-process.php';
	require_once dirname( __DIR__, 2 ) . '/src/admin/inc/class-ss-admin-rest.php';

	final class ResetLockBackgroundProcess extends Background_Process {

		/** @var string */
		protected $action = 'archive_creation_job';

		/** @param mixed $item */
		protected function task( $item ) {
			return false;
		}
	}

	final class BackgroundQueueResetJob {

		/** @var Options */
		private $options;

		/** @var int */
		public $delete_all_calls = 0;

		/** @var int|null */
		public $site_id;

		/** @var string|null */
		public $current_task = 'fetch_urls';

		/** @var bool */
		public $active_lock = false;

		public function __construct( Options $options ) {
			$this->options = $options;
		}

		public function set_current_site_id( $site_id ): void {
			$this->site_id = (int) $site_id;
		}

		public function delete_all(): void {
			++$this->delete_all_calls;
		}

		public function has_active_process_lock(): bool {
			return $this->active_lock;
		}

		public function get_identifier(): string {
			return 'wp_archive_creation_job';
		}

		public function get_options(): Options {
			return $this->options;
		}

		public function reset_runtime_state(): void {
			$this->current_task = null;
		}
	}

	final class BackgroundQueueResetTest extends UnitTestCase {

		/** @var Admin_Rest */
		private $rest;

		/** @var BackgroundQueueResetJob */
		private $job;

		protected function setUp(): void {
			parent::setUp();

			WpEnv::$multisite       = true;
			WpEnv::$current_blog_id = 7;
			WpEnv::$options['simply-static'] = array(
				'delivery_method'        => 'zip',
				'encryption_key'         => 'keep-me',
				'archive_status_messages' => array( 'fetch' => array( 'message' => 'stale' ) ),
				'archive_deploy_id'      => 'stale-deploy',
				'pages_status'           => array( 'remaining' => 5, 'total' => 10 ),
				'archive_name'           => 'partial-export',
				'archive_start_time'     => '2026-07-12 10:00:00',
				'archive_end_time'       => null,
				'generate_type'          => 'update',
				'archive_task_list'      => array( 'setup', 'fetch_urls', 'wrapup' ),
				'zip_batch_offset'       => 500,
				'zip_total_files'        => 1000,
				'zip_files'              => array( '/tmp/one', '/tmp/two' ),
			);
			WpEnv::$options['wp_archive_creation_job_status'] = 2;
			foreach ( array( 'simply-static-404-only', 'simply-static-use-single', 'simply-static-use-build', 'simply-static-use-language' ) as $flag ) {
				WpEnv::$options[ $flag ] = 'stale';
			}

			WpEnv::$site_transients = array(
				'wp_archive_creation_job_process_lock'        => 'legacy-lock',
				'wp_archive_creation_job_process_lock_site_7' => 'site-lock',
				'wp_archive_creation_job_process_lock_site_8' => 'other-site-lock',
			);
			WpEnv::$site_options[ Plugin::SLUG . '_multisite_export_running' ] = 7;
			$GLOBALS['simply_static_test_scheduled_hooks'] = array(
				'wp_archive_creation_job_cron'    => array( 100, 200 ),
				'simply_static_site_export_cron' => array( 300 ),
			);

			$options   = Options::reinstance();
			$this->job = new BackgroundQueueResetJob( $options );
			$plugin    = ( new \ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();

			$job_property = new \ReflectionProperty( Plugin::class, 'archive_creation_job' );
			$job_property->setAccessible( true );
			$job_property->setValue( $plugin, $this->job );

			$instance_property = new \ReflectionProperty( Plugin::class, 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null, $plugin );

			$this->rest = new Admin_Rest();
		}

		protected function tearDown(): void {
			$instance_property = new \ReflectionProperty( Plugin::class, 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null, null );
			unset( $GLOBALS['simply_static_test_scheduled_hooks'] );

			parent::tearDown();
		}

		public function test_reset_clears_stale_queue_runtime_and_one_shot_state(): void {
			$reset_actions = 0;
			add_action(
				'ss_after_background_queue_reset',
				static function () use ( &$reset_actions ): void {
					++$reset_actions;
				}
			);

			$response = json_decode( (string) $this->rest->reset_background_queue(), true );

			self::assertSame( 200, $response['status'] ?? null );
			self::assertSame( 1, $this->job->delete_all_calls );
			self::assertSame( 7, $this->job->site_id );
			self::assertNull( $this->job->current_task );
			self::assertSame( 1, $reset_actions );

			$options = WpEnv::$options['simply-static'];
			self::assertSame( 'zip', $options['delivery_method'] );
			self::assertSame( 'keep-me', $options['encryption_key'] );
			self::assertSame( array(), $options['archive_status_messages'] );
			self::assertNull( $options['archive_deploy_id'] );
			self::assertSame( array(), $options['pages_status'] );
			self::assertNull( $options['archive_name'] );
			self::assertNull( $options['archive_start_time'] );
			self::assertNull( $options['archive_end_time'] );
			self::assertSame( 'export', $options['generate_type'] );
			self::assertSame( array(), $options['archive_task_list'] );
			self::assertNull( $options['zip_batch_offset'] );
			self::assertNull( $options['zip_total_files'] );
			self::assertNull( $options['zip_files'] );

			self::assertArrayNotHasKey( 'wp_archive_creation_job_status', WpEnv::$options );
			foreach ( array( 'simply-static-404-only', 'simply-static-use-single', 'simply-static-use-build', 'simply-static-use-language' ) as $flag ) {
				self::assertArrayNotHasKey( $flag, WpEnv::$options );
			}

			self::assertSame( 'legacy-lock', WpEnv::$site_transients['wp_archive_creation_job_process_lock'] );
			self::assertArrayNotHasKey( 'wp_archive_creation_job_process_lock_site_7', WpEnv::$site_transients );
			self::assertSame( 'other-site-lock', WpEnv::$site_transients['wp_archive_creation_job_process_lock_site_8'] );
			self::assertArrayNotHasKey( Plugin::SLUG . '_multisite_export_running', WpEnv::$site_options );

			self::assertArrayNotHasKey( 'wp_archive_creation_job_cron', $GLOBALS['simply_static_test_scheduled_hooks'] );
			self::assertSame(
				array( 300 ),
				$GLOBALS['simply_static_test_scheduled_hooks']['simply_static_site_export_cron']
			);
		}

		public function test_reset_preserves_another_sites_multisite_export_marker(): void {
			WpEnv::$site_options[ Plugin::SLUG . '_multisite_export_running' ] = 8;

			$this->rest->reset_background_queue();

			self::assertSame( 8, WpEnv::$site_options[ Plugin::SLUG . '_multisite_export_running' ] );
		}

		public function test_single_site_reset_clears_the_global_process_lock(): void {
			WpEnv::$multisite = false;

			$this->rest->reset_background_queue();

			self::assertArrayNotHasKey( 'wp_archive_creation_job_process_lock', WpEnv::$site_transients );
			self::assertSame( 'site-lock', WpEnv::$site_transients['wp_archive_creation_job_process_lock_site_7'] );
		}

		public function test_reset_refuses_to_race_an_active_background_worker(): void {
			$this->job->active_lock = true;

			$response = json_decode( (string) $this->rest->reset_background_queue(), true );

			self::assertSame( 409, $response['status'] ?? null );
			self::assertSame( 0, $this->job->delete_all_calls );
			self::assertSame( '2026-07-12 10:00:00', WpEnv::$options['simply-static']['archive_start_time'] );
			self::assertSame( 'fetch_urls', $this->job->current_task );
			self::assertArrayHasKey( 'wp_archive_creation_job_cron', $GLOBALS['simply_static_test_scheduled_hooks'] );
			self::assertSame( 'stale', WpEnv::$options['simply-static-404-only'] );
		}

		public function test_process_lock_guard_distinguishes_active_stale_and_invalid_locks(): void {
			$process = new ResetLockBackgroundProcess();
			$process->set_current_site_id( 7 );
			$lock_key = 'wp_archive_creation_job_process_lock_site_7';

			WpEnv::$site_transients[ $lock_key ] = microtime() . ':active-token';
			self::assertTrue( $process->has_active_process_lock() );

			WpEnv::$site_transients[ $lock_key ] = '0.00000000 ' . ( time() - 120 ) . ':stale-token';
			self::assertFalse( $process->has_active_process_lock() );

			WpEnv::$site_transients[ $lock_key ] = 'legacy-invalid-lock';
			self::assertFalse( $process->has_active_process_lock() );
		}
	}
}
