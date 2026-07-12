<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Extractor;

final class UrlExtractorTest extends UnitTestCase {

	/** @var string */
	private $archive_dir;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-html-encoding-helper.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/class-ss-url-extractor.php' );

		$base = WpEnv::$upload_dir['basedir'] . '/simply-static/url-extractor';
		WpEnv::$options['simply-static'] = array(
			'temp_files_dir'       => $base,
			'archive_name'         => 'archive/',
			'destination_url_type' => 'absolute',
			'destination_scheme'   => 'https://',
			'destination_host'     => 'static.example.test',
			'relative_path'        => '',
			'origin_url'           => '',
			'force_replace_url'    => false,
			'use_forms'            => false,
			'use_comments'         => false,
		);
		Options::reinstance();
		$this->archive_dir = trailingslashit( $base ) . 'archive/';
		wp_mkdir_p( $this->archive_dir );
	}

	/**
	 * @dataProvider htmlLinkProvider
	 */
	public function test_extracts_local_html_links_and_ignores_non_crawlable_links( string $html, ?string $expected ): void {
		$extractor = $this->extractor( 'html', $html );
		$urls      = $extractor->extract_and_update_urls();

		if ( null === $expected ) {
			self::assertSame( array(), array_values( $urls ) );
		} else {
			self::assertContains( $expected, $urls );
		}
	}

	/** @return array<string,array{string,?string}> */
	public function htmlLinkProvider(): array {
		return array(
			'root relative'       => array( '<a href="/one.html">one</a>', 'https://example.test/one.html' ),
			'document relative'   => array( '<a href="two.html">two</a>', 'https://example.test/blog/two.html' ),
			'parent path'         => array( '<a href="../three.html">three</a>', 'https://example.test/three.html' ),
			'protocol relative'   => array( '<a href="//example.test/four.html">four</a>', 'https://example.test/four.html' ),
			'query and fragment'  => array( '<a href="/five.html?q=1#part">five</a>', 'https://example.test/five.html' ),
			'external'            => array( '<a href="https://external.test/a">external</a>', null ),
			'javascript'          => array( '<a href="javascript:alert(1)">bad</a>', null ),
			'fragment only'       => array( '<a href="#section">same page</a>', null ),
			'commented markup'    => array( '<!-- <a href="/hidden">hidden</a> -->', null ),
		);
	}

	public function test_extracts_srcset_style_and_media_attributes(): void {
		$html = '<style>.hero{background:url("/hero.webp")}</style>'
			. '<img src="/image.jpg" srcset="/small.jpg 320w, /large.jpg 1280w" style="mask-image:url(/mask.svg)">'
			. '<video src="movie.mp4" poster="/poster.jpg"></video>';
		$urls = $this->extractor( 'html', $html )->extract_and_update_urls();

		foreach ( array(
			'https://example.test/hero.webp',
			'https://example.test/image.jpg',
			'https://example.test/small.jpg',
			'https://example.test/large.jpg',
			'https://example.test/mask.svg',
			'https://example.test/blog/movie.mp4',
			'https://example.test/poster.jpg',
		) as $expected ) {
			self::assertContains( $expected, $urls );
		}
	}

	public function test_css_imports_and_urls_are_extracted_and_rewritten(): void {
		$css = '@import url("../base.css"); .a{background:url(./image.png)} .b{src:url(https://external.test/font.woff2)}';
		$extractor = $this->extractor( 'css', $css, 'assets/site.css' );
		$urls = $extractor->extract_and_update_urls();

		self::assertContains( 'https://example.test/base.css', $urls );
		self::assertContains( 'https://example.test/blog/image.png', $urls );
		self::assertNotContains( 'https://external.test/font.woff2', $urls );
		self::assertStringContainsString( 'https://static.example.test/base.css', $extractor->get_body() );
	}

	public function test_json_and_xml_urls_are_replaced_without_touching_external_origins(): void {
		$json = '{"local":"https:\/\/example.test\/api\/items","external":"https:\/\/external.test\/api"}';
		$json_extractor = $this->extractor( 'json', $json, 'data.json' );
		$json_extractor->extract_and_update_urls();
		self::assertStringContainsString( 'static.example.test', $json_extractor->get_body() );
		self::assertStringContainsString( 'external.test', $json_extractor->get_body() );

		$xml = '<?xml version="1.0"?><urlset><url><loc>https://example.test/page</loc></url><image>https://external.test/a.jpg</image></urlset>';
		$xml_extractor = $this->extractor( 'xml', $xml, 'sitemap.xml' );
		$urls = $xml_extractor->extract_and_update_urls();
		self::assertContains( 'https://example.test/page', $urls );
		self::assertStringContainsString( 'https://static.example.test/page', $xml_extractor->get_body() );
		self::assertStringContainsString( 'https://external.test/a.jpg', $xml_extractor->get_body() );
	}

	public function test_relative_and_offline_destination_modes_produce_local_paths(): void {
		WpEnv::$options['simply-static']['destination_url_type'] = 'relative';
		Options::reinstance();
		$relative = $this->extractor( 'html', '<a href="/target/">target</a>' );
		$relative->extract_and_update_urls();
		self::assertStringContainsString( 'href="/target/"', $relative->get_body() );

		WpEnv::$options['simply-static']['destination_url_type'] = 'offline';
		Options::reinstance();
		$offline = $this->extractor( 'html', '<a href="/target/">target</a>' );
		$offline->extract_and_update_urls();
		self::assertStringContainsString( 'target/index.html', $offline->get_body() );
	}

	private function extractor( string $type, string $body, string $file_path = 'page.html' ): Url_Extractor {
		$unique_path = str_replace( '/', '-', uniqid( '', true ) ) . '-' . basename( $file_path );
		file_put_contents( $this->archive_dir . $unique_path, $body );

		$page = Page::initialize( array(
			'url'               => 'https://example.test/blog/page',
			'file_path'         => $unique_path,
			'http_status_code'  => 200,
			'content_type'      => 'text/' . $type . '; charset=UTF-8',
		) );

		return new Url_Extractor( $page );
	}
}
