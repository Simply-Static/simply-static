<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static WP Includes Crawler class
 *
 * This crawler detects URLs for essential files in wp-includes directory.
 */
class Wp_Includes_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'wp_includes';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Includes Directory', 'simply-static' );
		$this->description = __( 'Detects essential files in wp-includes directory.', 'simply-static' );
	}

	/**
	 * Check if the crawler is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		// If smart_crawl is enabled, we force this crawler to be active to ensure core assets are preserved.
		if ( \Simply_Static\Options::instance()->get( 'smart_crawl' ) ) {
			return true;
		}

		return parent::is_active();
	}

	/**
	 * Detect wp-includes files.
	 *
	 * @return array List of wp-includes file URLs
	 */
	public function detect(): array {
		$wp_inc       = defined( 'WPINC' ) ? WPINC : 'wp-includes';
		$origin_url   = \Simply_Static\Util::origin_url();
		$origin_parts = wp_parse_url( $origin_url );
		$inc_path     = wp_parse_url( includes_url(), PHP_URL_PATH );
		$inc_path     = is_string( $inc_path ) && '' !== $inc_path ? $inc_path : '/' . $wp_inc;
		$origin_path  = isset( $origin_parts['path'] ) ? '/' . trim( $origin_parts['path'], '/' ) : '';

		if ( '' !== $origin_path && '/' !== $origin_path ) {
			$inc_path = '/' . ltrim( $inc_path, '/' );

			if ( $inc_path === $origin_path ) {
				$inc_path = '/';
			} elseif ( 0 === strpos( $inc_path . '/', trailingslashit( $origin_path ) ) ) {
				$inc_path = substr( $inc_path, strlen( $origin_path ) );
			}
		}

		$includes_url = trailingslashit( \Simply_Static\Util::safe_join_url( $origin_url, $inc_path ) );
		$wp_inc_dir   = trailingslashit( ABSPATH . $wp_inc );

		$urls = [
			\Simply_Static\Util::safe_join_url( $includes_url, 'js/jquery/jquery.min.js' ),
			\Simply_Static\Util::safe_join_url( $includes_url, 'js/wp-emoji-release.min.js' ),
		];

		if ( get_option( 'thread_comments' ) ) {
			$urls[] = \Simply_Static\Util::safe_join_url( $includes_url, 'js/comment-reply.min.js' );
		}

		$dirs = [ 'css/', 'js/', 'fonts/', 'images/', 'blocks/' ];
		$exts = [ 'css', 'js', 'json', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico' ];

		foreach ( $dirs as $dir ) {
			$full_path = $wp_inc_dir . $dir;
			$dir_url   = \Simply_Static\Util::safe_join_url( $includes_url, $dir );
			if ( ! is_dir( $full_path ) ) continue;

			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $full_path, \RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $it as $file ) {
				if ( $file->isDir() ) continue;
				// WordPress keeps removed core assets as empty placeholders for some releases.
				if ( 0 === $file->getSize() ) continue;
				$rel = \Simply_Static\Util::safe_relative_path( $full_path, $file->getPathname() );
				if ( in_array( strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) ), $exts, true ) ) {
					$urls[] = \Simply_Static\Util::safe_join_url( $dir_url, $rel );
				}
			}
		}
		return array_unique( $urls );
	}
}
