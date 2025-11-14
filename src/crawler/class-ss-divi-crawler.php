<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Divi Crawler class
 *
 * This crawler detects URLs for Divi theme cached assets and theme asset files.
 */
class Divi_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'divi';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Divi Assets', 'simply-static' );
		$this->description = __( 'Detects Divi theme cache and asset files.', 'simply-static' );
	}

	/**
	 * Check if Divi is the active (parent) theme.
	 *
	 * @return bool
	 */
	public function dependency_active() : bool {
		// Only consider Divi available when the Divi THEME is active (including child themes).
		// In WordPress, get_template() returns the parent theme directory name. For a Divi child theme,
		// get_template() will still be 'Divi'. This avoids false positives from the Divi Builder plugin
		// or just having the Divi theme directory present.
		$tpl = function_exists( 'get_template' ) ? get_template() : '';
		return 'Divi' === $tpl;
	}

	/**
	 * Check if the crawler is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		if ( ! $this->dependency_active() ) {
			return false;
		}
		return parent::is_active();
	}

	/**
	 * Detect Divi-related asset URLs.
	 *
	 * @return array List of asset URLs
	 */
	public function detect() : array {
		$asset_urls = [];

		$site_url = site_url();
		$wp_path  = ABSPATH;

		$directories = [
			// Divi cache directory (generated assets)
			'/wp-content/et-cache'          => $wp_path . 'wp-content/et-cache',
			// Divi theme assets
			'/wp-content/themes/Divi'        => $wp_path . 'wp-content/themes/Divi',
		];

		foreach ( $directories as $url_path => $dir_path ) {
			if ( is_dir( $dir_path ) ) {
				$directory_urls = $this->scan_directory_for_assets( $dir_path, $site_url . $url_path );
				$asset_urls     = array_merge( $asset_urls, $directory_urls );
			} else {
				\Simply_Static\Util::debug_log( "Directory does not exist: $dir_path" );
			}
		}

		// Unique URLs only
		$asset_urls = array_values( array_unique( $asset_urls ) );

		\Simply_Static\Util::debug_log( sprintf( 'Divi crawler detected %d asset URLs', count( $asset_urls ) ) );

		return $asset_urls;
	}

	/**
	 * Stream Divi asset URLs directly into the queue in batches to reduce memory usage.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		$count     = 0;
		$batch     = [];
		$batch_sz  = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );

		$site_url = site_url();
		$wp_path  = ABSPATH;

		$targets = [
			'/wp-content/et-cache'   => $wp_path . 'wp-content/et-cache',
			'/wp-content/themes/Divi' => $wp_path . 'wp-content/themes/Divi',
		];

		$extensions = [ 'css','js','png','jpg','jpeg','gif','svg','webp','woff','woff2','ttf','eot','otf','ico','mp4','webm' ];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_divi_directories', [ '.git','node_modules','vendor/bin','vendor/composer','tests' ] );

		foreach ( $targets as $url_base => $dir_root ) {
			if ( ! is_dir( $dir_root ) ) {
				\Simply_Static\Util::debug_log( "Directory does not exist: $dir_root" );
				continue;
			}
			try {
				$it = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $dir_root, \RecursiveDirectoryIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $it as $file ) {
					if ( $file->isDir() ) { continue; }
					$rel = \Simply_Static\Util::safe_relative_path( $dir_root, $file->getPathname() );
					$skip = false;
					foreach ( (array) $skip_dirs as $sd ) {
						if ( $sd && strpos( $rel, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
					}
					if ( $skip ) { continue; }
					$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
					if ( $ext && ! in_array( $ext, $extensions, true ) ) { continue; }
					$batch[] = \Simply_Static\Util::safe_join_url( $site_url . $url_base, $rel );
					if ( count( $batch ) >= $batch_sz ) {
						$count += $this->enqueue_urls_batch( $batch );
						$batch = [];
						usleep( 100000 );
					}
				}
			} catch ( \Exception $e ) {
				\Simply_Static\Util::debug_log( 'Error streaming Divi directory crawl: ' . $e->getMessage() );
			}
		}

		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Divi crawler added %d URLs (streamed)', $count ) );
		return $count;
	}

	/**
	 * Enqueue a batch of URLs and return how many were added.
	 *
	 * @param array $urls
	 * @return int
	 */
	private function enqueue_urls_batch( array $urls ): int {
		$added = 0;
		\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $urls ), $this->name ) );
  foreach ( $urls as $url ) {
  			// Skip URLs that are excluded by settings/patterns
  			if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
  				\Simply_Static\Util::debug_log( sprintf( 'Divi crawler skipping excluded URL: %s', $url ) );
  				continue;
  			}
			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$added++;
		}
		return $added;
	}

	/**
	 * Scan a directory for Divi asset files recursively (with filtering for asset file types).
	 *
	 * @param string $dir Directory path
	 * @param string $url_base Base URL for the directory
	 *
	 * @return array List of asset URLs
	 */
	private function scan_directory_for_assets( $dir, $url_base ): array {
		$urls = [];

		$asset_extensions = [
			'css','js','png','jpg','jpeg','gif','svg','webp','woff','woff2','ttf','eot','otf','ico','mp4','webm'
		];

		$skip_dirs = apply_filters( 'ss_skip_crawl_divi_directories', [ '.git','node_modules','vendor/bin','vendor/composer','tests' ] );

		if ( ! is_dir( $dir ) ) {
			\Simply_Static\Util::debug_log( "Directory does not exist: $dir" );
			return $urls;
		}

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ( $iterator as $file ) {
				if ( $file->isDir() ) { continue; }
				$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
				$skip = false;
				foreach ( (array) $skip_dirs as $sd ) {
					if ( $sd && strpos( $relative_path, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
				}
				if ( $skip ) { continue; }
				$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
				if ( in_array( $extension, $asset_extensions, true ) ) {
					$urls[] = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				}
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}
}
