<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_File_Handler extends Page_Handler {
	/**
	 * After the file is fetched into the archive directory, replace any origin URLs
	 * with the destination URL in plain-text files like robots.txt, llms.txt, _redirects, and _headers.
	 *
	 * @param string $destination_dir Absolute path to the archive directory.
	 * @return void
	 */
	public function after_file_fetch( $destination_dir ) {
		// Ensure we have a relative path saved on the page.
		$relative_path = isset( $this->page->file_path ) ? $this->page->file_path : '';
		if ( empty( $relative_path ) ) {
			return;
		}

		$full_path = Util::combine_path( $destination_dir, $relative_path );
		if ( ! file_exists( $full_path ) || ! is_readable( $full_path ) ) {
			return;
		}

		// Only process text files we target: robots.txt, llms.txt, _redirects, or _headers
		$basename = strtolower( basename( $full_path ) );
		if ( $basename !== 'robots.txt' && $basename !== 'llms.txt' && $basename !== '_redirects' && $basename !== '_headers' ) {
			return;
		}

		$contents = @file_get_contents( $full_path );
		if ( $contents === false || $contents === '' ) {
			return;
		}

		$replaced = Util::replace_origin_urls_in_text( $contents );

		if ( $replaced !== $contents ) {
			@file_put_contents( $full_path, $replaced );
			Util::debug_log( '[Text_File_Handler] Replaced URLs in ' . $basename . ' at ' . $full_path );
			return;
		}

		Util::debug_log( '[Text_File_Handler] No URL replacements performed for ' . $basename . ' at ' . $full_path );
		// If nothing changed after both passes, keep original file.
	}
}
