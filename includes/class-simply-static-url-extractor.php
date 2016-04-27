<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL extractor class
 *
 * Note that in addition to extracting URLs this class also makes modifications
 * to the Simply_Static_Url_Response that is passed into it: URLs in the body of
 * the response are updated to be absolute URLs.
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
		'img'          => array( 'src', 'usemap', 'longdesc', 'dynsrc', 'lowsrc', 'srcset' ),
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
	 * @var Simply_Static_Url_Response
	 */
	protected $response;

	/**
	 * The url of the site
	 * @var array
	 */
	protected $extracted_urls = array();

	/**
	 * Constructor
	 * @param string response URL Response object
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
			$this->response->body = $this->extract_urls_from_html();
		}

		if ( $this->response->is_css() ) {
			$this->response->body = $this->extract_urls_from_css( $this->response->body );
		}

		return array_unique( $this->extracted_urls );
	}

	/**
	 * Loops through all elements in the DOM to pull out URLs
	 * @return string The HTML with all URLs made absolute
	 */
	private function extract_urls_from_html() {
		$doc = new DOMDocument();
		// ensuring we don't throw visible errors during html loading
		libxml_use_internal_errors( true );

		// DOMDocument doesn't handle encoding correctly and garbles the output.
		// mb_convert_encoding is an extension though, so we're checking if it's
		// available first.
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$this->response->body = mb_convert_encoding( $this->response->body, 'HTML-ENTITIES', 'UTF-8' );
		}
		@$doc->loadHTML( $this->response->body );  // suppress warnings

		libxml_use_internal_errors( false );
		// get all elements on the page
		$elements = $doc->getElementsByTagName( '*' );
		foreach ( $elements as $element ) {
			$tag_name = $element->tagName;

			if ( $tag_name === 'style' ) {
				$updated_css = $this->extract_urls_from_css( $element->nodeValue );
				$element->nodeValue = $updated_css;
			} else {
				$style_attr_value = $element->getAttribute( 'style' );
				if ( $style_attr_value !== '' ) {
					$updated_css = $this->extract_urls_from_css( $style_attr_value );
					$element->setAttribute( 'style', $updated_css );
				}

				if ( array_key_exists( $tag_name, self::$match_elements ) ) {
					$match_attributes = self::$match_elements[ $tag_name ];
					foreach ( $match_attributes as $attribute_name ) {

						$extracted_urls = array();
						// srcset is a fair bit different from most html
						// attributes, so it gets it's own processsing
						if ( $attribute_name === 'srcset' ) {
							$extracted_urls = $this->extract_urls_from_srcset( $element->getAttribute( $attribute_name ) );
						} else {
							$extracted_urls[] = $element->getAttribute( $attribute_name );
						}

						foreach ( $extracted_urls as $extracted_url ) {
							if ( $extracted_url !== '' ) {
								$absolute_extracted_url = $this->add_to_extracted_urls( $extracted_url );
								$element->setAttribute( $attribute_name, $absolute_extracted_url );
							}
						}
					}
				}
			}
		}

		// update the response body with updated links
		return $doc->saveHTML();
	}

	private function extract_urls_from_srcset( $srcset ) {
		$extracted_urls = array();

		foreach( explode( ',', $srcset ) as $url_and_descriptor ) {
			// remove the (optional) descriptor
			// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-srcset
			$extracted_urls[] = trim( preg_replace( '/[\d\.]+[xw]\s*$/', '', $url_and_descriptor ) );
		}

		return $extracted_urls;
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
	 * @param  string $text The CSS to extract URLs from
	 * @return string The CSS with all URLs made absolute
	 */
	private function extract_urls_from_css( $text ) {
		$patterns = array( "/url\(\s*[\"']?([^)\"']+)/", // url()
		            "/@import\s+[\"']([^\"']+)/" ); // @import w/o url()

		foreach ( $patterns as $pattern ) {
			$text = preg_replace_callback( $pattern, array( $this, 'css_matches' ), $text );
		}

		return $text;
	}

	/**
	 * callback function for preg_replace in extract_urls_from_css
	 *
	 * Takes the match, extracts the URL, adds it to the list of URLs, converts
	 * the URL to an absolute URL.
	 *
	 * @param  array $matches Array of preg_replace matches
	 * @return string An updated string for the text that was originally matched
	 */
	private function css_matches( $matches ) {
		$full_match = $matches[0];
		$extracted_url = $matches[1];

		if ( isset( $extracted_url ) && $extracted_url !== '' ) {
			$absolute_extracted_url = $this->add_to_extracted_urls( $extracted_url );
			$full_match = str_ireplace( $extracted_url, $absolute_extracted_url, $full_match );
		}

		return $full_match;
	}

	/**
	 * Add an extracted URL (relative or absolute) to the extracted URLs array
	 *
	 * Absolute URLs are only added if the scheme/host matches the site it was
	 * extracted from. Relative URLs are converted to absolute URLs before being
	 * added to the array.
	 *
	 * @return string The URL that should be added to the list of extracted URLs
	 * @return string The URL, converted to an absolute URL
	 */
	private function add_to_extracted_urls( $extracted_url ) {
		$absolute_url = sist_relative_to_absolute_url( $extracted_url, $this->response->url );

		if ( $absolute_url && sist_is_local_url( $absolute_url ) ) {
			$this->extracted_urls[] = sist_remove_params_and_fragment( $absolute_url );
		}

		return $absolute_url;
	}
}
