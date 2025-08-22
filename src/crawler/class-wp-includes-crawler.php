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
class WP_Includes_Crawler extends Crawler {

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
	 * Detect wp-includes files.
	 *
	 * @return array List of wp-includes file URLs
	 */
	public function detect(): array {
		$asset_urls = [];

		// Get the site URL
		$site_url = site_url();

		// Get the WordPress ABSPATH
		$wp_path = ABSPATH;

		// List of directories to scan for wp-includes
		$wp_includes_directories = [
			'/wp-includes/css/dist/',
			'/wp-includes/js/dist/'
		];

		// Only include blocks directory if it's a block theme
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			array_unshift( $wp_includes_directories, '/wp-includes/blocks/' );
		}

		// Check if comments are enabled and add comment-reply.min.js
		if ( get_option( 'thread_comments' ) ) {
			$comment_reply_js = $site_url . '/wp-includes/js/comment-reply.min.js?ver=' . get_bloginfo( 'version' );
			$asset_urls[] = $comment_reply_js;
			\Simply_Static\Util::debug_log( "Added comment-reply.min.js to assets" );
		}

		// Scan each directory and add files to asset URLs
		foreach ( $wp_includes_directories as $directory ) {
			$directory_clean = $directory !== null ? ltrim( $directory, '/' ) : '';
			$full_path       = $wp_path . $directory_clean;
			$directory_urls  = $this->scan_directory_for_assets( $full_path, $site_url . $directory );
			$asset_urls      = array_merge( $asset_urls, $directory_urls );
		}

		return $asset_urls;
	}

	/**
	 * Scan a directory for asset files recursively
	 *
	 * @param string $dir Directory path
	 * @param string $url_base Base URL for the directory
	 *
	 * @return array List of asset URLs
	 */
	private function scan_directory_for_assets( $dir, $url_base ): array {
		$urls = [];

		// Check if directory exists
		if ( ! is_dir( $dir ) ) {
			\Simply_Static\Util::debug_log( "Directory does not exist: $dir" );

			return $urls;
		}

		try {
			// Get all files in the directory
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $file ) {
				// Skip directories
				if ( $file->isDir() ) {
					continue;
				}

				// Get the relative path from the base directory
				$relative_path = str_replace( $dir, '', $file->getPathname() );
				$relative_path = str_replace( '\\', '/', $relative_path ); // Normalize slashes for URLs

				// Get the file extension
				$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );

				// Only include CSS and JS files
				if ( $extension === 'css' || $extension === 'js' ) {
					// Create the full URL
					$url    = $url_base . $relative_path;
					$urls[] = $url;
				}
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " asset URLs in $dir" );
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}
}
