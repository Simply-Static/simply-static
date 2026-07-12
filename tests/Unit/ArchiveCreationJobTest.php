<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Archive_Creation_Job;
use Simply_Static\Background_Process;
use Simply_Static\Multisite;
use Simply_Static\Options;
use Simply_Static\Pause_Exception;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

$admin_include = ABSPATH . 'wp-admin/includes/admin.php';
if ( ! file_exists( $admin_include ) ) {
	wp_mkdir_p( dirname( $admin_include ) );
	file_put_contents( $admin_include, "<?php\n" );
}

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-multisite.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-async-request.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-background-process.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/exceptions/class-ss-pause-exception.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-archive-creation-job.php';

final class ArchiveTaskDouble {

	/** @var mixed */
	public $result = false;

	/** @var \Throwable|null */
	public $throwable;

	/** @var int */
	public $perform_calls = 0;

	/** @var int */
	public $cleanup_calls = 0;

	/** @var \Throwable|null */
	public $cleanup_throwable;

	/** @var int */
	public $processed_pages = 0;

	/** @var int */
	public $total_pages = 0;

	/** @var bool */
	public $throw_on_progress = false;

	/** @return mixed */
	public function perform() {
		++$this->perform_calls;
		if ( $this->throwable ) {
			throw $this->throwable;
		}

		return $this->result;
	}

	public function cleanup(): void {
		++$this->cleanup_calls;
		if ( $this->cleanup_throwable ) {
			throw $this->cleanup_throwable;
		}
	}

	public function get_processed_pages(): int {
		if ( $this->throw_on_progress ) {
			throw new \RuntimeException( 'Progress unavailable' );
		}

		return $this->processed_pages;
	}

	public function get_total_pages(): int {
		if ( $this->throw_on_progress ) {
			throw new \RuntimeException( 'Progress unavailable' );
		}

		return $this->total_pages;
	}
}

final class ArchiveCancelTaskDouble {

	/** @var int */
	public $perform_calls = 0;

	/** @var mixed */
	public $status_seen;

	public function perform(): bool {
		++$this->perform_calls;
		$this->status_seen = get_option( 'wp_archive_creation_job_status' );
		return true;
	}
}

final class ArchiveConstructorThrows {

	public function __construct() {
		throw new \TypeError( 'Constructor type failure' );
	}
}

final class ArchiveCreationJobHarness extends Archive_Creation_Job {

	/** @var array<string,mixed> */
	public $task_objects = array();

	/** @var array<int,object> */
	public $batches = array();

	/** @var mixed[] */
	public $queued = array();

	/** @var int */
	public $save_calls = 0;

	/** @var int */
	public $dispatch_calls = 0;

	/** @var mixed */
	public $dispatch_result = true;

	/** @var bool */
	public $queue_save_successful = true;

	/** @var bool */
	public $processing_for_test = false;

	/** @var int */
	public $unlock_calls = 0;

	/** @var int */
	public $lock_calls = 0;

	/** @var bool */
	public $start_lock_result = true;

	/** @var bool */
	public $active_lock_for_test = false;

	/** @var int */
	public $clear_event_calls = 0;

	/** @var int */
	public $schedule_calls = 0;

	/** @var ArchiveCancelTaskDouble|null */
	public $cancel_task_for_test;

	/** @var bool */
	public $paused_for_test = false;

	/** @var bool */
	public $cancelled_for_test = false;

	public function __construct( Options $options ) {
		$this->options                  = $options;
		$this->identifier               = 'wp_archive_creation_job';
		$this->cron_hook_identifier     = 'wp_archive_creation_job_cron';
		$this->cron_interval_identifier = 'wp_archive_creation_job_cron_interval';
	}

	/** @return mixed */
	public function get_task_object( $task_name ) {
		if ( array_key_exists( $task_name, $this->task_objects ) ) {
			return $this->task_objects[ $task_name ];
		}

		return parent::get_task_object( $task_name );
	}

	/** @return mixed */
	public function resolve_task_with_parent( string $task_name ) {
		return parent::get_task_object( $task_name );
	}

	/** @return array<int,object> */
	public function get_batches( $limit = 0, $for_site_id = null ) {
		return $this->batches;
	}

	/** @param mixed $data */
	public function push_to_queue( $data ) {
		$this->queued[] = $data;
		return $this;
	}

	public function save() {
		++$this->save_calls;
		return $this;
	}

	public function was_last_queue_save_successful() {
		return $this->queue_save_successful;
	}

	public function dispatch() {
		++$this->dispatch_calls;
		return $this->dispatch_result;
	}

	public function is_processing() {
		return $this->processing_for_test;
	}

	public function lock_process( $reset_start_time = true ) {
		++$this->lock_calls;
		return $this->start_lock_result;
	}

	public function has_active_process_lock() {
		return $this->active_lock_for_test;
	}

	protected function unlock_process() {
		++$this->unlock_calls;
		return $this;
	}

	protected function clear_scheduled_event() {
		++$this->clear_event_calls;
	}

	protected function schedule_event() {
		++$this->schedule_calls;
	}

	public function is_paused() {
		return $this->paused_for_test
			|| Background_Process::STATUS_PAUSED === get_option( 'wp_archive_creation_job_status' );
	}

	public function is_cancelled() {
		return $this->cancelled_for_test
			|| Background_Process::STATUS_CANCELLED === get_option( 'wp_archive_creation_job_status' );
	}

	public function get_chain_id() {
		return '00000000-0000-4000-8000-000000000001';
	}

	/** @return false|string */
	public function run_task( string $task_name ) {
		return $this->task( $task_name );
	}

	/** @return int|null */
	public function task_progress( string $task_name ) {
		return $this->get_task_progress( $task_name );
	}

	/** @return string|null */
	public function next_task() {
		return $this->find_next_task();
	}

	public function set_current_task_for_test( ?string $task_name ): void {
		$this->current_task = $task_name;
	}

	public function set_runtime_state_for_test(): void {
		$this->current_task       = 'fetch_urls';
		$this->task_list          = array( 'setup', 'fetch_urls' );
		$this->is_task_processing = true;
	}

	/** @return array<string,mixed> */
	public function runtime_state_for_test(): array {
		return array(
			'current_task' => $this->current_task,
			'task_list'    => $this->task_list,
			'processing'   => $this->is_task_processing,
		);
	}

	public function finish_for_test(): void {
		$this->complete();
	}

	protected function get_cancel_task() {
		if ( $this->cancel_task_for_test ) {
			return $this->cancel_task_for_test;
		}

		return parent::get_cancel_task();
	}

	/** @return bool|array */
	public function allowed_classes_for_test() {
		return $this->allowed_batch_data_classes;
	}

	/** @return mixed */
	public static function unserialize_for_test( $value, $allowed_classes ) {
		return parent::maybe_unserialize( $value, $allowed_classes );
	}
}

final class ArchiveFailureLifecycleHarness extends Archive_Creation_Job {

	/** @var array<string,mixed> */
	public $task_objects = array();

	/** @var object|null */
	public $batch;

	/** @var int */
	public $dispatch_calls = 0;

	/** @var int */
	public $clear_calls = 0;

	public function __construct( Options $options ) {
		$this->options                  = $options;
		$this->identifier               = 'wp_archive_creation_job';
		$this->cron_hook_identifier     = 'wp_archive_creation_job_cron';
		$this->cron_interval_identifier = 'wp_archive_creation_job_cron_interval';
		$this->batch = (object) array(
			'key'  => 'wp_archive_creation_job_batch_test',
			'data' => array( 'fetch_urls' ),
		);
	}

	/** @return mixed */
	public function get_task_object( $task_name ) {
		return $this->task_objects[ $task_name ] ?? false;
	}

	/** @return array<int,object> */
	public function get_batches( $limit = 0, $for_site_id = null ) {
		return $this->batch ? array( $this->batch ) : array();
	}

	public function lock_process( $reset_start_time = true ) {
		return true;
	}

	protected function unlock_process() {
		return $this;
	}

	/** @param mixed[] $data */
	public function update( $key, $data ) {
		$this->batch->data = $data;
		return $this;
	}

	public function delete( $key ) {
		$this->batch = null;
		return $this;
	}

	public function should_continue() {
		return true;
	}

	public function is_paused() {
		return false;
	}

	public function is_cancelled() {
		return false;
	}

	public function dispatch() {
		++$this->dispatch_calls;
		return true;
	}

	protected function clear_scheduled_event() {
		++$this->clear_calls;
	}

	public function get_chain_id() {
		return '00000000-0000-4000-8000-000000000004';
	}

	public function run_handle() {
		return parent::handle();
	}
}

final class ArchiveCreationJobTest extends UnitTestCase {

	/** @var Options */
	private $options;

	/** @var ArchiveCreationJobHarness */
	private $job;

	protected function setUp(): void {
		parent::setUp();
		WpEnv::$options['simply-static'] = array(
			'debugging_mode'          => false,
			'delivery_method'         => 'zip',
			'archive_status_messages' => array(),
			'archive_start_time'      => null,
			'archive_end_time'        => null,
		);
		$this->options = Options::reinstance();
		$this->job     = new ArchiveCreationJobHarness( $this->options );
	}

	public function test_constructor_registers_async_and_cron_hooks_for_a_completed_job(): void {
		$job = new Archive_Creation_Job();

		self::assertInstanceOf( Archive_Creation_Job::class, $job );
		self::assertArrayHasKey( 'wp_ajax_wp_archive_creation_job', WpEnv::$filters );
		self::assertArrayHasKey( 'wp_ajax_nopriv_wp_archive_creation_job', WpEnv::$filters );
		self::assertArrayHasKey( 'wp_archive_creation_job_cron', WpEnv::$filters );
		self::assertArrayHasKey( 'wp_archive_creation_job_query_args', WpEnv::$filters );
		$allowed_property = new \ReflectionProperty( Archive_Creation_Job::class, 'allowed_batch_data_classes' );
		$allowed_property->setAccessible( true );
		self::assertFalse( $allowed_property->getValue( $job ) );
		self::assertInstanceOf(
			'__PHP_Incomplete_Class',
			ArchiveCreationJobHarness::unserialize_for_test( serialize( new \stdClass() ), false )
		);
	}

	public function test_task_list_filter_receives_delivery_method_and_status_markers_are_persisted(): void {
		$received_delivery = null;
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function ( array $tasks, string $delivery ) use ( &$received_delivery ): array {
				$received_delivery = $delivery;
				return array( 'setup', 'fetch_urls' );
			},
			10,
			2
		);

		self::assertSame( array( 'setup', 'fetch_urls' ), $this->job->get_task_list() );
		self::assertSame( 'zip', $received_delivery );
		self::assertSame( $this->options, $this->job->get_options() );
		self::assertSame( 2, $this->job->set_job_interval( 99 ) );

		$this->job->mark_as_paused();
		$this->job->mark_as_resumed();
		$messages = $this->options->get( 'archive_status_messages' );
		self::assertCount( 2, $messages );
		self::assertSame( array( 'Export paused.', 'Export resumed.' ), array_column( $messages, 'message' ) );
	}

	public function test_start_persists_type_before_filtering_and_resets_stale_archive_state(): void {
		$this->options
			->set( 'archive_status_messages', array( 'old' => array( 'message' => 'stale' ) ) )
			->set( 'archive_task_list', array( 'stale_task' ) )
			->set( 'zip_batch_offset', 90 )
			->set( 'zip_total_files', 100 )
			->set( 'zip_files', array( '/tmp/old' ) );
		$type_seen_by_filter = null;
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			function ( array $tasks ) use ( &$type_seen_by_filter ): array {
				$type_seen_by_filter = $this->options->get( 'generate_type' );
				return array( 'setup', 'fetch_urls' );
			}
		);

		self::assertTrue( $this->job->start( 5, 'update' ) );
		self::assertSame( 'update', $type_seen_by_filter );
		self::assertSame( array( 'setup' ), $this->job->queued );
		self::assertSame( 1, $this->job->save_calls );
		self::assertSame( 1, $this->job->dispatch_calls );
		self::assertSame( 1, $this->job->unlock_calls );
		self::assertSame( 5, $this->job->get_current_site_id() );
		self::assertSame( array(), $this->options->get( 'archive_status_messages' ) );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_start_time' ) );
		self::assertNull( $this->options->get( 'archive_end_time' ) );
		self::assertNull( $this->options->get( 'zip_batch_offset' ) );
		self::assertNull( $this->options->get( 'zip_total_files' ) );
		self::assertNull( $this->options->get( 'zip_files' ) );
		self::assertNotEmpty( $this->options->get( 'archive_deploy_id' ) );
		self::assertNull( $this->options->get( 'archive_name' ) );
		self::assertSame( array( 'setup', 'fetch_urls' ), $this->options->get( 'archive_task_list' ) );
	}

	public function test_full_export_gets_a_fresh_archive_name(): void {
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup' );
			}
		);

		self::assertTrue( $this->job->start( 9, 'export' ) );
		self::assertStringStartsWith( 'simply-static-9-', $this->options->get( 'archive_name' ) );
	}

	public function test_active_export_uses_immutable_persisted_task_snapshot(): void {
		$live_tasks = array( 'setup', 'fetch_urls', 'create_zip_archive', 'wrapup' );
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function () use ( &$live_tasks ): array {
				return $live_tasks;
			}
		);

		self::assertTrue( $this->job->start( 1, 'export' ) );
		self::assertSame( $live_tasks, $this->options->get( 'archive_task_list' ) );

		$live_tasks = array( 'setup', 'transfer_files_locally', 'wrapup' );
		self::assertSame(
			array( 'setup', 'fetch_urls', 'create_zip_archive', 'wrapup' ),
			$this->job->get_task_list()
		);
		$this->job->set_current_task_for_test( 'fetch_urls' );
		self::assertSame( 'create_zip_archive', $this->job->next_task() );
	}

	public function test_empty_task_list_fails_cleanly_without_queueing_work(): void {
		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( array(), $this->job->queued );
		self::assertSame( 0, $this->job->save_calls );
		self::assertSame( 0, $this->job->dispatch_calls );
		self::assertNull( $this->options->get( 'archive_start_time' ) );
		self::assertStringContainsString(
			'no archive tasks',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
	}

	public function test_queue_persistence_failure_rolls_back_active_archive_state(): void {
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup' );
			}
		);
		$this->job->queue_save_successful = false;

		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( 1, $this->job->save_calls );
		self::assertSame( 0, $this->job->dispatch_calls );
		self::assertSame( 1, $this->job->unlock_calls );
		self::assertSame( 1, $this->job->clear_event_calls );
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_end_time' ) );
		self::assertStringContainsString(
			'queue could not be saved',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
		self::assertContains( 'ss_archive_creation_job_start_failed', WpEnv::$action_log );
	}

	public function test_dispatch_failure_rolls_back_queue_instead_of_reporting_start_success(): void {
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup' );
			}
		);
		$this->job->dispatch_result = false;

		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( 1, $this->job->save_calls );
		self::assertSame( 1, $this->job->dispatch_calls );
		self::assertSame( 1, $this->job->unlock_calls );
		self::assertSame( 1, $this->job->clear_event_calls );
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
		self::assertStringContainsString(
			'worker could not be dispatched',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
		self::assertContains( 'ss_archive_creation_job_start_failed', WpEnv::$action_log );
	}

	public function test_empty_task_failure_removes_multisite_marker_and_restores_original_blog(): void {
		$this->register_multisite_start_hooks();
		WpEnv::$multisite       = true;
		WpEnv::$current_blog_id = 1;

		self::assertFalse( $this->job->start( 7, 'export' ) );

		self::assertSame( 1, WpEnv::$current_blog_id );
		self::assertSame( array(), WpEnv::$blog_stack );
		self::assertArrayNotHasKey( 'simply-static_multisite_export_running', WpEnv::$site_options );
		self::assertContains( 'ss_archive_creation_job_start_failed', WpEnv::$action_log );
	}

	public function test_multisite_export_claim_is_atomic_and_preserves_another_sites_owner(): void {
		$multisite = ( new \ReflectionClass( Multisite::class ) )->newInstanceWithoutConstructor();
		WpEnv::$multisite       = true;
		WpEnv::$current_blog_id = 7;
		WpEnv::$site_options['simply-static_multisite_export_running'] = 8;

		try {
			$multisite->add_export();
			self::fail( 'A marker owned by another site must reject the claim.' );
		} catch ( \RuntimeException $exception ) {
			self::assertStringContainsString( 'another site', $exception->getMessage() );
		}

		self::assertSame( 8, WpEnv::$site_options['simply-static_multisite_export_running'] );
	}

	public function test_task_list_exception_cleans_multisite_start_state_before_rethrowing(): void {
		$this->register_multisite_start_hooks();
		WpEnv::$multisite       = true;
		WpEnv::$current_blog_id = 1;
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				throw new \RuntimeException( 'Task list failed' );
			}
		);

		try {
			$this->job->start( 7, 'export' );
			self::fail( 'The task-list exception should be rethrown.' );
		} catch ( \RuntimeException $exception ) {
			self::assertSame( 'Task list failed', $exception->getMessage() );
		}

		self::assertSame( 1, WpEnv::$current_blog_id );
		self::assertSame( array(), WpEnv::$blog_stack );
		self::assertArrayNotHasKey( 'simply-static_multisite_export_running', WpEnv::$site_options );
		self::assertContains( 'ss_archive_creation_job_start_failed', WpEnv::$action_log );
	}

	public function test_pre_switched_multisite_context_is_not_nested_or_restored_twice_on_failure(): void {
		$this->register_multisite_start_hooks();
		WpEnv::$multisite       = true;
		WpEnv::$current_blog_id = 1;
		switch_to_blog( 7 );
		self::assertSame( array( 1 ), WpEnv::$blog_stack );
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				throw new \RuntimeException( 'Task list failed after outer switch' );
			}
		);

		try {
			$this->job->start( 7, 'export' );
			self::fail( 'The task-list exception should be rethrown.' );
		} catch ( \RuntimeException $exception ) {
			self::assertSame( 'Task list failed after outer switch', $exception->getMessage() );
		}

		// The legacy start hook did not add another frame and therefore must not
		// consume the outer run_in_blog_context() switch during failure cleanup.
		self::assertSame( 7, WpEnv::$current_blog_id );
		self::assertSame( array( 1 ), WpEnv::$blog_stack );
		self::assertArrayNotHasKey( 'simply-static_multisite_export_running', WpEnv::$site_options );
		restore_current_blog();
		self::assertSame( 1, WpEnv::$current_blog_id );
		self::assertSame( array(), WpEnv::$blog_stack );
	}

	public function test_start_rejects_an_active_job_without_mutating_the_queue(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null );

		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( array(), $this->job->queued );
		self::assertContains( 'ss_archive_creation_job_already_running', WpEnv::$action_log );
	}

	public function test_start_gate_rejects_live_or_contended_process_lock_before_state_mutation(): void {
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls' );
			}
		);
		$this->job->active_lock_for_test = true;

		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( 0, $this->job->lock_calls );
		self::assertSame( array(), $this->job->queued );
		self::assertNull( $this->options->get( 'archive_start_time' ) );

		$this->job->active_lock_for_test = false;
		$this->job->start_lock_result    = false;
		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( 1, $this->job->lock_calls );
		self::assertSame( 0, $this->job->unlock_calls );
		self::assertSame( array(), $this->job->queued );
		self::assertNull( $this->options->get( 'generate_type' ) );
	}

	public function test_parent_task_resolution_uses_filter_and_missing_class_records_an_error(): void {
		$resolved = new ArchiveTaskDouble();
		add_filter(
			'simply_static_class_name',
			static function ( string $class_name, string $task_name ) use ( $resolved ): string {
				return 'mapped' === $task_name ? get_class( $resolved ) : $class_name;
			},
			10,
			2
		);

		self::assertInstanceOf( ArchiveTaskDouble::class, $this->job->resolve_task_with_parent( 'mapped' ) );
		self::assertFalse( $this->job->resolve_task_with_parent( 'definitely_missing' ) );
		self::assertStringContainsString(
			'Definitely_missing_Task',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
	}

	public function test_task_retries_when_paused_or_incomplete(): void {
		$task = new ArchiveTaskDouble();
		$this->job->task_objects['fetch_urls'] = $task;
		$this->job->paused_for_test = true;

		self::assertSame( 'fetch_urls', $this->job->run_task( 'fetch_urls' ) );
		self::assertSame( 0, $task->perform_calls );

		$this->job->paused_for_test = false;
		$task->result = false;
		self::assertSame( 'fetch_urls', $this->job->run_task( 'fetch_urls' ) );
		self::assertSame( 1, $task->perform_calls );
	}

	public function test_completed_task_advances_and_cleans_the_next_task_then_finishes_at_end(): void {
		$current = new ArchiveTaskDouble();
		$current->result = true;
		$next = new ArchiveTaskDouble();
		$this->job->task_objects = array(
			'setup'      => $current,
			'fetch_urls' => $next,
		);
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls' );
			}
		);

		self::assertSame( 'fetch_urls', $this->job->run_task( 'setup' ) );
		self::assertSame( 1, $next->cleanup_calls );

		$next->result = true;
		self::assertFalse( $this->job->run_task( 'fetch_urls' ) );
		self::assertSame( 1, $next->perform_calls );
	}

	public function test_pause_exception_retries_but_other_exceptions_cancel_with_status(): void {
		$task = new ArchiveTaskDouble();
		$this->job->task_objects['fetch_urls'] = $task;
		$task->throwable = new Pause_Exception( 'Pause now' );

		self::assertSame( 'fetch_urls', $this->job->run_task( 'fetch_urls' ) );
		self::assertNull( $this->options->get( 'archive_end_time' ) );

		$task->throwable = new \RuntimeException( 'Crawler exploded' );
		self::assertSame( 'cancel', $this->job->run_task( 'fetch_urls' ) );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_end_time' ) );
		self::assertStringContainsString(
			'Crawler exploded',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
		self::assertContains( 'ss_completed', WpEnv::$action_log );
	}

	public function test_task_constructor_type_errors_enter_terminal_exception_path(): void {
		add_filter(
			'simply_static_class_name',
			static function ( string $class_name, string $task_name ): string {
				return 'constructor_failure' === $task_name ? ArchiveConstructorThrows::class : $class_name;
			},
			10,
			2
		);

		self::assertSame( 'cancel', $this->job->run_task( 'constructor_failure' ) );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_end_time' ) );
		self::assertStringContainsString(
			'Constructor type failure',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
	}

	public function test_cleanup_errors_are_caught_before_advancing_to_next_task(): void {
		$current = new ArchiveTaskDouble();
		$current->result = true;
		$next = new ArchiveTaskDouble();
		$next->cleanup_throwable = new \Error( 'Cleanup failed' );
		$this->job->task_objects = array(
			'setup'      => $current,
			'fetch_urls' => $next,
		);
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls' );
			}
		);

		self::assertSame( 'cancel', $this->job->run_task( 'setup' ) );
		self::assertSame( 1, $next->cleanup_calls );
		self::assertStringContainsString(
			'Cleanup failed',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
	}

	public function test_wp_error_cancels_and_missing_task_is_removed(): void {
		$error_task = new ArchiveTaskDouble();
		$error_task->result = new \WP_Error( 'fetch_failed', 'Origin returned 503' );
		$this->job->task_objects['fetch_urls'] = $error_task;

		self::assertSame( 'cancel', $this->job->run_task( 'fetch_urls' ) );
		self::assertStringContainsString(
			'Origin returned 503',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);

		$this->job->task_objects['missing'] = false;
		self::assertSame( 'cancel', $this->job->run_task( 'missing' ) );
		self::assertSame( 'missing', $this->job->get_current_task() );
		$this->job->task_objects['cancel'] = false;
		self::assertFalse( $this->job->run_task( 'cancel' ) );
	}

	public function test_full_error_lifecycle_runs_cancel_cleanup_without_emitting_success(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null )
			->set( 'archive_task_list', array( 'fetch_urls' ) );
		$error_task = new ArchiveTaskDouble();
		$error_task->result = new \WP_Error( 'fetch_failed', 'Origin returned 503' );
		$cancel_task = new ArchiveTaskDouble();
		$cancel_task->result = true;
		$job = new ArchiveFailureLifecycleHarness( $this->options );
		$job->task_objects = array(
			'fetch_urls' => $error_task,
			'cancel'     => $cancel_task,
		);
		$completion_results = array();
		add_action(
			'ss_completed',
			static function ( string $result ) use ( &$completion_results ): void {
				$completion_results[] = $result;
			},
			10,
			1
		);

		self::assertSame( 'cancel', $job->run_handle() );

		self::assertSame( array( 'error' ), $completion_results );
		self::assertSame( 1, $error_task->perform_calls );
		self::assertSame( 1, $cancel_task->perform_calls );
		self::assertNull( $job->batch );
		self::assertArrayNotHasKey( 'done', $this->options->get( 'archive_status_messages' ) );
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
		self::assertSame( 1, $job->clear_calls );
		self::assertSame( 0, $job->dispatch_calls );
	}

	public function test_missing_task_full_lifecycle_is_terminal_and_never_reports_success(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null )
			->set( 'archive_task_list', array( 'fetch_urls' ) );
		$cancel_task = new ArchiveTaskDouble();
		$cancel_task->result = true;
		$job = new ArchiveFailureLifecycleHarness( $this->options );
		$job->task_objects = array( 'cancel' => $cancel_task );
		$completion_results = array();
		add_action(
			'ss_completed',
			static function ( string $result ) use ( &$completion_results ): void {
				$completion_results[] = $result;
			}
		);

		self::assertSame( 'cancel', $job->run_handle() );

		self::assertSame( array( 'error' ), $completion_results );
		self::assertSame( 1, $cancel_task->perform_calls );
		self::assertNull( $job->batch );
		self::assertStringContainsString(
			'class is unavailable',
			$this->options->get( 'archive_status_messages' )['error']['message']
		);
		self::assertArrayNotHasKey( 'done', $this->options->get( 'archive_status_messages' ) );
	}

	public function test_progress_combines_task_position_with_countable_and_zip_progress(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null )
			->set( 'zip_total_files', 200 )
			->set( 'zip_batch_offset', 50 );
		$countable = new ArchiveTaskDouble();
		$countable->processed_pages = 50;
		$countable->total_pages     = 100;
		$this->job->task_objects['fetch_urls'] = $countable;
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls', 'create_zip_archive', 'wrapup' );
			}
		);

		$this->job->batches = array( (object) array( 'data' => array( 'fetch_urls' ) ) );
		self::assertSame( 37, $this->job->get_progress() );

		$this->job->batches = array( (object) array( 'data' => array( 'create_zip_archive' ) ) );
		self::assertSame( 56, $this->job->get_progress() );
		self::assertSame( 25, $this->job->task_progress( 'create_zip_archive' ) );

		$this->job->batches = array( (object) array( 'data' => array( 'unknown_task' ) ) );
		self::assertSame( 0, $this->job->get_progress() );

		$this->job->batches = array( (object) array( 'data' => array( 12, 'done' ) ) );
		self::assertSame( 100, $this->job->get_progress() );
	}

	public function test_progress_handles_invalid_or_throwing_task_counters(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null );
		$task = new ArchiveTaskDouble();
		$this->job->task_objects['fetch_urls'] = $task;

		self::assertNull( $this->job->task_progress( 'fetch_urls' ) );
		$task->total_pages = 10;
		$task->processed_pages = 50;
		self::assertSame( 100, $this->job->task_progress( 'fetch_urls' ) );
		$task->throw_on_progress = true;
		self::assertNull( $this->job->task_progress( 'fetch_urls' ) );
	}

	public function test_job_done_and_running_respect_timestamps_pause_and_cancel_states(): void {
		self::assertTrue( $this->job->is_job_done() );
		self::assertFalse( $this->job->is_running() );

		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null );
		self::assertFalse( $this->job->is_job_done() );
		self::assertTrue( $this->job->is_running() );

		$this->job->paused_for_test = true;
		self::assertFalse( $this->job->is_running() );
		$this->job->paused_for_test    = false;
		$this->job->cancelled_for_test = true;
		self::assertFalse( $this->job->is_running() );

		$this->job->cancelled_for_test = false;
		$this->options->set( 'archive_end_time', '2026-07-12 11:00:00' );
		self::assertTrue( $this->job->is_job_done() );
		self::assertSame( 100, $this->job->get_progress() );
	}

	public function test_cancelling_paused_job_sets_terminal_status_before_cleanup_without_resuming(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null )
			->set( 'archive_task_list', array( 'setup', 'fetch_urls', 'wrapup' ) );
		$cancel_task = new ArchiveCancelTaskDouble();
		$this->job->cancel_task_for_test = $cancel_task;
		$this->job->paused_for_test      = true;

		$this->job->cancel();

		self::assertSame( 1, $cancel_task->perform_calls );
		self::assertSame( Background_Process::STATUS_CANCELLED, $cancel_task->status_seen );
		self::assertSame( Background_Process::STATUS_CANCELLED, WpEnv::$options['wp_archive_creation_job_status'] );
		self::assertSame( 0, $this->job->schedule_calls );
		self::assertSame( 1, $this->job->dispatch_calls );
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_end_time' ) );
	}

	public function test_new_export_cannot_start_until_cancelled_queue_state_is_cleared(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null )
			->set( 'archive_task_list', array( 'setup', 'fetch_urls' ) );
		$this->job->cancel_task_for_test = new ArchiveCancelTaskDouble();
		$this->job->cancel();
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls', 'wrapup' );
			}
		);

		self::assertFalse( $this->job->start( 1, 'export' ) );
		self::assertSame( array(), $this->job->queued );
		self::assertSame( 1, $this->job->dispatch_calls );
		self::assertSame( Background_Process::STATUS_CANCELLED, WpEnv::$options['wp_archive_creation_job_status'] );
		self::assertContains( 'ss_archive_creation_job_already_running', WpEnv::$action_log );
	}

	public function test_completion_records_duration_clears_status_and_emits_lifecycle_actions(): void {
		$this->options
			->set( 'archive_start_time', '2026-07-12 10:00:00' )
			->set( 'archive_end_time', null );
		WpEnv::$options['wp_archive_creation_job_status'] = 2;

		$this->job->finish_for_test();

		self::assertSame( 'done', $this->job->get_current_task() );
		self::assertSame( '2026-07-12 12:00:00', $this->options->get( 'archive_end_time' ) );
		self::assertStringContainsString(
			'02:00:00',
			$this->options->get( 'archive_status_messages' )['done']['message']
		);
		self::assertArrayNotHasKey( 'wp_archive_creation_job_status', WpEnv::$options );
		self::assertSame( 1, $this->job->clear_event_calls );
		self::assertContains( 'wp_archive_creation_job_completed', WpEnv::$action_log );
		self::assertContains( 'ss_completed', WpEnv::$action_log );
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
	}

	public function test_next_task_runtime_reset_shutdown_guard_and_die_override_are_deterministic(): void {
		add_filter(
			'simplystatic.archive_creation_job.task_list',
			static function (): array {
				return array( 'setup', 'fetch_urls' );
			}
		);
		$this->job->set_current_task_for_test( 'setup' );
		self::assertSame( 'fetch_urls', $this->job->next_task() );
		$this->job->set_current_task_for_test( 'not_registered' );
		self::assertNull( $this->job->next_task() );

		$this->job->set_runtime_state_for_test();
		$this->job->reset_runtime_state();
		self::assertSame(
			array( 'current_task' => null, 'task_list' => array(), 'processing' => false ),
			$this->job->runtime_state_for_test()
		);
		self::assertSame( array(), $this->options->get( 'archive_task_list' ) );
		$this->job->shutdown_handler();
		self::assertSame( 0, $this->job->clear_event_calls );
		self::assertSame( 'cancel', $this->job->maybe_wp_die( 'ignored' ) );
	}

	private function register_multisite_start_hooks(): Multisite {
		$multisite = ( new \ReflectionClass( Multisite::class ) )->newInstanceWithoutConstructor();
		add_action( 'ss_archive_creation_job_before_start', array( $multisite, 'switch_to_blog' ), 10, 2 );
		add_action( 'ss_archive_creation_job_before_start', array( $multisite, 'add_export' ), 40, 2 );
		add_action( 'ss_archive_creation_job_start_failed', array( $multisite, 'handle_start_failure' ), 99, 2 );

		return $multisite;
	}
}
