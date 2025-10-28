<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_File_Handler extends Page_Handler {
	/**
	 * After the file is fetched into the archive directory, replace any origin URLs
	 * with the destination URL in plain-text files like robots.txt and llms.txt.
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

		// Only process text files we target: robots.txt or llms.txt
		$basename = strtolower( basename( $full_path ) );
		if ( $basename !== 'robots.txt' && $basename !== 'llms.txt' ) {
			return;
		}

		$contents = @file_get_contents( $full_path );
		if ( $contents === false || $contents === '' ) {
			return;
		}

		$options         = Options::instance();
		$destination_url = rtrim( $options->get_destination_url(), '/' );

		// First pass: regex on host (with optional port) to handle generic cases.
		$origin_host  = Util::origin_host();
		$host_no_port = preg_replace( '/:\\d+$/', '', (string) $origin_host );
		$pattern      = '/(?:https?:)?\\/\\/' . preg_quote( $host_no_port, '/' ) . '(?::\\d+)?/i';
		$replaced     = preg_replace( $pattern, $destination_url, $contents );

		// Second pass fallback: replace exact origin home URL prefixes (http, https, protocol-relative),
		// including potential subdirectory installs, in case the first regex didn't match due to path/port differences.
		$home_http  = set_url_scheme( home_url( '/' ), 'http' );
		$home_https = set_url_scheme( home_url( '/' ), 'https' );
		$home_proto = preg_replace( '#^https?:#i', '', $home_https ); // //example.com/...

		$search  = [ rtrim( $home_http, '/' ), rtrim( $home_https, '/' ), rtrim( $home_proto, '/' ) ];
		$replace = array_fill( 0, count( $search ), rtrim( $destination_url, '/' ) );
		$replaced2 = str_replace( $search, rtrim( $destination_url, '/' ), $replaced );

		if ( $replaced2 !== $contents ) {
			@file_put_contents( $full_path, $replaced2 );
			Util::debug_log( '[Text_File_Handler] Replaced URLs in ' . $basename . ' at ' . $full_path );
			return;
		}

		Util::debug_log( '[Text_File_Handler] No URL replacements performed for ' . $basename . ' at ' . $full_path );
		// If nothing changed after both passes, keep original file.
	}
}
