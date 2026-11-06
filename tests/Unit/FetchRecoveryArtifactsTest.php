<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Create_Zip_Archive_Task;
use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-fetch-urls-task.php';

final class FetchRecoveryArtifactsWpdb {

	public $rows = array();
	public $reset_calls = 0;
	public $fail_read = false;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function prepare( string $query, string $start_time ): string {
		return str_replace( '%s', "'" . addslashes( $start_time ) . "'", $query );
	}

	public function get_results( string $query, $output = null ) {
		if ( $this->fail_read ) {
			return null;
		}
		if ( false === strpos( $query, "last_checked_at >= '2026-07-12 11:00:00'" ) || false === strpos( $query, 'fetch_attempts > 0' ) || false === strpos( $query, 'file_path IS NOT NULL' ) ) {
			throw new \RuntimeException( 'Unexpected artifact lookup: ' . $query );
		}

		return array_values( array_filter( $this->rows, static function ( array $row ): bool {
			return null !== $row['last_checked_at'] && $row['last_checked_at'] >= '2026-07-12 11:00:00' && $row['fetch_attempts'] > 0 && ! empty( $row['file_path'] );
		} ) );
	}

	public function get_var( string $query ) {
		if ( ! preg_match( "/file_path = '([^']+)'/", $query, $matches ) || false === strpos( $query, 'fetch_attempts = 0' ) ) {
			throw new \RuntimeException( 'Unexpected completed-alias lookup: ' . $query );
		}
		return count( array_filter( $this->rows, static function ( array $row ) use ( $matches ): bool {
			return $row['file_path'] === $matches[1] && null !== $row['last_checked_at'] && $row['last_checked_at'] >= '2026-07-12 11:00:00' && $row['fetch_attempts'] === 0;
		} ) );
	}

	public function query( string $query ): int {
		if ( false !== strpos( $query, 'SET last_checked_at = NULL, file_path = NULL WHERE' ) ) {
			++$this->reset_calls;
			$count = 0;
			foreach ( $this->rows as &$row ) {
				if ( null !== $row['last_checked_at'] && $row['last_checked_at'] >= '2026-07-12 11:00:00' && $row['fetch_attempts'] > 0 ) {
					$row['last_checked_at'] = null;
					$row['file_path'] = null;
					++$count;
				}
			}
			return $count;
		}

		throw new \RuntimeException( 'Unexpected artifact recovery update: ' . $query );
	}
}

final class FetchRecoveryArtifactsTest extends UnitTestCase {

	private $database;
	private $previous_wpdb;
	private $temporary_dir;
	private $archive_dir;

	protected function setUp(): void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->database = new FetchRecoveryArtifactsWpdb();
		$GLOBALS['wpdb'] = $this->database;
		$this->temporary_dir = sys_get_temp_dir() . '/' . uniqid( 'ss-recovered-artifacts-', true );
		$this->archive_dir = $this->temporary_dir . '/site/';
		wp_mkdir_p( $this->archive_dir );
		WpEnv::$options['simply-static'] = array(
			'temp_files_dir'     => $this->temporary_dir,
			'archive_name'       => 'site',
			'archive_start_time' => '2026-07-12 11:00:00',
			'generate_type'      => 'export',
		);
		Options::reinstance();
		$this->database->rows = array( $this->row( 1, 3, 'page/index.html' ) );
	}

	protected function tearDown(): void {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->temporary_dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $file ) {
			$file->isLink() || ! $file->isDir() ? unlink( $file->getPathname() ) : rmdir( $file->getPathname() );
		}
		rmdir( $this->temporary_dir );
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	public function test_recovered_final_attempt_is_removed_from_full_zip_and_completed_files_are_kept(): void {
		$this->write_file( 'page/index.html', '<html>Unprocessed origin URLs</html>' );
		$this->write_file( 'kept/index.html', '<html>Completed page</html>' );
		$this->database->rows[] = $this->row( 2, 0, 'kept/index.html' );

		self::assertSame( 1, ( new Fetch_Urls_Task() )->recover_interrupted_pages() );
		self::assertSame( 3, $this->database->rows[0]['fetch_attempts'] );
		self::assertNull( $this->database->rows[0]['last_checked_at'] );
		self::assertNull( $this->database->rows[0]['file_path'] );
		self::assertFileDoesNotExist( $this->archive_dir . 'page/index.html' );
		self::assertFileExists( $this->archive_dir . 'kept/index.html' );

		$admin_includes = ABSPATH . 'wp-admin/includes';
		wp_mkdir_p( $admin_includes );
		if ( ! file_exists( $admin_includes . '/class-pclzip.php' ) ) {
			file_put_contents( $admin_includes . '/class-pclzip.php', '<?php' );
		}
		require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-create-zip-archive.php';
		$zip_task = new Create_Zip_Archive_Task();
		$files = new \ReflectionMethod( $zip_task, 'get_files_to_include' );
		$files->setAccessible( true );
		self::assertSame( array( realpath( $this->archive_dir . 'kept/index.html' ) ), $files->invoke( $zip_task, $this->archive_dir, true ) );
	}

	public function test_absent_artifact_can_be_recovered_without_a_cleanup_error(): void {
		self::assertSame( 1, ( new Fetch_Urls_Task() )->recover_interrupted_pages() );
		self::assertSame( 3, $this->database->rows[0]['fetch_attempts'] );
		self::assertNull( $this->database->rows[0]['file_path'] );
	}

	public function test_artifacts_from_an_older_export_are_left_untouched(): void {
		$this->write_file( 'page/index.html', 'Previously generated' );
		$this->database->rows[0]['last_checked_at'] = '2026-07-11 11:00:00';

		self::assertSame( 0, ( new Fetch_Urls_Task() )->recover_interrupted_pages() );
		self::assertSame( 'Previously generated', file_get_contents( $this->archive_dir . 'page/index.html' ) );
		self::assertSame( 'page/index.html', $this->database->rows[0]['file_path'] );
	}

	public function test_cleanup_refuses_to_remove_a_completed_alias_and_preserves_the_claim(): void {
		$this->write_file( 'page/index.html', 'Shared completed artifact' );
		$this->database->rows[] = $this->row( 2, 0, 'page/index.html' );

		$this->assert_recovery_fails_before_reset();
		self::assertSame( 'Shared completed artifact', file_get_contents( $this->archive_dir . 'page/index.html' ) );
	}

	public function test_traversal_cannot_remove_files_outside_the_archive(): void {
		file_put_contents( $this->temporary_dir . '/outside.html', 'Keep outside file' );
		$this->database->rows[0]['file_path'] = '../outside.html';

		$this->assert_recovery_fails_before_reset();
		self::assertSame( 'Keep outside file', file_get_contents( $this->temporary_dir . '/outside.html' ) );
	}

	public function test_symlinked_directory_cannot_remove_files_outside_the_archive(): void {
		wp_mkdir_p( $this->temporary_dir . '/outside' );
		file_put_contents( $this->temporary_dir . '/outside/index.html', 'Keep symlink target' );
		symlink( $this->temporary_dir . '/outside', $this->archive_dir . 'linked' );
		$this->database->rows[0]['file_path'] = 'linked/index.html';

		$this->assert_recovery_fails_before_reset();
		self::assertSame( 'Keep symlink target', file_get_contents( $this->temporary_dir . '/outside/index.html' ) );
	}

	public function test_directory_artifact_is_refused_before_the_claim_is_reset(): void {
		wp_mkdir_p( $this->archive_dir . 'page/index.html' );

		$this->assert_recovery_fails_before_reset();
		self::assertDirectoryExists( $this->archive_dir . 'page/index.html' );
	}

	public function test_failed_unlink_preserves_the_record_for_recovery(): void {
		$this->write_file( 'page/index.html', 'Keep file after cleanup failure' );
		$directory = $this->archive_dir . 'page';
		chmod( $directory, 0555 );
		try {
			if ( is_writable( $directory ) ) {
				self::markTestSkipped( 'This user can unlink files from read-only directories.' );
			}
			$this->assert_recovery_fails_before_reset();
			self::assertFileExists( $this->archive_dir . 'page/index.html' );
		} finally {
			chmod( $directory, 0755 );
		}
	}

	public function test_failed_artifact_lookup_aborts_before_resetting_any_claim(): void {
		$this->database->fail_read = true;
		$this->assert_recovery_fails_before_reset();
	}

	private function assert_recovery_fails_before_reset(): void {
		try {
			( new Fetch_Urls_Task() )->recover_interrupted_pages();
			self::fail( 'Artifact cleanup should have failed.' );
		} catch ( \RuntimeException $exception ) {
			self::assertSame( 0, $this->database->reset_calls );
			self::assertSame( '2026-07-12 12:00:00', $this->database->rows[0]['last_checked_at'] );
			self::assertNotNull( $this->database->rows[0]['file_path'] );
			self::assertSame( 3, $this->database->rows[0]['fetch_attempts'] );
		}
	}

	private function row( int $id, int $attempts, string $path ): array {
		return array(
			'id'              => $id,
			'last_checked_at' => '2026-07-12 12:00:00',
			'fetch_attempts'  => $attempts,
			'file_path'       => $path,
		);
	}

	private function write_file( string $path, string $contents ): void {
		wp_mkdir_p( dirname( $this->archive_dir . $path ) );
		file_put_contents( $this->archive_dir . $path, $contents );
	}
}
