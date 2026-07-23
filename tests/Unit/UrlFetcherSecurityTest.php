<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

final class UrlFetcherSecurityTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-url-fetcher.php' );

		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => 'secret',
			'debugging_mode'           => false,
		);
		Options::reinstance();
	}

	public function test_local_development_requests_skip_tls_verification_and_receive_basic_auth(): void {
		Url_Fetcher::remote_get( 'https://example.test/page' );

		self::assertCount( 1, WpEnv::$remote_requests );
		$request = WpEnv::$remote_requests[0];
		self::assertFalse( $request['args']['sslverify'] );
		self::assertSame( 0, $request['args']['redirection'] );
		self::assertSame( 'Basic ' . base64_encode( 'crawler:secret' ), $request['args']['headers']['Authorization'] );
	}

	public function test_production_requests_keep_tls_verification_enabled(): void {
		WpEnv::$home_url = 'https://example.com';
		WpEnv::$site_url = 'https://example.com';
		Options::reinstance();

		Url_Fetcher::remote_get( 'https://example.com/page' );

		self::assertTrue( WpEnv::$remote_requests[0]['args']['sslverify'] );
	}

	public function test_ssl_filter_can_override_automatic_detection(): void {
		add_filter( 'ss_remote_get_sslverify', '__return_true' );
		Url_Fetcher::remote_get( 'https://example.test/page' );
		self::assertTrue( WpEnv::$remote_requests[0]['args']['sslverify'] );

		remove_filter( 'ss_remote_get_sslverify', '__return_true' );
		WpEnv::$remote_requests = array();
		WpEnv::$home_url        = 'https://example.com';
		WpEnv::$site_url        = 'https://example.com';
		Options::reinstance();
		add_filter( 'ss_remote_get_sslverify', '__return_false' );

		Url_Fetcher::remote_get( 'https://example.com/page' );
		self::assertFalse( WpEnv::$remote_requests[0]['args']['sslverify'] );
	}

	public function test_external_and_alternate_port_requests_are_rejected_before_http(): void {
		$external = Url_Fetcher::remote_get( 'https://external.test/page' );
		$port     = Url_Fetcher::remote_get( 'https://example.test:8443/page' );

		self::assertInstanceOf( \WP_Error::class, $external );
		self::assertSame( 'simply_static_unsafe_url', $external->get_error_code() );
		self::assertInstanceOf( \WP_Error::class, $port );
		self::assertCount( 0, WpEnv::$remote_requests );
	}

	public function test_empty_credentials_do_not_emit_an_empty_basic_header(): void {
		WpEnv::$options['simply-static']['http_basic_auth_username'] = '';
		WpEnv::$options['simply-static']['http_basic_auth_password'] = '';
		Options::reinstance();

		Url_Fetcher::remote_get( 'https://example.test/page' );

		self::assertArrayNotHasKey( 'Authorization', WpEnv::$remote_requests[0]['args']['headers'] );
	}
}
