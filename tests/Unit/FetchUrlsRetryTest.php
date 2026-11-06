<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionProperty;
use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Page_Handler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/class-ss-skip-further-processing-exception.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-fetch-urls-task.php';

final class FetchRetryWpdb {

	public $row;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function prepare( string $query, ...$args ): string {
		foreach ( $args as $value ) {
			$replacement = is_int( $value ) ? (string) $value : "'" . addslashes( (string) $value ) . "'";
			$query = preg_replace( '/%[sd]/', $replacement, $query, 1 );
		}

		return $query;
	}

	public function query( string $query ): int {
		if ( false !== strpos( $query, 'SET last_checked_at = NULL WHERE id = ' ) ) {
			$this->row['last_checked_at'] = null;
			return 1;
		}

		if ( preg_match( "/SET last_checked_at = '([^']+)', fetch_attempts = ([0-9]+), updated_at = '([^']+)'/", $query, $matches ) ) {
			$this->row['last_checked_at'] = $matches[1];
			$this->row['fetch_attempts'] = (int) $matches[2];
			$this->row['updated_at'] = $matches[3];
			return 1;
		}

		throw new \RuntimeException( 'Unexpected fetch-retry query: ' . $query );
	}

	public function update( string $table, array $fields, array $where ): int {
		$this->row = array_merge( $this->row, $fields );
		return 1;
	}
}

final class FetchRetryTask extends Fetch_Urls_Task {

	private $database;

	public function __construct( FetchRetryWpdb $database ) {
		parent::__construct();
		$this->database = $database;
	}

	public function get_pages_to_process() {
		$row = $this->database->row;
		if ( $row['fetch_attempts'] >= 3 || ( null !== $row['last_checked_at'] && $row['last_checked_at'] >= $this->get_start_time() ) ) {
			return array();
		}

		return array( Page::initialize( $row ) );
	}

	public function get_processed_pages() {
		return null !== $this->database->row['last_checked_at'] && $this->database->row['last_checked_at'] >= $this->get_start_time() ? 1 : 0;
	}

	public function get_total_pages( $cached = true ) {
		return 1;
	}

	protected function get_exhausted_pages_count() {
		return $this->database->row['fetch_attempts'] >= 3 ? 1 : 0;
	}

	protected function check_if_running() {}
	protected function maybe_refresh_process_lock() {}

	// Keep these tests focused on fetch/retry behavior and real file generation.
	protected function can_extract_urls_from_static_page( $static_page ) {
		return false;
	}
}

final class FetchUrlsRetryTest extends UnitTestCase {

	private $database;
	private $previous_wpdb;
	private $temporary_dir;

	protected function setUp(): void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->database = new FetchRetryWpdb();
		$GLOBALS['wpdb'] = $this->database;
		$this->temporary_dir = sys_get_temp_dir() . '/' . uniqid( 'ss-fetch-retry-', true );
		WpEnv::$options['simply-static'] = array(
			'archive_name'            => 'retry-export',
			'temp_files_dir'          => $this->temporary_dir,
			'archive_start_time'      => '2026-07-12 11:00:00',
			'archive_status_messages' => array(),
			'generate_type'          => 'export',
		);
		// Skip full-site homepage validation for this single-page fixture.
		WpEnv::$options['simply-static-use-single'] = '42';
		$this->database->row = array(
			'id'              => 1,
			'site_id'         => 1,
			'post_id'         => 42,
			'url'             => 'https://example.test/page/',
			'file_path'       => 'older-export/index.html',
			'handler'         => Page_Handler::class,
			'fetch_attempts'  => 0,
			'last_checked_at' => null,
			'updated_at'      => '2026-07-12 11:00:00',
		);
		Options::reinstance();
		$this->reset_fetcher();
	}

	protected function tearDown(): void {
		$this->reset_fetcher();
		if ( is_dir( $this->temporary_dir ) ) {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $this->temporary_dir, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $files as $file ) {
				$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
			}
			rmdir( $this->temporary_dir );
		}
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	/** @dataProvider transient_statuses */
	public function test_temporary_http_failure_is_retried_and_success_resets_attempts( int $status ): void {
		$this->respond( $status );

		self::assertFalse( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertSame( 1, $this->database->row['fetch_attempts'] );
		self::assertNull( $this->database->row['last_checked_at'] );
		self::assertNull( $this->database->row['file_path'] );
		self::assertStringContainsString( 'HTTP ' . $status, $this->database->row['error_message'] );
		self::assertFileDoesNotExist( Options::instance()->get_archive_dir() . 'page/index.html' );
		self::assertCount( 1, WpEnv::$remote_requests );

		$this->respond( 200 );
		self::assertTrue( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertSame( 0, $this->database->row['fetch_attempts'] );
		self::assertSame( '2026-07-12 12:00:00', $this->database->row['last_checked_at'] );
		self::assertNull( $this->database->row['error_message'] );
		self::assertSame( 'page/index.html', $this->database->row['file_path'] );
		self::assertSame( '<html>Page</html>', file_get_contents( Options::instance()->get_archive_dir() . 'page/index.html' ) );
	}

	public static function transient_statuses(): array {
		return array( array( 408 ), array( 429 ), array( 500 ), array( 502 ), array( 503 ), array( 504 ), array( 599 ) );
	}

	public function test_transport_failure_preserves_details_and_exhausts_the_retry_allowance(): void {
		WpEnv::$remote_response = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );

		for ( $attempt = 1; $attempt <= 3; ++$attempt ) {
			self::assertFalse( ( new FetchRetryTask( $this->database ) )->perform() );
			self::assertSame( $attempt, $this->database->row['fetch_attempts'] );
			self::assertNull( $this->database->row['last_checked_at'] );
			self::assertNull( $this->database->row['file_path'] );
			self::assertStringContainsString( 'cURL error 28', $this->database->row['error_message'] );
		}

		self::assertTrue( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertCount( 3, WpEnv::$remote_requests );
		self::assertStringContainsString( 'Skipped 1 page or file', Options::instance()->get( 'archive_status_messages' )['fetch_urls_warning']['message'] );
	}

	public function test_permanent_not_found_response_is_not_retried_as_a_transient_error(): void {
		$this->respond( 404 );

		self::assertTrue( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertSame( 0, $this->database->row['fetch_attempts'] );
		self::assertSame( 404, $this->database->row['http_status_code'] );
		self::assertSame( '2026-07-12 12:00:00', $this->database->row['last_checked_at'] );
		// The existing fallback removes the page-handler parameter once.
		self::assertCount( 2, WpEnv::$remote_requests );
		self::assertStringContainsString( '?simply_static_page=', WpEnv::$remote_requests[0]['url'] );
		self::assertSame( 'https://example.test/page/', WpEnv::$remote_requests[1]['url'] );
	}

	public function test_transient_error_from_the_unprepared_url_fallback_stays_pending(): void {
		$this->respond( 404 );
		add_filter( 'ss_remote_get_args', function ( array $args ): array {
			if ( count( WpEnv::$remote_requests ) === 1 ) {
				$this->respond( 503 );
			}
			return $args;
		} );

		self::assertFalse( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertSame( 1, $this->database->row['fetch_attempts'] );
		self::assertNull( $this->database->row['last_checked_at'] );
		self::assertNull( $this->database->row['file_path'] );
		self::assertStringContainsString( 'HTTP 503', $this->database->row['error_message'] );
		self::assertCount( 2, WpEnv::$remote_requests );
	}

	public function test_unprepared_url_fallback_shares_the_page_fetch_time_budget(): void {
		$this->respond( 404 );
		$filtered_page = null;
		add_filter( 'simply_static_fetch_timeout', static function ( $timeout, Page $page ) use ( &$filtered_page ) {
			$filtered_page = $page;
			return 10;
		}, 10, 2 );
		add_filter( 'ss_remote_get_args', static function ( array $args ): array {
			// A request-level override must not give the fallback a new budget.
			$args['timeout'] = 60;
			return $args;
		} );

		self::assertTrue( ( new FetchRetryTask( $this->database ) )->perform() );
		self::assertInstanceOf( Page::class, $filtered_page );
		self::assertCount( 2, WpEnv::$remote_requests );
		$first_timeout = WpEnv::$remote_requests[0]['args']['timeout'];
		$second_timeout = WpEnv::$remote_requests[1]['args']['timeout'];
		self::assertGreaterThan( 0, $second_timeout );
		self::assertLessThanOrEqual( 10, $first_timeout );
		self::assertLessThan( $first_timeout, $second_timeout );
	}

	private function respond( int $status ): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => $status ),
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '<html>Page</html>',
		);
	}

	private function reset_fetcher(): void {
		$property = new ReflectionProperty( Url_Fetcher::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}
}
