<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static URL extractor class
 */
class Simply_Static_Url_Extractor {

	/**
	 * The contents of the html/css/etc. page/file
	 * @var string
	 */
	protected $page_contents;

	/**
	 * The url of the site
	 * @var string
	 */
	protected $base_url;

    /**
	 * Constructor
	 * @param string $page_contents The contents of the html/css/etc. page/file
	 * @param string $url The url of the site
	 */
	public function __construct( $page_contents, $base_url ) {
		$this->page_contents = $page_contents;
		$this->base_url = $base_url;
	}

	/**
	 * Extracts the list of unique URLs
	 * @param string $origin_url Base URL of site. Used to extract URLs that relate only to the current site.
	 * @return array $urls
	 */
	public function extract_urls() {
		$extracted_urls = array();

		error_log( '/' . str_replace( '/', '\/', $this->base_url ) . '[^"\'#\? ]+/i' );
		if (preg_match_all( '/' . str_replace( '/', '\/', $this->base_url ) . '[^"\'#\? ]+/i', $this->page_contents, $matches ) ) {
			$extracted_urls = array_unique( $matches[0] );
		}

		return $extracted_urls;
	}

}
