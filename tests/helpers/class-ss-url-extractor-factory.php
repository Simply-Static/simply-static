<?php
namespace Simply_Static;

/**
 * Returns a faked URL Extractor
 */
class Url_Extractor_Factory extends \WP_UnitTestCase {

	public static function build( $content_type, $body, $url ) {
		$page = Page_Factory::create();

		$options = Options::instance();

		file_put_contents( $options->get_archive_dir() . $page->file_path, $body );
		$page->content_type = 'text/' . $content_type . '; charset=UTF-8';
		$page->url = $url;
		$page->save();

		return new Url_Extractor( $page );
	}

	public static function build_from_static_page( $page ) {
		return new Url_Extractor( $page );
	}
}
