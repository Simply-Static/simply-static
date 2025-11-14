<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Elementor Crawler class
 *
 * This crawler detects URLs for Elementor assets and jQuery files.
 */
class Elementor_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'elementor';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Elementor Assets', 'simply-static' );
		$this->description = __( 'Detects Elementor assets and jQuery files.', 'simply-static' );
	}

	/**
	 * Stream Elementor asset URLs directly into the queue in batches.
	 * Mirrors the streaming approach used by Uploads and Plugin Assets crawlers
	 * to avoid building huge arrays in memory.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		$count     = 0;
		$batch     = [];
		$batch_sz  = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );

		$site_url = site_url();
		$wp_path  = ABSPATH;

		$directories = [
			'/wp-content/uploads/elementor'              => $wp_path . 'wp-content/uploads/elementor',
			'/wp-content/uploads/elementor/css'          => $wp_path . 'wp-content/uploads/elementor/css',
			// Elementor plugin assets
			'/wp-content/plugins/elementor/assets'       => $wp_path . 'wp-content/plugins/elementor/assets',
			'/wp-content/plugins/elementor/assets/js'    => $wp_path . 'wp-content/plugins/elementor/assets/js',
			'/wp-content/plugins/elementor/assets/css'   => $wp_path . 'wp-content/plugins/elementor/assets/css',
			'/wp-content/plugins/elementor/assets/lib'   => $wp_path . 'wp-content/plugins/elementor/assets/lib',
			// Core jQuery (Elementor relies on this)
			'/wp-includes/js/jquery'                     => $wp_path . 'wp-includes/js/jquery',
		];

		// Stream files from the directories
		foreach ( $directories as $url_path => $dir_path ) {
			if ( ! is_dir( $dir_path ) ) {
				\Simply_Static\Util::debug_log( "Directory does not exist: $dir_path" );
				continue;
			}
			try {
				$it = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $dir_path, \RecursiveDirectoryIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $it as $file ) {
					if ( $file->isDir() ) { continue; }
					// Build a safe relative path from the directory prefix
					$rel = \Simply_Static\Util::safe_relative_path( $dir_path, $file->getPathname() );
					$batch[] = \Simply_Static\Util::safe_join_url( $site_url . $url_path, $rel );
					if ( count( $batch ) >= $batch_sz ) {
						$count += $this->enqueue_urls_batch( $batch );
						$batch = [];
						usleep( 100000 );
					}
				}
			} catch ( \Exception $e ) {
				\Simply_Static\Util::debug_log( 'Error streaming Elementor directory crawl: ' . $e->getMessage() );
			}
		}

		// Add specific imagesloaded.min.js
		$batch[] = $site_url . '/wp-includes/js/imagesloaded.min.js';
		if ( count( $batch ) >= $batch_sz ) {
			$count += $this->enqueue_urls_batch( $batch );
			$batch = [];
		}

		// Stream Lottie URLs if Elementor Pro is active
		if ( $this->is_elementor_pro_active() ) {
			global $wpdb;
			$rows = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data'", ARRAY_A );
			if ( $rows ) {
				foreach ( $rows as $row ) {
					if ( empty( $row['meta_value'] ) ) { continue; }
					$decoded = json_decode( $row['meta_value'], true );
					if ( ! $decoded || ! is_array( $decoded ) ) { continue; }
					foreach ( $decoded as $widget_data ) {
						$flat_widget = $this->flatten_data( $widget_data );
						foreach ( (array) $flat_widget as $item ) {
							if ( empty( $item['widgetType'] ) || 'lottie' !== $item['widgetType'] ) { continue; }
							if ( empty( $item['settings']['source_json'] ) ) { continue; }
							$src = $item['settings']['source_json'];
							if ( empty( $src['source'] ) || $src['source'] !== 'library' ) { continue; }
							if ( empty( $src['url'] ) ) { continue; }
							$batch[] = $src['url'];
							if ( count( $batch ) >= $batch_sz ) {
								$count += $this->enqueue_urls_batch( $batch );
								$batch = [];
								usleep( 100000 );
							}
						}
					}
				}
			}
		}

		// Flush remaining
		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Elementor crawler added %d URLs (streamed)', $count ) );
		return $count;
	}

	/**
	 * Enqueue a batch of URLs and return how many were added.
	 *
	 * @param array $urls
	 * @return int
	 */
	private function enqueue_urls_batch( array $urls ): int {
		$count = 0;
		\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $urls ), $this->name ) );
  foreach ( $urls as $url ) {
			// Skip URLs that are excluded by settings/patterns
			if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Elementor crawler skipping excluded URL: %s', $url ) );
				continue;
			}
			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$count++;
		}
		return $count;
	}

	/**
	 * Check if Elementor is installed.
	 *
	 * @return boolean
	 */
	public function dependency_active() : bool {
		return defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Check if Elementor Pro is active.
	 *
	 * @return boolean
	 */
	public function is_elementor_pro_active() {
		return defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/**
	 * Check if the crawler is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		// First check if Elementor is installed
		if ( ! $this->dependency_active() ) {
			return false;
		}

		// Then check if the crawler is active in the options
		return parent::is_active();
	}

	/**
	 * Detect Elementor and jQuery files.
	 *
	 * @return array List of asset URLs
	 */
	public function detect(): array {
		$asset_urls = [];

		// Get the site URL and WordPress ABSPATH
		$site_url = site_url();
		$wp_path = ABSPATH;

		// Directories to scan
		$directories = [
			// Elementor uploads directory
			'/wp-content/uploads/elementor' => $wp_path . 'wp-content/uploads/elementor',
			// Elementor CSS directory (for post CSS files)
			'/wp-content/uploads/elementor/css' => $wp_path . 'wp-content/uploads/elementor/css',
			// Elementor plugin assets
			'/wp-content/plugins/elementor/assets'     => $wp_path . 'wp-content/plugins/elementor/assets',
			'/wp-content/plugins/elementor/assets/js'  => $wp_path . 'wp-content/plugins/elementor/assets/js',
			'/wp-content/plugins/elementor/assets/css' => $wp_path . 'wp-content/plugins/elementor/assets/css',
			'/wp-content/plugins/elementor/assets/lib' => $wp_path . 'wp-content/plugins/elementor/assets/lib',
			// jQuery directory
			'/wp-includes/js/jquery' => $wp_path . 'wp-includes/js/jquery',
		];

		// Scan each directory and add files to asset URLs
		foreach ( $directories as $url_path => $dir_path ) {
			if ( is_dir( $dir_path ) ) {
				$directory_urls = $this->scan_directory_for_assets( $dir_path, $site_url . $url_path );
				$asset_urls = array_merge( $asset_urls, $directory_urls );
			} else {
				\Simply_Static\Util::debug_log( "Directory does not exist: $dir_path" );
			}
		}

		// Add specific imagesloaded.min.js file
		$asset_urls[] = $site_url . '/wp-includes/js/imagesloaded.min.js';

		// Add Lottie files if Elementor Pro is active
		if ( $this->is_elementor_pro_active() ) {
			$lottie_urls = $this->detect_lottie_files();
			$asset_urls = array_merge( $asset_urls, $lottie_urls );
		}

		return $asset_urls;
	}

	/**
	 * Detect Lottie files used in Elementor Pro.
	 *
	 * @return array List of Lottie file URLs
	 */
	private function detect_lottie_files(): array {
		global $wpdb;
		$lottie_urls = [];

		// Get all Elementor data from post meta
		$elementor_data = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data'", ARRAY_A );

		if ( ! $elementor_data ) {
			return $lottie_urls;
		}

		foreach ( $elementor_data as $data ) {
			// Skip if meta_value is empty
			if ( empty( $data['meta_value'] ) ) {
				continue;
			}

			// Decode the JSON data
			$decoded_data = json_decode( $data['meta_value'], true );

			// Skip if JSON decoding failed
			if ( ! $decoded_data || ! is_array( $decoded_data ) ) {
				continue;
			}

			foreach ( $decoded_data as $widget_data ) {
				// Flatten the widget data to find all Lottie widgets
				$flat_widget = $this->flatten_data( $widget_data );

				// Filter to find only Lottie widgets with library source
				$lottie_files = array_filter( $flat_widget, function ( $item ) {
					if ( ! isset( $item['widgetType'] ) ) {
						return false;
					}

					if ( empty( $item['settings'] ) ) {
						return false;
					}

					if ( empty( $item['settings']['source_json'] ) ) {
						return false;
					}

					if ( 'library' !== $item['settings']['source_json']['source'] ) {
						return false;
					}

					return $item['widgetType'] === 'lottie';
				} );

				if ( ! $lottie_files ) {
					continue;
				}

				foreach ( $lottie_files as $lottie_widget ) {
					$lottie_urls[] = $lottie_widget['settings']['source_json']['url'];
				}
			}
		}

		// Remove duplicates
		$lottie_urls = array_unique( $lottie_urls );

		\Simply_Static\Util::debug_log( "Found " . count( $lottie_urls ) . " Lottie file URLs" );

		return $lottie_urls;
	}

	/**
	 * Flatten nested Elementor data structure.
	 *
	 * @param array $data The data to flatten
	 * @param array $flat_array The accumulated flat array
	 * @return array The flattened data
	 */
	private function flatten_data( $data, $flat_array = [] ) {
		if ( ! is_array( $data ) ) {
			return $flat_array;
		}

		if ( ! empty( $data['elements'] ) ) {
			$flat_array = $this->flatten_data( $data['elements'], $flat_array );
			unset( $data['elements'] );
		}

		$array_keys = array_keys( $data );

		foreach ( $array_keys as $number ) {
			if ( ! is_integer( $number ) ) {
				continue;
			}

			$flat_array = $this->flatten_data( $data[ $number ], $flat_array );
			unset( $data[ $number ] );
		}

		if ( isset( $data['elements'] ) ) {
			unset( $data['elements'] );
		}

		$flat_array[] = array_merge( $data, $flat_array );

		return $flat_array;
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
				
				// Build the relative path safely
				$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
				
				// Create the full URL with safe joining
				$url = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				$urls[] = $url;
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " asset URLs in $dir" );
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}
}
