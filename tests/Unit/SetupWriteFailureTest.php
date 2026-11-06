<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Setup_Task;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-setup-task.php';

final class SetupWriteFailureWpdb {
	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function query( string $query ): int {
		return 1;
	}
}

final class SetupWriteFailureTask extends Setup_Task {
	public $on_additional_files;

	public function delete_temp_static_files() {
		return true;
	}

	public function add_origin_and_additional_urls_to_db( $additional_urls ) {
	}

	public function add_additional_files_to_db( $additional_files ) {
		if ( $this->on_additional_files ) {
			call_user_func( $this->on_additional_files );
		}
	}
}

final class SetupWriteFailureTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wpdb'] = new SetupWriteFailureWpdb();
		WpEnv::$options[ Page::SCHEMA_VERSION_OPTION ] = Page::SCHEMA_VERSION;
		WpEnv::$options['simply-static'] = array(
			'archive_start_time'      => '2026-07-12 10:00:00',
			'archive_end_time'        => null,
			'archive_status_messages' => array(),
			'temp_files_dir'          => sys_get_temp_dir(),
			'archive_name'            => '',
		);
		Options::reinstance();
	}

	public function test_setup_returns_a_write_error_ignored_by_an_integration(): void {
		add_action(
			'ss_after_setup_task',
			static function (): void {
				Options::instance()->set( 'archive_page_write_error', 'Unable to save https://example.test/long-url/: database error.' );
			}
		);

		$result = ( new SetupWriteFailureTask() )->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'ss_page_write_failed', $result->get_error_code() );
		self::assertStringContainsString( 'https://example.test/long-url/', $result->get_error_message() );
	}

	public function test_a_new_synchronous_export_clears_the_previous_write_failure(): void {
		Options::instance()->set( 'archive_page_write_error', 'A previous export failed.' );

		self::assertTrue( ( new SetupWriteFailureTask() )->perform() );
		self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );
	}

	public function test_failed_queue_does_not_run_post_setup_hooks_that_can_clear_the_live_destination(): void {
		$task = new SetupWriteFailureTask();
		$task->on_additional_files = static function (): void {
			Options::instance()->set( 'archive_page_write_error', 'Unable to save an additional file.' );
		};
		$hook_ran = false;
		add_action( 'ss_after_setup_task', static function () use ( &$hook_ran ): void {
			$hook_ran = true;
		} );

		self::assertInstanceOf( \WP_Error::class, $task->perform() );
		self::assertFalse( $hook_ran );
	}
}
