<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Crawler\Uploads_Crawler;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment;

final class UploadsCrawlerWpdb {

	/** @var array<int,array<string,mixed>> */
	public $inserts = array();

	/** @var int */
	public $insert_id = 1;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @return null */
	public function get_row( string $query, $output = null ) {
		return null;
	}

	/** @param array<string,mixed> $data */
	public function insert( string $table, array $data ): int {
		$this->inserts[] = array(
			'table' => $table,
			'data'  => $data,
		);
		++$this->insert_id;

		return 1;
	}
}

final class UploadsCrawlerTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-uploads-crawler.php' );
	}

	public function test_large_upload_tree_resumes_from_persisted_checkpoints(): void {
		$root = sys_get_temp_dir() . '/ss-uploads-resume-' . bin2hex( random_bytes( 8 ) );
		wp_mkdir_p( $root . '/2026/deep' );
		wp_mkdir_p( $root . '/cache' );
		file_put_contents( $root . '/photo.jpg', 'image' );
		file_put_contents( $root . '/notes.txt', 'ignore' );
		file_put_contents( $root . '/2026/one.png', 'image' );
		file_put_contents( $root . '/2026/deep/two.pdf', 'document' );
		file_put_contents( $root . '/cache/hidden.jpg', 'cache' );

		WpTestEnvironment::$upload_dir = array(
			'basedir' => $root,
			'baseurl' => 'https://example.test/wp-content/uploads',
		);
		$GLOBALS['wpdb'] = new UploadsCrawlerWpdb();
		Options::instance()->set( 'archive_start_time', '2026-08-19 10:00:00' )->save();
		add_filter( 'ss_uploads_additional_directories', static function () {
			return array();
		} );
		add_filter( 'simply_static_uploads_crawler_max_entries_per_batch', static function () {
			return 2;
		} );

		$total_added = 0;
		$complete    = false;
		$progress    = array();
		$history     = array();

		try {
			for ( $attempt = 0; $attempt < 20; $attempt++ ) {
				$crawler      = new Uploads_Crawler();
				$batch_added  = $crawler->add_urls_to_queue();
				$total_added += $batch_added;
				$complete     = $crawler->is_complete();
				$progress     = $crawler->get_progress();
				$history[]    = array(
					'batch_added' => $batch_added,
					'complete'    => $complete,
					'progress'    => $progress,
					'state'       => Options::instance()->get( 'uploads_crawler_state' ),
				);
				if ( $complete ) {
					break;
				}
				self::assertIsArray( Options::instance()->get( 'uploads_crawler_state' ) );
			}

			self::assertTrue( $complete );
			self::assertSame( 3, $total_added, wp_json_encode( $history ) );
			self::assertSame( 3, $progress['added'] );
			self::assertGreaterThan( 3, $progress['scanned'] );
			self::assertNull( Options::instance()->get( 'uploads_crawler_state' ) );

			$urls = array_column( array_column( $GLOBALS['wpdb']->inserts, 'data' ), 'url' );
			sort( $urls );
			self::assertSame(
				array(
					'https://example.test/wp-content/uploads/2026/deep/two.pdf',
					'https://example.test/wp-content/uploads/2026/one.png',
					'https://example.test/wp-content/uploads/photo.jpg',
				),
				$urls
			);
		} finally {
			$this->remove_tree( $root );
		}
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

	private function remove_tree( string $root ): void {
		if ( ! is_dir( $root ) ) {
			return;
		}
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			if ( $item->isDir() && ! $item->isLink() ) {
				@rmdir( $item->getPathname() );
			} else {
				@unlink( $item->getPathname() );
			}
		}
		@rmdir( $root );
	}
}
