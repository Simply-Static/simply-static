<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL extractor class
 *
 * @package Simply_Static
 */
class Simply_Static_Url_Extractor {

	/**
	 * The following pages were incredibly helpful:
	 * - http://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
	 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_web_page
	 * - http://php.net/manual/en/book.dom.php
	 */

	/** @const */
	protected static $match_elements = array(
		// HTML
		'a'            => array( 'href', 'urn' ),
		'base'         => array( 'href' ),
		'form'         => array( 'action', 'data' ),
		'img'          => array( 'src', 'usemap', 'longdesc', 'dynsrc', 'lowsrc' ),
		'link'         => array( 'href' ),

		'applet'       => array( 'code', 'codebase', 'archive', 'object' ),
		'area'         => array( 'href' ),
		'body'         => array( 'background', 'credits', 'instructions', 'logo' ),
		'input'        => array( 'src', 'usemap', 'dynsrc', 'lowsrc', 'action', 'formaction' ),

		'blockquote'   => array( 'cite' ),
		'del'          => array( 'cite' ),
		'frame'        => array( 'longdesc', 'src' ),
		'head'         => array( 'profile' ),
		'iframe'       => array( 'longdesc', 'src' ),
		'ins'          => array( 'cite' ),
		'object'       => array( 'archive', 'classid', 'codebase', 'data', 'usemap' ),
		'q'            => array( 'cite' ),
		'script'       => array( 'src' ),

		'audio'        => array( 'src' ),
		'command'      => array( 'icon' ),
		'embed'        => array( 'src', 'code', 'pluginspage' ),
		'event-source' => array( 'src' ),
		'html'         => array( 'manifest', 'background', 'xmlns' ),
		'source'       => array( 'src' ),
		'video'        => array( 'src', 'poster' ),

		'bgsound'      => array( 'src' ),
		'div'          => array( 'href', 'src' ),
		'ilayer'       => array( 'src' ),
		'table'        => array( 'background' ),
		'td'           => array( 'background' ),
		'th'           => array( 'background' ),
		'layer'        => array( 'src' ),
		'xml'          => array( 'src' ),

		'button'       => array( 'action', 'formaction' ),
		'datalist'     => array( 'data' ),
		'select'       => array( 'data' ),

		'access'       => array( 'path' ),
		'card'         => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
		'go'           => array( 'href' ),
		'option'       => array( 'onpick' ),
		'template'     => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
		'wml'          => array( 'xmlns' )
	);

	// /** @const */
	// protected static $match_metas = array(
	//	 'content-base',
	//	 'content-location',
	//	 'referer',
	//	 'location',
	//	 'refresh',
	// );

	/**
	 * The URL request response
	*
	 * @var Simply_Static_Url_Response
	 */
	protected $response;

	/**
	 * The url of the site
	 *
	 * @var array
	 */
	protected $extracted_urls = array();

	/**
	 * Constructor
	 *
	 * @param string $page_contents The contents of the html/css/etc. page/file
	 * @param string $url The url of the site
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}

	/**
	 * Extracts URLs from the response
	 *
	 * Returns a list of unique URLs from the body of the response. It only
	 * extracts URLs from the same domain, either absolute urls or relative urls
	 * that are then converted to absolute urls.
	 *
	 * Note that no validation is performed on whether the URLs would actually
	 * return a 200/OK response.
	 *
	 * @return array $urls
	 */
	public function extract_urls() {

		if ( $this->response->is_html() ) {
			$this->extract_urls_from_html();
		}

		if ( $this->response->is_css() ) {
			$this->extract_urls_from_css( $this->response->body );
		}

		return array_unique( $this->extracted_urls );
	}

	/**
	 * Loops through all elements in the DOM to pull out URLs
	*
	 * @return void
	 */
	private function extract_urls_from_html() {

		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( $this->response->body );
		libxml_use_internal_errors( false );
		// get all elements on the page
		$elements = $doc->getElementsByTagName( '*' );
		foreach ( $elements as $element ) {
			$tag_name = $element->tagName;

			if( $tag_name === 'style' ) {
				$this->extract_urls_from_css( $element->nodeValue );
			} else {
				$style_attr_value = $element->getAttribute( 'style' );
				if ( $style_attr_value !== '' ) {
					$this->extract_urls_from_css( $style_attr_value );
				}

				if ( array_key_exists( $tag_name, self::$match_elements ) ) {
					$match_attributes = self::$match_elements[ $tag_name ];
					foreach ( $match_attributes as $attribute_name ) {
						$extracted_url = $element->getAttribute( $attribute_name );
						$this->add_to_extracted_urls( $extracted_url );
					}
				}
			}
		}
	}

	/**
	 * Use regex to extract URLs on CSS pages
	 *
	 * URLs in CSS follow three basic patterns:
	 * - @import "common.css" screen, projection;
	 * - @import url("fineprint.css") print;
	 * - background-image: url(image.png);
	 *
	 * URLs are either contained within url(), part of an @import statement,
	 * or both.
	 *
	 * @return void
	 */
	private function extract_urls_from_css( $text ) {

		$patterns = array( "/url\(\s*[\"']?([^)\"']+)/", // url()
		            "/@import\s+[\"']([^\"']+)/" ); // @import w/o url()

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $text, $matches, PREG_PATTERN_ORDER ) === false ) {
				return;
			}

			foreach ( $matches[1] as $match ) {
				if ( !empty($match) ) {
					$this->add_to_extracted_urls( $match );
				}
			}
		}

	}

	/**
	 * Add an extracted URL (relative or absolute) to the extracted URLs array
	 *
	 * Absolute URLs are only added if the scheme/host matches the site it was
	 * extracted from. Relative URLs are converted to absolute URLs before being
	 * added to the array.
	 *
	 * @return void
	 */
	private function add_to_extracted_urls( $extracted_url ) {
		$absolute_url = sist_relative_to_absolute_url( $extracted_url, $this->response->url );

		if ( $absolute_url && sist_is_local_url( $absolute_url ) ) {
			$this->extracted_urls[] = sist_remove_params_and_fragment( $absolute_url );
		}
	}
}
