<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL extractor class
 *
 * Note that in addition to extracting URLs this class also makes modifications
 * to the Simply_Static_Url_Response that is passed into it: URLs in the body of
 * the response are updated to be absolute URLs.
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
	protected static $match_tags = array(
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
	 * Are we saving destination URL as absolute, relative, or for offline use?
	 * @var string
	 */
	protected $destination_url_type;

	/**
	 * Base relative path to use for generating relative URLs
	 * @var string
	 */
	protected $relative_path;

	/**
	 * The url of the site
	 * @var array
	 */
	protected $extracted_urls = array();

	/**
	 * Constructor
	 * @param string  $response             URL Response object
	 * @param boolean $destination_url_type Absolute/relative/offline URLs?
	 */
	public function __construct( Simply_Static_Url_Response $response, $destination_url_type, $relative_path ) {
		$this->response = $response;
		$this->destination_url_type = $destination_url_type;
		$this->relative_path = $relative_path;
	}

	/**
	 * Extracts URLs from the response and update them based on the dest. type
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
	public function extract_and_update_urls() {
		if ( $this->response->is_html() ) {
			$this->response->save_body( $this->extract_urls_from_html() );
		}

		if ( $this->response->is_css() ) {
			$this->response->save_body( $this->extract_urls_from_css( $this->response->get_body() ) );
		}

		return array_unique( $this->extracted_urls );
	}


	/**
	 * Extract URLs and convert URLs to absolute URLs for each tag
	 *
	 * The tag is passed by reference, so it's updated directly and nothing is
	 * returned from this function.
	 * @param  simple_html_dom_node $tag        SHDP dom node
	 * @param  string               $tag_name   name of the tag
	 * @param  array                $attributes array of attribute notes
	 * @return void
	 */
	private function extract_urls_and_update_tag( &$tag, $tag_name, $attributes ) {
		if ( isset( $tag->style ) ) {
			$updated_css = $this->extract_urls_from_css( $tag->style );
			$tag->style = $updated_css;
		}

		foreach ( $attributes as $attribute_name ) {
			if ( isset( $tag->$attribute_name ) ) {
				$extracted_urls = array();
				$attribute_value = $tag->$attribute_name;

				// srcset is a fair bit different from most html
				// attributes, so it gets it's own processsing
				if ( $attribute_name === 'srcset' ) {
					$extracted_urls = $this->extract_urls_from_srcset( $attribute_value );
				} else {
					$extracted_urls[] = $attribute_value;
				}

				foreach ( $extracted_urls as $extracted_url ) {
					if ( $extracted_url !== '' ) {
						$absolute_extracted_url = $this->add_to_extracted_urls( $extracted_url );
						$attribute_value = str_replace( $extracted_url, $absolute_extracted_url, $attribute_value );
					}
				}
				$tag->$attribute_name = $attribute_value;
			}
		}
	}

	/**
	 * Loop through elements of interest in the DOM to pull out URLs
	 *
	 * There are specific html tags and -- more precisely -- attributes that
	 * we're looking for. We loop through tags with attributes we care about,
	 * which the attributes for URLs, extract and update any URLs we find, and
	 * then return the updated HTML.
	 * @return string The HTML with all URLs made absolute
	 */
	private function extract_urls_from_html() {
		$html_string = $this->response->get_body();

		$dom = Sunra\PhpSimple\HtmlDomParser::str_get_html(
			$html_string,
			$lowercase = true,
			$forceTagsClosed = true,
			$target_charset = DEFAULT_TARGET_CHARSET,
			$stripRN = false,
			$defaultBRText = DEFAULT_BR_TEXT,
			$defaultSpanText = DEFAULT_SPAN_TEXT
		);

		// return the original html string if dom is blank or boolean (unparseable)
		if ( $dom == '' || is_bool( $dom ) ) {

			return $html_string;

		} else {
			// handle tags with attributes
			foreach ( self::$match_tags as $tag_name => $attributes ) {

				$tags = $dom->find( $tag_name );

				foreach ( $tags as $tag ) {
					$this->extract_urls_and_update_tag( $tag, $tag_name, $attributes );
				}
			}

			// handle 'style' tag differently, since we need to parse the content
			$tags = $dom->find( 'style' );

			foreach ( $tags as $tag ) {
				$updated_css = $this->extract_urls_from_css( $tag->innertext );
				$tag->innertext = $updated_css;
			}

			return $dom->save();

		}

	}

	/**
	 * Extract URLs from the srcset attribute
	 * @param  string $srcset Value of the srcset attribute
	 * @return array  Array of extracted URLs
	 */
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
	 * @return string The URL, converted to an absolute/relative/offline URL
	 */
	private function add_to_extracted_urls( $extracted_url ) {
		$url = sist_relative_to_absolute_url( $extracted_url, $this->response->url );

		if ( $url && sist_is_local_url( $url ) ) {
			$this->extracted_urls[] = sist_remove_params_and_fragment( $url );

			if ( $this->destination_url_type == 'relative' ) {

				$url = sist_get_path_from_local_url( $url );
				$url = $this->relative_path . $url;

			} else if ( $this->destination_url_type == 'offline' ) {
				// remove the scheme/host from the url
				$page_path = sist_get_path_from_local_url( $this->response->url );
				$extracted_path = sist_get_path_from_local_url( $url );

				// create a path from one page to the other
				$path = sist_create_offline_path( $extracted_path, $page_path );

				$path_info = sist_url_path_info( $url );
				if ( $path_info['extension'] === '' ) {
					// If there's no extension, we need to add a /index.html,
					// and do so before any params or fragments.
					$clean_path = sist_remove_params_and_fragment( $path );
					$fragment = substr( $path, strlen( $clean_path ) );

					$path = trailingslashit( $clean_path );
					$path .= 'index.html' . $fragment;
				}

				$url = $path;
			}
		}

		return $url;
	}
}
