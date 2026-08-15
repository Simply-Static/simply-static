<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Create_Zip_Archive_Task;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class ZipArchiveTaskTest extends UnitTestCase {

	/** @var string */
	private $temp_dir;

	/** @var string */
	private $archive_dir;

	protected function setUp(): void {
		parent::setUp();
		if ( ! class_exists( \ZipArchive::class ) ) {
			self::markTestSkipped( 'ZipArchive is required for batched ZIP tests.' );
		}

		$admin_includes = ABSPATH . 'wp-admin/includes';
		wp_mkdir_p( $admin_includes );
		if ( ! file_exists( $admin_includes . '/class-pclzip.php' ) ) {
			file_put_contents( $admin_includes . '/class-pclzip.php', "<?php\n" );
		}

		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/tasks/class-ss-task.php' );
		$this->requireSource( 'src/tasks/class-ss-create-zip-archive.php' );

		$this->temp_dir    = WpEnv::$upload_dir['basedir'] . '/simply-static/zip-tests-' . uniqid( '', true );
		$this->archive_dir = trailingslashit( $this->temp_dir ) . 'site/';
		wp_mkdir_p( $this->archive_dir . 'nested' );
		WpEnv::$options['simply-static'] = array(
			'temp_files_dir'         => $this->temp_dir,
			'archive_name'           => 'site/',
			'generate_type'          => 'export',
			'zip_batch_offset'       => 0,
			'zip_files'              => null,
			'archive_status_messages' => array(),
		);
		Options::reinstance();
	}

	public function test_batched_zip_uses_a_stable_file_snapshot_and_preserves_empty_marker_files(): void {
		file_put_contents( $this->archive_dir . 'a.txt', 'alpha' );
		file_put_contents( $this->archive_dir . 'nested/b.txt', 'beta' );
		file_put_contents( $this->archive_dir . '.nojekyll', '' );
		add_filter( 'ss_zip_batch_size', static function () { return 1; } );

		$task = new Create_Zip_Archive_Task();
		self::assertFalse( $task->create_zip() );
		self::assertSame( 1, WpEnv::$options['simply-static']['zip_batch_offset'] );
		self::assertNull( WpEnv::$options['simply-static']['zip_files'] );
		self::assertCount( 3, WpEnv::$options[ Create_Zip_Archive_Task::ZIP_FILES_OPTION ] );

		// Files appearing mid-export belong to the next export, not this archive.
		file_put_contents( $this->archive_dir . 'late.txt', 'late' );
		self::assertFalse( $task->create_zip() );
		$result = $task->create_zip();
		self::assertIsString( $result );

		$zip = new \ZipArchive();
		self::assertTrue( $zip->open( untrailingslashit( $this->archive_dir ) . '.zip' ) );
		self::assertNotFalse( $zip->locateName( 'a.txt' ) );
		self::assertNotFalse( $zip->locateName( 'nested/b.txt' ) );
		$marker_index = $zip->locateName( '.nojekyll' );
		self::assertNotFalse( $marker_index );
		self::assertSame( 0, $zip->statIndex( $marker_index )['size'] );
		self::assertFalse( $zip->locateName( 'late.txt' ) );
		$zip->close();
	}

	public function test_zero_batch_size_is_clamped_and_cannot_stall_progress(): void {
		file_put_contents( $this->archive_dir . 'only.txt', 'content' );
		add_filter( 'ss_zip_batch_size', static function () { return 0; } );

		$result = ( new Create_Zip_Archive_Task() )->create_zip();

		self::assertIsString( $result );
		self::assertFileExists( untrailingslashit( $this->archive_dir ) . '.zip' );
	}

	public function test_perform_publishes_structured_download_link(): void {
		file_put_contents( $this->archive_dir . 'index.html', '<h1>Archived</h1>' );
		$zip_file = WP_CONTENT_DIR . '/uploads/simply-static/activity-link-test.zip';
		$debug_log = $this->temp_dir . '/zip-debug.log';
		wp_mkdir_p( dirname( $zip_file ) );
		add_filter( 'ss_zip_filename', static function () use ( $zip_file ) {
			return $zip_file;
		} );
		add_filter( 'ss_debug_log_file', static function () use ( $debug_log ) {
			return $debug_log;
		} );
		WpEnv::$options['simply-static']['debugging_mode'] = true;
		Options::reinstance();

		self::assertTrue( ( new Create_Zip_Archive_Task() )->perform() );

		$status = WpEnv::$options['simply-static']['archive_status_messages']['create_zip_archive'];
		self::assertSame( 'ZIP archive created: ', $status['message'] );
		self::assertSame( 'Click here to download', $status['link']['label'] );
		self::assertStringStartsWith( 'https://example.test/wp-content/', $status['link']['url'] );
		self::assertStringEndsWith( '/activity-link-test.zip', $status['link']['url'] );
		self::assertStringContainsString(
			'Status message: [create_zip_archive] ZIP archive created: ' . $status['link']['url'],
			file_get_contents( $debug_log )
		);
	}

	public function test_perform_maps_an_external_uploads_directory_to_its_public_url(): void {
		file_put_contents( $this->archive_dir . 'index.html', '<h1>Archived</h1>' );

		self::assertTrue( ( new Create_Zip_Archive_Task() )->perform() );

		$zip_file = untrailingslashit( $this->archive_dir ) . '.zip';
		$status   = WpEnv::$options['simply-static']['archive_status_messages']['create_zip_archive'];
		$relative = substr( wp_normalize_path( $zip_file ), strlen( wp_normalize_path( WpEnv::$upload_dir['basedir'] ) ) );

		self::assertFileExists( $zip_file );
		self::assertGreaterThanOrEqual( 22, filesize( $zip_file ) );
		self::assertSame( WpEnv::$upload_dir['baseurl'] . $relative, $status['link']['url'] );
	}

	public function test_perform_does_not_report_success_when_the_zip_has_no_public_url(): void {
		$private_temp_dir = sys_get_temp_dir() . '/simply-static-private-zip-' . uniqid( '', true );
		$private_archive  = trailingslashit( $private_temp_dir ) . 'site/';
		wp_mkdir_p( $private_archive );
		file_put_contents( $private_archive . 'index.html', '<h1>Archived</h1>' );

		WpEnv::$options['simply-static']['temp_files_dir'] = $private_temp_dir;
		Options::reinstance();

		$result = ( new Create_Zip_Archive_Task() )->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'zip_url_unavailable', $result->get_error_code() );
		self::assertFileExists( untrailingslashit( $private_archive ) . '.zip' );
		self::assertArrayNotHasKey( 'create_zip_archive', WpEnv::$options['simply-static']['archive_status_messages'] );
	}

	public function test_filtered_files_outside_archive_and_symlink_escapes_are_excluded(): void {
		file_put_contents( $this->archive_dir . 'safe.txt', 'safe' );
		$outside = WpEnv::$upload_dir['basedir'] . '/outside-secret.txt';
		file_put_contents( $outside, 'secret' );
		$link = $this->archive_dir . 'escape.txt';
		$linked = function_exists( 'symlink' ) && @symlink( $outside, $link );

		add_filter( 'ss_zip_files_to_include', static function ( array $files ) use ( $outside, $link, $linked ): array {
			$files[] = $outside;
			if ( $linked ) {
				$files[] = $link;
			}
			return $files;
		} );

		( new Create_Zip_Archive_Task() )->create_zip();
		$zip = new \ZipArchive();
		self::assertTrue( $zip->open( untrailingslashit( $this->archive_dir ) . '.zip' ) );
		self::assertNotFalse( $zip->locateName( 'safe.txt' ) );
		self::assertFalse( $zip->locateName( 'outside-secret.txt' ) );
		self::assertFalse( $zip->locateName( 'escape.txt' ) );
		$zip->close();
	}
}
