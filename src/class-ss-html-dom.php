<?php
namespace Simply_Static;

use simplehtmldom;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class which handles dom parsing with simplehtmldom.
 */
class HtmlDomParser {

	/**
	 * Getting html from files.
	 *
	 * @param string  $url URL to parse.
	 * @param boolean $use_include_path include path or not.
	 * @param string  $context current context.
	 * @param integer $offset given offset.
	 * @param integer $max_length maximum length.
	 * @param boolean $lowercase lowercase file names.
	 * @param boolean $force_tags_closed forcing tag closing.
	 * @param string  $target_charset define the charset.
	 * @param boolean $strip_rn strip rn.
	 * @param boolean $default_br_text default br text.
	 * @param boolean $default_span_text default span text.
	 * @return object
	 */
	public static function file_get_html( $url, $use_include_path = false, $context = null, $offset = 0, $max_length = -1, $lowercase = true, $force_tags_closed = true, $target_charset = 'DEFAULT_TARGET_CHARSET', $strip_rn = true, $default_br_text = 'DEFAULT_BR_TEXT', $default_span_text = 'DEFAULT_SPAN_TEXT' ) {
		if ( $max_length <= 0 ) {
			$max_length = 'MAX_FILE_SIZE';
		}

		$dom = new simplehtmldom\HtmlDocument(
			null,
			$lowercase,
			$force_tags_closed,
			$target_charset,
			$strip_rn,
			$default_br_text,
			$default_span_text
		);

		$contents = file_get_contents(
			$url,
			$use_include_path,
			$context,
			$offset,
			$max_length + 1 // Load extra byte for limit check
		);

		if ( empty( $contents ) || strlen( $contents ) > $max_length ) {
			$dom->clear();
			return false;
		}

		return $dom->load( $contents, $lowercase, $strip_rn );
	}

	/**
	 * Getting html from strings.
	 *
	 * @param string  $str current string.
	 * @param boolean $lowercase lowercase or not.
	 * @param boolean $force_tags_closed force tag closing.
	 * @param string  $target_charset define the charset.
	 * @param boolean $strip_rn strip rns.
	 * @param string  $default_br_text default br text.
	 * @param string  $default_span_text default span text.
	 * @return object
	 */
	public static function str_get_html( $str, $lowercase = true, $force_tags_closed = true, $target_charset = 'DEFAULT_TARGET_CHARSET', $strip_rn = true, $default_br_text = 'DEFAULT_BR_TEXT', $default_span_text = 'DEFAULT_SPAN_TEXT' ) {
		$dom = new simplehtmldom\HtmlDocument(
			null,
			$lowercase,
			$force_tags_closed,
			$target_charset,
			$strip_rn,
			$default_br_text,
			$default_span_text
		);

		if ( empty( $str ) || strlen( $str ) > 'MAX_FILE_SIZE' ) {
			$dom->clear();
			return false;
		}

		return $dom->load( $str, $lowercase, $strip_rn );
	}
}
