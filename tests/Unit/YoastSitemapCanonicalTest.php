<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Yoast_Integration;

final class YoastSitemapCanonicalTest extends UnitTestCase {

	private const CANONICAL = '_yoast_wpseo_canonical';

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-yoast-integration.php' );

		WpEnv::$home_url = 'https://wordpress.internal';
		WpEnv::$site_url = WpEnv::$home_url;
		WpEnv::$permalinks[263] = 'https://wordpress.internal/blog/example/';
		WpEnv::$post_meta[263] = array(
			self::CANONICAL                      => array( 'https://static.example/blog/example/' ),
			'_yoast_wpseo_meta-robots-noindex'   => array( '1' ),
			'_yoast_wpseo_metadesc'             => array( 'Preserve the description.' ),
			'custom_metadata'                    => array( 'one', 'two' ),
		);
		Options::instance()
			->set( 'origin_url', WpEnv::$home_url )
			->set( 'destination_url_type', 'absolute' )
			->set( 'destination_scheme', 'https://' )
			->set( 'destination_host', 'static.example' );

		( new Yoast_Integration() )->run();
	}

	public function test_only_sitemap_generation_sees_the_origin_self_canonical(): void {
		$stored = WpEnv::$post_meta[263];
		self::assertSame( $stored, get_post_meta( 263 ) );
		self::assertSame( 'page', apply_filters( 'wpseo_build_sitemap_post_type', 'page' ) );

		// Yoast's get_post_custom() reads the complete metadata array.
		$expected = $stored;
		$expected[ self::CANONICAL ][0] = WpEnv::$permalinks[263];
		self::assertSame( $expected, get_post_meta( 263 ) );
		self::assertSame( $stored, WpEnv::$post_meta[263] );
		self::assertSame( '1', get_post_meta( 263, '_yoast_wpseo_meta-robots-noindex', true ) );

		self::assertSame( '<urlset>', apply_filters( 'wpseo_sitemap_urlset', '<urlset>' ) );
		self::assertSame( $stored, get_post_meta( 263 ) );
	}

	public function test_individual_canonical_reads_keep_wordpress_single_and_list_semantics(): void {
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( WpEnv::$permalinks[263], get_post_meta( 263, self::CANONICAL, true ) );
		self::assertSame( array( WpEnv::$permalinks[263] ), get_post_meta( 263, self::CANONICAL ) );
		self::assertSame( array( 'one', 'two' ), get_post_meta( 263, 'custom_metadata' ) );
	}

	/** @dataProvider excludedCanonicalProvider */
	public function test_other_canonical_targets_are_preserved( string $canonical ): void {
		WpEnv::$post_meta[263][ self::CANONICAL ] = array( $canonical );
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( $canonical, get_post_meta( 263, self::CANONICAL, true ) );
		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
	}

	public function excludedCanonicalProvider(): array {
		return array(
			'external site'       => array( 'https://other.example/blog/example/' ),
			'different page'      => array( 'https://static.example/blog/other/' ),
			'query string'        => array( 'https://static.example/blog/example/?canonical=1' ),
			'fragment'            => array( 'https://static.example/blog/example/#section' ),
			'different slash'     => array( 'https://static.example/blog/example' ),
			'different scheme'    => array( 'http://static.example/blog/example/' ),
			'lookalike hostname'  => array( 'https://static.example.attacker.test/blog/example/' ),
			'already source URL'  => array( 'https://wordpress.internal/blog/example/' ),
			'empty canonical'     => array( '' ),
		);
	}

	public function test_existing_metadata_short_circuits_are_preserved(): void {
		$metadata = array( self::CANONICAL => array( 'https://other.example/' ) );
		add_filter( 'get_post_metadata', static function () use ( $metadata ) { return $metadata; }, 5 );
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( $metadata, get_post_meta( 263 ) );
	}

	/** @dataProvider unsupportedDestinationProvider */
	public function test_unsupported_destinations_do_not_change_metadata( string $type, string $scheme, string $host ): void {
		Options::instance()->set( 'destination_url_type', $type )->set( 'destination_scheme', $scheme )->set( 'destination_host', $host );
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
	}

	public function unsupportedDestinationProvider(): array {
		return array(
			'relative export'   => array( 'relative', 'https://', 'static.example' ),
			'offline export'    => array( 'offline', 'https://', 'static.example' ),
			'empty host'        => array( 'absolute', 'https://', '' ),
			'unsupported scheme' => array( 'absolute', 'ftp://', 'static.example' ),
			'credentials'       => array( 'absolute', 'https://', 'user@static.example' ),
			'query string'      => array( 'absolute', 'https://', 'static.example?setting=1' ),
			'fragment'          => array( 'absolute', 'https://', 'static.example#setting' ),
		);
	}

	public function test_non_post_sitemaps_reset_any_previous_normalization(): void {
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );
		self::assertSame( WpEnv::$permalinks[263], get_post_meta( 263, self::CANONICAL, true ) );

		self::assertSame( 'category', apply_filters( 'wpseo_build_sitemap_post_type', 'category' ) );
		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
		self::assertSame( '1', apply_filters( 'wpseo_build_sitemap_post_type', '1' ) );
		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
	}

	public function test_error_responses_restore_normal_metadata_reads(): void {
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );
		self::assertSame( 'HTTP/1.1 404 Not Found', apply_filters( 'status_header', 'HTTP/1.1 404 Not Found', 404 ) );
		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
	}

	public function test_origin_and_destination_subdirectories_map_to_the_same_page(): void {
		Options::instance()->set( 'origin_url', 'https://wordpress.internal/source' )->set( 'destination_host', 'static.example/published' );
		WpEnv::$permalinks[263] = 'https://wordpress.internal/source/blog/example/';
		WpEnv::$post_meta[263][ self::CANONICAL ] = array( 'https://static.example/published/blog/example/' );
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( WpEnv::$permalinks[263], get_post_meta( 263, self::CANONICAL, true ) );
	}

	public function test_external_permalinks_do_not_become_local_canonicals(): void {
		WpEnv::$permalinks[263] = 'https://wordpress.internal.attacker.test/blog/example/';
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
	}

	public function test_missing_canonical_metadata_is_preserved(): void {
		unset( WpEnv::$post_meta[263][ self::CANONICAL ] );
		apply_filters( 'wpseo_build_sitemap_post_type', 'page' );

		self::assertSame( WpEnv::$post_meta[263], get_post_meta( 263 ) );
		self::assertSame( '', get_post_meta( 263, self::CANONICAL, true ) );
	}
}
