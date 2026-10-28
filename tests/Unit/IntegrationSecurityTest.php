<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Integration;
use Simply_Static\Options;
use Simply_Static\AIO_SEO_Integration;
use Simply_Static\Rank_Math_Integration;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Yoast_Integration;

final class IntegrationSecurityTest extends UnitTestCase {

	/** @var Integration */
	private $integration;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-aio-seo-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-rank-math-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-yoast-integration.php' );

		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => 'secret',
		);
		Options::reinstance();

		$this->integration = new class extends Integration {
			/** @return array|\WP_Error */
			public function fetch( string $url ) {
				return $this->auth_remote_get( $url );
			}

			/** @return array|\WP_Error */
			public function post( string $url, array $args = array() ) {
				return $this->auth_remote_post( $url, $args );
			}

			/** @param array|\WP_Error $response @return string[] */
			public function parse( $response ): array {
				return $this->extract_sitemap_index_urls( $response );
			}
		};
	}

	public function test_authenticated_local_development_requests_are_non_redirecting(): void {
		$this->integration->fetch( 'https://example.test/sitemap.xml' );

		self::assertCount( 1, WpEnv::$remote_requests );
		$args = WpEnv::$remote_requests[0]['args'];
		self::assertFalse( $args['sslverify'] );
		self::assertSame( 0, $args['redirection'] );
		self::assertSame( 'Basic ' . base64_encode( 'crawler:secret' ), $args['headers']['Authorization'] );
	}

	public function test_external_integration_request_is_rejected_before_network_io(): void {
		$response = $this->integration->fetch( 'https://attacker.test/sitemap.xml' );

		self::assertInstanceOf( \WP_Error::class, $response );
		self::assertSame( 'ss_disallowed_remote_url', $response->get_error_code() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_authenticated_local_post_requests_are_non_redirecting(): void {
		$this->integration->post(
			'https://example.test/wp-json/plugin/endpoint',
			array( 'headers' => array( 'Content-Type' => 'application/json' ) )
		);

		self::assertCount( 1, WpEnv::$remote_requests );
		self::assertSame( 'POST', WpEnv::$remote_requests[0]['method'] );
		$args = WpEnv::$remote_requests[0]['args'];
		self::assertFalse( $args['sslverify'] );
		self::assertSame( 0, $args['redirection'] );
		self::assertSame( 'application/json', $args['headers']['Content-Type'] );
		self::assertSame( 'Basic ' . base64_encode( 'crawler:secret' ), $args['headers']['Authorization'] );
	}

	public function test_external_integration_post_is_rejected_before_network_io(): void {
		$response = $this->integration->post( 'https://attacker.test/collect' );

		self::assertInstanceOf( \WP_Error::class, $response );
		self::assertSame( 'ss_disallowed_remote_url', $response->get_error_code() );
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_sitemap_parser_keeps_unique_exact_origin_urls_only(): void {
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '<?xml version="1.0"?><s:sitemapindex xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">'
				. '<s:sitemap><s:loc>https://example.test/posts.xml</s:loc></s:sitemap>'
				. '<s:sitemap><s:loc>https://example.test/posts.xml</s:loc></s:sitemap>'
				. '<s:sitemap><s:loc>https://attacker.test/internal.xml</s:loc></s:sitemap>'
				. '<s:sitemap><s:loc>https://example.test:8443/private.xml</s:loc></s:sitemap>'
				. '<s:sitemap><s:loc>https://user@example.test/creds.xml</s:loc></s:sitemap>'
				. '<s:sitemap><s:loc>https://example.test/pages.xml</s:loc></s:sitemap>'
				. '</s:sitemapindex>',
		);

		self::assertSame(
			array( 'https://example.test/posts.xml', 'https://example.test/pages.xml' ),
			$this->integration->parse( $response )
		);
	}

	public function test_sitemap_parser_rejects_entities_and_oversized_documents(): void {
		$entity = array(
			'response' => array( 'code' => 200 ),
			'body'     => '<!DOCTYPE sitemapindex [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><sitemapindex><sitemap><loc>&xxe;</loc></sitemap></sitemapindex>',
		);
		self::assertSame( array(), $this->integration->parse( $entity ) );

		add_filter( 'ss_integration_sitemap_max_bytes', static function () { return 1024; } );
		$oversized = array(
			'response' => array( 'code' => 200 ),
			'body'     => '<sitemapindex>' . str_repeat( ' ', 2000 ) . '</sitemapindex>',
		);
		self::assertSame( array(), $this->integration->parse( $oversized ) );
	}

	public function test_sitemap_parser_enforces_configured_url_limit(): void {
		add_filter( 'ss_integration_sitemap_max_urls', static function () { return 1; } );
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '<sitemapindex><sitemap><loc>https://example.test/a.xml</loc></sitemap><sitemap><loc>https://example.test/b.xml</loc></sitemap></sitemapindex>',
		);

		self::assertSame( array( 'https://example.test/a.xml' ), $this->integration->parse( $response ) );
	}

	public function test_rank_math_schema_replaces_managed_studio_origins(): void {
		WpEnv::$options['simply-static'] = array(
			'delivery_method'     => 'simply-static-studio',
			'destination_url_type' => 'absolute',
			'destination_scheme'   => 'https://',
			'destination_host'     => 'www.rootz.test',
		);
		Options::reinstance();

		$managed_origin = 'https://wp-ay95mtke8lh8t09.onstatic.studio';
		$legacy_origin  = 'https:\/\/wp.ewx03qnbk7ve09k.static4.studio';
		$attacker_url   = $managed_origin . '.attacker.test/profile';
		$schema         = '<html><head><script type="application/ld+json" class="rank-math-schema">'
			. '{"@graph":['
			. '{"@type":"WebSite","url":"' . $managed_origin . '"},'
			. '{"@type":"Person","name":"admin","sameAs":["' . $managed_origin . '"]},'
			. '{"@type":"ImageObject","url":"' . $legacy_origin . '\/wp-content\/uploads\/author.jpg"},'
			. '{"@type":"Thing","url":"' . $attacker_url . '"}'
			. ']}'
			. '</script></head><body></body></html>';

		$output = ( new Rank_Math_Integration() )->replace_json_schema( $schema, 'https://www.rootz.test/' );

		self::assertIsString( $output );
		self::assertStringNotContainsString( $managed_origin . '"', $output );
		self::assertStringNotContainsString( 'wp.ewx03qnbk7ve09k.static4.studio', $output );
		self::assertStringContainsString( '"url":"https://www.rootz.test"', $output );
		self::assertStringContainsString( '"sameAs":["https://www.rootz.test"]', $output );
		self::assertStringContainsString( 'https:\/\/www.rootz.test\/wp-content\/uploads\/author.jpg', $output );
		self::assertStringContainsString( $attacker_url, $output );
	}

	public function test_rank_math_schema_keeps_managed_urls_outside_studio_exports(): void {
		WpEnv::$options['simply-static'] = array(
			'delivery_method'     => 'local',
			'destination_url_type' => 'absolute',
			'destination_scheme'   => 'https://',
			'destination_host'     => 'static.example.test',
		);
		Options::reinstance();

		$managed_origin = 'https://wp-example.onstatic.studio';
		$schema         = '<script type="application/ld+json" class="rank-math-schema">'
			. '{"@type":"Person","sameAs":["' . $managed_origin . '"]}'
			. '</script>';

		$output = ( new Rank_Math_Integration() )->replace_json_schema( $schema, 'https://example.test/' );

		self::assertIsString( $output );
		self::assertStringContainsString( $managed_origin, $output );
	}

	public function test_dynamic_robots_url_is_queued_for_page_driven_deployments(): void {
		self::assertSame(
			array( 'https://example.test/existing', 'https://example.test/robots.txt' ),
			$this->integration->add_dynamic_robots_url( array( 'https://example.test/existing' ) )
		);

		add_filter( 'ss_include_robots_txt_in_export', static function () { return false; } );

		self::assertSame(
			array( 'https://example.test/existing' ),
			$this->integration->add_dynamic_robots_url( array( 'https://example.test/existing' ) )
		);
	}

	/** @dataProvider robotsExclusionProvider */
	public function test_dynamic_robots_url_honors_url_exclusions( string $exclusion ): void {
		WpEnv::$options['simply-static']['urls_to_exclude'] = $exclusion;
		Options::reinstance();

		self::assertSame(
			array( 'https://example.test/existing' ),
			$this->integration->add_dynamic_robots_url( array( 'https://example.test/existing' ) )
		);
	}

	/** @return array<string,array{string}> */
	public function robotsExclusionProvider(): array {
		return array(
			'filename' => array( 'robots.txt' ),
			'root path' => array( '/robots.txt' ),
		);
	}

	public function test_seo_integrations_do_not_generate_excluded_rule_files_directly(): void {
		WpEnv::$options['simply-static']['archive_name'] = 'excluded-rule-files';
		WpEnv::$options['simply-static']['urls_to_exclude'] = "robots.txt\nllms.txt";
		Options::reinstance();

		$integrations = array(
			new AIO_SEO_Integration(),
			new Rank_Math_Integration(),
			new Yoast_Integration(),
		);
		foreach ( $integrations as $integration ) {
			self::assertSame( array(), $integration->maybe_add_text_files( array() ) );
		}

		self::assertSame( array(), WpEnv::$remote_requests );
		self::assertFileDoesNotExist( Options::instance()->get_archive_dir() . 'robots.txt' );
		self::assertFileDoesNotExist( Options::instance()->get_archive_dir() . 'llms.txt' );
	}
}
