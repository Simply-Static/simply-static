<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Theme Assets Crawler class
 *
 * This crawler detects URLs for assets (CSS, JS, images) used by the active theme.
 */
class Theme_Assets_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'theme_assets';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Theme Assets', 'simply-static' );
		$this->description = __( 'Detects URLs for assets (CSS, JS, images) used by the active theme.', 'simply-static' );
	}

	/**
	 * Detect theme asset URLs.
	 *
	 * @return array List of theme asset URLs
	 */
	public function detect(): array {
		$asset_urls = [];

		// Get the active theme
		$theme = wp_get_theme();

		// Get the theme directory path and URL
		$theme_dir = get_stylesheet_directory();
		$theme_url = get_stylesheet_directory_uri();

		// Scan the theme directory for asset files
		$asset_urls = $this->scan_directory_for_assets( $theme_dir, $theme_url );

		// If the theme has a parent, scan the parent theme as well
		if ( $theme->parent() ) {
			$parent_theme_dir = get_template_directory();
			$parent_theme_url = get_template_directory_uri();

			// Only scan the parent theme if it's different from the child theme
			if ( $parent_theme_dir !== $theme_dir ) {
				$asset_urls = array_merge(
					$asset_urls,
					$this->scan_directory_for_assets( $parent_theme_dir, $parent_theme_url )
				);
			}
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

		// Asset file extensions to look for
		$asset_extensions = [
			'css',
			'js',
			'png',
			'jpg',
			'jpeg',
			'gif',
			'svg',
			'webp',
			'woff',
			'woff2',
			'ttf',
			'eot',
			'otf',
			'ico',
		];

		// Skip these directories
		$skip_dirs = apply_filters( 'ss_skip_crawl_theme_directories', [
			'.git',
			'node_modules',
			'vendor/bin',
			'vendor/composer',
			'tests'
		] );

		// Get all files in the directory
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $files as $file ) {
			// Skip directories
			if ( $file->isDir() ) {
				continue;
			}

			// Skip files in directories we want to ignore
			$relative_path = str_replace( $dir, '', $file->getPathname() );
			$should_skip   = false;

			foreach ( $skip_dirs as $skip_dir ) {
				if ( strpos( $relative_path, '/' . $skip_dir . '/' ) !== false ) {
					$should_skip = true;
					break;
				}
			}

			if ( $should_skip ) {
				continue;
			}

			// Check if the file has an asset extension
			$extension = strtolower( $file->getExtension() );
			if ( in_array( $extension, $asset_extensions ) ) {
				// Convert the file path to a URL
				$relative_url = str_replace( '\\', '/', $relative_path );
				$url          = $url_base . $relative_url;

				$urls[] = $url;
			}
		}

		return $urls;
	}
}
