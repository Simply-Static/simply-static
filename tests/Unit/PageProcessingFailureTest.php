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
		return 1;
	}

	protected function get_exhausted_pages_count() {
		return $this->page->fetch_attempts >= 3 ? 1 : 0;
	}

	protected function process_page( $static_page ) {
		if ( $this->should_fail ) {
			throw new \RuntimeException( 'Transient fetch failure' );
		}

		++$this->processed_pages;
	}

	protected function check_if_running() {
	}

	protected function maybe_refresh_process_lock() {
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
		self::assertFalse( $task->perform() );
		self::assertSame( 2, $page->fetch_attempts );
		self::assertFalse( $task->perform() );
		self::assertSame( 3, $page->fetch_attempts );

		self::assertTrue( $task->perform() );
		self::assertSame( 'Transient fetch failure', $page->error_message );
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
