<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Extractor;
use Simply_Static\Url_Fetcher;
use Simply_Static\Util;

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
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/class-ss-url-fetcher.php' );
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

	public function test_removes_wordpress_rest_discovery_links_when_rest_api_is_not_exported(): void {
		WpEnv::$options['simply-static']['add_rest_api'] = false;
		Options::reinstance();

		$html = '<html><head>'
			. '<link href="https://example.test/wp-json/" rel="https://api.w.org/">'
			. '<link title="REST JSON" href="https://example.test/?rest_route=/wp/v2/pages/7" type="application/json" rel="alternate">'
			. '<link rel="alternate" type="application/json+oembed" href="https://example.test/wp-json/oembed/1.0/embed?url=https%3A%2F%2Fexample.test%2Fblog%2Fpage">'
			. '<link rel="alternate" type="text/xml+oembed" href="https://example.test/wp-json/oembed/1.0/embed?format=xml">'
			. '<link title="Site feed" rel="alternate" type="application/json" href="https://example.test/feed.json">'
			. '</head><body></body></html>';
		$extractor = $this->extractor( 'html', $html );
		$urls      = $extractor->extract_and_update_urls();
		$body      = $extractor->get_body();

		self::assertStringNotContainsString( 'api.w.org', $body );
		self::assertStringNotContainsString( 'rest_route', $body );
		self::assertStringNotContainsString( 'json+oembed', $body );
		self::assertStringNotContainsString( 'xml+oembed', $body );
		self::assertStringContainsString( 'title="Site feed"', $body );
		self::assertNotContains( 'https://example.test/wp-json/', $urls );
		self::assertContains( 'https://example.test/feed.json', $urls );
	}

	public function test_preserves_wordpress_rest_discovery_links_when_rest_api_is_exported(): void {
		WpEnv::$options['simply-static']['add_rest_api'] = true;
		Options::reinstance();

		$html      = '<link rel="https://api.w.org/" href="https://example.test/wp-json/">';
		$extractor = $this->extractor( 'html', $html );
		$urls      = $extractor->extract_and_update_urls();

		self::assertStringContainsString( 'rel="https://api.w.org/"', $extractor->get_body() );
		self::assertStringContainsString( 'href="https://static.example.test/wp-json/"', $extractor->get_body() );
		self::assertContains( 'https://example.test/wp-json/', $urls );
	}

	public function test_css_imports_and_urls_are_extracted_and_rewritten(): void {
		$css = '@import url("../base.css"); .a{background:url(./image.png)} .b{src:url(https://external.test/font.woff2)}'
			. ".c{background:url('../single.png')}";
		$extractor = $this->extractor( 'css', $css, 'assets/site.css' );
		$urls = $extractor->extract_and_update_urls();

		self::assertContains( 'https://example.test/base.css', $urls );
		self::assertContains( 'https://example.test/blog/image.png', $urls );
		self::assertContains( 'https://example.test/single.png', $urls );
		self::assertNotContains( 'https://external.test/font.woff2', $urls );
		self::assertStringContainsString( 'https://static.example.test/base.css', $extractor->get_body() );
		self::assertStringContainsString( "url('https://static.example.test/single.png')", $extractor->get_body() );
	}

	public function test_css_svg_data_uri_preserves_namespace_url(): void {
		$css = '.search{background-image:url("data:image/svg+xml,<svg xmlns=\\"http://www.w3.org/2000/svg\\" '
			. 'width=\\"24\\" height=\\"24\\" fill=\\"none\\" stroke=\\"rgb(136, 145, 164)\\">'
			. '<circle cx=\\"11\\" cy=\\"11\\" r=\\"8\\"></circle></svg>")}'
			. '.select{mask-image:url("data:image/svg+xml,%3Csvg xmlns=\\"http://www.w3.org/2000/svg\\"%3E%3C/svg%3E")}';
		$extractor = $this->extractor( 'css', $css, 'assets/site.css' );

		$extractor->extract_and_update_urls();

		self::assertSame( $css, $extractor->get_body() );
	}

	public function test_large_css_svg_data_uri_does_not_empty_stylesheet(): void {
		$path = str_repeat( 'M0 0L1 1', 4000 );
		$css  = '.icon{background-image:url("data:image/svg+xml,<svg xmlns=\\"http://www.w3.org/2000/svg\\">'
			. '<path d=\\"' . $path . '\\"></path></svg>")}.after{background-image:url("../after.png")}';
		$expected  = str_replace( '../after.png', 'https://static.example.test/after.png', $css );
		$extractor = $this->extractor( 'css', $css, 'assets/large-svg.css' );

		$extractor->extract_and_update_urls();

		$body = $extractor->get_body();
		self::assertSame( strlen( $expected ), strlen( $body ) );
		self::assertSame( hash( 'sha256', $expected ), hash( 'sha256', $body ) );
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

	public function test_feed_discovery_links_target_the_exported_xml_file(): void {
		WpEnv::$options['simply-static']['add_feeds'] = true;
		Options::reinstance();

		$html = '<html><head>'
			. '<link rel="alternate" type="application/rss+xml" href="https://example.test/feed/">'
			. '</head><body><a href="https://example.test/feed/">ordinary page link</a></body></html>';
		$extractor = $this->extractor( 'html', $html );

		$urls = $extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'href="https://static.example.test/feed/index.xml"', $body );
		self::assertStringContainsString( 'href="https://static.example.test/feed/"', $body );
		self::assertContains( 'https://example.test/feed/', $urls );
	}

	public function test_disabled_feeds_are_not_advertised(): void {
		WpEnv::$options['simply-static']['add_feeds'] = false;
		Options::reinstance();

		$html = '<html><head>'
			. '<link rel="alternate" type="application/rss+xml" href="https://example.test/feed/">'
			. '</head><body></body></html>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();

		self::assertStringNotContainsString( 'application/rss+xml', $extractor->get_body() );
	}

	public function test_external_feed_discovery_links_are_preserved(): void {
		WpEnv::$options['simply-static']['add_feeds'] = false;
		Options::reinstance();

		$html = '<html><head>'
			. '<link rel="alternate" type="application/rss+xml" href="https://feeds.example.net/feed/">'
			. '</head><body></body></html>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();

		self::assertStringContainsString( 'href="https://feeds.example.net/feed/"', $extractor->get_body() );
	}

	public function test_plain_permalink_feed_discovery_targets_the_stable_xml_file(): void {
		WpEnv::$options['simply-static']['add_feeds'] = true;
		Options::reinstance();

		$html = '<html><head>'
			. '<link rel="alternate" type="application/rss+xml" href="https://example.test/?feed=rss2">'
			. '</head><body></body></html>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();

		self::assertStringContainsString(
			'href="https://static.example.test/feed/index.xml"',
			$extractor->get_body()
		);
	}

	public function test_feed_self_reference_targets_xml_and_preserves_all_items(): void {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
			. '<atom:link href="https://example.test/feed/" rel="self" type="application/rss+xml" />'
			. '<link>https://example.test/</link>'
			. '<item><guid>https://example.test/?p=1</guid><link>https://example.test/one/</link></item>'
			. '<item><guid>https://example.test/?p=2</guid><link>https://example.test/two/</link></item>'
			. '</channel></rss>';
		$extractor = $this->extractor( 'xml', $xml, 'feed/index.xml', 'https://example.test/feed/' );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'href="https://static.example.test/feed/index.xml"', $body );
		self::assertStringContainsString( '<link>https://static.example.test/</link>', $body );
		self::assertStringContainsString( '<link>https://static.example.test/one/</link>', $body );
		self::assertStringContainsString( '<link>https://static.example.test/two/</link>', $body );

		$document = new \DOMDocument();
		self::assertTrue( $document->loadXML( $body ) );
		self::assertSame( 2, $document->getElementsByTagName( 'item' )->length );
	}

	public function test_plain_permalink_feed_only_rewrites_its_self_reference(): void {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
			. '<atom:link href="https://example.test/?feed=rss2" rel="self" type="application/rss+xml" />'
			. '<link>https://example.test/</link>'
			. '</channel></rss>';
		$extractor = $this->extractor( 'xml', $xml, 'feed/index.xml', 'https://example.test/?feed=rss2' );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'href="https://static.example.test/feed/index.xml"', $body );
		self::assertStringContainsString( '<link>https://static.example.test/</link>', $body );
	}

	public function test_replaces_bare_origin_host_in_inline_scripts(): void {
		$html = '<script id="google_gtagjs-js-after">'
			. 'gtag("set","linker",{"domains":["example.test"]});'
			. "const alternate = 'example.test';"
			. 'const external = "external.test";'
			. 'const prefixed = "www.example.test";'
			. 'const suffixed = "example.test.invalid";'
			. 'const email = "user@example.test";'
			. '</script>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( '"domains":["static.example.test"]', $body );
		self::assertStringContainsString( "const alternate = 'static.example.test';", $body );
		self::assertStringContainsString( 'const external = "external.test";', $body );
		self::assertStringContainsString( 'const prefixed = "www.example.test";', $body );
		self::assertStringContainsString( 'const suffixed = "example.test.invalid";', $body );
		self::assertStringContainsString( 'const email = "user@example.test";', $body );
	}

	public function test_preserves_bare_origin_host_for_non_absolute_exports(): void {
		WpEnv::$options['simply-static']['destination_url_type'] = 'relative';
		WpEnv::$options['simply-static']['relative_path'] = '/';
		Options::reinstance();

		$html = '<script>gtag("set","linker",{"domains":["example.test"]});</script>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();

		self::assertStringContainsString( '"domains":["example.test"]', $extractor->get_body() );
	}

	public function test_restores_comments_when_dom_filter_returns_html_string(): void {
		add_filter( 'ss_dom_before_save', function ( $dom ) {
			return $dom instanceof \DOMDocument ? $dom->saveHTML() : $dom;
		} );

		$html = '<meta name="google-site-verification" content="verification-code">'
			. '<!-- Google tag (gtag.js) snippet added by Site Kit -->'
			. '<script id="google_gtagjs-js-after">'
			. 'gtag("set","linker",{"domains":["example.test"]});'
			. '</script>'
			. '<!-- End Google tag (gtag.js) snippet added by Site Kit -->';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( '<!-- Google tag (gtag.js) snippet added by Site Kit -->', $body );
		self::assertStringContainsString( '<!-- End Google tag (gtag.js) snippet added by Site Kit -->', $body );
		self::assertStringContainsString( '"domains":["static.example.test"]', $body );
		self::assertStringNotContainsString( 'COMMENT_PLACEHOLDER', $body );
		self::assertStringNotContainsString( 'SCRIPT_PLACEHOLDER', $body );
	}

	public function test_decodes_css_glyphs_when_dom_filter_returns_html_string(): void {
		add_filter( 'ss_dom_before_save', function ( $dom ) {
			return $dom instanceof \DOMDocument ? $dom->saveHTML() : $dom;
		} );

		$html = '<html><head><style id="elementor-frontend-inline-css">'
			. '.previous::after{content:"←"}.next::after{content:"→"}.check::before{content:"✓"}'
			. '</style></head><body></body></html>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'content:"←"', $body );
		self::assertStringContainsString( 'content:"→"', $body );
		self::assertStringContainsString( 'content:"✓"', $body );
		self::assertStringNotContainsString( '&larr;', $body );
		self::assertStringNotContainsString( '&rarr;', $body );
		self::assertStringNotContainsString( '&#10003;', $body );
	}

	public function test_restores_comments_in_script_only_html_fragment(): void {
		$html = '<!-- Google tag (gtag.js) snippet added by Site Kit -->'
			. '<script>gtag("config","G-TEST");</script>'
			. '<!-- End Google tag (gtag.js) snippet added by Site Kit -->';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( '<!-- Google tag (gtag.js) snippet added by Site Kit -->', $body );
		self::assertStringContainsString( '<script>gtag("config","G-TEST");</script>', $body );
		self::assertStringContainsString( '<!-- End Google tag (gtag.js) snippet added by Site Kit -->', $body );
		self::assertStringNotContainsString( 'COMMENT_PLACEHOLDER', $body );
		self::assertStringNotContainsString( 'SCRIPT_PLACEHOLDER', $body );
	}

	public function test_relative_and_offline_destination_modes_produce_local_paths(): void {
		WpEnv::$options['simply-static']['destination_url_type'] = 'relative';
		WpEnv::$options['simply-static']['relative_path'] = '/';
		Options::reinstance();
		$relative = $this->extractor( 'html', '<a href="https://example.test/target/">target</a>' );
		self::assertSame( '/target/', $relative->convert_url( 'https://example.test/target/' ) );
		self::assertSame(
			'window.site = "/tc/";',
			$relative->force_replace( 'window.site = "https://example.test/tc/";' )
		);
		$relative->extract_and_update_urls();
		self::assertStringContainsString( 'href="/target/"', $relative->get_body() );
		self::assertStringNotContainsString( 'href="//target/"', $relative->get_body() );

		WpEnv::$options['simply-static']['relative_path'] = '/docs/';
		Options::reinstance();
		$mounted = $this->extractor( 'html', '<a href="https://example.test/target/">target</a>' );
		self::assertSame( '/docs/target/', $mounted->convert_url( 'https://example.test/target/' ) );

		WpEnv::$options['simply-static']['destination_url_type'] = 'offline';
		Options::reinstance();
		$offline = $this->extractor( 'html', '<a href="/target/">target</a>' );
		$offline->extract_and_update_urls();
		self::assertStringContainsString( 'target/index.html', $offline->get_body() );
	}

	public function test_relative_exports_preserve_wordpress_install_subdirectory_for_assets(): void {
		WpEnv::$home_url = 'https://example.test';
		WpEnv::$site_url = 'https://example.test/wordpress';
		WpEnv::$options['simply-static']['destination_url_type'] = 'relative';
		WpEnv::$options['simply-static']['relative_path'] = '/';
		WpEnv::$options['simply-static']['wp_content_directory'] = 'wp-content';
		WpEnv::$options['simply-static']['wp_includes_directory'] = 'wp-includes';
		Options::reinstance();

		$content_url  = 'https://example.test/wordpress/wp-content/themes/site/style.css';
		$includes_url = 'https://example.test/wordpress/wp-includes/js/jquery/jquery.min.js';

		// Redirect comparisons still normalize alternate WordPress URL bases.
		self::assertSame( '/wp-content/themes/site/style.css', Util::get_path_from_local_url( $content_url ) );
		self::assertSame( '/wp-includes/js/jquery/jquery.min.js', Util::get_path_from_local_url( $includes_url ) );

		// Export paths retain the WordPress Address suffix relative to the public Site Address.
		self::assertSame( '/wordpress/wp-content/themes/site/style.css', Util::get_public_path_from_local_url( $content_url ) );
		self::assertSame( '/wordpress/wp-includes/js/jquery/jquery.min.js', Util::get_public_path_from_local_url( $includes_url ) );

		$fetcher_ref = new \ReflectionClass( Url_Fetcher::class );
		$fetcher     = $fetcher_ref->newInstanceWithoutConstructor();
		$archive_dir = $fetcher_ref->getProperty( 'archive_dir' );
		$archive_dir->setAccessible( true );
		$archive_dir->setValue( $fetcher, $this->archive_dir );
		$asset_page = Page::initialize( array(
			'url'              => $content_url,
			'http_status_code' => 200,
			'content_type'     => 'text/css',
		) );

		self::assertSame(
			'wordpress/wp-content/themes/site/style.css',
			$fetcher->create_directories_for_static_page( $asset_page )
		);

		$relative = $this->extractor( 'html', '<link rel="stylesheet" href="' . $content_url . '"><script src="' . $includes_url . '"></script>' );
		self::assertSame( '/wordpress/wp-content/themes/site/style.css', $relative->convert_url( $content_url ) );
		self::assertSame( '/wordpress/wp-includes/js/jquery/jquery.min.js', $relative->convert_url( $includes_url ) );

		$relative->extract_and_update_urls();
		self::assertStringContainsString( 'href="/wordpress/wp-content/themes/site/style.css"', $relative->get_body() );
		self::assertStringContainsString( 'src="/wordpress/wp-includes/js/jquery/jquery.min.js"', $relative->get_body() );
	}

	public function test_subdirectory_asset_prefix_composes_with_hide_wordpress_directories(): void {
		WpEnv::$home_url = 'https://example.test';
		WpEnv::$site_url = 'https://example.test/wordpress';
		WpEnv::$options['simply-static']['wp_content_directory'] = 'assets';
		WpEnv::$options['simply-static']['wp_includes_directory'] = 'core';
		Options::reinstance();

		self::assertSame(
			'/wordpress/assets/themes/site/style.css',
			Util::get_public_path_from_local_url( 'https://example.test/wordpress/wp-content/themes/site/style.css' )
		);
		self::assertSame(
			'/wordpress/core/js/jquery/jquery.min.js',
			Util::get_public_path_from_local_url( 'https://example.test/wordpress/wp-includes/js/jquery/jquery.min.js' )
		);

		WpEnv::$home_url = 'https://example.test/wordpress';
		WpEnv::$options['simply-static']['wp_content_directory'] = 'wp-content';
		WpEnv::$options['simply-static']['wp_includes_directory'] = 'wp-includes';
		Options::reinstance();

		self::assertSame(
			'/wp-content/themes/site/style.css',
			Util::get_public_path_from_local_url( 'https://example.test/wordpress/wp-content/themes/site/style.css' )
		);
	}

	public function test_rewrites_unconfigured_runtime_origin_without_force_replace(): void {
		$runtime_origin = 'https://wp-runtime.example.test';
		$sprite_path    = '/wp-content/plugins/simple-social-icons/symbol-defs.svg';
		$portrait_path  = '/wp-content/uploads/story-portrait.jpg';
		$html           = '<html><head>'
			. '<link rel="preconnect" href="' . $runtime_origin . '">'
			. '<link rel="dns-prefetch" href="' . $runtime_origin . '">'
			. '</head><body>'
			. '<svg><use xlink:href="' . $runtime_origin . $sprite_path . '#social-facebook"></use></svg>'
			. '<amp-story publisher-logo-src="' . $runtime_origin . '/wp-content/uploads/logo.jpg" '
			. 'poster-portrait-src="' . $runtime_origin . $portrait_path . '"></amp-story>'
			. '<input type="hidden" name="page_url" value="' . $runtime_origin . '/blog/page/">'
			. '<p>Visit ' . $runtime_origin . ' or search '
			. 'https://external.test/?q=' . $runtime_origin . '/privacy-policy.</p>'
			. '</body></html>';

		$extractor = $this->extractor( 'html', $html, 'page.html', $runtime_origin . '/blog/page/' );
		$urls      = $extractor->extract_and_update_urls();
		$body      = $extractor->get_body();

		self::assertStringNotContainsString( $runtime_origin, $body );
		self::assertStringNotContainsString( 'rel="preconnect"', $body );
		self::assertStringNotContainsString( 'rel="dns-prefetch"', $body );
		self::assertStringContainsString( 'https://static.example.test' . $sprite_path . '#social-facebook', $body );
		self::assertStringContainsString( 'https://static.example.test' . $portrait_path, $body );
		self::assertStringContainsString( 'value="https://static.example.test/blog/page/"', $body );
		self::assertStringContainsString( 'https://external.test/?q=https://static.example.test/privacy-policy', $body );
		self::assertContains( $runtime_origin . $sprite_path, $urls );
		self::assertContains( $runtime_origin . $portrait_path, $urls );
		self::assertFalse( Util::is_local_url( $runtime_origin . '/after-extraction' ) );
	}

	public function test_preserves_excluded_php_form_actions_during_force_replace(): void {
		WpEnv::$options['simply-static']['force_replace_url'] = true;
		Options::reinstance();

		$endpoint  = 'https://example.test/wp-content/themes/site/download-ics.php';
		add_filter(
			'simply_static_content_before_save',
			static function ( $content ) use ( $endpoint ) {
				return str_replace( $endpoint, 'https://static.example.test/wp-content/themes/site/download-ics.php', $content );
			},
			PHP_INT_MAX
		);
		$html      = '<form action="' . $endpoint . '" method="post"></form>'
			. '<form action="https://example.test/contact/"></form>'
			. '<a href="' . $endpoint . '">download</a>';
		$extractor = $this->extractor( 'html', $html );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'action="' . $endpoint . '"', $body );
		self::assertStringContainsString( 'action="https://static.example.test/contact/"', $body );
		self::assertStringContainsString( 'href="https://static.example.test/wp-content/themes/site/download-ics.php"', $body );
		self::assertStringNotContainsString( 'SS_PRESERVED_FORM_ACTION_', $body );
	}

	public function test_preserves_excluded_php_form_actions_on_unconfigured_runtime_origin(): void {
		$runtime_origin = 'https://wp-runtime.example.test';
		$endpoint       = $runtime_origin . '/wp-content/themes/site/download-ics.php';
		$html           = '<form class="calendar" action=\'' . $endpoint . '\' method="post"></form>'
			. '<input type="hidden" value="' . $runtime_origin . '/event/">';
		$extractor      = $this->extractor( 'html', $html, 'page.html', $runtime_origin . '/store/' );

		$extractor->extract_and_update_urls();
		$body = $extractor->get_body();

		self::assertStringContainsString( 'action="' . $endpoint . '"', $body );
		self::assertStringContainsString( 'value="https://static.example.test/event/"', $body );
		self::assertStringNotContainsString( 'SS_PRESERVED_FORM_ACTION_', $body );
	}

	public function test_form_action_preservation_can_be_enabled_by_filter(): void {
		WpEnv::$options['simply-static']['force_replace_url'] = true;
		Options::reinstance();

		$endpoint = 'https://example.test/calendar/download';
		add_filter(
			'simply_static_preserve_form_action',
			static function ( $preserve, $action ) use ( $endpoint ) {
				return $preserve || $endpoint === $action;
			},
			10,
			2
		);

		$extractor = $this->extractor( 'html', '<form action="' . $endpoint . '"></form>' );
		$extractor->extract_and_update_urls();

		self::assertStringContainsString( 'action="' . $endpoint . '"', $extractor->get_body() );
	}

	private function extractor( string $type, string $body, string $file_path = 'page.html', string $page_url = 'https://example.test/blog/page' ): Url_Extractor {
		$unique_path = str_replace( '/', '-', uniqid( '', true ) ) . '-' . basename( $file_path );
		file_put_contents( $this->archive_dir . $unique_path, $body );

		$page = Page::initialize( array(
			'url'               => $page_url,
			'file_path'         => $unique_path,
			'http_status_code'  => 200,
			'content_type'      => 'text/' . $type . '; charset=UTF-8',
		) );

		return new Url_Extractor( $page );
	}
}
