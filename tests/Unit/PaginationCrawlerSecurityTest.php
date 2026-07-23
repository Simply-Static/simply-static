<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Crawler\Pagination_Crawler;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class PaginationCrawlerSecurityTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-pagination-crawler.php' );
	}

	public function test_scans_only_exact_origin_with_tls_redirect_and_body_limits(): void {
		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => 'secret',
		);
		Options::reinstance();
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '<a href="/articles/page/2/">Next</a>',
		);

		$urls = $this->extract( 'https://example.test/articles/' );

		self::assertSame( array( 'https://example.test/articles/page/2/' ), $urls );
		self::assertCount( 2, WpEnv::$remote_requests );
		foreach ( WpEnv::$remote_requests as $request ) {
			self::assertFalse( $request['args']['sslverify'] );
			self::assertSame( 0, $request['args']['redirection'] );
			self::assertSame( 2 * 1024 * 1024 + 1, $request['args']['limit_response_size'] );
			self::assertSame( 'Basic ' . base64_encode( 'crawler:secret' ), $request['args']['headers']['Authorization'] );
		}
	}

	public function test_detects_numeric_custom_page_pagination_urls(): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => implode(
				'',
				array(
					'<a href="https://example.test/articles/2/">Page 2</a>',
					'<a href="/articles/3/">Page 3</a>',
				)
			),
		);

		$urls = $this->extract( 'https://example.test/articles/' );

		self::assertSame(
			array(
				'https://example.test/articles/2/',
				'https://example.test/articles/3/',
			),
			$urls
		);
		self::assertCount( 3, WpEnv::$remote_requests );
	}

	public function test_rejects_external_base_url_before_network_io(): void {
		self::assertSame( array(), $this->extract( 'https://attacker.test/articles/' ) );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_rejects_oversized_and_non_success_responses(): void {
		add_filter( 'simply_static_pagination_max_response_bytes', static function () {
			return 1024;
		} );
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => str_repeat( 'x', 1025 ),
		);
		self::assertSame( array(), $this->extract( 'https://example.test/articles/' ) );

		WpEnv::$remote_requests = array();
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 302 ),
			'body'     => '<a href="/articles/page/2/">Next</a>',
		);
		self::assertSame( array(), $this->extract( 'https://example.test/articles/' ) );
		self::assertCount( 1, WpEnv::$remote_requests );
	}

	public function test_custom_page_discovery_enforces_global_url_cap_and_reports_truncation(): void {
		add_filter(
			'simply_static_pagination_max_generated_urls',
			static function (): int {
				return 100;
			}
		);
		$links = array();
		for ( $page = 2; $page <= 151; ++$page ) {
			$links[] = '<a href="/articles/page/' . $page . '/">Page</a>';
		}
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => implode( '', $links ),
		);

		$urls = $this->extract( 'https://example.test/articles/' );

		self::assertCount( 100, $urls );
		self::assertSame( 'https://example.test/articles/page/101/', $urls[99] );
		self::assertContains( 'simply_static_pagination_truncated', WpEnv::$action_log );
		self::assertStringContainsString(
			'safety limit reached',
			WpEnv::$options['simply-static']['archive_status_messages']['pagination_warning']['message']
		);
	}

	/** @return string[] */
	private function extract( string $base_url ): array {
		$crawler = new Pagination_Crawler();
		$method  = new ReflectionMethod( Pagination_Crawler::class, 'extract_pagination_urls_from_page' );
		$method->setAccessible( true );

		return $method->invoke( $crawler, $base_url, 'https://example.test/' );
	}
}
