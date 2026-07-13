<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;

final class ExcludedBackupPage {

	/** @var string */
	public $url;

	/** @var string|null */
	public $file_path = 'wp-content/uploads/simply-static/backup-old/studio-backup.zip';

	/** @var string|null */
	public $last_checked_at;

	/** @var string */
	public $status_message = '';

	/** @var bool */
	public $saved = false;

	public function __construct( string $url ) {
		$this->url = $url;
	}

	public function set_status_message( string $message ): void {
		$this->status_message = $message;
	}

	public function save(): bool {
		$this->saved = true;

		return true;
	}
}

final class FetchUrlsExclusionTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/tasks/class-ss-task.php' );
		$this->requireSource( 'src/tasks/traits/class-ss-skip-further-processing-exception.php' );
		$this->requireSource( 'src/tasks/traits/trait-ss-can-process-pages.php' );
		$this->requireSource( 'src/tasks/class-ss-fetch-urls-task.php' );
		Options::instance()->set( 'archive_start_time', '2026-07-13 08:15:32' );
	}

	public function test_incremental_export_clears_stale_file_path_for_excluded_backup(): void {
		$key  = str_repeat( 'a', 32 );
		$page = new ExcludedBackupPage(
			'https://example.test/wp-content/uploads/simply-static/backup-' . $key . '/studio-backup.zip'
		);

		$method = new ReflectionMethod( Fetch_Urls_Task::class, 'process_page' );
		$method->setAccessible( true );
		$method->invoke( new Fetch_Urls_Task(), $page );

		self::assertNull( $page->file_path );
		self::assertNotNull( $page->last_checked_at );
		self::assertTrue( $page->saved );
		self::assertSame( 'Do not save or follow', $page->status_message );
	}
}
