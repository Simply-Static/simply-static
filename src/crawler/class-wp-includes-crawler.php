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
	 * Collect style*.css files directly within each block subdirectory under wp-includes/blocks.
	 * This ensures core block styles like navigation/style.min.css and social-links/style.min.css
	 * are included even if a directory scan misses them in some environments.
	 *
	 * @param string $blocks_dir Absolute path to wp-includes/blocks (no trailing slash required)
	 * @param string $blocks_url Base URL to wp-includes/blocks (site_url + '/wp-includes/blocks')
	 * @return array URLs to block style CSS files
	 */
	private function get_block_style_urls( string $blocks_dir, string $blocks_url ): array {
		$urls = [];
		$blocks_dir = rtrim( $blocks_dir, DIRECTORY_SEPARATOR );
		if ( ! is_dir( $blocks_dir ) ) {
			\Simply_Static\Util::debug_log( "Blocks directory does not exist: $blocks_dir" );
			return $urls;
		}
		try {
			// Use simple glob for immediate children style*.css files
			$pattern = $blocks_dir . DIRECTORY_SEPARATOR . '*'. DIRECTORY_SEPARATOR . 'style*.css';
			$files = glob( $pattern );
			if ( $files ) {
				foreach ( $files as $abs_file ) {
					$rel = \Simply_Static\Util::safe_relative_path( $blocks_dir, $abs_file );
					$urls[] = \Simply_Static\Util::safe_join_url( $blocks_url, $rel );
				}
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( 'Error collecting block style URLs: ' . $e->getMessage() );
		}
		return $urls;
	}

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
			'/wp-includes/js/dist/',
			'/wp-includes/js/jquery/'
		];

		// Always include blocks directory; block assets can be required even for classic themes.
		array_unshift( $wp_includes_directories, '/wp-includes/blocks/' );

		// Check if comments are enabled and add comment-reply.min.js
		if ( get_option( 'thread_comments' ) ) {
			$comment_reply_js = $site_url . '/wp-includes/js/comment-reply.min.js?ver=' . get_bloginfo( 'version' );
			$asset_urls[] = $comment_reply_js;
			\Simply_Static\Util::debug_log( "Added comment-reply.min.js to assets" );
		}

		// Always include core jQuery file from wp-includes
		$jquery_core_js = $site_url . '/wp-includes/js/jquery/jquery.min.js';
		$asset_urls[]   = $jquery_core_js;
		\Simply_Static\Util::debug_log( "Added jquery.min.js to assets" );

		// Scan each directory and add files to asset URLs
		foreach ( $wp_includes_directories as $directory ) {
			$directory_clean = $directory !== null ? ltrim( $directory, '/' ) : '';
			$full_path       = $wp_path . $directory_clean;
			$directory_urls  = $this->scan_directory_for_assets( $full_path, $site_url . $directory );
			$asset_urls      = array_merge( $asset_urls, $directory_urls );
		}

		// Ensure core block style sheets are always included
		$blocks_dir = $wp_path . 'wp-includes/blocks';
		$blocks_url = $site_url . '/wp-includes/blocks';
		$asset_urls = array_merge( $asset_urls, $this->get_block_style_urls( $blocks_dir, $blocks_url ) );

		return $asset_urls;
	}

	/**
	 * Stream URLs directly into the queue to avoid building large arrays.
	 *
	 * @return int
	 */
	public function add_urls_to_queue(): int {
		$count    = 0;
		$batch    = [];
		$batch_sz = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );

		$site_url = site_url();
		$wp_path  = ABSPATH;

		$dirs = [
			'/wp-includes/css/dist/',
			'/wp-includes/js/dist/',
			'/wp-includes/js/jquery/',
			'/wp-includes/blocks/',
		];

		// Always include special scripts first.
		if ( get_option( 'thread_comments' ) ) {
			$count += $this->enqueue_urls_batch( [ $site_url . '/wp-includes/js/comment-reply.min.js?ver=' . get_bloginfo( 'version' ) ] );
		}
		$count += $this->enqueue_urls_batch( [ $site_url . '/wp-includes/js/jquery/jquery.min.js' ] );

		foreach ( $dirs as $directory ) {
			$directory_clean = ltrim( $directory, '/' );
			$full_path       = $wp_path . $directory_clean;
			$base_url        = $site_url . $directory;

			if ( ! is_dir( $full_path ) ) {
				\Simply_Static\Util::debug_log( "Directory does not exist: $full_path" );
				continue;
			}

			try {
				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $full_path, \RecursiveDirectoryIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::SELF_FIRST
				);

				foreach ( $iterator as $file ) {
					if ( $file->isDir() ) {
						continue;
					}
					
					// Build a safe relative path from the directory prefix
					$relative_path = \Simply_Static\Util::safe_relative_path( $full_path, $file->getPathname() );
					$ext = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
					if ( $ext !== 'css' && $ext !== 'js' ) {
						continue;
					}
					
					// Join with exactly one slash
					$batch[] = \Simply_Static\Util::safe_join_url( $base_url, $relative_path );
					if ( count( $batch ) >= $batch_sz ) {
						$count += $this->enqueue_urls_batch( $batch );
						$batch = [];
						usleep( 100000 );
					}
				}
			} catch ( \Exception $e ) {
				\Simply_Static\Util::debug_log( 'Error streaming wp-includes crawl: ' . $e->getMessage() );
			}
		}
		
		// Explicitly add core block style sheets (style*.css) to ensure inclusion
		$extra_block_styles = $this->get_block_style_urls( $wp_path . 'wp-includes/blocks', $site_url . '/wp-includes/blocks' );
		if ( ! empty( $extra_block_styles ) ) {
			$count += $this->enqueue_urls_batch( $extra_block_styles );
		}
		
		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'WP Includes crawler added %d URLs (streamed)', $count ) );
		return $count;
	}

	/**
	 * Enqueue a batch of URLs.
	 * @param array $urls
	 * @return int
	 */
	private function enqueue_urls_batch( array $urls ): int {
		$added = 0;
		\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $urls ), $this->name ) );
		foreach ( $urls as $url ) {
			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$added++;
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
				
				// Build a safe relative path from the base directory
				$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
				
				// Get the file extension
				$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
				
				// Only include CSS and JS files
				if ( $extension === 'css' || $extension === 'js' ) {
					// Create the full URL with safe joining
					$url    = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
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
