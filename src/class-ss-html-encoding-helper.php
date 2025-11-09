<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper for preparing HTML strings for DOMDocument while preserving emojis and multilingual text.
 *
 * Responsibilities:
 * - Normalize input to UTF-8 (if needed) before DOM parsing.
 * - Optionally pre-encode characters to numeric entities using mb_encode_numericentity (preferred).
 * - Use mb_convert_encoding(..., 'HTML-ENTITIES', ...) only as a legacy fallback for PHP < 8.2.
 *
 * Filters:
 * - simply_static_dom_input_charset: override the detected input charset before DOM.
 * - simply_static_preencode_mode: 'none' | 'bmp_only' (default) | 'all'.
 * - simply_static_preencode_for_dom: boolean override to enable/disable pre-encoding entirely.
 */
class Html_Encoding_Helper {
	/**
	 * Prepare HTML for DOMDocument::loadHTML().
	 *
	 * @param string $html_string Raw HTML string.
	 * @param string $detected_charset The site's/blog's charset (e.g., 'UTF-8').
	 * @param mixed $context Optional context, typically the Url_Extractor instance ($this) for filters.
	 *
	 * @return array{html:string, encoding:string} Array with 'html' (string prepared for DOM) and 'encoding' (DOMDocument encoding to set).
	 */
	public static function prepare_html_for_dom( $html_string, $detected_charset, $context = null ) {
		// Selective pre-encoding configuration
		$preencode_mode = apply_filters( 'simply_static_preencode_mode', 'bmp_only', $context );
		$preencode      = apply_filters( 'simply_static_preencode_for_dom', $preencode_mode !== 'none', $detected_charset, $context );

		// Start with the original HTML; we avoid charset conversion for simplicity and robustness.
		$html_prepared = $html_string;

		if ( $preencode && function_exists( 'mb_encode_numericentity' ) ) {
			// Build conversion map depending on mode
			if ( $preencode_mode === 'all' ) {
				// Encode all non-ASCII including non-BMP
				$convmap = array( 0x80, 0x10FFFF, 0, 0xFFFF );
			} elseif ( $preencode_mode === 'bmp_only' ) {
				// Encode only BMP excluding surrogate range to preserve emojis (non-BMP) as real UTF-8.
				// Ranges: [0x80..0xD7FF] and [0xE000..0xFFFD]
				$convmap = array(
					0x80,
					0xD7FF,
					0,
					0xFFFF,
					0xE000,
					0xFFFD,
					0,
					0xFFFF,
				);
			} else {
				$convmap = array();
			}

			if ( ! empty( $convmap ) ) {
				$html_prepared = mb_encode_numericentity( $html_string, $convmap, $detected_charset );
			}
		} elseif ( $preencode && function_exists( 'mb_convert_encoding' ) && version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
			// Legacy fallback path for older PHP only
			$html_prepared = mb_convert_encoding( $html_string, 'HTML-ENTITIES', $detected_charset );
		}

		return array(
			'html'     => $html_prepared,
			'encoding' => is_string( $detected_charset ) && $detected_charset !== '' ? $detected_charset : 'UTF-8',
		);
	}
}
