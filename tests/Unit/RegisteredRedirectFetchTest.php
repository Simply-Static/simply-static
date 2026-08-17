<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;

$simply_static_root = dirname( __DIR__, 2 );
require_once $simply_static_root . '/src/class-ss-plugin.php';
require_once $simply_static_root . '/src/class-ss-options.php';
require_once $simply_static_root . '/src/class-ss-util.php';
require_once $simply_static_root . '/src/tasks/class-ss-task.php';
require_once $simply_static_root . '/src/tasks/traits/class-ss-skip-further-processing-exception.php';
require_once $simply_static_root . '/src/tasks/traits/trait-ss-can-process-pages.php';
require_once $simply_static_root . '/src/tasks/class-ss-fetch-urls-task.php';

final class RegisteredRedirectPage {
	/** @var string */
	public $url = 'https://origin.example/old-page';

	/** @var string|null */
	public $last_checked_at;

	/** @var int|null */
	public $http_status_code;

	/** @var string|null */
	public $redirect_url;

	/** @var bool */
	public $error_cleared = false;

	/** @var int */
	public $save_calls = 0;

	public function clear_error_message(): void {
		$this->error_cleared = true;
	}

	public function save(): void {
		$this->save_calls++;
	}
}

final class RegisteredRedirectFetchTask extends Fetch_Urls_Task {
	/** @var array<int,mixed>|null */
	public $handled_redirect;

	public function handleRegisteredRedirect( $page ): bool {
		return $this->maybe_handle_registered_redirect( $page, true, true );
	}

	public function handle_30x_redirect( $static_page, $save_file, $follow_urls ) {
		$this->handled_redirect = array( $static_page, $save_file, $follow_urls );
	}
}

final class RegisteredRedirectFetchTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::instance()->set( 'archive_start_time', '2026-08-17 12:00:00' );
	}

	public function test_registered_redirect_bypasses_the_http_fetch_path(): void {
		add_filter(
			'simply_static_registered_redirect',
			static function () {
				return array(
					'url'         => 'https://origin.example/new-page',
					'status_code' => 307,
				);
			},
			10,
			2
		);

		$page = new RegisteredRedirectPage();
		$task = new RegisteredRedirectFetchTask();

		self::assertTrue( $task->handleRegisteredRedirect( $page ) );
		self::assertTrue( $page->error_cleared );
		self::assertSame( '2026-07-12 12:00:00', $page->last_checked_at );
		self::assertSame( 307, $page->http_status_code );
		self::assertSame( 'https://origin.example/new-page', $page->redirect_url );
		self::assertSame( array( $page, true, true ), $task->handled_redirect );
		self::assertSame( 1, $page->save_calls );
	}

	public function test_invalid_registered_redirect_falls_back_to_normal_fetching(): void {
		add_filter(
			'simply_static_registered_redirect',
			static function () {
				return array(
					'url'         => 'https://origin.example/gone',
					'status_code' => 410,
				);
			},
			10,
			2
		);

		$page = new RegisteredRedirectPage();
		$task = new RegisteredRedirectFetchTask();

		self::assertFalse( $task->handleRegisteredRedirect( $page ) );
		self::assertFalse( $page->error_cleared );
		self::assertNull( $task->handled_redirect );
	}
}
