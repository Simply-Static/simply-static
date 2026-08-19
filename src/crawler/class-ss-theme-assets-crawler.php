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

		$allowed = (array) \Simply_Static\Options::instance()->get( 'themes_to_include' );
		$allowed = is_array( $allowed ) ? array_filter( array_map( 'strval', $allowed ) ) : [];
		$allowed = apply_filters( 'ss_crawlable_themes', $allowed );

		$themes     = [];
		$child_slug = get_stylesheet();
		if ( empty( $allowed ) || in_array( $child_slug, $allowed, true ) ) {
			$themes[] = [ get_stylesheet_directory(), get_stylesheet_directory_uri() ];
		}
		$parent_slug = get_template();
		if ( $parent_slug && $parent_slug !== $child_slug ) {
			if ( empty( $allowed ) || in_array( $parent_slug, $allowed, true ) ) {
				$themes[] = [ get_template_directory(), get_template_directory_uri() ];
			}
		}

		foreach ( $themes as [$dir, $url] ) {
			$asset_urls = array_merge( $asset_urls, $this->scan_directory_for_assets( $dir, $url ) );
		}

		return $asset_urls;
	}

	/**
	 * Stream URLs directly into the queue for theme and parent theme.
	 *
	 * @return int
	 */
	public function add_urls_to_queue(): int {
		$extensions = [
			'css',
			'js',
			'png',
			'jpg',
			'jpeg',
			'gif',
			'svg',
			'webp',
			'avif',
			'heic',
			'tiff',
			'woff',
			'woff2',
			'ttf',
			'eot',
			'otf',
			'ico',
			'json'
		];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_theme_directories', [
			'.git',
			'node_modules',
			'vendor/bin',
			'vendor/composer',
			'tests'
		] );

		$allowed = (array) \Simply_Static\Options::instance()->get( 'themes_to_include' );
		$allowed = is_array( $allowed ) ? array_filter( array_map( 'strval', $allowed ) ) : [];
		$allowed = apply_filters( 'ss_crawlable_themes', $allowed );

		$themes     = [];
		$child_slug = get_stylesheet();
		if ( empty( $allowed ) || in_array( $child_slug, $allowed, true ) ) {
			$themes[] = [ get_stylesheet_directory(), get_stylesheet_directory_uri() ];
		}
		$parent_slug = get_template();
		if ( wp_get_theme()->parent() ) {
			$parent_dir = get_template_directory();
			$parent_url = get_template_directory_uri();
			if ( $parent_dir !== ( $themes[0][0] ?? '' ) ) {
				if ( empty( $allowed ) || in_array( $parent_slug, $allowed, true ) ) {
					$themes[] = [ $parent_dir, $parent_url ];
				}
			}
		}

		$directories = array_map( static function ( $theme ) {
			return array( 'basedir' => $theme[0], 'baseurl' => $theme[1] );
		}, $themes );

		return $this->enqueue_directory_batch(
			'theme_assets_crawler_state',
			$directories,
			$extensions,
			(array) $skip_dirs,
			'simply_static_theme_assets_crawler_max_entries_per_batch',
			'simply_static_theme_assets_crawler_max_batch_seconds'
		);
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
		$urls        = [];
		$max_entries = max( 1, min( 100000, (int) apply_filters( 'simply_static_theme_detection_max_entries', 5000 ) ) );
		$deadline    = microtime( true ) + max( 0.5, min( 15, (float) apply_filters( 'simply_static_theme_detection_max_seconds', 5 ) ) );
		$scanned     = 0;

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
			'avif',
			'heic',
			'tiff',
			'woff',
			'woff2',
			'ttf',
			'eot',
			'otf',
			'ico',
			'json',
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
			$scanned++;
			if ( $scanned > $max_entries || microtime( true ) >= $deadline ) {
				break;
			}
			// Skip directories
			if ( $file->isDir() ) {
				continue;
			}

			// Build a safe relative path and skip files in ignored directories
			$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
			$should_skip   = false;

			foreach ( $skip_dirs as $skip_dir ) {
				$skip_dir = trim( $skip_dir, '/' );
				if ( $skip_dir === '' ) {
					continue;
				}
				if (
					strpos( $relative_path, '/' . $skip_dir . '/' ) !== false ||
					strpos( $relative_path, $skip_dir . '/' ) === 0 ||
					substr( $relative_path, - ( strlen( $skip_dir ) + 1 ) ) === '/' . $skip_dir
				) {
					$should_skip = true;
					break;
				}
			}

			if ( $should_skip ) {
				continue;
			}

			// Check if the file has an asset extension
			$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
			if ( in_array( $extension, $asset_extensions, true ) ) {
				// Convert the file path to a URL and join safely
				$url    = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
