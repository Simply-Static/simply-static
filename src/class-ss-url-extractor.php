<?php

namespace Simply_Static;

use Exception;
use voku\helper\HtmlDomParser;
use function WPML\FP\apply;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static URL extractor class
 *
 * Note that in addition to extracting URLs this class also makes modifications
 * to the Simply_Static\Url_Response that is passed into it: URLs in the body of
 * the response are updated to be absolute URLs.
 */
class Url_Extractor {

	/**
	 * The following pages were incredibly helpful:
	 * - http://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
	 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_web_page
	 * - http://php.net/manual/en/book.dom.php
	 */

	protected static $match_tags = array(
		'a'       => array( 'href', 'urn', 'style' ),
		'base'    => array( 'href' ),
		'img'     => array( 'src', 'usemap', 'longdesc', 'dynsrc', 'lowsrc', 'srcset', 'data-src', 'data-bg' ),
		'picture' => array( 'src', 'srcset' ),
		'source'  => array( 'srcset' ),
		'amp-img' => array( 'src', 'srcset' ),
		'link'    => array( 'href' ),

		'applet' => array( 'code', 'codebase', 'archive', 'object' ),
		'area'   => array( 'href' ),
		'body'   => array( 'background', 'credits', 'instructions', 'logo' ),
		'input'  => array( 'src', 'usemap', 'dynsrc', 'lowsrc', 'formaction' ),

		'blockquote' => array( 'cite' ),
		'del'        => array( 'cite' ),
		'frame'      => array( 'longdesc', 'src' ),
		'head'       => array( 'profile' ),
		'ins'        => array( 'cite' ),
		'object'     => array( 'archive', 'classid', 'codebase', 'data', 'usemap' ),
		'q'          => array( 'cite' ),
		'script'     => array( 'src' ),

		'audio'        => array( 'src' ),
		'figure'       => array( 'src' ),
		'command'      => array( 'icon' ),
		'embed'        => array( 'src', 'code', 'pluginspage' ),
		'event-source' => array( 'src' ),
		'html'         => array( 'manifest', 'background', 'xmlns' ),
		'source'       => array( 'src', 'srcset' ),
		'video'        => array( 'src', 'poster' ),

		'bgsound' => array( 'src' ),
		'div'     => array( 'href', 'src', 'style' ),
		'span'    => array( 'href', 'src', 'style' ),
		'section' => array( 'style' ),
		'footer'  => array( 'style' ),
		'header'  => array( 'style' ),
		'ilayer'  => array( 'src' ),
		'table'   => array( 'background' ),
		'td'      => array( 'background' ),
		'th'      => array( 'background' ),
		'layer'   => array( 'src' ),
		'xml'     => array( 'src' ),

		'button'   => array( 'formaction', 'style' ),
		'datalist' => array( 'data' ),
		'select'   => array( 'data' ),

		'access'   => array( 'path' ),
		'card'     => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
		'go'       => array( 'href' ),
		'option'   => array( 'onpick' ),
		'template' => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
		'wml'      => array( 'xmlns' ),

		'meta' => array( 'content' ),
		'link' => array( 'href' ),
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
	 * The static page to extract URLs from
	 * @var \Simply_Static\Page
	 */
	protected $static_page;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * The url of the site
	 * @var array
	 */
	public $extracted_urls = array();

	/**
	 * Constructor
	 *
	 * @param string $static_page Simply_Static\Page to extract URLs from
	 */
	public function __construct( $static_page ) {
		$this->static_page = $static_page;
		$this->options     = Options::instance();
	}

	/**
	 * Fetch the content from our file
	 * @return string
	 */
	public function get_body() {
		// Setting the stream context to prevent an issue where non-latin
		// characters get converted to html codes like #1234; inappropriately
		// http://stackoverflow.com/questions/5600371/file-get-contents-converts-utf-8-to-iso-8859-1
		$opts    = array(
			'http' => array(
				'header' => "Accept-Charset: UTF-8"
			)
		);
		$context = stream_context_create( $opts );
		$path    = $this->options->get_archive_dir() . $this->static_page->file_path;

		return file_get_contents( $path, false, $context );
	}

	/**
	 * Save a string back to our file (e.g. after having updated URLs)
	 *
	 * @param string $static_page Simply_Static\Page to extract URLs from
	 *
	 * @return int|false
	 */
	public function save_body( $content ) {
		$content = apply_filters( 'simply_static_content_before_save', $content, $this );

		return file_put_contents( $this->options->get_archive_dir() . $this->static_page->file_path, $content );
	}

	/**
	 * Get the Static Page.
	 *
	 * @return \Simply_Static\Page|string
	 */
	public function get_static_page() {
		return $this->static_page;
	}

	/**
	 * Extracts URLs from the static_page and update them based on the dest. type
	 *
	 * Returns a list of unique URLs from the body of the static_page. It only
	 * extracts URLs from the same domain, either absolute urls or relative urls
	 * that are then converted to absolute urls.
	 *
	 * Note that no validation is performed on whether the URLs would actually
	 * return a 200/OK response.
	 *
	 * @return array
	 */
	public function extract_and_update_urls() {
		if ( $this->static_page->is_type( 'html' ) ) {
			$this->save_body( $this->extract_and_replace_urls_in_html() );
		}

		if ( $this->static_page->is_type( 'css' ) ) {
			$this->save_body( $this->extract_and_replace_urls_in_css( $this->get_body() ) );
		}

		if ( $this->static_page->is_type( 'xml' ) ) {
			$this->save_body( $this->extract_and_replace_urls_in_xml() );
		}

		if ( $this->static_page->is_type( 'html' ) || $this->static_page->is_type( 'css' ) || $this->static_page->is_type( 'xml' ) ) {
			// Replace encoded URLs.
			$this->replace_encoded_urls();

			// If activated forced string/replace for URLs.
			if ( $this->options->get( 'force_replace_url' ) ) {
				$this->force_replace_urls();
			}
		}

		return array_unique( $this->extracted_urls );
	}

	/**
	 * Replaces origin URL with destination URL in response body
	 *
	 * This is a function of last resort for URL replacement. Ideally it was
	 * already done in one of the extract_and_replace_urls_in_x functions.
	 *
	 * This catches instances of WordPress URLs and replaces them with the
	 * destinaton_url. This generally works fine for absolute and relative URL
	 * generation. It'll produce sub-optimal results for offline URLs, in that
	 * it's only replacing the host and not adjusting the path according to the
	 * current page. The point of this is more to remove any traces of the
	 * WordPress URL than anything else.
	 *
	 * @return void
	 */
	public function replace_encoded_urls() {

		$destination_url = $this->options->get_destination_url();
		$response_body   = $this->get_body();

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`
		$response_body = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $destination_url, '/' ), $response_body );

		// replace encoded URLs, as found in query params
		$response_body = preg_replace( '/(https?%3A)?%2F%2F' . addcslashes( urlencode( Util::origin_host() ), '.' ) . '/i', urlencode( $destination_url ), $response_body );

		$this->save_body( $response_body );
	}

	/**
	 * Replaces origin URL with destination URL in response body
	 *
	 * This is a function of last resort for URL replacement. Ideally it was
	 * already done in one of the extract_and_replace_urls_in_x functions.
	 *
	 * This catches instances of WordPress URLs and replaces them with the
	 * destinaton_url. This generally works fine for absolute and relative URL
	 * generation. It'll produce sub-optimal results for offline URLs, in that
	 * it's only replacing the host and not adjusting the path according to the
	 * current page. The point of this is more to remove any traces of the
	 * WordPress URL than anything else.
	 *
	 * @return void
	 */
	public function force_replace_urls() {
		/*
		TODO:
		Can we get it to work with offline URLs via preg_replace_callback
		+ convert_url? To do that we'd need to grab the entire URL. Ideally
		that would also work with escaped URLs / inside of JavaScript. And
		even more ideally, we'd only have a single preg_replace.
		 */

		$destination_url = $this->options->get_destination_url();
		$response_body   = $this->get_body();

		// replace any instance of the origin url, whether it starts with https://, http://, or //.
		$response_body = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $destination_url, $response_body );

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`.
		// e.g. {"concatemoji":"http:\/\/www.example.org\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.6.1"}.
		$response_body = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $destination_url, '/' ), $response_body );

		$response_body = apply_filters( 'simply_static_force_replaced_urls_body', $response_body, $this->static_page );

		$this->save_body( $response_body );
	}

	/**
	 * Extract URLs and convert URLs to absolute URLs for each tag
	 *
	 * The tag is passed by reference, so it's updated directly and nothing is
	 * returned from this function.
	 *
	 * @param simple_html_dom_node $tag SHDP dom node
	 * @param string $tag_name name of the tag
	 * @param array $attributes array of attribute notes
	 *
	 * @return void
	 */
	private function extract_urls_and_update_tag( &$tag, $tag_name, $attributes ) {
		if ( isset( $tag->style ) ) {
			$updated_css = $this->extract_and_replace_urls_in_css( $tag->style );
			$tag->style  = $updated_css;
		}

		foreach ( $attributes as $attribute_name ) {
			if ( isset( $tag->$attribute_name ) ) {
				$extracted_urls  = array();
				$attribute_value = $tag->$attribute_name;

				// we need to verify that the meta tag is a URL.
				if ( 'meta' === $tag_name ) {
					if ( filter_var( $attribute_value, FILTER_VALIDATE_URL ) ) {
						$extracted_urls[] = $attribute_value;
					}
				} else {
					// srcset is a fair bit different from most html
					if ( $attribute_name === 'srcset' ) {
						$extracted_urls = $this->extract_urls_from_srcset( $attribute_value );
					} else {
						$extracted_urls[] = $attribute_value;
					}
				}

				$strict_url_validation = apply_filters( 'simply_static_strict_url_validation', false );

				foreach ( $extracted_urls as $extracted_url ) {
					if ( $strict_url_validation && ! filter_var( $extracted_url, FILTER_VALIDATE_URL ) ) {
						continue;
					}

					if ( $extracted_url !== '' ) {
						$updated_extracted_url = $this->add_to_extracted_urls( $extracted_url );
						$attribute_value       = str_replace( $extracted_url, $updated_extracted_url, $attribute_value );
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
	private function extract_and_replace_urls_in_html() {
		$html_string = $this->get_body();
		$match_tags  = apply_filters( 'ss_match_tags', self::$match_tags );

		$dom = HtmlDomParser::str_get_html( $html_string );

		// return the original html string if dom is blank or boolean (unparseable)
		if ( $dom == '' || is_bool( $dom ) ) {
			return $html_string;
		} else {
			// handle tags with attributes
			foreach ( $match_tags as $tag_name => $attributes ) {
				$tags = $dom->find( $tag_name );

				foreach ( $tags as $tag ) {
					$this->extract_urls_and_update_tag( $tag, $tag_name, $attributes );
				}
			}

			// handle 'style' tag differently, since we need to parse the content.
			$parse_inline_style = apply_filters( 'ss_parse_inline_style', true );

			if ( $parse_inline_style ) {
				$style_tags = $dom->find( 'style' );

				foreach ( $style_tags as $tag ) {
					// Check if valid content exists.
					try {
						$updated_css        = $this->extract_and_replace_urls_in_css( $tag->innerhtmlKeep );
						$tag->innerhtmlKeep = $updated_css;
					} catch ( Exception $e ) {
						// If not skip the result.
						continue;
					}
				}
			}

			// handle 'script' tag differently, since we need to parse the content.
			$parse_inline_script = apply_filters( 'ss_parse_inline_script', true );

			if ( $parse_inline_script ) {
				$script_tags = $dom->find( 'script' );

				foreach ( $script_tags as $tag ) {
					// Check if valid content exists.
					try {
						$updated_script     = $this->extract_and_replace_urls_in_script( $tag->innerhtmlKeep );
						$tag->innerhtmlKeep = $updated_script;
						$this->extract_and_replace_urls_in_script_inner_text( $tag );
					} catch ( Exception $e ) {
						// If not skip the result.
						continue;
					}
				}
			}

			do_action(
				'ss_after_extract_and_replace_urls_in_html',
				$dom,
				$this
			);

			// Further manipulate Dom?
			$dom = apply_filters( 'ss_dom_before_save', $dom, $this->static_page->url );

			return $dom->save();
		}
	}

	/**
	 * Extract URLs from the srcset attribute
	 *
	 * @param string $srcset Value of the srcset attribute
	 *
	 * @return array  Array of extracted URLs
	 */
	private function extract_urls_from_srcset( $srcset ) {
		$extracted_urls = array();

		foreach ( explode( ',', $srcset ) as $url_and_descriptor ) {
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
	 * @param string $text The CSS to extract URLs from
	 *
	 * @return string The CSS with all URLs converted
	 */
	private function extract_and_replace_urls_in_css( $text ) {
		$text     = html_entity_decode( $text );
		$patterns = array(
			"/url\(\s*[\"']?([^)\"']+)/", // url()
			"/@import\s+[\"']([^\"']+)/"
		); // @import w/o url()

		foreach ( $patterns as $pattern ) {
			$text = preg_replace_callback( $pattern, array( $this, 'css_matches' ), $text );
		}

		return $text;
	}

	private function extract_and_replace_urls_in_script( $text ) {
		if ( $this->is_json( $text ) ) {
			$decoded_text = html_entity_decode( $text, ENT_NOQUOTES );
		} else {
			$decoded_text = html_entity_decode( $text );
		}

		$decoded_text = apply_filters( 'simply_static_decoded_urls_in_script', $decoded_text, $this->static_page, $this );

		$text = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $this->options->get_destination_url(), $decoded_text );

		return $text;
	}

	/**
	 * @param \ $tag
	 *
	 * @return array|string|string[]|null
	 */
	private function extract_and_replace_urls_in_script_inner_text( $tag ) {

		$regex = '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i';

		switch ( $this->options->get( 'destination_url_type' ) ) {
			case 'absolute':
				$convert_to = $this->options->get_destination_url();
				break;
			case 'relative':
				// Adding \/? before end of regex pattern to convert url.com/ & url.com to relative path, ex. /path/.
				$regex      = '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '\/?/i';
				$convert_to = $this->options->get( 'relative_path' );
				break;
			default:
				// Offline mode.
				// Adding \/? before end of regex pattern to convert url.com/ & url.com to relative path, ex. /path/.
				$regex      = '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '\/?/i';
				$convert_to = '/';
		}

		if ( $this->is_json( $tag->innerText ) ) {
			$decoded_text = html_entity_decode( $tag->innerText, ENT_NOQUOTES );
		} else {
			$decoded_text = html_entity_decode( $tag->innerText );
		}

		$decoded_text = apply_filters( 'simply_static_decoded_text_in_script', $decoded_text, $this->static_page, $convert_to, $tag, $this );

		$tag->innerText = preg_replace( $regex, $convert_to, $decoded_text );

		return $tag;
	}

	/**
	 * Check whether a given string is a valid JSON representation.
	 *
	 * Copied from: WP CLI, https://github.com/wp-cli/wp-cli/blob/f3e4b0785aa3d3132ee73be30aedca8838a8fa06/php/utils.php#L1600-L1612
	 *
	 * @param string $argument String to evaluate.
	 * @param bool $ignore_scalars Optional. Whether to ignore scalar values.
	 *                               Defaults to true.
	 *
	 * @return bool Whether the provided string is a valid JSON representation.
	 */
	protected function is_json( $argument, $ignore_scalars = true ) {
		if ( ! is_string( $argument ) || '' === $argument ) {
			return false;
		}
		$arg = $argument[0];
		if ( $ignore_scalars && ! in_array( $argument[0], [ '{', '[' ], true ) ) {
			return false;
		}

		json_decode( $argument, $assoc = true );

		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * callback function for preg_replace in extract_and_replace_urls_in_css
	 *
	 * Takes the match, extracts the URL, adds it to the list of URLs, converts
	 * the URL to a destination URL.
	 *
	 * @param array $matches Array of preg_replace matches
	 *
	 * @return string An updated string for the text that was originally matched
	 */
	public function css_matches( $matches ) {
		$full_match    = $matches[0];
		$extracted_url = $matches[1];

		if ( isset( $extracted_url ) && $extracted_url !== '' ) {
			$updated_extracted_url = $this->add_to_extracted_urls( $extracted_url );
			$full_match            = str_ireplace( $extracted_url, $updated_extracted_url, $full_match );
		}

		return $full_match;
	}

	/**
	 * Use regex to extract URLs from XML docs (e.g. /feed/)
	 * @return string The XML with all of the URLs converted
	 */
	private function extract_and_replace_urls_in_xml() {
		$xml_string = $this->get_body();
		// match anything starting with http/s plus all following characters
		// except: [space] " ' <
		$pattern = "/https?:\/\/[^\s\"'<]+/";
		$text    = preg_replace_callback( $pattern, array( $this, 'xml_matches' ), $xml_string );

		return $text;
	}

	/**
	 * Callback function for preg_replace in extract_and_replace_urls_in_xml
	 *
	 * Takes the match, adds it to the list of URLs, converts the URL to a
	 * destination URL.
	 *
	 * @param array $matches Array of regex matches found in the XML doc
	 *
	 * @return string         The extracted, converted URL
	 */
	private function xml_matches( $matches ) {
		$extracted_url = $matches[0];

		if ( isset( $extracted_url ) && $extracted_url !== '' ) {
			$updated_extracted_url = $this->add_to_extracted_urls( $extracted_url );
		}

		return $updated_extracted_url;
	}

	/**
	 * Add a URL to the extracted URLs array and convert to absolute/relative/offline
	 *
	 * URLs are first converted to absolute URLs. Then they're checked to see if
	 * they are local URLs; if they are, they're added to the extracted URLs
	 * queue.
	 *
	 * If the destination URL type requested was absolute, the WordPress scheme/
	 * host is swapped for the destination scheme/host. If the destination URL
	 * type is relative/offline, the URL is converted to that format. Then the
	 * URL is returned.
	 *
	 * @return string The URL that should be added to the list of extracted URLs
	 * @return string The URL, converted to an absolute/relative/offline URL
	 */
	public function add_to_extracted_urls( $extracted_url ) {
		$url = Util::relative_to_absolute_url( $extracted_url, $this->static_page->url );

		if ( $url && Util::is_local_url( $url ) ) {
			// add to extracted urls queue
			$this->extracted_urls[] = apply_filters(
				'simply_static_extracted_url',
				Util::remove_params_and_fragment( $url ),
				$url,
				$this->static_page
			);

			$url = $this->convert_url( $url );
		}

		return $url;
	}

	/**
	 * Convert URL to absolute URL at desired host or to a relative or offline URL
	 *
	 * @param string $url Absolute URL to convert
	 *
	 * @return string      Converted URL
	 */
	private function convert_url( $url ) {

		$url = apply_filters( 'simply_static_pre_converted_url', $url, $this->static_page, $this );

		if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
			$url = $this->convert_absolute_url( $url );
		} else if ( $this->options->get( 'destination_url_type' ) == 'relative' ) {
			$url = $this->convert_relative_url( $url );
		} else if ( $this->options->get( 'destination_url_type' ) == 'offline' ) {
			$url = $this->convert_offline_url( $url );
		}

		$url = remove_query_arg( 'simply_static_page', $url );

		return apply_filters( 'simply_static_converted_url', $url, $this->static_page, $this );
	}

	/**
	 * Convert a WordPress URL to a URL at the destination scheme/host
	 *
	 * @param string $url Absolute URL to convert
	 *
	 * @return string      URL at destination scheme/host
	 */
	private function convert_absolute_url( $url ) {
		$destination_url = $this->options->get_destination_url();
		$url             = Util::strip_protocol_from_url( $url );
		$url             = str_replace( Util::origin_host(), $destination_url, $url );

		return $url;
	}

	/**
	 * Convert a WordPress URL to a relative path
	 *
	 * @param string $url Absolute URL to convert
	 *
	 * @return string      Relative path for the URL
	 */
	private function convert_relative_url( $url ) {
		$url = Util::get_path_from_local_url( $url );
		$url = $this->options->get( 'relative_path' ) . $url;

		return $url;
	}

	/**
	 * Convert a WordPress URL to a path for offline usage
	 *
	 * This function compares current page's URL to the provided URL and
	 * creates a path for getting from one page to the other. It also attaches
	 * /index.html onto the end of any path that isn't a file, before any
	 * fragments or params.
	 *
	 * Example:
	 *   static_page->url: http://static-site.dev/2013/01/11/page-a/
	 *               $url: http://static-site.dev/2013/01/10/page-b/
	 *               path: ./../../10/page-b/index.html
	 *
	 * @param string $url Absolute URL to convert
	 *
	 * @return string      Converted path
	 */
	private function convert_offline_url( $url ) {
		// remove the scheme/host from the url
		$page_path      = Util::get_path_from_local_url( $this->static_page->url );
		$extracted_path = Util::get_path_from_local_url( $url );

		// create a path from one page to the other
		$path = Util::create_offline_path( $extracted_path, $page_path );

		$path_info = Util::url_path_info( $url );
		if ( $path_info['extension'] === '' ) {
			// If there's no extension, we need to add a /index.html,
			// and do so before any params or fragments.
			$clean_path = Util::remove_params_and_fragment( $path );
			$fragment   = substr( $path, strlen( $clean_path ) );

			$path = trailingslashit( $clean_path );
			$path .= 'index.html' . $fragment;
		}

		return $path;
	}
}
