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
		$count      = 0;
		$batch      = [];
		$batch_sz   = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );
		$extensions = [
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
			'ico'
		];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_theme_directories', [
			'.git',
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
		if ( $p = wp_get_theme()->parent() ) {
			$parent_dir = get_template_directory();
			$parent_url = get_template_directory_uri();
			if ( $parent_dir !== ( $themes[0][0] ?? '' ) ) {
				if ( empty( $allowed ) || in_array( $parent_slug, $allowed, true ) ) {
					$themes[] = [ $parent_dir, $parent_url ];
				}
			}
		}

		foreach ( $themes as [$dir, $url_base] ) {
			if ( ! is_dir( $dir ) ) {
				\Simply_Static\Util::debug_log( "Theme directory does not exist: $dir" );
				continue;
			}

			try {
				$it = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $it as $file ) {
					if ( $file->isDir() ) {
						continue;
					}
					// Build a safe relative path from the theme directory prefix
					$rel  = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
					$skip = false;
					foreach ( (array) $skip_dirs as $sd ) {
						$sd = trim( $sd, "/" );
						if ( $sd === '' ) {
							continue;
						}
						if (
							strpos( $rel, '/' . $sd . '/' ) !== false ||
							strpos( $rel, $sd . '/' ) === 0 ||
							substr( $rel, - ( strlen( $sd ) + 1 ) ) === '/' . $sd
						) {
							$skip = true;
							break;
						}
					}
					if ( $skip ) {
						continue;
					}
					$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
					if ( ! in_array( $ext, $extensions, true ) ) {
						continue;
					}
					// Join with exactly one slash between base and relative
					$batch[] = \Simply_Static\Util::safe_join_url( $url_base, $rel );
					if ( count( $batch ) >= $batch_sz ) {
						$count += $this->enqueue_urls_batch( $batch );
						$batch = [];
						usleep( 100000 );
					}
				}
			} catch ( \Exception $e ) {
				\Simply_Static\Util::debug_log( 'Error streaming theme crawl: ' . $e->getMessage() );
			}
		}

		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Theme assets crawler added %d URLs (streamed)', $count ) );

		return $count;
	}

	/**
	 * Enqueue a batch of URLs.
	 *
	 * @param array $urls
	 *
	 * @return int
	 */
	private function enqueue_urls_batch( array $urls ): int {
		$added = 0;
		\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $urls ), $this->name ) );
		foreach ( $urls as $url ) {
			// Skip URLs that are excluded by settings/patterns
			if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Theme assets crawler skipping excluded URL: %s', $url ) );
				continue;
			}
			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$added ++;
		}

		return $added;
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
