<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Rank_Math_Integration;
use Simply_Static\Tests\Support\UnitTestCase;

$simply_static_root = dirname( __DIR__, 2 );
require_once $simply_static_root . '/src/class-ss-plugin.php';
require_once $simply_static_root . '/src/class-ss-options.php';
require_once $simply_static_root . '/src/class-ss-util.php';
require_once $simply_static_root . '/src/integrations/class-ss-integration.php';
require_once $simply_static_root . '/src/integrations/class-ss-rank-math-integration.php';

final class RankMathRedirectPage {
	/** @var string */
	public $url;

	/** @var string|null */
	public $last_modified_at;

	/** @var int */
	public $json_writes = 0;

	/** @var mixed */
	private $json_data = array();

	/** @param mixed $redirect_data Redirect metadata. */
	public function __construct( string $url, $redirect_data = null ) {
		$this->url = $url;

		if ( null !== $redirect_data ) {
			$this->json_data['rank_math_static_redirect'] = $redirect_data;
		}
	}

	/** @return array<string,mixed> */
	public function get_json(): array {
		return $this->json_data;
	}

	/** @param array<string,mixed> $data */
	public function set_json( array $data ): void {
		$this->json_data = $data;
		$this->json_writes++;
	}

	/** @return mixed */
	public function get_json_data_by_key( string $key ) {
		return array_key_exists( $key, $this->json_data ) ? $this->json_data[ $key ] : null;
	}

	/** @param mixed $data */
	public function set_json_data_by_key( string $key, $data ): void {
		$this->json_data[ $key ] = $data;
	}

	/** @return mixed */
	public function redirectData() {
		return $this->get_json_data_by_key( 'rank_math_static_redirect' );
	}
}

final class TestableRankMathRedirectIntegration extends Rank_Math_Integration {
	/** @var mixed */
	private $rows;

	/** @var int */
	public $redirect_reads = 0;

	/** @param mixed $rows */
	public function __construct( $rows ) {
		parent::__construct();
		$this->rows = $rows;
	}

	protected function get_redirects() {
		$this->redirect_reads++;

		return $this->rows;
	}

	/** @return array<string,array{url:string,status_code:int}>|null */
	public function staticRedirects() {
		return $this->get_static_redirects();
	}

	/** @param array<string,mixed> $redirect */
	public function storeRedirect( $page, array $redirect ): void {
		$this->set_static_page_redirect( $page, $redirect );
	}

	public function clearRedirect( $page ): bool {
		return $this->clear_static_page_redirect( $page );
	}

	/** @param mixed $redirect */
	public function registeredRedirect( $redirect, $page ) {
		return $this->get_registered_redirect( $redirect, $page );
	}
}

final class RankMathRedirectIntegrationTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::instance()->set( 'origin_url', 'https://wordpress.internal/site' );
		Options::instance()->set( 'archive_start_time', '2026-08-17 12:00:00' );
	}

	public function test_exact_redirects_are_resolved_from_the_configured_origin(): void {
		$integration = new TestableRankMathRedirectIntegration(
			array(
				array(
					'header_code' => '302',
					'url_to'      => '/new-page',
					'sources'     => array(
						array( 'pattern' => 'old-page', 'comparison' => 'exact' ),
					),
				),
				array(
					'header_code' => '301',
					'url_to'      => 'https://external.example/landing',
					'sources'     => array(
						array( 'pattern' => '/external-offer', 'comparison' => 'exact' ),
					),
				),
			)
		);

		self::assertSame(
			array(
				'https://wordpress.internal/site/old-page' => array(
					'url'         => 'https://wordpress.internal/site/new-page',
					'status_code' => 302,
				),
				'https://wordpress.internal/site/external-offer' => array(
					'url'         => 'https://external.example/landing',
					'status_code' => 301,
				),
			),
			$integration->staticRedirects()
		);
	}

	public function test_non_exact_and_non_redirect_rules_keep_the_http_fallback(): void {
		$integration = new TestableRankMathRedirectIntegration(
			array(
				array(
					'header_code' => '301',
					'url_to'      => '/new-page',
					'sources'     => array(
						array( 'pattern' => 'old-*', 'comparison' => 'regex' ),
					),
				),
				array(
					'header_code' => '410',
					'url_to'      => '/gone',
					'sources'     => array(
						array( 'pattern' => 'old-page', 'comparison' => 'exact' ),
					),
				),
			)
		);

		self::assertSame( array(), $integration->staticRedirects() );
	}

	public function test_redirect_query_failures_are_not_treated_as_an_empty_rule_set(): void {
		$integration = new TestableRankMathRedirectIntegration( null );

		self::assertNull( $integration->staticRedirects() );
	}

	public function test_query_specific_redirects_preserve_the_source_query_on_the_target(): void {
		$integration = new TestableRankMathRedirectIntegration(
			array(
				array(
					'header_code' => '301',
					'url_to'      => '/new-page?existing=1#details',
					'sources'     => array(
						array( 'pattern' => 'old-page?campaign=summer&lang=en', 'comparison' => 'exact' ),
					),
				),
			)
		);

		self::assertSame(
			array(
				'https://wordpress.internal/site/old-page?campaign=summer&lang=en' => array(
					'url'         => 'https://wordpress.internal/site/new-page?existing=1&campaign=summer&lang=en#details',
					'status_code' => 301,
				),
			),
			$integration->staticRedirects()
		);
	}

	public function test_query_specific_redirects_honor_rank_math_query_string_filter(): void {
		add_filter(
			'rank_math/redirection/add_query_string',
			static function () {
				return false;
			},
			10,
			2
		);

		$integration = new TestableRankMathRedirectIntegration(
			array(
				array(
					'header_code' => '301',
					'url_to'      => '/new-page?existing=1#details',
					'sources'     => array(
						array( 'pattern' => 'old-page?campaign=summer', 'comparison' => 'exact' ),
					),
				),
			)
		);

		self::assertSame(
			array(
				'https://wordpress.internal/site/old-page?campaign=summer' => array(
					'url'         => 'https://wordpress.internal/site/new-page?existing=1#details',
					'status_code' => 301,
				),
			),
			$integration->staticRedirects()
		);
	}

	public function test_registered_redirect_uses_page_metadata_without_loading_all_rules(): void {
		$redirect = array(
			'url'         => 'https://wordpress.internal/site/new-target',
			'status_code' => 302,
		);
		$page = new RankMathRedirectPage( 'https://wordpress.internal/site/source', $redirect );
		$integration = new TestableRankMathRedirectIntegration(
			array(
				array(
					'header_code' => '301',
					'url_to'      => '/unused',
					'sources'     => array(
						array( 'pattern' => 'unused', 'comparison' => 'exact' ),
					),
				),
			)
		);

		self::assertSame( $redirect, $integration->registeredRedirect( null, $page ) );
		self::assertSame( 0, $integration->redirect_reads );
	}

	public function test_registered_redirect_uses_http_fallback_for_single_exports(): void {
		$redirect = array(
			'url'         => 'https://wordpress.internal/site/new-target',
			'status_code' => 302,
		);
		$page = new RankMathRedirectPage( 'https://wordpress.internal/site/source', $redirect );
		$integration = new TestableRankMathRedirectIntegration( array() );
		update_option( 'simply-static-use-single', '42' );

		self::assertSame( 'existing-redirect', $integration->registeredRedirect( 'existing-redirect', $page ) );
		self::assertSame( 0, $integration->redirect_reads );
	}

	public function test_changed_redirect_metadata_marks_an_existing_page_for_changes_only(): void {
		$old_redirect = array(
			'url'         => 'https://wordpress.internal/site/old-target',
			'status_code' => 301,
		);
		$new_redirect = array(
			'url'         => 'https://wordpress.internal/site/new-target',
			'status_code' => 301,
		);
		$page = new RankMathRedirectPage( 'https://wordpress.internal/site/source', $old_redirect );
		$page->last_modified_at = '2026-08-01 08:00:00';
		$integration = new TestableRankMathRedirectIntegration( array() );

		$integration->storeRedirect( $page, $old_redirect );
		self::assertSame( '2026-08-01 08:00:00', $page->last_modified_at );
		self::assertSame( 0, $page->json_writes );

		$integration->storeRedirect( $page, $new_redirect );
		self::assertSame( $new_redirect, $page->redirectData() );
		self::assertSame( '2026-07-12 12:00:00', $page->last_modified_at );
		self::assertSame( 1, $page->json_writes );
	}

	public function test_clearing_stale_redirect_metadata_preserves_other_page_data(): void {
		$redirect = array(
			'url'         => 'https://wordpress.internal/site/old-target',
			'status_code' => 301,
		);
		$page = new RankMathRedirectPage( 'https://wordpress.internal/site/source', $redirect );
		$page->set_json_data_by_key( 'rank_math_static_redirect_export_started_at', '2026-08-16 12:00:00' );
		$page->set_json_data_by_key( 'unrelated_data', 'keep-me' );
		$integration = new TestableRankMathRedirectIntegration( array() );

		self::assertTrue( $integration->clearRedirect( $page ) );
		self::assertNull( $page->redirectData() );
		self::assertNull( $page->get_json_data_by_key( 'rank_math_static_redirect_export_started_at' ) );
		self::assertSame( 'keep-me', $page->get_json_data_by_key( 'unrelated_data' ) );
		self::assertSame( '2026-07-12 12:00:00', $page->last_modified_at );
		self::assertFalse( $integration->clearRedirect( $page ) );
	}
}
