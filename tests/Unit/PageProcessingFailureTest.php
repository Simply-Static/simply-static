<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Task;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/class-ss-skip-further-processing-exception.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';

final class PageProcessingWpdb {

	/** @var string[] */
	public $queries = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/**
	 * @param string $query SQL query.
	 * @param mixed  ...$args Query arguments.
	 */
	public function prepare( $query, ...$args ): string {
		return $query;
	}

	public function query( string $query ): int {
		$this->queries[] = $query;

		return 1;
	}
}

final class RetryStatePage {

	/** @var int */
	public $id = 1;

	/** @var string */
	public $url = 'https://example.test/page/';

	/** @var string|null */
	public $last_transferred_at;

	/** @var int */
	public $fetch_attempts;

	/** @var string */
	public $error_message = '';

	/** @var array<int,int> */
	public $saved_attempts = array();

	public function __construct( int $fetch_attempts = 0 ) {
		$this->fetch_attempts = $fetch_attempts;
	}

	public function save(): bool {
		$this->saved_attempts[] = $this->fetch_attempts;

		return true;
	}

	public function set_error_message( string $message ): void {
		$this->error_message = $message;
	}
}

final class RetryStateTask extends Task {

	use \Simply_Static\canProcessPages;

	/** @var RetryStatePage */
	private $page;

	/** @var bool */
	private $should_fail;

	/** @var int */
	private $processed_pages = 0;

	/** @var \Throwable|null */
	public $failure;

	/** @var int */
	public $yield_calls = 0;

	/** @var bool */
	public $exclude_exhausted_from_total = false;

	public function __construct( RetryStatePage $page, bool $should_fail = false ) {
		parent::__construct();
		$this->page        = $page;
		$this->should_fail = $should_fail;
	}

	/** @return bool */
	public function perform() {
		return $this->process_pages();
	}

	/** @return RetryStatePage[] */
	public function get_pages_to_process() {
		return $this->page->fetch_attempts < 3 ? array( $this->page ) : array();
	}

	public function get_processed_pages() {
		return $this->processed_pages;
	}

	public function get_total_pages( $cached = true ) {
		return $this->exclude_exhausted_from_total && $this->page->fetch_attempts >= 3 ? 0 : 1;
	}

	protected function get_exhausted_pages_count() {
		return $this->page->fetch_attempts >= 3 ? 1 : 0;
	}

	protected function process_page( $static_page ) {
		if ( $this->failure ) {
			throw $this->failure;
		}

		if ( $this->should_fail ) {
			throw new \RuntimeException( 'Transient fetch failure' );
		}

		++$this->processed_pages;
	}

	protected function check_if_running() {
	}

	protected function maybe_refresh_process_lock() {
	}

	protected function maybe_yield_process() {
		++$this->yield_calls;
	}
}

final class ExhaustedPagesTask extends Task {

	use \Simply_Static\canProcessPages;

	/** @var int */
	public $failed_pages = 0;

	/** @var int */
	public $processed_pages = 0;

	/** @var int */
	public $total_pages = 0;

	/** @return bool */
	public function perform() {
		return $this->process_pages();
	}

	/** @return array<int,mixed> */
	public function get_pages_to_process() {
		return array();
	}

	public function get_processed_pages() {
		return $this->processed_pages;
	}

	public function get_total_pages( $cached = true ) {
		return $this->total_pages;
	}

	protected function get_exhausted_pages_count() {
		return $this->failed_pages;
	}
}

final class PageProcessingFailureTest extends UnitTestCase {

	/** @var PageProcessingWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb       = new PageProcessingWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
		WpEnv::$options['simply-static'] = array(
			'archive_status_messages' => array(),
		);
		Options::reinstance();
	}

	public function test_exhausted_rows_are_skipped_without_failing_the_export(): void {
		$task                  = new ExhaustedPagesTask();
		$task->failed_pages    = 2;
		$task->processed_pages = 3;
		$task->total_pages     = 5;

		$result = $task->perform();
		$messages = Options::instance()->get( 'archive_status_messages' );

		self::assertTrue( $result );
		self::assertStringContainsString( '3 of 5', $messages['task']['message'] );
		self::assertStringContainsString(
			'Skipped 2 pages or files',
			$messages['task_warning']['message']
		);
		self::assertArrayNotHasKey( 'task_error', $messages );
	}

	public function test_empty_queue_is_successful_when_no_rows_failed(): void {
		$task                  = new ExhaustedPagesTask();
		$task->processed_pages = 5;
		$task->total_pages     = 5;

		self::assertTrue( $task->perform() );
	}

	public function test_success_resets_fetch_attempts_after_the_stage_completes(): void {
		$page = new RetryStatePage( 2 );
		$task = new RetryStateTask( $page );

		self::assertTrue( $task->perform() );
		self::assertSame( 0, $page->fetch_attempts );
		self::assertSame( array( 3, 0 ), $page->saved_attempts );
	}

	public function test_failures_retain_attempts_and_exhaust_the_allowance(): void {
		$page = new RetryStatePage();
		$task = new RetryStateTask( $page, true );

		self::assertFalse( $task->perform() );
		self::assertSame( 1, $page->fetch_attempts );
		self::assertNull( $page->last_transferred_at );
		self::assertFalse( $task->perform() );
		self::assertSame( 2, $page->fetch_attempts );
		self::assertFalse( $task->perform() );
		self::assertSame( 3, $page->fetch_attempts );

		self::assertTrue( $task->perform() );
		self::assertSame( 'Transient fetch failure', $page->error_message );
	}

	public function test_active_worker_budget_is_checked_before_claiming_a_page(): void {
		$job = new class {
			public $active = true;
			public $continue_calls = 0;

			public function is_worker_active(): bool {
				return $this->active;
			}

			public function should_continue(): bool {
				++$this->continue_calls;
				return false;
			}
		};
		$plugin_class = new \ReflectionClass( \Simply_Static\Plugin::class );
		$plugin = $plugin_class->newInstanceWithoutConstructor();
		$instance_property = $plugin_class->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$previous_plugin = $instance_property->getValue();
		$job_property = $plugin_class->getProperty( 'archive_creation_job' );
		$job_property->setAccessible( true );
		$job_property->setValue( $plugin, $job );
		$instance_property->setValue( null, $plugin );

		try {
			$page = new RetryStatePage();
			$task = new RetryStateTask( $page );

			self::assertFalse( $task->perform() );
			self::assertSame( 1, $job->continue_calls );
			self::assertSame( 0, $page->fetch_attempts );
			self::assertSame( array(), $page->saved_attempts );
			self::assertSame( array(), $this->wpdb->queries );

			// A direct task call with an idle job keeps its normal behavior.
			$job->active = false;
			self::assertTrue( $task->perform() );
			self::assertSame( 1, $job->continue_calls );
			self::assertSame( array( 1, 0 ), $page->saved_attempts );
		} finally {
			$instance_property->setValue( null, $previous_plugin );
		}
	}

	public function test_transient_failure_releases_the_claim_and_requests_a_fresh_worker(): void {
		$page = new RetryStatePage();
		$task = new RetryStateTask( $page );
		$task->failure = new \Simply_Static\Skip_Further_Processing_Exception( 'Origin overloaded' );

		self::assertFalse( $task->perform() );
		self::assertSame( 1, $page->fetch_attempts );
		self::assertNull( $page->last_transferred_at );
		self::assertSame( 'Origin overloaded', $page->error_message );
		self::assertSame( 1, $task->yield_calls );
	}

	public function test_final_failed_attempt_still_records_a_warning_when_totals_exclude_it(): void {
		$page = new RetryStatePage( 2 );
		$task = new RetryStateTask( $page );
		$task->exclude_exhausted_from_total = true;
		$task->failure = new \Simply_Static\Skip_Further_Processing_Exception( 'Origin overloaded' );

		self::assertFalse( $task->perform() );
		self::assertSame( 3, $page->fetch_attempts );
		self::assertSame( 0, $task->get_total_pages() );
		self::assertTrue( $task->perform() );
		self::assertStringContainsString(
			'Skipped 1 page or file',
			Options::instance()->get( 'archive_status_messages' )['task_warning']['message']
		);
	}

	public function test_throwables_release_the_in_memory_claim_before_saving(): void {
		$page = new RetryStatePage();
		$task = new RetryStateTask( $page );
		$task->failure = new \Error( 'Handler failed' );

		self::assertFalse( $task->perform() );
		self::assertSame( 1, $page->fetch_attempts );
		self::assertNull( $page->last_transferred_at );
		self::assertSame( 'Handler failed', $page->error_message );
	}

	public function test_later_stage_gets_a_fresh_retry_allowance_after_success(): void {
		$page        = new RetryStatePage( 2 );
		$core_stage  = new RetryStateTask( $page );
		$pro_stage   = new RetryStateTask( $page, true );

		self::assertTrue( $core_stage->perform() );
		self::assertSame( 0, $page->fetch_attempts );
		self::assertFalse( $pro_stage->perform() );
		self::assertSame( 1, $page->fetch_attempts );
	}
}
