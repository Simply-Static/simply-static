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
		$site_url = \Simply_Static\Util::origin_url();
		$wp_path  = trailingslashit( ABSPATH );
		$inc_path = wp_parse_url( includes_url(), PHP_URL_PATH );
		$wp_inc   = '/' . untrailingslashit( ltrim( $inc_path, '/' ) ) . '/';

		$urls = [
			$site_url . $wp_inc . 'js/jquery/jquery.min.js',
			$site_url . $wp_inc . 'js/wp-emoji-release.min.js',
		];

		if ( get_option( 'thread_comments' ) ) {
			$urls[] = $site_url . $wp_inc . 'js/comment-reply.min.js';
		}

		$dirs = [ 'css/dist/', 'js/', 'fonts/', 'images/', 'blocks/' ];
		$exts = [ 'css', 'js', 'json', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico' ];

		foreach ( $dirs as $dir ) {
			$full_path = $wp_path . ltrim( $wp_inc, '/' ) . $dir;
			if ( ! is_dir( $full_path ) ) continue;

			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $full_path, \RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $it as $file ) {
				if ( $file->isDir() ) continue;
				$rel = \Simply_Static\Util::safe_relative_path( $full_path, $file->getPathname() );
				if ( in_array( strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) ), $exts, true ) ) {
					$urls[] = \Simply_Static\Util::safe_join_url( $site_url . $wp_inc . $dir, $rel );
				}
			}
		}
		return array_unique( $urls );
	}
}
