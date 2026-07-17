<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Page_Handlers;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-page-handlers.php';

final class RequestUriPageHandler {

	/** @var string|null */
	public $request_uri;

	public function run_hooks(): void {
		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null;
	}
}

final class RequestUriPage {

	/** @var RequestUriPageHandler */
	private $handler;

	public function __construct( RequestUriPageHandler $handler ) {
		$this->handler = $handler;
	}

	public function get_handler(): RequestUriPageHandler {
		return $this->handler;
	}
}

final class RequestUriPageHandlers extends Page_Handlers {

	/** @var RequestUriPage */
	private $page;

	public function __construct( RequestUriPage $page ) {
		$this->page = $page;
		parent::__construct();
	}

	public function includes() {
		// The request lifecycle test supplies its own handler.
	}

	public function get_static_page() {
		return $this->page;
	}
}

final class PageHandlersRequestTest extends UnitTestCase {

	/**
	 * @dataProvider requestUriProvider
	 */
	public function test_internal_page_id_is_hidden_from_later_request_url_consumers(
		string $request_uri,
		string $expected_uri
	): void {
		$original_get             = $_GET;
		$had_original_request_uri = array_key_exists( 'REQUEST_URI', $_SERVER );
		$original_request_uri     = $_SERVER['REQUEST_URI'] ?? null;

		try {
			$_GET['simply_static_page'] = '59229';
			$_SERVER['REQUEST_URI']      = $request_uri;

			$handler = new RequestUriPageHandler();
			new RequestUriPageHandlers( new RequestUriPage( $handler ) );

			$later_request_uri = null;
			add_action(
				'init',
				static function () use ( &$later_request_uri ): void {
					$later_request_uri = $_SERVER['REQUEST_URI'] ?? null;
				},
				PHP_INT_MAX
			);

			do_action( 'init' );

			self::assertSame( $request_uri, $handler->request_uri );
			self::assertSame( $expected_uri, $later_request_uri );
			self::assertSame( '59229', $_GET['simply_static_page'] );
		} finally {
			$_GET = $original_get;
			if ( $had_original_request_uri ) {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			} else {
				unset( $_SERVER['REQUEST_URI'] );
			}
		}
	}

	/** @return array<string,array{string,string}> */
	public function requestUriProvider(): array {
		return array(
			'root page' => array(
				'/?simply_static_page=59229',
				'/',
			),
			'page with preceding query argument' => array(
				'/news/?lang=de&simply_static_page=59229',
				'/news/?lang=de',
			),
			'page with following query argument' => array(
				'/news/?simply_static_page=59229&preview=1',
				'/news/?preview=1',
			),
			'page with surrounding query arguments' => array(
				'/news/?lang=de&simply_static_page=59229&preview=1',
				'/news/?lang=de&preview=1',
			),
		);
	}
}
