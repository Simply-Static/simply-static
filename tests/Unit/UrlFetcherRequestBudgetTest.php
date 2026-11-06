<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Page;
use Simply_Static\Page_Handler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';

final class RequestBudgetPage extends Page {

	public function save() {
		return true;
	}
}

final class UrlFetcherRequestBudgetTest extends UnitTestCase {

	public function test_expired_budget_does_not_send_a_request(): void {
		$response = Url_Fetcher::remote_get( 'https://example.test/page/', null, microtime( true ) - 1 );

		self::assertInstanceOf( \WP_Error::class, $response );
		self::assertSame( 'simply_static_fetch_timeout', $response->get_error_code() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_deadline_caps_a_filtered_timeout_and_direct_callers_keep_their_timeout(): void {
		add_filter( 'ss_remote_get_args', static function ( array $args ): array {
			$args['timeout'] = 90;
			return $args;
		} );

		Url_Fetcher::remote_get( 'https://example.test/page/', null, microtime( true ) + 5 );
		Url_Fetcher::remote_get( 'https://example.test/page/' );

		self::assertGreaterThan( 0, WpEnv::$remote_requests[0]['args']['timeout'] );
		self::assertLessThanOrEqual( 5, WpEnv::$remote_requests[0]['args']['timeout'] );
		self::assertSame( 90, WpEnv::$remote_requests[1]['args']['timeout'] );
	}

	public function test_empty_stream_recovery_shares_the_budget_and_propagates_transport_failure(): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '',
		);
		add_filter( 'ss_remote_get_args', static function ( array $args ): array {
			$args['timeout'] = 90;
			if ( count( WpEnv::$remote_requests ) === 1 ) {
				WpEnv::$remote_response = new \WP_Error( 'http_request_failed', 'Recovery request timed out' );
			}
			return $args;
		} );
		$page = RequestBudgetPage::initialize( array(
			'id'      => 1,
			'url'     => 'https://example.test/page/',
			'handler' => Page_Handler::class,
		) );

		self::assertFalse( Url_Fetcher::instance()->fetch( $page, false, microtime( true ) + 5 ) );
		self::assertCount( 2, WpEnv::$remote_requests );
		self::assertLessThanOrEqual( 5, WpEnv::$remote_requests[0]['args']['timeout'] );
		self::assertLessThan( WpEnv::$remote_requests[0]['args']['timeout'], WpEnv::$remote_requests[1]['args']['timeout'] );
		self::assertNull( $page->http_status_code );
		self::assertStringContainsString( 'Recovery request timed out', $page->error_message );
		self::assertFileDoesNotExist( WpEnv::$remote_requests[0]['args']['filename'] );
	}

	public function test_empty_recovery_response_keeps_its_temporary_http_status(): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '',
		);
		add_filter( 'ss_remote_get_args', static function ( array $args ): array {
			if ( count( WpEnv::$remote_requests ) === 1 ) {
				WpEnv::$remote_response['response']['code'] = 503;
			}
			return $args;
		} );
		$page = RequestBudgetPage::initialize( array(
			'id'      => 1,
			'url'     => 'https://example.test/page/',
			'handler' => Page_Handler::class,
		) );

		// Fetch_Urls_Task must see the 503 so it can retry the page.
		self::assertTrue( Url_Fetcher::instance()->fetch( $page, false, microtime( true ) + 5 ) );
		self::assertSame( 503, $page->http_status_code );
		self::assertNull( $page->file_path );
		self::assertCount( 2, WpEnv::$remote_requests );
		self::assertFileDoesNotExist( WpEnv::$remote_requests[0]['args']['filename'] );
	}
}
