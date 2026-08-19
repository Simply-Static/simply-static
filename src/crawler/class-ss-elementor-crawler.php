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
		$signature = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'site_url'           => site_url(),
			'elementor_version'  => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '',
		) ) );
		$state     = $this->load_elementor_state( $signature );
		$added_now = 0;

		if ( 'directories' === $state['stage'] ) {
			$added_now += $this->enqueue_directory_batch(
				'elementor_directory_crawler_state',
				$this->get_elementor_scan_directories(),
				array(),
				(array) apply_filters( 'ss_skip_crawl_elementor_directories', array( '.git', 'node_modules', 'cache', 'tmp', 'temp' ) ),
				'simply_static_elementor_crawler_max_entries_per_batch',
				'simply_static_elementor_crawler_max_batch_seconds'
			);
			if ( ! $this->is_complete() ) {
				return $added_now;
			}

			$directory_progress = $this->get_progress();
			$state['stage']      = 'lottie';
			$state['added']      = (int) ( $directory_progress['added'] ?? 0 );
			$state['scanned']    = (int) ( $directory_progress['scanned'] ?? 0 );
			$this->save_crawler_state( 'elementor_crawler_state', $state );
		}

		if ( empty( $state['imagesloaded_added'] ) ) {
			$added                        = $this->enqueue_urls( array( site_url( '/wp-includes/js/imagesloaded.min.js' ) ) );
			$added_now                   += $added;
			$state['added']               += $added;
			$state['imagesloaded_added'] = true;
			$this->save_crawler_state( 'elementor_crawler_state', $state );
		}

		if ( ! $this->is_elementor_pro_active() ) {
			$this->finish_elementor_crawl( $state );
			return $added_now;
		}

		$added_now += $this->enqueue_lottie_batch( $state );
		if ( $this->is_complete() ) {
			$this->finish_elementor_crawl( $state );
		} else {
			$this->save_crawler_state( 'elementor_crawler_state', $state );
		}

		return $added_now;
	}

	/** @return array<int,array{basedir:string,baseurl:string}> */
	protected function get_elementor_scan_directories() : array {
		return array(
			array(
				'basedir' => ABSPATH . 'wp-content/uploads/elementor',
				'baseurl' => site_url( '/wp-content/uploads/elementor' ),
			),
			array(
				'basedir' => ABSPATH . 'wp-content/plugins/elementor/assets',
				'baseurl' => site_url( '/wp-content/plugins/elementor/assets' ),
			),
			array(
				'basedir' => ABSPATH . 'wp-includes/js/jquery',
				'baseurl' => site_url( '/wp-includes/js/jquery' ),
			),
		);
	}

	/** @return array<string,mixed> */
	private function load_elementor_state( $signature ) : array {
		$state = \Simply_Static\Options::instance()->get( 'elementor_crawler_state' );
		if ( ! is_array( $state )
			|| 1 !== ( $state['version'] ?? null )
			|| $signature !== ( $state['signature'] ?? null )
			|| ! in_array( $state['stage'] ?? null, array( 'directories', 'lottie' ), true )
			|| ! is_int( $state['last_meta_id'] ?? null )
			|| $state['last_meta_id'] < 0
			|| ! is_int( $state['added'] ?? null )
			|| $state['added'] < 0
			|| ! is_int( $state['scanned'] ?? null )
			|| $state['scanned'] < 0
		) {
			return array(
				'version'            => 1,
				'signature'          => $signature,
				'stage'              => 'directories',
				'last_meta_id'       => 0,
				'imagesloaded_added' => false,
				'added'              => 0,
				'scanned'            => 0,
			);
		}

		return $state;
	}

	/**
	 * Process a bounded set of Elementor JSON rows for Lottie URLs.
	 *
	 * @param array<string,mixed> $state Mutable crawler state.
	 *
	 * @return int URLs added by this invocation.
	 */
	private function enqueue_lottie_batch( array &$state ) : int {
		global $wpdb;

		$this->complete = false;
		$batch_size     = max( 1, min( 50, (int) apply_filters( 'simply_static_elementor_meta_batch_size', 5 ) ) );
		$row_limit      = max( 1024, min( 10 * 1024 * 1024, (int) apply_filters( 'simply_static_elementor_meta_max_bytes', 5 * 1024 * 1024 ) ) );
		$entry_limit    = max( $batch_size, min( 1000, (int) apply_filters( 'simply_static_elementor_meta_max_entries_per_batch', 50 ) ) );
		$seconds        = (float) apply_filters( 'simply_static_elementor_meta_max_batch_seconds', 10 );
		$deadline       = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$processed      = 0;
		$added_now      = 0;

		do {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_id > %d AND OCTET_LENGTH(meta_value) <= %d ORDER BY meta_id ASC LIMIT %d",
					$state['last_meta_id'],
					$row_limit,
					$batch_size
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) || empty( $rows ) ) {
				$this->complete = true;
				break;
			}

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['meta_id'] ) ) {
					continue;
				}
				$state['last_meta_id'] = max( $state['last_meta_id'], (int) $row['meta_id'] );
				$state['scanned']++;
				$processed++;
				$urls            = $this->extract_lottie_urls_from_json( $row['meta_value'] ?? '' );
				$added           = empty( $urls ) ? 0 : $this->enqueue_urls( $urls );
				$added_now      += $added;
				$state['added'] += $added;
			}

			if ( method_exists( $wpdb, 'flush' ) ) {
				$wpdb->flush();
			}

			if ( count( $rows ) < $batch_size ) {
				$this->complete = true;
				break;
			}
		} while ( $processed < $entry_limit && microtime( true ) < $deadline );

		$this->progress = array(
			'added'   => $state['added'],
			'scanned' => $state['scanned'],
		);

		return $added_now;
	}

	/** @param array<string,mixed> $state */
	private function finish_elementor_crawl( array $state ) : void {
		$this->complete = true;
		$this->progress = array(
			'added'   => $state['added'],
			'scanned' => $state['scanned'],
		);
		$this->clear_crawler_state( 'elementor_crawler_state' );
		\Simply_Static\Util::debug_log( sprintf( 'Elementor crawler added %d URLs across resumable batches.', $state['added'] ) );
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
		$batch_size = max( 1, min( 50, (int) apply_filters( 'simply_static_elementor_meta_batch_size', 5 ) ) );
		$row_limit   = max( 1024, min( 10 * 1024 * 1024, (int) apply_filters( 'simply_static_elementor_meta_max_bytes', 5 * 1024 * 1024 ) ) );
		$max_entries = max( 1, min( 10000, (int) apply_filters( 'simply_static_elementor_meta_detection_limit', 500 ) ) );
		$seconds     = (float) apply_filters( 'simply_static_elementor_meta_detection_seconds', 10 );
		$deadline    = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$processed   = 0;

		do {
			$query_limit = min( $batch_size, $max_entries - $processed );
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_id > %d AND OCTET_LENGTH(meta_value) <= %d ORDER BY meta_id ASC LIMIT %d",
					$last_meta_id,
					$row_limit,
					$query_limit
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
				$processed++;

				foreach ( $this->extract_lottie_urls_from_json( isset( $row['meta_value'] ) ? $row['meta_value'] : '' ) as $lottie_url ) {
					$lottie_urls[ $lottie_url ] = $lottie_url;
				}
			}

			if ( method_exists( $wpdb, 'flush' ) ) {
				$wpdb->flush();
			}

			if ( count( $rows ) < $query_limit || $next_meta_id <= $last_meta_id || $processed >= $max_entries || microtime( true ) >= $deadline ) {
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

		$urls  = array();
		$stack = array( $decoded );

		while ( ! empty( $stack ) ) {
			$item = array_pop( $stack );
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( isset( $item['widgetType'] ) && 'lottie' === $item['widgetType'] ) {
				$source = isset( $item['settings']['source_json'] ) && is_array( $item['settings']['source_json'] )
					? $item['settings']['source_json']
					: array();
				if ( 'library' === ( isset( $source['source'] ) ? $source['source'] : '' ) && ! empty( $source['url'] ) && is_string( $source['url'] ) ) {
					$urls[ $source['url'] ] = $source['url'];
				}
			}
			foreach ( $item as $value ) {
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
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
		$urls        = [];
		$max_entries = max( 1, min( 100000, (int) apply_filters( 'simply_static_elementor_detection_max_entries', 5000 ) ) );
		$seconds     = (float) apply_filters( 'simply_static_elementor_detection_max_seconds', 5 );
		$deadline    = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$scanned     = 0;

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
				$scanned++;
				if ( $scanned > $max_entries || microtime( true ) >= $deadline ) {
					break;
				}
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
