<?php
namespace Simply_Static;

class Url_Extractor_Test extends \WP_UnitTestCase {

	/**
	 * Set the test domain
	 */
	const DOMAIN = 'http://example.org';

	/**
	 * Set the test URL
	 */
	const URL = 'http://example.org/blog/my-first-blog-post';

	/**
	 * Helper function for creating extractors
	 */
	public function build_extractor( $content_type, $body, $url = self::URL ) {
		return Url_Extractor_Factory::build( $content_type, $body, $url );
	}

	/**
	 * Placeholder test to prevent PHPUnit from throwing errors
	 */
	public function test_class_is_tested() {
		$this->assertTrue( true );
	}

	/**
	 * Testing for URL extraction on increasingly more malformed link href values.
	 */
	public function test_extract_urls_with_html_links() {

		$content_and_urls = array(
			// basic relative url
			"<a href='/one.html'>one</a>"
				=> self::DOMAIN . '/one.html',
			// extra spacing around href attribute
			"<a href='    /two.html  '>two</a>"
				=> self::DOMAIN . '/two.html',
			// no quotes around href attribute
			"<a href=three.html>three</a>"
				=> self::DOMAIN . '/blog/three.html',
			// support for ../ links
			'<a href="../four.html">four</a>'
				=> self::DOMAIN . '/four.html',
			// support for ./ links
			'<a href=./five.html>five</a>'
				=> self::DOMAIN . '/blog/five.html',
			// extra spacing between href name and value
			'<a href = six.html>six</a>'
				=> self::DOMAIN . '/blog/six.html',
			// space in url
			"<a href='file seven.xml'>seven</a>"
				=> self::DOMAIN . '/blog/file seven.xml',
			// This fails: (http dom library only returns 'file')
			// space in url and no quotes
			//'<a href=file eight.pdf>eight</a>'
			//	=> self::DOMAIN . '/blog/file eight.pdf'
			//	multiple attributes with no spacing
			'<a href=nine.html test=test>nine</a>'
				=> self::DOMAIN . '/blog/nine.html',
			// relative url with path
			"<a href='/path/ten.html'>ten</a>"
				=> self::DOMAIN . '/path/ten.html',
			// query params get striped out
			"<a href='/11.html?test=true'>11</a>"
				=> self::DOMAIN . '/11.html',
			// fragments get striped out
			"<a href='/12.html#test'>12</a>"
				=> self::DOMAIN . '/12.html',
			// non-standard casing
			"<A HRef='/THIRTEEN.html'>13</a>"
				=> self::DOMAIN . '/THIRTEEN.html',
			// absolute url
			"<a href='" . self::DOMAIN . "/14'>14</a>"
				=> self::DOMAIN . '/14',
			// absolute url with fragment only
			"<a href='" . self::DOMAIN . "#section15'>15</a>"
				=> self::DOMAIN . '/',
			// absolute url with path and fragment
			"<a href='" . self::DOMAIN . "/test#section16'>16</a>"
				=> self::DOMAIN . '/test',
			// absolute url with path
			"<a href='" . self::DOMAIN . "/test/17.html'>17</a>"
				=> self::DOMAIN . '/test/17.html',
			// external urls don't get included
			"<a href='http://www.external.com/18.htm'>18</a>"
				=> false,
			// protocol-less URLs
			"<a href='//example.org/19.html'>19</a>"
				=> self::DOMAIN . '/19.html',
			// href's with just a hash
			"<a href='#dontlinkmebro'>20</a>"
				=> false
		);

		foreach ( $content_and_urls as $content => $url ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_url = current( $extractor->extract_and_update_urls() );
			$this->assertEquals( $url, $extracted_url );

		}

	}

	/**
	 * Testing that URL extraction alters content in predictable ways
	 */
	public function test_extract_urls_content_preservation_for_absolute_and_relative_urls() {

		$destination_url_types = array(
			'absolute' => self::DOMAIN,
			'relative' => ''
		);

		$content_before_and_after = array(
			// basic relative url
			'<a href="one.htm">one</a>'
				=> '<a href="%s/blog/one.htm">one</a>',
			// extra spacing around href attribute
			'<a href="    /two.htm  ">two</a>'
				=> '<a href="%s/two.htm">two</a>',
			// no quotes around href attribute
			'<a href=three.htm>three</a>'
				=> '<a href=%s/blog/three.htm>three</a>',
			// support for ../ links
			'<a href="../four.htm">four</a>'
				=> '<a href="%s/four.htm">four</a>',
			// support for ./ links
			'<a href=./five.htm>five</a>'
				=> '<a href=%s/blog/five.htm>five</a>',
			// extra spacing between href name and value
			'<a href = six.htm>six</a>'
				=> '<a href = %s/blog/six.htm>six</a>',
			// space in url
			'<a href="file seven.pdf">seven</a>'
				=> '<a href="%s/blog/file seven.pdf">seven</a>',
			// This fails: (http dom library only returns 'file')
			// // space in url and no quotes
			// '<a href=file eight.pdf>eight</a>'
			// 	=> '<a href=%s/blog/file eight.pdf>eight</a>',
			//	multiple attributes with no spacing
			'<a href=nine.htm test=test>nine</a>'
				=> '<a href=%s/blog/nine.htm test=test>nine</a>',
			// relative url with path
			'<a href="/path/ten.htm">ten</a>'
				=> '<a href="%s/path/ten.htm">ten</a>',
			// query params get striped out
			'<a href="/11.htm?test=true">11</a>'
				=> '<a href="%s/11.htm?test=true">11</a>',
			// fragments get striped out
			'<a href="/12.htm#test">12</a>'
				=> '<a href="%s/12.htm#test">12</a>',
			// non-standard casing
			'<A HRef="/THIRTEEN.htm">13</a>'
				=> '<a href="%s/THIRTEEN.htm">13</a>',
			// absolute url
			'<a href="' . self::DOMAIN . '/14">14</a>'
				=> '<a href="%s/14">14</a>',
			// absolute url with fragment only
			'<a href="' . self::DOMAIN . '#section15">15</a>'
				=> '<a href="%s/#section15">15</a>',
			// absolute url with path and fragment
			'<a href="' . self::DOMAIN . '/test#section16">16</a>'
				=> '<a href="%s/test#section16">16</a>',
			// absolute url with path
			'<a href="' . self::DOMAIN . '/test/17.htm">17</a>'
				=> '<a href="%s/test/17.htm">17</a>',
			// external url
			'<a href="http://www.external.com/18.htm">18</a>'
				=> '<a href="http://www.external.com/18.htm">18</a>',
			// protocol-less URL
			'<a href="//example.org/19.htm">19</a>'
				=> '<a href="%s/19.htm">19</a>',
			// href's with just a hash
			'<a href="#dontlinkmebro">20</a>'
				=> '<a href="#dontlinkmebro">20</a>'
		);


		foreach ( $content_before_and_after as $content_before => $content_after ) {

			foreach ( $destination_url_types as $type => $prefix ) {

				$options = Options::instance();
				$options->set( 'destination_url_type', $type );
				$options->save();

				$extractor = Url_Extractor_Factory::build( 'html', $content_before, self::URL );
				$extractor->extract_and_update_urls();
				$this->assertContains( sprintf( $content_after, $prefix ), $extractor->get_body() );

			}

		}

	}

	/**
	 * Testing that URL extraction alters content in predictable ways
	 */
	public function test_extract_urls_content_preservation_for_offline_access() {

		$content_before_and_after = array(
			// basic relative url
			'<a href="one.htm">one</a>'
				=> '<a href="./one.htm">one</a>',
			// extra spacing around href attribute
			'<a href="    /two.htm  ">two</a>'
				=> '<a href="./../two.htm">two</a>',
			// no quotes around href attribute
			'<a href=three.htm>three</a>'
				=> '<a href=./three.htm>three</a>',
			// support for ../ links
			'<a href="../four.htm">four</a>'
				=> '<a href="./../four.htm">four</a>',
			// support for ./ links
			'<a href=./five.htm>five</a>'
				=> '<a href=./five.htm>five</a>',
			// extra spacing between href name and value
			'<a href = six.htm>six</a>'
				=> '<a href = ./six.htm>six</a>',
			// space in url
			'<a href="file seven.pdf">seven</a>'
				=> '<a href="./file seven.pdf">seven</a>',
			// This fails: (http dom library only returns 'file')
			// // space in url and no quotes
			// '<a href=file eight.pdf>eight</a>'
			// 	=> '<a href=%s/blog/file eight.pdf>eight</a>',
			//	multiple attributes with no spacing
			'<a href=nine.htm test=test>nine</a>'
				=> '<a href=./nine.htm test=test>nine</a>',
			// relative url with path
			'<a href="/path/ten.htm">ten</a>'
				=> '<a href="./../path/ten.htm">ten</a>',
			// query params get striped out
			'<a href="/11.htm?test=true">11</a>'
				=> '<a href="./../11.htm?test=true">11</a>',
			// fragments get striped out
			'<a href="/12.htm#test">12</a>'
				=> '<a href="./../12.htm#test">12</a>',
			// non-standard casing
			'<A HRef="/THIRTEEN.htm">13</a>'
				=> '<a href="./../THIRTEEN.htm">13</a>',
			// absolute url
			'<a href="' . self::DOMAIN . '/14">14</a>'
				=> '<a href="./../14/index.html">14</a>',
			// absolute url with fragment only
			'<a href="' . self::DOMAIN . '#section15">15</a>'
				=> '<a href="./../index.html#section15">15</a>',
			// absolute url with path and fragment
			'<a href="' . self::DOMAIN . '/test#section16">16</a>'
				=> '<a href="./../test/index.html#section16">16</a>',
			// absolute url with path
			'<a href="' . self::DOMAIN . '/test/17.htm">17</a>'
				=> '<a href="./../test/17.htm">17</a>',
			// external url
			'<a href="http://www.external.com/18.htm">18</a>'
				=> '<a href="http://www.external.com/18.htm">18</a>',
			// protocol-less URL
			'<a href="//example.org/19.htm">19</a>'
				=> '<a href="./../19.htm">19</a>',
			// href's with just a hash
			'<a href="#dontlinkmebro">20</a>'
				=> '<a href="#dontlinkmebro">20</a>',
			// offline urls: same level
			'<a href="/blog/my-second-post">21</a>'
				=> '<a href="./my-second-post/index.html">21</a>',
			// offline urls: up a level
			'<a href="/blog/third/my-third-post">22</a>'
				=> '<a href="./third/my-third-post/index.html">22</a>',
			// offline urls: down a level
			'<a href="/my-fourth-post">23</a>'
				=> '<a href="./../my-fourth-post/index.html">23</a>',
			// offline urls: sibling
			'<a href="/fifth/my-fifth-post">24</a>'
				=> '<a href="./../fifth/my-fifth-post/index.html">24</a>',
		);

		foreach ( $content_before_and_after as $content_before => $content_after ) {

			$options = Options::instance();
			$options->set( 'destination_url_type', 'offline' );
			$options->save();

			$extractor = Url_Extractor_Factory::build( 'html', $content_before, self::URL );
			$extractor->extract_and_update_urls();
			$this->assertContains( $content_after, $extractor->get_body() );

		}


	}

	/**
	 * Test URL extraction on other types of HTML elements.
	 */
	public function test_extract_urls_with_other_html_elements() {

		$content_and_urls = array(
			"<link rel='stylesheet' id='test-css'  href='/test.css' type='text/css' media='all' />"
				=> self::DOMAIN . '/test.css',
			"<link href='//fonts.googleapis.com/css?family=Judson:400,400italic,700' rel='stylesheet' type='text/css'>"
				=> false,
			'<applet codebase="/classes"></applet>'
				=> self::DOMAIN . '/classes',
			'<area shape="rect" coords="0,0,10,10" href="sun.htm">'
				=> self::DOMAIN . '/blog/sun.htm',
			'<ins cite="why.htm">Cited text.</ins>'
				=> self::DOMAIN . '/blog/why.htm',
			'<!-- <ins cite="why.htm">Cited text.</ins> -->'
				=> false
		);

		foreach ( $content_and_urls as $content => $url ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_url = current( $extractor->extract_and_update_urls() );
			$this->assertEquals( $url, $extracted_url );

		}

	}

	/**
	 * Test URL extraction on HTML elements with multiple attributes (possibly)
	 * containing URLs.
	 */
	public function test_extract_urls_with_multiple_html_attributes() {

		$content_and_urls = array(
			'<iframe src="/default.asp" longdesc="w3s.txt"></iframe>' => array(
				self::DOMAIN . '/default.asp',
				self::DOMAIN . '/blog/w3s.txt' ),
			'<img src="w3html.gif" alt="W3Schools.com" longdesc="w3html.txt">' => array(
				self::DOMAIN . '/blog/w3html.gif',
				self::DOMAIN . '/blog/w3html.txt' ),
			'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="/swflash.cab#version=9,0,28,0">' => array(
				self::DOMAIN . '/swflash.cab' ),
			'<OBJECT CLASSID="/yahtzee.py" CODETYPE="application/x-python" TITLE="My Yahtzee Game"></OBJECT>' => array(
				self::DOMAIN . '/yahtzee.py' ),
			'<video controls src="movie.ogg" poster="/images/w3html5.gif"></video>' => array(
				self::DOMAIN . '/blog/movie.ogg',
				self::DOMAIN . '/images/w3html5.gif' )
		);

		foreach ( $content_and_urls as $content => $urls ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_urls = $extractor->extract_and_update_urls();

			foreach ( $urls as $url ) {
				$this->assertTrue( in_array( $url, $extracted_urls ) );
			}

		}

	}

	/**
	 * Test URL extraction on img HTML elements with the srcset attribute
	 */
	public function test_extract_urls_with_srcset() {
		$content_and_urls = array(
			'<img width="1200" height="750" src="/wp-content/uploads/2013/03/soworthloving-wallpaper-1200x750.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="Markup: Image Alignment" srcset="/wp-content/uploads/2013/03/soworthloving-wallpaper-300x188.jpg 300w, /wp-content/uploads/2013/03/soworthloving-wallpaper-768x480.jpg 768w, /wp-content/uploads/2013/03/soworthloving-wallpaper-1024x640.jpg 1024w, /wp-content/uploads/2013/03/soworthloving-wallpaper-1200x750.jpg 1200w" sizes="(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 60vw, (max-width: 1362px) 62vw, 840px">' => array(
				self::DOMAIN . '/wp-content/uploads/2013/03/soworthloving-wallpaper-300x188.jpg',
				self::DOMAIN . '/wp-content/uploads/2013/03/soworthloving-wallpaper-768x480.jpg',
				self::DOMAIN . '/wp-content/uploads/2013/03/soworthloving-wallpaper-1024x640.jpg',
				self::DOMAIN . '/wp-content/uploads/2013/03/soworthloving-wallpaper-1200x750.jpg',
			),
			'<img src="" srcset="       soworthloving-wallpaper-300x188.jpg       300w     ,   soworthloving wallpaper 768x480.jpg 768w  , soworthloving-wallpaper-1024x640.jpg   1024w  , soworthloving-wallpaper-1200x750.jpg 1200w" sizes="(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 60vw, (max-width: 1362px) 62vw, 840px">' => array(
				self::DOMAIN . '/blog/soworthloving-wallpaper-300x188.jpg',
				self::DOMAIN . '/blog/soworthloving wallpaper 768x480.jpg',
				self::DOMAIN . '/blog/soworthloving-wallpaper-1024x640.jpg',
				self::DOMAIN . '/blog/soworthloving-wallpaper-1200x750.jpg',
			)
		);

		foreach ( $content_and_urls as $content => $urls ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_urls = $extractor->extract_and_update_urls();

			foreach ( $urls as $url ) {
				$this->assertTrue( in_array( $url, $extracted_urls ) );
			}

		}
	}

	// TODO: Not presently handling delimited attributes.
	// /**
	//  * Test URL extraction on HTML elements with attributes containing space or
	//  * comma delimited lists of URLs.
	//  */
	// public function test_extract_urls_with_delimited_attributes() {

	// 	$content_and_urls = array(
	// 		'<object classid="java:giant-dog.class" archive="/giant-dog.jar /giant-dog2.jar /giant-dog3.jar"></object>' => array(
	// 			self::DOMAIN . '/giant-dog.jar',
	// 			self::DOMAIN . '/giant-dog2.jar',
	// 			self::DOMAIN . '/giant-dog3.jar' ),
	// 		'<applet archive="/archive/myjar.jar,/archive/myjar2.jar,/archive/myjar3.jar"></applet>' => array(
	// 			self::DOMAIN . '/archive/myjar.jar',
	// 			self::DOMAIN . '/archive/myjar2.jar',
	// 			self::DOMAIN . '/archive/myjar3.jar' )
	// 	);

	// 	foreach ( $content_and_urls as $content => $urls ) {

	// 		$extractor = $this->build_extractor( 'html', $content );
	// 		$extracted_urls = $extractor->extract_and_update_urls();

	// 		foreach ( $extracted_urls as $extracted_url ) {
	// 			$this->assertTrue( in_array( $extracted_url, $urls ) );
	// 		}

	// 	}

	// }

	/**
	 * Test URL extraction on CSS style attributes on HTML elements.
	 */
	public function test_extract_urls_with_css_style_attributes() {

		$content_and_urls = array(
			'<body style=\'background: #00ff00 url("/smiley.gif") no-repeat fixed center;\'></body>'
				=> self::DOMAIN . '/smiley.gif',
			"<div style='background-image: url(\"/one.png\")'></div>"
				=> self::DOMAIN . '/one.png',
			"<div STYLE='background-IMAGE: url(/two.png)'></div>"
				=> self::DOMAIN . '/two.png',
			"<div style=\"background-image: url('/three.png')\"></div>"
				=> self::DOMAIN . '/three.png'
		);

		foreach ( $content_and_urls as $content => $url ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_url = current( $extractor->extract_and_update_urls() );
			$this->assertEquals( $url, $extracted_url );

		}

	}

	/**
	 * Test URL extraction on CSS style blocks on HTML pages.
	 */
	public function test_extract_urls_with_css_style_blocks() {

		$content_and_urls = array(
			'<STYLE> @import url("/import.css") print; </STYLE>'
				=> self::DOMAIN . '/import.css',
			"<style> div { background-image: url(/image-one.png); } </style>"
				=> self::DOMAIN . '/image-one.png'
		);

		foreach ( $content_and_urls as $content => $url ) {

			$extractor = $this->build_extractor( 'html', $content );
			$extracted_url = current( $extractor->extract_and_update_urls() );
			$this->assertEquals( $url, $extracted_url );

		}

	}

	/**
	 * Test URL extraction on CSS files.
	 */
	public function test_extract_urls_on_css_files() {

		$content_and_urls = array(
			'@import url("./one.css") print;'
				=> self::DOMAIN . '/blog/one.css',
			'@import url("../two.css") projection, tv;'
				=> self::DOMAIN . '/two.css',
			'@import \'three.css\';'
				=> self::DOMAIN . '/blog/three.css',
			'@import "./four.css" print;'
				=> self::DOMAIN . '/blog/four.css',
			'@import url("chrome://five/");'
				=> false,
			'@import url(\'six.css\') screen and (orientation:landscape);'
				=> self::DOMAIN . '/blog/six.css',
			'.seven { background-image: url(seven.png); }'
				=> self::DOMAIN . '/blog/seven.png',
			'.eight { background-image: url("eight.png"); }'
				=> self::DOMAIN . '/blog/eight.png',
			'.nine { background-image: url(  \'nine.png\'  ); }'
				=> self::DOMAIN . '/blog/nine.png'
		);

		foreach ( $content_and_urls as $content => $url ) {

			$extractor = $this->build_extractor( 'css', $content );
			$extracted_url = current( $extractor->extract_and_update_urls() );
			$this->assertEquals( $url, $extracted_url );

		}

	}

}
