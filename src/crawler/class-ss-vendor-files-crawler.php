<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Vendor Files Crawler class
 *
 * This crawler detects URLs for vendor files (third-party libraries, frameworks, etc.).
 */
class Vendor_Files_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'vendor_files';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Vendor Files', 'simply-static' );
		$this->description = __( 'Detects URLs for vendor files (third-party libraries, frameworks, etc.).', 'simply-static' );
	}

	/**
	 * Detect vendor file URLs.
	 *
	 * @return array List of vendor file URLs
	 */
	public function detect() : array {
		$vendor_urls = [];

		// Check for common vendor directories in plugins
		$vendor_urls = array_merge(
			$vendor_urls,
			$this->scan_plugins_for_vendor_files()
		);

		// Check for common vendor directories in themes
		$vendor_urls = array_merge(
			$vendor_urls,
			$this->scan_themes_for_vendor_files()
		);

		// Check for common CDN-hosted libraries
		$vendor_urls = array_merge(
			$vendor_urls,
			$this->detect_cdn_libraries()
		);

		return array_unique($vendor_urls);
	}

	/**
	 * Scan plugins for vendor files
	 *
	 * @return array List of vendor file URLs
	 */
	private function scan_plugins_for_vendor_files() : array {
		$urls = [];

		// Get the plugin directory URL and path
		$plugins_url = plugins_url();
		$plugins_dir = WP_PLUGIN_DIR;

		// Get all active plugins
		$active_plugins = get_option('active_plugins');

		// Common vendor directories to look for
		$vendor_dirs = [
			'vendor',
			'vendors',
			'lib',
			'libs',
			'library',
			'libraries',
			'assets/vendor',
			'assets/vendors',
			'assets/lib',
			'assets/libs',
			'includes/vendor',
			'includes/vendors',
			'includes/lib',
			'includes/libs',
		];

		// Common vendor file extensions
		$vendor_extensions = [
			'js', 'css', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'svg'
		];

		foreach ($active_plugins as $plugin) {
			// Get the plugin directory
			$plugin_dir = dirname($plugin);
			$plugin_path = $plugins_dir . '/' . $plugin_dir;

			// Skip if the plugin directory doesn't exist
			if (!is_dir($plugin_path)) {
				continue;
			}

			// Check each vendor directory
			foreach ($vendor_dirs as $vendor_dir) {
				$full_vendor_path = $plugin_path . '/' . $vendor_dir;

				if (is_dir($full_vendor_path)) {
					// Scan the vendor directory for files
					$urls = array_merge(
						$urls,
						$this->scan_directory_for_vendor_files(
							$full_vendor_path,
							$plugins_url . '/' . $plugin_dir . '/' . $vendor_dir,
							$vendor_extensions
						)
					);
				}
			}
		}

		return $urls;
	}

	/**
	 * Scan themes for vendor files
	 *
	 * @return array List of vendor file URLs
	 */
	private function scan_themes_for_vendor_files() : array {
		$urls = [];

		// Get the active theme
		$theme = wp_get_theme();

		// Get the theme directory path and URL
		$theme_dir = get_stylesheet_directory();
		$theme_url = get_stylesheet_directory_uri();

		// Common vendor directories to look for
		$vendor_dirs = [
			'vendor',
			'vendors',
			'lib',
			'libs',
			'library',
			'libraries',
			'assets/vendor',
			'assets/vendors',
			'assets/lib',
			'assets/libs',
			'includes/vendor',
			'includes/vendors',
			'includes/lib',
			'includes/libs',
		];

		// Common vendor file extensions
		$vendor_extensions = [
			'js', 'css', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'svg'
		];

		// Check each vendor directory in the active theme
		foreach ($vendor_dirs as $vendor_dir) {
			$full_vendor_path = $theme_dir . '/' . $vendor_dir;

			if (is_dir($full_vendor_path)) {
				// Scan the vendor directory for files
				$urls = array_merge(
					$urls,
					$this->scan_directory_for_vendor_files(
						$full_vendor_path,
						$theme_url . '/' . $vendor_dir,
						$vendor_extensions
					)
				);
			}
		}

		// If the theme has a parent, check the parent theme as well
		if ($theme->parent()) {
			$parent_theme_dir = get_template_directory();
			$parent_theme_url = get_template_directory_uri();

			// Only scan the parent theme if it's different from the child theme
			if ($parent_theme_dir !== $theme_dir) {
				// Check each vendor directory in the parent theme
				foreach ($vendor_dirs as $vendor_dir) {
					$full_vendor_path = $parent_theme_dir . '/' . $vendor_dir;

					if (is_dir($full_vendor_path)) {
						// Scan the vendor directory for files
						$urls = array_merge(
							$urls,
							$this->scan_directory_for_vendor_files(
								$full_vendor_path,
								$parent_theme_url . '/' . $vendor_dir,
								$vendor_extensions
							)
						);
					}
				}
			}
		}

		return $urls;
	}

	/**
	 * Scan a directory for vendor files recursively
	 *
	 * @param string $dir Directory path
	 * @param string $url_base Base URL for the directory
	 * @param array $extensions File extensions to look for
	 * @return array List of vendor file URLs
	 */
	private function scan_directory_for_vendor_files($dir, $url_base, $extensions) : array {
		$urls = [];

		// Skip these directories
		$skip_dirs = ['.git', 'node_modules', 'tests', 'docs', 'examples'];

		// Check if directory exists
		if ( ! is_dir( $dir ) ) {
			\Simply_Static\Util::debug_log( "Directory does not exist: $dir" );
			return $urls;
		}

		try {
			// Get all files in the directory
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			// Process files in batches to prevent memory issues
			$batch_size = apply_filters( 'simply_static_vendor_files_batch_size', 500 );
			$file_count = 0;
			$batch_count = 0;
			$file_batch = [];

			foreach ( $iterator as $file ) {
				// Skip directories
				if ( $file->isDir() ) {
					continue;
				}

				$file_batch[] = $file;
				$file_count++;

				// Process batch when it reaches the batch size
				if ( $file_count % $batch_size === 0 ) {
					$batch_count++;
					\Simply_Static\Util::debug_log( "Processing vendor files batch $batch_count with $batch_size files" );
					$urls = array_merge( $urls, $this->process_vendor_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $extensions ) );
					$file_batch = []; // Reset batch
				}
			}

			// Process any remaining files
			if ( ! empty( $file_batch ) ) {
				$batch_count++;
				\Simply_Static\Util::debug_log( "Processing final vendor files batch $batch_count with " . count( $file_batch ) . " files" );
				$urls = array_merge( $urls, $this->process_vendor_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $extensions ) );
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " vendor file URLs in $dir" );
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}

	/**
	 * Process a batch of vendor files
	 *
	 * @param array $files Array of SplFileInfo objects
	 * @param string $dir Base directory path
	 * @param string $url_base Base URL
	 * @param array $skip_dirs Directories to skip
	 * @param array $extensions Valid file extensions
	 *
	 * @return array List of vendor file URLs
	 */
	private function process_vendor_file_batch( $files, $dir, $url_base, $skip_dirs, $extensions ): array {
		$urls = [];

		foreach ( $files as $file ) {
			// Skip files in directories we want to ignore
			$relative_path = str_replace($dir, '', $file->getPathname());
			$should_skip = false;

			foreach ($skip_dirs as $skip_dir) {
				if (strpos($relative_path, '/' . $skip_dir . '/') !== false) {
					$should_skip = true;
					break;
				}
			}

			if ($should_skip) {
				continue;
			}

			// Check if the file has a vendor extension
			$extension = strtolower($file->getExtension());
			if (in_array($extension, $extensions)) {
				// Convert the file path to a URL
				$relative_url = str_replace('\\', '/', $relative_path);
				$url = $url_base . $relative_url;

				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Detect CDN-hosted libraries
	 *
	 * @return array List of CDN library URLs
	 */
	private function detect_cdn_libraries() : array {
		$urls = [];

		// This is a complex task as we'd need to parse HTML/JS to find CDN URLs
		// For now, we'll focus on local vendor files
		// A more comprehensive solution would involve parsing enqueued scripts
		// to find CDN URLs, but that's beyond the scope of this implementation

		return $urls;
	}
}
