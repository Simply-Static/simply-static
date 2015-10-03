<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The following pages were incredibly helpful:
 * - http://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_web_page
 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_css_file
 * - http://php.net/manual/en/book.dom.php
 */

/**
 * Simply Static URL extractor class
 */
class Simply_Static_Url_Extractor {

	/** @const */
	protected static $match_elements = array(
		// HTML
		'a'            => array( 'href', 'urn' ),
		'base'         => array( 'href' ),
		'form'         => array( 'action', 'data' ),
		'img'          => array( 'src', 'usemap', 'longdesc' ),
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
		'img'          => array( 'dynsrc', 'lowsrc' ),
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
	 * URL that has been parsed with parse_url
	*
	 * @var array
	 */
	protected $parsed_page_url = array();


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
		$this->parsed_page_url = parse_url( $response->url );
	}

	/**
	 * Extracts URLs from the response
	 *
	 * Returns a list of unique URLs from the body of the response. It only
	 * extracts URLs from the same domain, either absolute urls or relative urls
	 * that are then converted to absolute urls.
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
		$url_pattern	 = '(([^\\\\\'", \(\)]*(\\\\.)?)+)';
		$urlfunc_pattern = 'url\(\s*[\'"]?' . $url_pattern . '[\'"]?\s*\)';

		$pattern         = '/(' .
			'(@import\s*[\'"]'  . $url_pattern     . '[\'"])' .
			'|(@import\s*'      . $urlfunc_pattern . ')'      .
			'|('                . $urlfunc_pattern . ')'      . ')/iu';

		if ( !preg_match_all( $pattern, $text, $matches, PREG_PATTERN_ORDER ) ) {
			return;
		}

		// @import '...' or @import "..."
		foreach ( $matches[3] as $match ) {
			if ( !empty($match) ) {
				$this->add_to_extracted_urls( preg_replace( '/\\\\(.)/u', '\\1', $match ) );
			}
		}

		// @import url(...) or @import url('...') or @import url("...")
		foreach ( $matches[7] as $match ) {
			if ( !empty($match) ) {
				$this->add_to_extracted_urls( preg_replace( '/\\\\(.)/u', '\\1', $match ) );
			}
		}

		// url(...) or url('...') or url("...")
		foreach ( $matches[11] as $match ) {
			if ( !empty($match) ) {
				$this->add_to_extracted_urls( preg_replace( '/\\\\(.)/u', '\\1', $match ) );
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
	function add_to_extracted_urls( $extracted_url ) {
		$extracted_url = trim( $extracted_url );
		if ( $extracted_url !== '' ) {
			$parsed_extracted_url = parse_url( $extracted_url );

			// parse_url can sometimes return false -- checking that they're both not false
			if ( $this->parsed_page_url && $parsed_extracted_url ) {
				// if the extracted url has a host
				if ( array_key_exists( 'host', $parsed_extracted_url ) ) {
					// and a scheme
					if ( array_key_exists( 'scheme', $parsed_extracted_url ) ) {
						// and that scheme+host matches the scheme+host of the page we extracted it from
						if ( $this->parsed_page_url['scheme'] === $parsed_extracted_url['scheme']
						&& $this->parsed_page_url['host'] === $parsed_extracted_url['host'] ) {
							$extracted_url = phpUri::parse( $this->response->url )->join( $parsed_extracted_url['path'] );
							$this->extracted_urls[] = $extracted_url;
						}
					}
				} else { // no host on extracted page (might be relative url)
					// filter out anything with a scheme, e.g. java:, data:, etc.)
					// also checking for a path (some links might only have a fragent, e.g. #section1)
					// and that the path is not just a slash
					if ( ! array_key_exists( 'scheme', $parsed_extracted_url ) && array_key_exists( 'path', $parsed_extracted_url ) ) {
						// turn our relative url into an absolute url
						$extracted_url = phpUri::parse( $this->response->url )->join( $parsed_extracted_url['path'] );
						$this->extracted_urls[] = $extracted_url;
					}
				}
			}
		}
	}
}
