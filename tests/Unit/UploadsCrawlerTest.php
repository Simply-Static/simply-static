<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Crawler\Uploads_Crawler;
use Simply_Static\Tests\Support\UnitTestCase;

final class UploadsCrawlerTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-uploads-crawler.php' );
	}

	public function test_private_backup_files_are_removed_before_upload_urls_are_built(): void {
		$root       = sys_get_temp_dir() . '/ss-uploads-crawler-' . bin2hex( random_bytes( 8 ) );
		$backup_dir = $root . '/simply-static/backup-' . str_repeat( 'a', 32 );
		wp_mkdir_p( $backup_dir );
		file_put_contents( $root . '/photo.jpg', 'image' );
		file_put_contents( $backup_dir . '/config.json', '{}' );
		file_put_contents( $backup_dir . '/studio-backup.zip', 'archive' );

		try {
			$method = new ReflectionMethod( Uploads_Crawler::class, 'process_file_batch' );
			$method->setAccessible( true );
			$urls = $method->invoke(
				new Uploads_Crawler(),
				array(
					new \SplFileInfo( $root . '/photo.jpg' ),
					new \SplFileInfo( $backup_dir . '/config.json' ),
					new \SplFileInfo( $backup_dir . '/studio-backup.zip' ),
				),
				$root,
				'https://example.test/wp-content/uploads',
				array(),
				array( 'jpg', 'json', 'zip' )
			);

			self::assertSame( array( 'https://example.test/wp-content/uploads/photo.jpg' ), $urls );
		} finally {
			@unlink( $backup_dir . '/config.json' );
			@unlink( $backup_dir . '/studio-backup.zip' );
			@unlink( $root . '/photo.jpg' );
			@rmdir( $backup_dir );
			@rmdir( dirname( $backup_dir ) );
			@rmdir( $root );
		}
	}
}
