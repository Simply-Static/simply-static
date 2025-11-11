<?php

namespace Simply_Static;

use Exception;
use DOMDocument;
use DOMXPath;

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
		'img'     => array(
			'src',
			'usemap',
			'longdesc',
			'dynsrc',
			'lowsrc',
			'srcset',
			'data-src',
			'data-srcset',
			'data-bg'
		),
		'use'     => array( 'href' ),
		'picture' => array( 'src', 'srcset', 'data-src', 'data-srcset', 'data-bg' ),
		'amp-img' => array( 'src', 'srcset' ),

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

		'audio'        => array( 'src', 'srcset' ),
		'figure'       => array( 'src', 'srcset' ),
		'command'      => array( 'icon' ),
		'embed'        => array( 'src', 'code', 'pluginspage' ),
		'event-source' => array( 'src' ),
		'html'         => array( 'manifest', 'background', 'xmlns' ),
		'source'       => array( 'src', 'srcset' ),
		'video'        => array( 'src', 'poster', 'srcset' ),
		'image'        => array( 'href', 'xlink:href', 'src', 'style', 'srcset' ),

		'bgsound' => array( 'src' ),
		'div'     => array( 'href', 'src', 'style', 'data-bg', 'data-thumbnail' ),
		'span'    => array( 'href', 'src', 'style', 'data-bg' ),
		'section' => array( 'style', 'data-bg' ),
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
		'atom' => array( 'href' ),
	);

	/**
	 * The static page to extract URLs from
	 * @var \Simply_Static\Page
	 */
	protected $static_page;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var \Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * The url of the site
	 * @var array
	 */
	public $extracted_urls = array();

	/**
	 * Stores script tags extracted from HTML
	 * @var array
	 */
	private $script_tags = array();

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

		// Restore script tags if they exist and there are placeholders in the content
		if ( ! empty( $this->script_tags ) && strpos( $content, 'SCRIPT_PLACEHOLDER' ) !== false ) {
			$content = preg_replace_callback( '/<!-- SCRIPT_PLACEHOLDER_(\d+) -->/', function ( $matches ) {
				$index = (int) $matches[1];
				if ( isset( $this->script_tags[ $index ] ) ) {
					return $this->script_tags[ $index ];
				} else {
					return '';
				}
			}, $content );
		}

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

		// Treat as CSS either by content-type or by file extension fallback (handles servers sending wrong or missing headers)
		$looks_like_css = $this->static_page->is_type( 'css' ) || ( isset( $this->static_page->file_path ) && substr( $this->static_page->file_path, - 4 ) === '.css' );
		if ( $looks_like_css ) {
			$this->save_body( $this->extract_and_replace_urls_in_css( $this->get_body() ) );
		}

		if ( $this->static_page->is_type( 'xml' ) || $this->static_page->is_type( 'xsl' ) ) {
			$this->save_body( $this->extract_and_replace_urls_in_xml() );
		}

		if ( $this->static_page->is_type( 'json' ) ) {
			// Check if the URL includes 'simply-static/configs'
			if ( strpos( $this->static_page->file_path, 'simply-static/configs' ) === false ) {
				// Proceed to replace the URL.
				$this->save_body( $this->extract_and_replace_urls_in_json() );
			}
		}

		if ( $this->static_page->is_type( 'html' ) || $this->static_page->is_type( 'css' ) || $this->static_page->is_type( 'xml' ) || $this->static_page->is_type( 'json' ) ) {
			// Check if the URL includes 'simply-static/configs'
			if ( strpos( $this->static_page->file_path, 'simply-static/configs' ) === false ) {
				// Replace encoded URLs.
				$this->replace_encoded_urls();
			}

			// If activated forced string/replace for URLs.
			if ( $this->options->get( 'force_replace_url' ) && ( ! $this->options->get( 'use_forms' ) && ! $this->options->get( 'use_comments' ) ) ) {
				$this->force_replace_urls();
			}
		}

		return array_unique( $this->extracted_urls );
	}

	/**
	 * Check if a string is valid JSON
	 *
	 * @param string $string The string to check
	 *
	 * @return bool Whether the string is valid JSON
	 */
	private function is_valid_json( $string ) {
		if ( ! is_string( $string ) ) {
			return false;
		}

		// Quick pre-check to avoid expensive decode attempts on non-JSON strings
		$trimmed = trim( $string );
		if ( $trimmed === '' ) {
			return false;
		}
		$first = $trimmed[0];
		if ( $first !== '{' && $first !== '[' && strpos( $trimmed, '{' ) === false && strpos( $trimmed, '[' ) === false ) {
			return false;
		}

		// Create a safe, normalized copy for detection only (do not mutate the original)
		// Decode HTML entities (including quotes) so JSON can be recognized reliably
		$normalized = html_entity_decode( $trimmed, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );

		// Attempt to decode
		$json_data = json_decode( $normalized, true );

		return $json_data !== null && ( is_array( $json_data ) || is_object( $json_data ) );
	}

	/**
	 * Flag for preserving attributes.
	 *
	 * @return mixed|null
	 */
	protected function can_preserve_attributes() {
		return apply_filters( 'ss_extract_html_preserve_attributes', true );
	}

	/**
	 * Preserve attributes in HTML content
	 *
	 * @param string $content The HTML content
	 *
	 * @return array An array containing the modified content and the preserved JSON attributes
	 */
	private function preserve_attributes( $content ) {

		if ( ! $this->can_preserve_attributes() ) {
			return $content;
		}

		// Protect both named and numeric (decimal/hex) entities commonly used within attributes
		// so that subsequent global decoding steps won't turn them into raw characters and break markup.
		$entity_variants = [
			'quote'     => [ '&quot;', '&#34;', '&#x22;', '&#X22;' ],
			'apos'      => [ '&apos;', '&#39;', '&#x27;', '&#X27;' ],
			'lessthan'  => [ '&lt;', '&#60;', '&#x3C;', '&#X3C;' ],
			'greatthan' => [ '&gt;', '&#62;', '&#x3E;', '&#X3E;' ],
			'ampersand' => [ '&amp;', '&#38;', '&#x26;', '&#X26;' ],
		];

		foreach ( $entity_variants as $placeholder_name => $variants ) {
			$placeholder = strtoupper( $placeholder_name ) . '_PLACEHOLDER';
			foreach ( $variants as $entity ) {
				if ( strpos( $content, $entity ) !== false ) {
					$content = str_replace( $entity, $placeholder, $content );
				}
			}
		}


		return $content;
	}

	/**
	 * Restore attributes in HTML content
	 *
	 * @param string $content The HTML content with placeholders
	 *
	 * @return string The HTML content with restored attributes
	 */
	private function restore_attributes( $content ) {

		if ( ! $this->can_preserve_attributes() ) {
			return $content;
		}

		// Restore placeholders back to safe, named entities for consistency
		$restore_map = [
			'QUOTE_PLACEHOLDER'     => '&quot;',
			'APOS_PLACEHOLDER'      => '&apos;',
			'LESSTHAN_PLACEHOLDER'  => '&lt;',
			'GREATTHAN_PLACEHOLDER' => '&gt;',
			'AMPERSAND_PLACEHOLDER' => '&amp;',
		];

		foreach ( $restore_map as $placeholder => $entity ) {
			if ( strpos( $content, $placeholder ) !== false ) {
				$content = str_replace( $placeholder, $entity, $content );
			}
		}

		return $content;
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

		// Preserve JSON attributes before replacement
		$response_body = $this->preserve_attributes( $response_body );

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`
		$response_body = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $destination_url, '/' ), $response_body );

		// replace encoded URLs, as found in query params
		$response_body = preg_replace( '/(https?%3A)?%2F%2F' . addcslashes( urlencode( Util::origin_host() ), '.' ) . '/i', urlencode( $destination_url ), $response_body );

		// Restore preserved JSON attributes
		$response_body = $this->restore_attributes( $response_body );

		$this->save_body( $response_body );
	}

	/**
	 * Force Replace the origin URL from the content with the destination URL.
	 *
	 * @param string $content Content.
	 *
	 * @return array|string|string[]
	 */
	public function force_replace( $content ) {
		$destination_url = $this->options->get_destination_url();

		// Preserve JSON attributes before replacement
		$content = $this->preserve_attributes( $content );

		// replace any instance of the origin url, whether it starts with https://, http://, or //.
		$content = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $destination_url, $content );

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`.
		// e.g. {"concatemoji":"http:\/\/www.example.org\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.6.1"}.
		$content = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $destination_url, '/' ), $content );

		// Restore preserved JSON attributes
		$content = $this->restore_attributes( $content );

		return $content;
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
		$response_body = $this->get_body();
		$response_body = $this->force_replace( $response_body );
		$response_body = apply_filters( 'simply_static_force_replaced_urls_body', $response_body, $this->static_page );

		$this->save_body( $response_body );
	}

	/**
	 * Extract URLs and convert URLs to absolute URLs for each tag
	 *
	 * The tag is passed by reference, so it's updated directly and nothing is
	 * returned from this function.
	 *
	 * @param DOMElement $tag DOM element node
	 * @param string $tag_name name of the tag
	 * @param array $attributes array of attribute notes
	 *
	 * @return void
	 */
	private function extract_urls_and_update_tag( &$tag, $tag_name, $attributes ) {
		// Handle style attribute
		if ( $tag->hasAttribute( 'style' ) ) {
			$style_value = $tag->getAttribute( 'style' );
			$updated_css = $this->extract_and_replace_urls_in_css( $style_value );
			$tag->setAttribute( 'style', $updated_css );
		}

		foreach ( $attributes as $attribute_name ) {
			if ( $tag->hasAttribute( $attribute_name ) ) {
				$extracted_urls  = array();
				$attribute_value = $tag->getAttribute( $attribute_name );

				// Skip processing any attribute that contains valid JSON to prevent breaking JSON structure
				if ( $this->is_valid_json( $attribute_value ) ) {
					// This attribute contains JSON, don't process it as a URL
					continue;
				}

				// we need to verify that the meta tag is a URL.
				if ( 'meta' === $tag_name ) {
					if ( filter_var( $attribute_value, FILTER_VALIDATE_URL ) ) {
						$extracted_urls[] = $attribute_value;
					}
				} else {
					// srcset is a fair bit different from most html
					if ( $attribute_name === 'srcset' || $attribute_name === 'data-srcset' ) {
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

						if ( ! is_null( $updated_extracted_url ) ) {
							$attribute_value = str_replace( $extracted_url, $updated_extracted_url, $attribute_value );
						}
					}
				}
				$tag->setAttribute( $attribute_name, $attribute_value );
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

		// Preserve JSON attributes before processing
		$html_string = $this->preserve_attributes( $html_string );

		// Extract and preserve non-conditional HTML comments to avoid altering their content (e.g., commented-out scripts)
		$html_comments                 = [];
		$comment_placeholder           = '<!-- COMMENT_PLACEHOLDER_%d -->';
		$non_conditional_comment_regex = '/<!--(?!\s*\[if).*?-->/s';
		$html_string                   = preg_replace_callback( $non_conditional_comment_regex, function ( $matches ) use ( &$html_comments, &$comment_placeholder ) {
			$index           = count( $html_comments );
			$html_comments[] = $matches[0];

			return sprintf( $comment_placeholder, $index );
		}, $html_string );

		// Next, extract and save all script tags using regex to ensure they're preserved
		$this->script_tags  = []; // Reset the array for each call
		$script_placeholder = '<!-- SCRIPT_PLACEHOLDER_%d -->';
		$script_regex       = '/<script\b[^>]*>.*?<\/script>/is';

		// Extract and preserve conditional comments
		$conditional_comments    = [];
		$conditional_placeholder = '<!-- CONDITIONAL_COMMENT_PLACEHOLDER_%d -->';
		// Match conditional comments with a simpler, more direct approach
		// First pattern: match complete conditional comments (with closing tags)
		$complete_conditional_regex = '/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/s';
		// Second pattern: match incomplete conditional comments (without closing tags)
		$incomplete_conditional_regex = '/<!--\[if[^\]]*\]>((?!<!--\[if).)*?(?=<!--|$)/s';

		// Use regex method to ensure script tags are preserved
		// Extract script tags, process them for URL replacement, and replace them with placeholders
		$html_string = preg_replace_callback( $script_regex, function ( $matches ) use ( &$script_placeholder ) {
			$index      = count( $this->script_tags );
			$script_tag = $matches[0]; // The entire script tag

			// Process script tag for URL replacement
			// Replace URLs in src attribute
			$script_tag = preg_replace_callback( '/<script\b([^>]*)src=(["\'])([^"\']+)(["\'])([^>]*)>/i', function ( $src_matches ) {
				$before_src  = $src_matches[1];
				$quote_start = $src_matches[2];
				$src_url     = $src_matches[3];
				$quote_end   = $src_matches[4];
				$after_src   = $src_matches[5];

				// Process the URL
				$updated_url = $this->add_to_extracted_urls( $src_url );

				return "<script{$before_src}src={$quote_start}{$updated_url}{$quote_end}{$after_src}>";
			}, $script_tag );

			// Replace URLs in script content
			$script_tag = preg_replace_callback( '/<script\b[^>]*>(.*?)<\/script>/is', function ( $content_matches ) {
				$script_content = $content_matches[1];
				if ( ! empty( $script_content ) ) {
					// Process the script content
					$updated_content = $this->extract_and_replace_urls_in_script( $script_content );

					return str_replace( $script_content, $updated_content, $content_matches[0] );
				}

				return $content_matches[0];
			}, $script_tag );

			// Save the processed script tag
			$this->script_tags[] = $script_tag;

			return sprintf( $script_placeholder, $index );
		}, $html_string );

		// First, extract and preserve complete conditional comments
		$html_string = preg_replace_callback( $complete_conditional_regex, function ( $matches ) use ( &$conditional_placeholder, &$conditional_comments ) {
			$index               = count( $conditional_comments );
			$conditional_comment = $matches[0]; // The complete conditional comment

			// Process URLs in the conditional comment if needed
			$conditional_comment = preg_replace_callback( '/<script\b([^>]*)src=(["\'])([^"\']+)(["\'])([^>]*)>/i', function ( $src_matches ) {
				$before_src  = $src_matches[1];
				$quote_start = $src_matches[2];
				$src_url     = $src_matches[3];
				$quote_end   = $src_matches[4];
				$after_src   = $src_matches[5];

				// Process the URL
				$updated_url = $this->add_to_extracted_urls( $src_url );

				return "<script{$before_src}src={$quote_start}{$updated_url}{$quote_end}{$after_src}>";
			}, $conditional_comment );

			// Save the processed conditional comment
			$conditional_comments[] = $conditional_comment;

			return sprintf( $conditional_placeholder, $index );
		}, $html_string );

		// Then, extract and fix incomplete conditional comments
		$html_string = preg_replace_callback( $incomplete_conditional_regex, function ( $matches ) use ( &$conditional_placeholder, &$conditional_comments ) {
			$index               = count( $conditional_comments );
			$conditional_comment = $matches[0]; // The incomplete conditional comment

			// Check if this is actually an incomplete conditional comment
			if ( strpos( $conditional_comment, '<!--[if' ) === 0 && strpos( $conditional_comment, '<![endif]-->' ) === false ) {
				// Process URLs in the conditional comment if needed
				$conditional_comment = preg_replace_callback( '/<script\b([^>]*)src=(["\'])([^"\']+)(["\'])([^>]*)>/i', function ( $src_matches ) {
					$before_src  = $src_matches[1];
					$quote_start = $src_matches[2];
					$src_url     = $src_matches[3];
					$quote_end   = $src_matches[4];
					$after_src   = $src_matches[5];

					// Process the URL
					$updated_url = $this->add_to_extracted_urls( $src_url );

					return "<script{$before_src}src={$quote_start}{$updated_url}{$quote_end}{$after_src}>";
				}, $conditional_comment );

				// Add the missing closing tag
				$conditional_comment .= '<![endif]-->';

				// Save the processed and fixed conditional comment
				$conditional_comments[] = $conditional_comment;

				return sprintf( $conditional_placeholder, $index );
			}

			// If it's not actually an incomplete conditional comment, return it unchanged
			return $conditional_comment;
		}, $html_string );

		// If there's no HTML to process, return early to avoid DOM warnings/errors
		if ( ! is_string( $html_string ) || trim( $html_string ) === '' ) {
			return $html_string;
		}

		// Use PHP's native DOMDocument
		$dom = new DOMDocument();

		// Suppress errors from malformed HTML
		libxml_use_internal_errors( true );

		// Determine site charset (fallback to UTF-8)
		$charset = \get_bloginfo( 'charset' );
		if ( empty( $charset ) ) {
			$charset = 'UTF-8';
		}

		// Prepare HTML for DOM via helper (prefers mb_encode_numericentity; legacy fallback for PHP < 8.2)
		$prepared     = Html_Encoding_Helper::prepare_html_for_dom( $html_string, $charset, $this );
		$html_for_dom = is_array( $prepared ) && isset( $prepared['html'] ) ? $prepared['html'] : $html_string;
		$dom_encoding = is_array( $prepared ) && isset( $prepared['encoding'] ) ? $prepared['encoding'] : $charset;

		// Load the HTML, preserving whitespace and silencing libxml warnings
		$dom->preserveWhiteSpace = true;
		$dom->formatOutput       = false;

		// Hint DOMDocument about the expected encoding
		$dom->encoding = $dom_encoding;
		$dom->loadHTML( $html_for_dom, LIBXML_NOWARNING | LIBXML_NOERROR );

		// Clear any errors
		libxml_clear_errors();

		// Create a DOMXPath object to query the DOM
		$xpath = new DOMXPath( $dom );

		// return the original html string if dom is blank or couldn't be parsed
		if ( ! $dom->documentElement ) {
			return $html_string;
		} else {
			// handle tags with attributes
			foreach ( $match_tags as $tag_name => $attributes ) {
				$elements = $xpath->query( '//' . $tag_name );

				if ( $elements ) {
					foreach ( $elements as $element ) {
						$this->extract_urls_and_update_tag( $element, $tag_name, $attributes );
					}
				}
			}

			// handle 'style' tag differently, since we need to parse the content.
			$parse_inline_style = apply_filters( 'ss_parse_inline_style', true );

			if ( $parse_inline_style ) {
				$style_tags = $xpath->query( '//style' );

				if ( $style_tags ) {
					foreach ( $style_tags as $tag ) {
						// Check if valid content exists.
						try {
							$content          = $tag->textContent;
							$updated_css      = $this->extract_and_replace_urls_in_css( $content );
							$tag->textContent = $updated_css;
						} catch ( Exception $e ) {
							// If not skip the result.
							continue;
						}
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

			// Check if $dom is still a DOMDocument object after filters
			if ( is_string( $dom ) ) {
				// If $dom has been converted to a string by a filter, return it directly
				return $dom;
			}

			// Ensure a proper <meta charset> is present as the first child of <head>
			try {
				$charset = is_string( $charset ) && $charset !== '' ? $charset : \get_bloginfo( 'charset' );
				if ( empty( $charset ) ) {
					$charset = 'UTF-8';
				}
				$head_nodes = $dom->getElementsByTagName( 'head' );
				$head       = $head_nodes && $head_nodes->length > 0 ? $head_nodes->item( 0 ) : null;
				if ( ! $head ) {
					// Create <head> if missing
					$head         = $dom->createElement( 'head' );
					$html_el_list = $dom->getElementsByTagName( 'html' );
					$html_el      = $html_el_list && $html_el_list->length > 0 ? $html_el_list->item( 0 ) : null;
					if ( $html_el ) {
						if ( $html_el->firstChild ) {
							$html_el->insertBefore( $head, $html_el->firstChild );
						} else {
							$html_el->appendChild( $head );
						}
					}
				}
				if ( $head ) {
					// Find existing <meta charset>
					$existing_meta = null;
					foreach ( $head->getElementsByTagName( 'meta' ) as $m ) {
						if ( $m->hasAttribute( 'charset' ) ) {
							$existing_meta = $m;
							break;
						}
					}
					if ( $existing_meta ) {
						$existing_meta->setAttribute( 'charset', $charset );
						// Move to top of <head>
						if ( $head->firstChild && $head->firstChild !== $existing_meta ) {
							$head->insertBefore( $existing_meta, $head->firstChild );
						}
					} else {
						$meta = $dom->createElement( 'meta' );
						$meta->setAttribute( 'charset', $charset );
						if ( $head->firstChild ) {
							$head->insertBefore( $meta, $head->firstChild );
						} else {
							$head->appendChild( $meta );
						}
					}
				}
			} catch ( \Throwable $e ) {
				// If anything goes wrong here, continue without blocking the export
			}

			// Save the HTML document
			$html = $dom->saveHTML();

			// Restore script tags
			$html = preg_replace_callback( '/<!-- SCRIPT_PLACEHOLDER_(\d+) -->/', function ( $matches ) {
				$index = (int) $matches[1];
				if ( isset( $this->script_tags[ $index ] ) ) {
					return $this->script_tags[ $index ];
				} else {
					return '';
				}
			}, $html );

			// Restore conditional comments
			$html = preg_replace_callback( '/<!-- CONDITIONAL_COMMENT_PLACEHOLDER_(\d+) -->/', function ( $matches ) use ( $conditional_comments ) {
				$index = (int) $matches[1];
				if ( isset( $conditional_comments[ $index ] ) ) {
					return $conditional_comments[ $index ];
				} else {
					return '';
				}
			}, $html );

			// Restore non-conditional comments exactly as they were
			$html = preg_replace_callback( '/<!-- COMMENT_PLACEHOLDER_(\d+) -->/', function ( $matches ) use ( $html_comments ) {
				$index = (int) $matches[1];

				return isset( $html_comments[ $index ] ) ? $html_comments[ $index ] : '';
			}, $html );

			// Restore JSON attributes
			$html = $this->restore_attributes( $html );

			// Decode HTML entities across the final HTML using the site's charset so non-Latin text (e.g., Japanese/Arabic)
			// is preserved as real characters instead of numeric entities. To avoid breaking complex attribute values
			// (e.g., Elementor's data-settings JSON that may contain encoded SVG like &lt;svg&gt;), we protect attributes
			// by replacing key entities with placeholders before decoding, then restore them afterwards.
			$charset = \get_bloginfo( 'charset' );

			if ( empty( $charset ) ) {
				$charset = 'UTF-8';
			}
			$should_decode_final = apply_filters( 'simply_static_decode_final_html', true, $this );

			if ( $should_decode_final ) {
				// Protect attribute content that must remain entity-encoded during the global decode
				$html = $this->preserve_attributes( $html );
				$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, $charset );
				// Restore the protected attribute content back to entities to keep markup valid
				$html = $this->restore_attributes( $html );
			}

			$html = apply_filters( 'ss_html_after_restored_attributes', $html, $this );

			return $html;
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
			$url_without_descriptor = trim( preg_replace( '/[\d\.]+[xw]\s*$/', '', $url_and_descriptor ) );
			// Check if the URL consists of only numbers - this fixes issue where SS detects srcset descriptor such as 100 150w as a URL which
			// is then replaced with relative URL for current post, this creates 5-10 additional "URLs" to be exported per article
			if ( preg_match( '/^\d+$/', trim( $url_without_descriptor ) ) ) {
				// If it does, skip it
				continue;
			}

			$extracted_urls[] = $url_without_descriptor;
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
		// Decode entities to ensure URLs are detected correctly, using site charset
		$charset = \get_bloginfo( 'charset' );
		if ( empty( $charset ) ) {
			$charset = 'UTF-8';
		}
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, $charset );

		// Pass 1: Handle url(...) constructs with quoted or unquoted values, including relative URLs.
		// Pattern breakdown:
		// - url( optional whitespace
		// - capture optional quote (single or double) in group 1
		// - capture the URL (anything but closing paren; we'll trim trailing whitespace) in group 2
		// - match the same optional quote in group 3 via backreference
		// - optional whitespace and closing paren
		$text = preg_replace_callback(
			'/url\(\s*(?:(["\'])\s*)?([^\)\s]+?)\s*(?:\1)?\s*\)/i',
			function ( $m ) {
				$quote = isset( $m[1] ) ? $m[1] : '';
				$raw   = $m[2];
				$val   = trim( $raw );

				// Skip data URIs or empty
				if ( $val === '' || stripos( $val, 'data:' ) === 0 ) {
					return $m[0];
				}

				$updated = $this->add_to_extracted_urls( $val );
				if ( empty( $updated ) ) {
					return $m[0];
				}

				// Reconstruct preserving original quote style if present
				if ( $quote === '"' || $quote === "'" ) {
					return 'url(' . $quote . $updated . $quote . ')';
				}

				return 'url(' . $updated . ')';
			},
			$text
		);

		// Pass 2: Fallback - replace any remaining bare local absolute or protocol-relative URLs by converting them.
		$escaped_origin = preg_quote( Util::origin_host(), '/' );
		$text           = preg_replace_callback(
			'/((?:https?:)?\/\/' . $escaped_origin . ')[^"\')\s;,]+/i',
			function ( $m ) {
				$matched_url = $m[0];
				$updated     = $this->add_to_extracted_urls( $matched_url );

				return $updated ?: $matched_url;
			},
			$text
		);

		return $text;
	}

	private function extract_and_replace_urls_in_script( $text ) {
		$charset = \get_bloginfo( 'charset' );
		if ( empty( $charset ) ) {
			$charset = 'UTF-8';
		}
		if ( $this->is_valid_json( $text ) ) {
			$decoded_text = html_entity_decode( $text, ENT_NOQUOTES | ENT_HTML5 | ENT_SUBSTITUTE, $charset );
		} else {
			$decoded_text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, $charset );
		}

		$decoded_text = apply_filters( 'simply_static_decoded_urls_in_script', $decoded_text, $this->static_page, $this );

		// Check if this is an importmap script
		$is_importmap = $this->is_valid_json( $decoded_text ) && strpos( $decoded_text, '"imports"' ) !== false;

		// Get the appropriate replacement URL based on destination URL type
		switch ( $this->options->get( 'destination_url_type' ) ) {
			case 'absolute':
				$convert_to = $this->options->get_destination_url();
				break;
			case 'relative':
				$convert_to = $this->options->get( 'relative_path' );
				break;
			default:
				// Offline mode
				$convert_to = '/';

				// For importmap scripts in offline mode, we need to add './' prefix
				if ( $is_importmap ) {
					$convert_to = './' . $convert_to;
				}
		}

		// Replace URLs in the script content
		// First, replace protocol-relative URLs (//example.com)
		$text = preg_replace( '/(["\'(])\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', '$1' . $convert_to, $decoded_text );

		// Then replace absolute URLs (http://example.com or https://example.com)
		$text = preg_replace( '/(["\'(])(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', '$1' . $convert_to, $text );

		// Also replace JSON-encoded URLs
		$text = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $convert_to, '/' ), $text );

		return $text;
	}


	/**
	 * Check whether a given string is a valid JSON representation.
	 *
	 * This is a legacy method, use is_valid_json() instead.
	 *
	 * @param string $argument String to evaluate.
	 * @param bool $ignore_scalars Optional. Whether to ignore scalar values.
	 *                               Defaults to true.
	 *
	 * @return bool Whether the provided string is a valid JSON representation.
	 * @deprecated Use is_valid_json() instead
	 */
	protected function is_json( $argument, $ignore_scalars = true ) {
		// For backward compatibility, maintain the original behavior
		if ( ! is_string( $argument ) || '' === $argument ) {
			return false;
		}

		if ( $ignore_scalars && ! in_array( $argument[0], [ '{', '[' ], true ) ) {
			return false;
		}

		return $this->is_valid_json( $argument );
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

		// Updated pattern to match both http/https URLs and protocol-relative URLs (starting with //)
		$pattern = "/(https?:\/\/|\/\/)[^\s\"'<]+?(?=(\s|\"|'|<|$|]]>))/";
		$text    = preg_replace_callback( $pattern, array( $this, 'xml_matches' ), $xml_string );

		return $text;
	}

	/**
	 * Use regex to extract URLs from JSON files (e.g. /feed/)
	 * @return string The JSON with all the URLs converted
	 */
	private function extract_and_replace_urls_in_json() {
		$json_string = $this->get_body();
		// match anything starting with http/s or // plus all following characters
		// except: [space] " ' <
		$pattern = '/(?:https?:)?\/\/[^\s"\'\<\>]+/';


		$text = preg_replace_callback( $pattern, array( $this, 'json_matches' ), $json_string );

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
	 * Callback function for preg_replace in extract_and_replace_urls_in_json
	 *
	 * Takes the match, adds it to the list of URLs, converts the URL to a
	 * destination URL.
	 *
	 * @param array $matches Array of regex matches found in the JSON file
	 *
	 * @return string         The extracted, converted URL
	 */
	private function json_matches( $matches ) {
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
			// Only add to extracted urls queue if smart_crawl is not enabled
			if ( ! $this->options->get( 'smart_crawl' ) ) {
				$this->extracted_urls[] = apply_filters(
					'simply_static_extracted_url',
					Util::remove_params_and_fragment( $url ),
					$url,
					$this->static_page
				);
			}

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
	public function convert_url( $url ) {

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
