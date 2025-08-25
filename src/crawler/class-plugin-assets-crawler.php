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

		// Get all active plugins
		$active_plugins = get_option( 'active_plugins' );

		foreach ( $active_plugins as $plugin ) {
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
			// Skip files in directories we want to ignore
			$relative_path = str_replace( $dir, '', $file->getPathname() );
			$should_skip   = false;

			foreach ( $skip_dirs as $skip_dir ) {
				if ( strpos( $relative_path, '/' . $skip_dir . '/' ) !== false ) {
					$should_skip = true;
					break;
				}
			}

			// Skip JSON files in the languages directory (used for admin translations)
			if ( ! $should_skip && strtolower( $file->getExtension() ) === 'json' && strpos( $relative_path, '/languages/' ) !== false ) {
				$should_skip = true;
			}

			// Skip composer.json files
			if ( ! $should_skip && strtolower( $file->getBasename() ) === 'composer.json' ) {
				$should_skip = true;
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
				$urls[]       = $url;
			}
		}

		return $urls;
	}
}
