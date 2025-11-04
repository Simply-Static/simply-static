<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Plugin Assets Crawler class
 *
 * This crawler detects URLs for assets (CSS, JS, images) used by plugins.
 */
class Plugin_Assets_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'plugin_assets';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Plugin Assets', 'simply-static' );
		$this->description = __( 'Detects URLs for assets (CSS, JS, images) used by active plugins.', 'simply-static' );
	}

	/**
	 * Detect plugin asset URLs.
	 *
	 * @return array List of plugin asset URLs
	 */
	public function detect(): array {
		$asset_urls = [];

		// Get the plugin directory URL and path
		$plugins_url = plugins_url();
		$plugins_dir = WP_PLUGIN_DIR;

		// Get all active plugins (including network-activated on multisite)
		$active_plugins = \Simply_Static\Util::get_all_active_plugins();
		$allowed        = (array) \Simply_Static\Options::instance()->get( 'plugins_to_include' );
		$allowed        = is_array( $allowed ) ? array_filter( array_map( 'strval', $allowed ) ) : [];
		$allowed        = apply_filters( 'ss_crawlable_plugins', $allowed );

		foreach ( $active_plugins as $plugin ) {
			// If user selected specific plugins, restrict to those (by directory slug)
			if ( ! empty( $allowed ) ) {
				$slug = dirname( $plugin );
				if ( ! in_array( $slug, $allowed, true ) ) {
					continue;
				}
			}
			// Get the plugin directory
			$plugin_dir  = dirname( $plugin );
			$plugin_path = $plugins_dir . '/' . $plugin_dir;

			// Skip if the plugin directory doesn't exist
			if ( ! is_dir( $plugin_path ) ) {
				continue;
			}

			// Scan the plugin directory for asset files
			$asset_urls = array_merge(
				$asset_urls,
				$this->scan_directory_for_assets( $plugin_path, $plugins_url . '/' . $plugin_dir )
			);
		}

		return $asset_urls;
	}

	/**
	 * Stream plugin asset URLs directly into the queue.
	 *
	 * @return int
	 */
	public function add_urls_to_queue(): int {
		$count      = 0;
		$batch      = [];
		$batch_sz   = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );
		$assets_ext = [
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
			'json'
		];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_plugin_directories', [
			'.git',
			'node_modules',
			'vendor/bin',
			'vendor/composer',
			'tests',
			'languages',
			'admin/build',
			'admin',
			'install-plugins',
			'freemius',
			'locale'
		] );

		$plugins_url    = plugins_url();
		$plugins_dir    = WP_PLUGIN_DIR;
		$active_plugins = \Simply_Static\Util::get_all_active_plugins();
		$allowed        = (array) \Simply_Static\Options::instance()->get( 'plugins_to_include' );
		$allowed        = is_array( $allowed ) ? array_filter( array_map( 'strval', $allowed ) ) : [];
		$allowed        = apply_filters( 'ss_crawlable_plugins', $allowed );

		foreach ( $active_plugins as $plugin ) {
			if ( ! empty( $allowed ) ) {
				$slug = dirname( $plugin );
				if ( ! in_array( $slug, $allowed, true ) ) {
					continue;
				}
			}
			$plugin_dir  = dirname( $plugin );
			$plugin_path = $plugins_dir . '/' . $plugin_dir;
			$base_url    = $plugins_url . '/' . $plugin_dir;
			if ( ! is_dir( $plugin_path ) ) {
				continue;
			}

			try {
				$it = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $plugin_path, \RecursiveDirectoryIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $it as $file ) {
					if ( $file->isDir() ) {
						continue;
					}
					// Build a safe relative path from the plugin directory prefix
					$rel = \Simply_Static\Util::safe_relative_path( $plugin_path, $file->getPathname() );
					// Skip unwanted directories
					$skip = false;
					foreach ( (array) $skip_dirs as $sd ) {
						if ( $sd && strpos( $rel, '/' . $sd . '/' ) !== false ) {
							$skip = true;
							break;
						}
					}
					// Extra skips (languages JSON and composer.json)
					$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
					if ( ! $skip && $ext === 'json' && strpos( $rel, '/languages/' ) !== false ) {
						$skip = true;
					}
					if ( ! $skip && strtolower( basename( $rel ) ) === 'composer.json' ) {
						$skip = true;
					}
					if ( $skip ) {
						continue;
					}
					if ( ! in_array( $ext, $assets_ext, true ) ) {
						continue;
					}
					// Join with exactly one slash between base and relative
					$batch[] = \Simply_Static\Util::safe_join_url( $base_url, $rel );
					if ( count( $batch ) >= $batch_sz ) {
						$count += $this->enqueue_urls_batch( $batch );
						$batch = [];
						usleep( 100000 );
					}
				}
			} catch ( \Exception $e ) {
				\Simply_Static\Util::debug_log( 'Error streaming plugin assets crawl: ' . $e->getMessage() );
			}
		}

		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Plugin assets crawler added %d URLs (streamed)', $count ) );

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
				\Simply_Static\Util::debug_log( sprintf( 'Plugin assets crawler skipping excluded URL: %s', $url ) );
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
			'json'
		];

		// Skip these directories
		$skip_dirs = apply_filters( 'ss_skip_crawl_plugin_directories', [
			'.git',
			'node_modules',
			'vendor/bin',
			'vendor/composer',
			'tests',
			'languages',
			'admin/build',
			'admin',
			'install-plugins',
			'freemius',
			'locale'
		] );

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

			// Process files in batches to prevent memory issues
			$batch_size  = apply_filters( 'simply_static_plugin_assets_batch_size', 500 );
			$file_count  = 0;
			$batch_count = 0;
			$file_batch  = [];

			foreach ( $iterator as $file ) {
				// Skip directories
				if ( $file->isDir() ) {
					continue;
				}

				$file_batch[] = $file;
				$file_count ++;

				// Process batch when it reaches the batch size
				if ( $file_count % $batch_size === 0 ) {
					$batch_count ++;
					$urls       = array_merge( $urls, $this->process_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $asset_extensions ) );
					$file_batch = []; // Reset batch
				}
			}

			// Process any remaining files
			if ( ! empty( $file_batch ) ) {
				$batch_count ++;
				$urls = array_merge( $urls, $this->process_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $asset_extensions ) );
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " asset URLs in $dir" );
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}

	/**
	 * Process a batch of files
	 *
	 * @param array $files Array of SplFileInfo objects
	 * @param string $dir Base directory path
	 * @param string $url_base Base URL
	 * @param array $skip_dirs Directories to skip
	 * @param array $asset_extensions Valid asset extensions
	 *
	 * @return array List of asset URLs
	 */
	private function process_file_batch( $files, $dir, $url_base, $skip_dirs, $asset_extensions ): array {
		$urls = [];

		foreach ( $files as $file ) {
			// Build a safe relative path and evaluate skip rules
			$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
			$should_skip   = false;

			foreach ( $skip_dirs as $skip_dir ) {
				if ( strpos( $relative_path, '/' . $skip_dir . '/' ) !== false ) {
					$should_skip = true;
					break;
				}
			}

			// Skip JSON files in the languages directory (used for admin translations)
			$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
			if ( ! $should_skip && $extension === 'json' && strpos( $relative_path, '/languages/' ) !== false ) {
				$should_skip = true;
			}

			// Skip composer.json files
			if ( ! $should_skip && strtolower( basename( $relative_path ) ) === 'composer.json' ) {
				$should_skip = true;
			}

			if ( $should_skip ) {
				continue;
			}

			// Check if the file has an asset extension
			if ( in_array( $extension, $asset_extensions, true ) ) {
				// Convert the file path to a URL and join safely
				$url    = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
