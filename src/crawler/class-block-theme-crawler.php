<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Block Theme Crawler class
 *
 * This crawler detects URLs for essential files required by block themes.
 */
class Block_Theme_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'block_theme';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Block Theme Files', 'simply-static' );
		$this->description = __( 'Detects essential files required by block themes.', 'simply-static' );
	}

	/**
	 * Detect block theme files.
	 *
	 * @return array List of block theme file URLs
	 */
	public function detect(): array {
		$asset_urls = [];

		// Only proceed if the site is using a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $asset_urls;
		}

		// Get the site URL
		$site_url = site_url();

		// Get the WordPress ABSPATH
		$wp_path = ABSPATH;

		// List of directories to scan for block themes
		$block_theme_directories = [
			'/wp-includes/blocks/',
			'/wp-includes/css/dist/',
			'/wp-includes/js/dist/'
		];

		// Scan each directory and add files to asset URLs
		foreach ( $block_theme_directories as $directory ) {
			$full_path = $wp_path . ltrim($directory, '/');
			$directory_urls = $this->scan_directory_for_assets($full_path, $site_url . $directory);
			$asset_urls = array_merge($asset_urls, $directory_urls);
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
				$relative_path = str_replace($dir, '', $file->getPathname());
				$relative_path = str_replace('\\', '/', $relative_path); // Normalize slashes for URLs

				// Create the full URL
				$url = $url_base . $relative_path;
				$urls[] = $url;
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " asset URLs in $dir" );
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}
}
