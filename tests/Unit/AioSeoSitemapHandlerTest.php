<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use DOMDocument;
use Simply_Static\AIO_SEO_Integration;
use Simply_Static\AIO_SEO_Sitemap_Handler;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use XSLTProcessor;

$simply_static_root = dirname( __DIR__, 2 );
require_once $simply_static_root . '/src/class-ss-plugin.php';
require_once $simply_static_root . '/src/class-ss-options.php';
require_once $simply_static_root . '/src/class-ss-util.php';
require_once $simply_static_root . '/src/class-ss-query.php';
require_once $simply_static_root . '/src/models/class-ss-model.php';
require_once $simply_static_root . '/src/models/class-ss-page.php';
require_once $simply_static_root . '/src/handlers/class-ss-page-handler.php';
require_once $simply_static_root . '/src/integrations/class-ss-integration.php';
require_once $simply_static_root . '/src/handlers/class-ss-aio-seo-sitemap-handler.php';
require_once $simply_static_root . '/src/integrations/class-ss-aio-seo-integration.php';

final class AioSeoSitemapWpdb {

	/** @var int */
	public $insert_id = 0;

	/** @var array<int,array<string,mixed>> */
	public $inserted_rows = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @return null */
	public function get_row( string $query, string $output_type ) {
		return null;
	}

	/** @param array<string,mixed> $fields */
	public function insert( string $table, array $fields ): int {
		$this->insert_id++;
		$this->inserted_rows[] = $fields;

		return 1;
	}
}

final class AioSeoSitemapHandlerTest extends UnitTestCase {

	/** @var string */
	private $archive_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->archive_dir = sys_get_temp_dir() . '/simply-static-aioseo-' . str_replace( '.', '', uniqid( '', true ) );
		mkdir( $this->archive_dir, 0777, true );

		Options::instance()
			->set( 'origin_url', 'https://wordpress.internal' )
			->set( 'destination_url_type', 'absolute' )
			->set( 'destination_scheme', 'https://' )
			->set( 'destination_host', 'static.example' );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );

		foreach ( glob( $this->archive_dir . '/*' ) ?: array() as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}

		if ( is_dir( $this->archive_dir ) ) {
			rmdir( $this->archive_dir );
		}

		parent::tearDown();
	}

	public function test_it_rewrites_root_and_paginated_sitemap_stylesheets(): void {
		$root_file      = $this->archive_dir . '/sitemap.xml';
		$paginated_file = $this->archive_dir . '/tribe_events-sitemap2.xml';

		file_put_contents(
			$root_file,
			'<?xml version="1.0"?>' . "\n"
			. '<?xml-stylesheet type="text/xsl" href="https://wordpress.internal/default-sitemap.xsl?sitemap=root"?>' . "\n"
			. '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>https://wordpress.internal/page-sitemap.xml</loc></sitemap></sitemapindex>'
		);
		file_put_contents(
			$paginated_file,
			'<?xml version="1.0"?>' . "\n"
			. "<?xml-stylesheet href='https://wordpress.internal/default-sitemap.xsl?sitemap=tribe_events' type='text/xsl'?>" . "\n"
			. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://wordpress.internal/event/</loc></url></urlset>'
		);

		$this->handler( 'https://wordpress.internal/main-sitemap.xsl' )->after_file_fetch( $this->archive_dir );

		$root       = (string) file_get_contents( $root_file );
		$paginated  = (string) file_get_contents( $paginated_file );
		$stylesheet = '<?xml-stylesheet type="text/xsl" href="https://static.example/main-sitemap.xsl"?>';

		self::assertStringContainsString( $stylesheet, $root );
		self::assertStringContainsString( $stylesheet, $paginated );
		self::assertStringContainsString( '<loc>https://static.example/page-sitemap.xml</loc>', $root );
		self::assertStringContainsString( '<loc>https://static.example/event/</loc>', $paginated );
		self::assertStringNotContainsString( 'default-sitemap.xsl', $root . $paginated );
	}

	public function test_the_static_stylesheet_transforms_indexes_and_url_sets(): void {
		$stylesheet = new DOMDocument();
		self::assertTrue( $stylesheet->loadXML( AIO_SEO_Sitemap_Handler::stylesheet_content() ) );

		$processor = new XSLTProcessor();
		self::assertTrue( $processor->importStylesheet( $stylesheet ) );

		$index = new DOMDocument();
		$index->loadXML(
			'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
			. '<sitemap><loc>https://static.example/page-sitemap.xml</loc><lastmod>2026-08-17</lastmod></sitemap>'
			. '</sitemapindex>'
		);
		$index_html = $processor->transformToXML( $index );

		self::assertIsString( $index_html );
		self::assertStringContainsString( 'contains 1 sitemaps', $index_html );
		self::assertStringContainsString( 'https://static.example/page-sitemap.xml', $index_html );

		$url_set = new DOMDocument();
		$url_set->loadXML(
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
			. '<url><loc>https://static.example/page/</loc><image:image><image:loc>https://static.example/image.jpg</image:loc></image:image></url>'
			. '</urlset>'
		);
		$url_set_html = $processor->transformToXML( $url_set );

		self::assertIsString( $url_set_html );
		self::assertStringContainsString( 'contains 1 URLs', $url_set_html );
		self::assertStringContainsString( 'https://static.example/page/', $url_set_html );
	}

	public function test_only_the_stylesheet_page_registers_the_virtual_endpoint(): void {
		$this->handler( 'https://wordpress.internal/sitemap.xml' )->run_hooks();
		self::assertArrayNotHasKey( 'template_redirect', WpEnv::$filters );

		$this->handler( 'https://wordpress.internal/main-sitemap.xsl' )->run_hooks();
		self::assertArrayHasKey( 'template_redirect', WpEnv::$filters );
	}

	public function test_single_exports_include_the_generated_stylesheet_url(): void {
		$urls = ( new AIO_SEO_Integration() )->add_sitemap_url( array() );

		self::assertContains( 'https://example.test/sitemap.xml', $urls );
		self::assertContains( 'https://example.test/main-sitemap.xsl', $urls );
	}

	public function test_the_generated_stylesheet_is_registered_as_a_deployable_page(): void {
		$wpdb             = new AioSeoSitemapWpdb();
		$GLOBALS['wpdb'] = $wpdb;

		( new AIO_SEO_Integration() )->register_stylesheet_page();

		self::assertCount( 1, $wpdb->inserted_rows );
		self::assertSame( 'https://example.test/main-sitemap.xsl', $wpdb->inserted_rows[0]['url'] );
		self::assertSame( AIO_SEO_Sitemap_Handler::class, $wpdb->inserted_rows[0]['handler'] );
		self::assertSame( 0, $wpdb->inserted_rows[0]['found_on_id'] );
	}

	public function test_allowed_query_argument_is_not_duplicated(): void {
		$integration = new AIO_SEO_Integration();

		self::assertSame(
			array( 'existing', 'simply_static_page' ),
			$integration->allowed_query_args( array( 'existing', 'simply_static_page' ) )
		);
	}

	private function handler( string $url ): AIO_SEO_Sitemap_Handler {
		$page = Page::initialize( array( 'url' => $url ) );

		return new AIO_SEO_Sitemap_Handler( $page );
	}
}
