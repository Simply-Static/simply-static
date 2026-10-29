<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Transfer_Files_Locally_Task;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-transfer.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-fetch-urls-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-transfer-files-locally-task.php';

final class HomepageExportWpdb {
	/** @var array<string,mixed>|null */
	public $row;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function get_row( $query, $output = null ) {
		return $this->row;
	}
}

final class HomepageFetchTask extends Fetch_Urls_Task {

	/** @var bool|\WP_Error */
	public $processing_result = true;

	public function process_pages() {
		return $this->processing_result;
	}

	public function get_pages_to_process() {
		return array();
	}
}

final class HomepageTransferTask extends Transfer_Files_Locally_Task {

	public function process_pages() {
		return true;
	}
}

final class HomepageExportTest extends UnitTestCase {

	/** @var string */
	private $directory;

	/** @var mixed */
	private $previous_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->directory = sys_get_temp_dir() . '/' . uniqid( 'ss-homepage-', true );
		wp_mkdir_p( $this->directory );
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = new HomepageExportWpdb();
		WpEnv::$options['simply-static'] = array(
			'archive_start_time'      => '2026-10-29 09:00:00',
			'archive_status_messages' => array(),
			'generate_type'           => 'export',
			'local_dir'               => $this->directory,
		);
		Options::reinstance();
	}

	protected function tearDown(): void {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->directory, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $file ) {
			$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		rmdir( $this->directory );
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	public function test_full_export_fails_before_signaling_fetch_completion_when_homepage_is_missing(): void {
		$result = $this->fetchTask()->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'ss_missing_homepage_file', $result->get_error_code() );
		self::assertStringContainsString( 'index.html', $result->get_error_message() );
		self::assertStringContainsString( 'https://example.test/', $result->get_error_message() );
		self::assertNotContains( 'ss_finished_fetching_pages', WpEnv::$action_log );
	}

	public function test_empty_homepage_does_not_satisfy_full_export(): void {
		file_put_contents( $this->directory . '/index.html', '' );

		self::assertInstanceOf( \WP_Error::class, $this->fetchTask()->perform() );
	}

	public function test_missing_homepage_error_includes_stored_request_failure(): void {
		$GLOBALS['wpdb']->row = array(
			'id' => 7,
			'url' => 'https://example.test/',
			'http_status_code' => 403,
			'error_message' => 'The homepage request was forbidden.',
		);

		$result = $this->fetchTask()->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertStringContainsString( 'HTTP status: 403', $result->get_error_message() );
		self::assertStringContainsString( 'request was forbidden', $result->get_error_message() );
	}

	public function test_canonical_alias_can_supply_homepage_without_original_page_file_path(): void {
		file_put_contents( $this->directory . '/index.html', '<html>Home</html>' );

		self::assertTrue( $this->fetchTask()->perform() );
		self::assertContains( 'ss_finished_fetching_pages', WpEnv::$action_log );
	}

	public function test_language_redirect_is_a_valid_root_homepage(): void {
		file_put_contents( $this->directory . '/index.html', '<meta http-equiv="refresh" content="0;url=/en/">' );

		self::assertTrue( $this->fetchTask()->perform() );
	}

	public function test_integration_entry_points_are_required_before_fetch_completion(): void {
		file_put_contents( $this->directory . '/index.html', 'Home' );
		add_filter( 'ss_required_homepage_urls', static function ( $urls ) {
			$urls[] = 'https://example.test/es/';
			return $urls;
		} );

		$result = $this->fetchTask()->perform();
		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertStringContainsString( 'es/index.html', $result->get_error_message() );
		self::assertInstanceOf( \WP_Error::class, ( new HomepageTransferTask() )->perform() );

		wp_mkdir_p( $this->directory . '/es' );
		file_put_contents( $this->directory . '/es/index.html', 'Inicio' );
		self::assertTrue( $this->fetchTask()->perform() );
		self::assertTrue( ( new HomepageTransferTask() )->perform() );
	}

	public function test_subdirectory_origin_uses_archive_root_and_respects_extension_filter(): void {
		Options::instance()->set( 'origin_url', 'https://example.test/network/site/' );
		add_filter( 'ss_default_extension_type', static function () { return 'htm'; } );
		file_put_contents( $this->directory . '/index.htm', 'Home' );

		self::assertTrue( $this->fetchTask()->perform() );
	}

	public function test_filtered_homepage_path_is_validated(): void {
		$GLOBALS['wpdb']->row = array( 'id' => 7, 'url' => 'https://example.test/' );
		add_filter( 'simply_static_relative_filename', static function ( $filename, $page ) {
			return 7 === $page->id ? 'home.html' : $filename;
		}, 10, 2 );
		file_put_contents( $this->directory . '/home.html', 'Home' );

		self::assertTrue( $this->fetchTask()->perform() );
		unlink( $this->directory . '/home.html' );
		self::assertInstanceOf( \WP_Error::class, $this->fetchTask()->perform() );
	}

	/** @dataProvider partialExportProvider */
	public function test_partial_exports_do_not_require_a_homepage( string $type, string $scope ): void {
		Options::instance()->set( 'generate_type', $type );
		if ( '' !== $scope ) {
			update_option( $scope, 12 );
		}

		self::assertTrue( $this->fetchTask()->perform() );
		self::assertTrue( ( new HomepageTransferTask() )->perform() );
	}

	public function partialExportProvider(): array {
		return array(
			'changes only'  => array( 'update', '' ),
			'single page'   => array( 'export', 'simply-static-use-single' ),
			'build'         => array( 'export', 'simply-static-use-build' ),
			'404 only'      => array( 'export', 'simply-static-404-only' ),
		);
	}

	public function test_intentionally_excluded_homepage_is_allowed(): void {
		Options::instance()->set( 'urls_to_exclude', 'https://example.test/' );

		self::assertTrue( $this->fetchTask()->perform() );
		self::assertTrue( ( new HomepageTransferTask() )->perform() );
	}

	public function test_homepage_excluded_by_integration_is_allowed(): void {
		add_filter( 'ss_find_excludable', static function () { return true; } );

		self::assertTrue( $this->fetchTask()->perform() );
	}

	public function test_homepage_is_not_validated_while_fetching_is_pending(): void {
		$task = $this->fetchTask();
		$task->processing_result = false;

		self::assertFalse( $task->perform() );
	}

	public function test_local_transfer_reports_missing_homepage_instead_of_success(): void {
		$result = ( new HomepageTransferTask() )->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertNotContains( 'ss_finished_transferring_files_locally', WpEnv::$action_log );
	}

	public function test_local_transfer_checks_homepage_after_integration_files_are_copied(): void {
		add_action( 'ss_before_finish_transferring_files_locally', function () {
			file_put_contents( $this->directory . '/index.html', 'Home' );
		} );

		self::assertTrue( ( new HomepageTransferTask() )->perform() );
		self::assertContains( 'ss_finished_transferring_files_locally', WpEnv::$action_log );
	}

	public function test_local_transfer_respects_integration_filename_filter(): void {
		$GLOBALS['wpdb']->row = array( 'id' => 7, 'url' => 'https://example.test/' );
		add_filter( 'ss_get_page_file_path_for_transfer', static function ( $filename, $page ) {
			return 7 === $page->id ? 'home.htm' : $filename;
		}, 10, 2 );
		file_put_contents( $this->directory . '/home.htm', 'Home' );

		self::assertTrue( ( new HomepageTransferTask() )->perform() );
	}

	private function fetchTask(): HomepageFetchTask {
		$task = new HomepageFetchTask();
		$task->archive_dir = $this->directory;
		return $task;
	}
}
