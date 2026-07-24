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
