<?php

declare(strict_types=1);

namespace Simply_Static {
	if ( ! function_exists( __NAMESPACE__ . '\\copy' ) ) {
		/**
		 * Test seam for destination-specific copy failures.
		 *
		 * @param string        $source      Source path.
		 * @param string        $destination Destination path.
		 * @param resource|null $context     Optional stream context.
		 *
		 * @return bool
		 */
		function copy( $source, $destination, $context = null ) {
			$operations = isset( $GLOBALS['simply_static_test_file_operations'] )
				? $GLOBALS['simply_static_test_file_operations']
				: array();

			if ( isset( $operations['fail_copy_to'] ) && $operations['fail_copy_to'] === $destination ) {
				return false;
			}

			return null === $context
				? \copy( $source, $destination )
				: \copy( $source, $destination, $context );
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\rename' ) ) {
		/**
		 * Test seam for destination-specific rename failures.
		 *
		 * @param string        $source      Source path.
		 * @param string        $destination Destination path.
		 * @param resource|null $context     Optional stream context.
		 *
		 * @return bool
		 */
		function rename( $source, $destination, $context = null ) {
			$operations = isset( $GLOBALS['simply_static_test_file_operations'] )
				? $GLOBALS['simply_static_test_file_operations']
				: array();

			if ( isset( $operations['fail_rename_to'] ) && $operations['fail_rename_to'] === $destination ) {
				return false;
			}

			return null === $context
				? \rename( $source, $destination )
				: \rename( $source, $destination, $context );
		}
	}

	if ( ! function_exists( __NAMESPACE__ . '\\wp_tempnam' ) ) {
		/**
		 * Record temporary fetch files so cleanup behavior can be asserted.
		 *
		 * @param string $filename Optional filename hint.
		 *
		 * @return string|false
		 */
		function wp_tempnam( $filename = '' ) {
			$temp_file = \wp_tempnam( $filename );

			if ( isset( $GLOBALS['simply_static_test_file_operations'] ) && is_array( $GLOBALS['simply_static_test_file_operations'] ) ) {
				$GLOBALS['simply_static_test_file_operations']['temp_files'][] = $temp_file;
			}

			return $temp_file;
		}
	}
}

namespace {
	$simply_static_root = dirname( __DIR__, 2 );
	require_once $simply_static_root . '/src/class-ss-plugin.php';
	require_once $simply_static_root . '/src/class-ss-options.php';
	require_once $simply_static_root . '/src/class-ss-phpuri.php';
	require_once $simply_static_root . '/src/class-ss-util.php';
	require_once $simply_static_root . '/src/class-ss-query.php';
	require_once $simply_static_root . '/src/models/class-ss-model.php';
	require_once $simply_static_root . '/src/models/class-ss-page.php';
	require_once $simply_static_root . '/src/handlers/class-ss-page-handler.php';
	require_once $simply_static_root . '/src/class-ss-url-fetcher.php';
	require_once $simply_static_root . '/src/tasks/class-ss-task.php';
	require_once $simply_static_root . '/src/tasks/traits/trait-ss-can-process-pages.php';
	require_once $simply_static_root . '/src/tasks/traits/trait-ss-can-transfer.php';
	require_once $simply_static_root . '/src/tasks/class-ss-transfer-files-locally-task.php';
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Options;
	use Simply_Static\Page;
	use Simply_Static\Page_Handler;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
	use Simply_Static\Transfer_Files_Locally_Task;
	use Simply_Static\Url_Fetcher;

	final class FileIntegrityPageHandler extends Page_Handler {
		/** @var int */
		public $after_fetch_calls = 0;

		public function after_file_fetch( $destination_dir ) {
			$this->after_fetch_calls++;
		}
	}

	final class FileIntegrityPage extends Page {
		/** @var int */
		public $save_calls = 0;

		/** @var FileIntegrityPageHandler|null */
		private $test_handler;

		public function save() {
			$this->save_calls++;

			return true;
		}

		public function get_handler() {
			if ( null === $this->test_handler ) {
				$this->test_handler = new FileIntegrityPageHandler( $this );
			}

			return $this->test_handler;
		}
	}

	final class TestableTransferFilesLocallyTask extends Transfer_Files_Locally_Task {
		public function configure_directories( string $archive_dir, string $destination_dir ): void {
			$this->archive_dir     = $archive_dir;
			$this->destination_dir = $destination_dir;
		}

		public function transfer_page( Page $page ): void {
			$this->process_page( $page );
		}
	}

	final class FileIntegrityTest extends UnitTestCase {
		/** @var string */
		private $archive_dir;

		/** @var string */
		private $destination_dir;

		/** @var Url_Fetcher */
		private $fetcher;

		protected function setUp(): void {
			parent::setUp();

			$token                 = str_replace( '.', '', uniqid( 'file-integrity-', true ) );
			$this->archive_dir      = sys_get_temp_dir() . '/' . $token . '/archive';
			$this->destination_dir  = sys_get_temp_dir() . '/' . $token . '/destination';
			$GLOBALS['simply_static_test_file_operations'] = array( 'temp_files' => array() );

			wp_mkdir_p( $this->archive_dir );
			wp_mkdir_p( $this->destination_dir );

			WpEnv::$options['simply-static'] = array(
				'archive_name'               => 'archive',
				'archive_start_time'         => '2026-07-12 10:00:00',
				'debugging_mode'             => false,
				'http_basic_auth_username'   => '',
				'http_basic_auth_password'   => '',
				'use_search'                 => false,
				'use_search_results_page'    => false,
			);
			Options::reinstance();

			$this->resetFetcher();
			$this->fetcher = Url_Fetcher::instance();
			$archive_property = new \ReflectionProperty( Url_Fetcher::class, 'archive_dir' );
			$archive_property->setAccessible( true );
			$archive_property->setValue( $this->fetcher, trailingslashit( $this->archive_dir ) );
		}

		protected function tearDown(): void {
			$this->resetFetcher();
			$this->removeTree( dirname( $this->archive_dir ) );
			unset( $GLOBALS['simply_static_test_file_operations'] );
			parent::tearDown();
		}

		public function test_genuine_zero_byte_http_response_stays_zero_bytes(): void {
			WpEnv::$remote_response = array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'text/html' ),
				'body'     => '',
			);
			$page = $this->pageFor( 'https://example.test/empty' );

			self::assertTrue( $this->fetcher->fetch( $page, false ) );

			$file_path = $this->archive_dir . '/empty/index.html';
			self::assertFileExists( $file_path );
			self::assertSame( 0, filesize( $file_path ) );
			self::assertSame( '', file_get_contents( $file_path ) );
			self::assertSame( 1, $page->get_handler()->after_fetch_calls );
		}

		public function test_failed_archive_rename_and_copy_returns_false_records_error_and_cleans_temp_file(): void {
			WpEnv::$remote_response = array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'text/html' ),
				'body'     => 'fetched body',
			);
			$destination = $this->archive_dir . '/failed.html';
			$GLOBALS['simply_static_test_file_operations']['fail_rename_to'] = $destination;
			$GLOBALS['simply_static_test_file_operations']['fail_copy_to']   = $destination;
			$page = $this->pageFor( 'https://example.test/failed.html' );

			self::assertFalse( $this->fetcher->fetch( $page, false ) );
			self::assertSame( 'Failed to save fetched file to archive', $page->error_message );
			self::assertSame( 1, $page->save_calls );
			self::assertSame( 0, $page->get_handler()->after_fetch_calls );
			self::assertFileDoesNotExist( $destination );

			$temp_files = $GLOBALS['simply_static_test_file_operations']['temp_files'];
			self::assertCount( 1, $temp_files );
			self::assertFileDoesNotExist( $temp_files[0] );
		}

		public function test_missing_transfer_source_throws_without_stamp_or_success_hook(): void {
			$page   = $this->transferPage( 'missing/index.html' );
			$events = $this->captureTransferEvents();
			$task   = $this->transferTask();

			try {
				$task->transfer_page( $page );
				self::fail( 'Expected a missing source file to throw.' );
			} catch ( \RuntimeException $exception ) {
				self::assertSame( 'Unable to find file in archive', $exception->getMessage() );
			}

			self::assertNull( $page->last_transferred_at );
			self::assertSame( 0, $page->save_calls );
			self::assertCount( 0, $events );
		}

		public function test_unavailable_local_destination_fails_the_transfer_task(): void {
			$blocker = $this->destination_dir . '/not-a-directory';
			file_put_contents( $blocker, 'file' );
			WpEnv::$options['simply-static']['local_dir'] = $blocker . '/child';
			Options::reinstance();

			$result = ( new Transfer_Files_Locally_Task() )->perform();

			self::assertInstanceOf( \WP_Error::class, $result );
			self::assertSame( 'ss_local_destination_unavailable', $result->get_error_code() );
		}

		public function test_transfer_copy_failure_throws_without_stamp_or_success_hook(): void {
			$file_path = 'copy-failure/index.html';
			$source    = $this->archive_dir . '/' . $file_path;
			$destination = $this->destination_dir . '/' . $file_path;
			wp_mkdir_p( dirname( $source ) );
			file_put_contents( $source, 'copy me' );
			$GLOBALS['simply_static_test_file_operations']['fail_copy_to'] = $destination;
			$page   = $this->transferPage( $file_path );
			$events = $this->captureTransferEvents();
			$task   = $this->transferTask();

			try {
				$task->transfer_page( $page );
				self::fail( 'Expected a failed transfer copy to throw.' );
			} catch ( \RuntimeException $exception ) {
				self::assertSame( 'Unable to copy file to destination', $exception->getMessage() );
			}

			self::assertNull( $page->last_transferred_at );
			self::assertSame( 0, $page->save_calls );
			self::assertCount( 0, $events );
			self::assertFileDoesNotExist( $destination );
		}

		public function test_successful_transfer_stamps_saves_and_fires_success_hook(): void {
			$file_path = 'success/index.html';
			$source    = $this->archive_dir . '/' . $file_path;
			wp_mkdir_p( dirname( $source ) );
			file_put_contents( $source, 'success' );
			$page   = $this->transferPage( $file_path );
			$events = $this->captureTransferEvents();
			$task   = $this->transferTask();

			$task->transfer_page( $page );

			self::assertSame( 'success', file_get_contents( $this->destination_dir . '/' . $file_path ) );
			self::assertSame( '2026-07-12 12:00:00', $page->last_transferred_at );
			self::assertSame( 1, $page->save_calls );
			self::assertCount( 1, $events );
			self::assertSame( $page, $events[0]['page'] );
			self::assertSame( $this->destination_dir, $events[0]['destination'] );
		}

		private function pageFor( string $url ): FileIntegrityPage {
			return FileIntegrityPage::initialize(
				array(
					'id'  => 1,
					'url' => $url,
				)
			);
		}

		private function transferPage( string $file_path ): FileIntegrityPage {
			return FileIntegrityPage::initialize(
				array(
					'id'                  => 1,
					'url'                 => 'https://example.test/' . $file_path,
					'file_path'           => $file_path,
					'last_transferred_at' => null,
				)
			);
		}

		private function transferTask(): TestableTransferFilesLocallyTask {
			$task = new TestableTransferFilesLocallyTask();
			$task->configure_directories( $this->archive_dir, $this->destination_dir );

			return $task;
		}

		/** @return \ArrayObject<int,array{page:Page,destination:string}> */
		private function captureTransferEvents(): \ArrayObject {
			$events = new \ArrayObject();
			add_action(
				'simply_static_page_file_transferred',
				static function ( $page, $destination ) use ( $events ) {
					$events->append( array(
						'page'        => $page,
						'destination' => $destination,
					) );
				},
				10,
				2
			);

			return $events;
		}

		private function resetFetcher(): void {
			$instance_property = new \ReflectionProperty( Url_Fetcher::class, 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null, null );
		}

		private function removeTree( string $path ): void {
			if ( is_link( $path ) || is_file( $path ) ) {
				unlink( $path );
				return;
			}

			if ( ! is_dir( $path ) ) {
				return;
			}

			$items = scandir( $path );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( '.' === $item || '..' === $item ) {
						continue;
					}

					$this->removeTree( $path . '/' . $item );
				}
			}

			rmdir( $path );
		}
	}
}
