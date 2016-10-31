<?php
/**
 * @package Simply_Static\Unit_tests
 */

/**
 * Returns a faked URL Extractor
 */
class Simply_Static_Url_Extractor_Factory extends WP_UnitTestCase {

	public static function build( $content_type, $body, $url ) {
		$page = Simply_Static_Page_Factory::create();

		$options = Simply_Static_Options::instance();

		file_put_contents( $options->get_archive_dir() . $page->file_path, $body );
		$page->content_type = 'text/' . $content_type . '; charset=UTF-8';
		$page->url = $url;
		$page->save();

		return new Simply_Static_Url_Extractor( $page );
	}

	public static function build_from_static_page( $page ) {
		return new Simply_Static_Url_Extractor( $page );
	}
}
