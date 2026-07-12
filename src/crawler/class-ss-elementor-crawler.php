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
			// Elementor uploads directory (recursive scan covers /css subdirectory)
			'/wp-content/uploads/elementor'        => $wp_path . 'wp-content/uploads/elementor',
			// Elementor plugin assets (recursive scan covers js/, css/, lib/ subdirectories)
			'/wp-content/plugins/elementor/assets' => $wp_path . 'wp-content/plugins/elementor/assets',
			// Core jQuery (Elementor relies on this)
			'/wp-includes/js/jquery'               => $wp_path . 'wp-includes/js/jquery',
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
 				// Skip PHP files — they are never served as static assets
 				if ( strtolower( $file->getExtension() ) === 'php' ) { continue; }
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

		// Stream Lottie URLs if Elementor Pro is active.
		if ( $this->is_elementor_pro_active() ) {
			foreach ( $this->detect_lottie_files() as $lottie_url ) {
				$batch[] = $lottie_url;

				if ( count( $batch ) >= $batch_sz ) {
					$count += $this->enqueue_urls_batch( $batch );
					$batch = [];
					usleep( 100000 );
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
		global $wpdb;
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
		// Free wpdb cached query results to prevent memory accumulation across batches
		$wpdb->flush();
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
			// Elementor uploads directory (recursive scan covers /css subdirectory)
			'/wp-content/uploads/elementor'        => $wp_path . 'wp-content/uploads/elementor',
			// Elementor plugin assets (recursive scan covers js/, css/, lib/ subdirectories)
			'/wp-content/plugins/elementor/assets' => $wp_path . 'wp-content/plugins/elementor/assets',
			// jQuery directory
			'/wp-includes/js/jquery'               => $wp_path . 'wp-includes/js/jquery',
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
		$lottie_urls = array();
		$last_meta_id = 0;
		$batch_size = (int) apply_filters( 'simply_static_elementor_meta_batch_size', 100 );
		$batch_size = max( 1, min( 1000, $batch_size ) );

		do {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_id > %d ORDER BY meta_id ASC LIMIT %d",
					$last_meta_id,
					$batch_size
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) || empty( $rows ) ) {
				break;
			}

			$next_meta_id = $last_meta_id;

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['meta_id'] ) ) {
					continue;
				}

				$next_meta_id = max( $next_meta_id, (int) $row['meta_id'] );

				foreach ( $this->extract_lottie_urls_from_json( isset( $row['meta_value'] ) ? $row['meta_value'] : '' ) as $lottie_url ) {
					$lottie_urls[ $lottie_url ] = $lottie_url;
				}
			}

			if ( method_exists( $wpdb, 'flush' ) ) {
				$wpdb->flush();
			}

			if ( count( $rows ) < $batch_size || $next_meta_id <= $last_meta_id ) {
				break;
			}

			$last_meta_id = $next_meta_id;
		} while ( true );

		$lottie_urls = array_values( $lottie_urls );

		\Simply_Static\Util::debug_log( "Found " . count( $lottie_urls ) . " Lottie file URLs" );

		return $lottie_urls;
	}

	/**
	 * Extract library-backed Lottie URLs from one Elementor JSON document.
	 *
	 * @param mixed $json Elementor postmeta JSON.
	 *
	 * @return array
	 */
	private function extract_lottie_urls_from_json( $json ): array {
		if ( ! is_string( $json ) || '' === trim( $json ) ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$urls = array();

		foreach ( $this->flatten_data( $decoded ) as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['widgetType'] ) || 'lottie' !== $item['widgetType'] ) {
				continue;
			}

			$source = isset( $item['settings']['source_json'] ) && is_array( $item['settings']['source_json'] )
				? $item['settings']['source_json']
				: array();

			if ( 'library' !== ( isset( $source['source'] ) ? $source['source'] : '' ) || empty( $source['url'] ) || ! is_string( $source['url'] ) ) {
				continue;
			}

			$urls[ $source['url'] ] = $source['url'];
		}

		return array_values( $urls );
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

		$stack = array(
			array(
				'data' => $data,
				'emit' => false,
			),
		);

		while ( ! empty( $stack ) ) {
			$frame = array_pop( $stack );
			$node  = $frame['data'];

			if ( $frame['emit'] ) {
				if ( ! empty( $node ) ) {
					$flat_array[] = $node;
				}

				continue;
			}

			$children = array();

			if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
				$children[] = $node['elements'];
			}

			unset( $node['elements'] );

			foreach ( array_keys( $node ) as $key ) {
				if ( ! is_int( $key ) ) {
					continue;
				}

				if ( is_array( $node[ $key ] ) ) {
					$children[] = $node[ $key ];
				}

				unset( $node[ $key ] );
			}

			$stack[] = array(
				'data' => $node,
				'emit' => true,
			);

			for ( $index = count( $children ) - 1; $index >= 0; $index -- ) {
				$stack[] = array(
					'data' => $children[ $index ],
					'emit' => false,
				);
			}
		}

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
				// Skip PHP files — they are never served as static assets
				if ( strtolower( $file->getExtension() ) === 'php' ) {
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
