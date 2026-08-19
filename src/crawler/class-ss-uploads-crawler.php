<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Uploads Crawler class
 *
 * This crawler detects URLs for media files in the uploads directory.
 */
class Uploads_Crawler extends Crawler {

	/** @var bool */
	protected $complete = true;

	/** @var array<string,int> */
	protected $progress = array(
		'added'   => 0,
		'scanned' => 0,
	);

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'uploads';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Uploads Directory', 'simply-static' );
		$this->description = __( 'Detects URLs for media files in the uploads directory.', 'simply-static' );
	}

	/**
	 * Detect media file URLs in the uploads directory.
	 *
	 * NOTE: Kept for backward compatibility, but not used by our overridden add_urls_to_queue().
	 *
	 * @return array List of media file URLs
	 */
	public function detect(): array {
		$media_urls = [];

		// Get the uploads directory information
		$uploads_dir = wp_upload_dir();
		$base_dir    = $uploads_dir['basedir'];
		$base_url    = $uploads_dir['baseurl'];

		$additional_dirs = [
			[
				'basedir' => WP_CONTENT_DIR . '/webp-express/webp-images/uploads',
				'baseurl' => content_url( 'webp-express/webp-images/uploads' ),
			],
			[
				'basedir' => WP_CONTENT_DIR . '/compressx-nextgen/uploads',
				'baseurl' => content_url( 'compressx-nextgen/uploads' ),
			],
		];

		$additional_dirs = apply_filters( 'ss_uploads_additional_directories', $additional_dirs );

		$scan_dirs = [
			[
				'basedir' => $base_dir,
				'baseurl' => $base_url,
			],
		];

		foreach ( $additional_dirs as $additional_dir ) {
			if ( is_dir( $additional_dir['basedir'] ) ) {
				$scan_dirs[] = $additional_dir;
			}
		}

		foreach ( $scan_dirs as $scan_dir ) {
			// Skip if the directory doesn't exist
			if ( ! is_dir( $scan_dir['basedir'] ) ) {
				\Simply_Static\Util::debug_log( "Directory does not exist: " . $scan_dir['basedir'] );
				continue;
			}

			// Scan the directory for media files
			$found_urls = $this->scan_directory_for_media_files( $scan_dir['basedir'], $scan_dir['baseurl'] );
			$media_urls = array_merge( $media_urls, $found_urls );
		}

		return $media_urls;
	}

	/**
	 * Stream one bounded batch of upload URLs into the queue.
	 *
	 * The previous implementation traversed the complete uploads tree in one
	 * request. Large libraries could outlive the background-process request and
	 * leave the export lock behind. Persist a directory/entry cursor instead so
	 * Discover_Urls_Task can yield and resume in the next worker request.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		$this->complete = true;
		$scan_dirs      = $this->get_scan_directories();
		$signature      = $this->get_state_signature( $scan_dirs );
		$state          = $this->load_state( $signature );
		$extensions     = (array) apply_filters( 'ss_uploads_media_extensions', $this->get_media_extensions() );
		$skip_dirs      = (array) apply_filters( 'ss_skip_crawl_uploads_directories', array( '.git', 'node_modules', 'cache', 'tmp', 'temp' ) );
		$queue_batch    = max( 1, min( 1000, (int) apply_filters( 'simply_static_crawler_batch_size', 100 ) ) );
		$entry_limit    = max( 1, min( 10000, (int) apply_filters( 'simply_static_uploads_crawler_max_entries_per_batch', 500 ) ) );
		$seconds        = (float) apply_filters( 'simply_static_uploads_crawler_max_batch_seconds', 10 );
		$deadline       = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$processed        = 0;
		$invocation_added = 0;
		$buffer           = array();

		while ( $state['scan_index'] < count( $scan_dirs ) ) {
			$scan = $scan_dirs[ $state['scan_index'] ];
			if ( null === $state['current_dir'] ) {
				if ( empty( $state['pending_dirs'] ) ) {
					$state['scan_index']++;
					if ( $state['scan_index'] < count( $scan_dirs ) ) {
						$state['pending_dirs'] = array( '' );
					}
					continue;
				}
				$state['current_dir']  = array_pop( $state['pending_dirs'] );
				$state['entry_offset'] = 0;
			}

			$directory = $this->resolve_scan_directory( $scan['basedir'], $state['current_dir'] );
			if ( false === $directory ) {
				\Simply_Static\Util::debug_log( 'Uploads crawler skipped a missing or unsafe directory cursor.' );
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			}

			try {
				$iterator = $this->get_directory_iterator( $directory );
				if ( $state['entry_offset'] > 0 ) {
					$iterator->seek( $state['entry_offset'] );
				}
			} catch ( \UnexpectedValueException $exception ) {
				\Simply_Static\Util::debug_log( 'Uploads crawler could not read directory: ' . $exception->getMessage() );
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			} catch ( \OutOfBoundsException $exception ) {
				// The directory became shorter between worker requests. Everything
				// before the persisted cursor was already handled, so move on safely.
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			}

			while ( $iterator->valid() ) {
				$file = $iterator->current();
				$state['entry_offset']++;
				$state['scanned']++;
				$processed++;

				if ( $file instanceof \SplFileInfo && ! $file->isLink() ) {
					$relative_path = ltrim( \Simply_Static\Util::safe_relative_path( $scan['basedir'], $file->getPathname() ), '/' );
					if ( $file->isDir() ) {
						if ( ! $this->should_skip_path( $relative_path, $skip_dirs ) ) {
							if ( count( $state['pending_dirs'] ) >= 50000 ) {
								throw new \RuntimeException( 'Uploads crawler directory queue exceeded its safety limit.' );
							}
							$state['pending_dirs'][] = $relative_path;
						}
					} elseif ( $file->isFile() && ! $this->should_skip_path( $relative_path, $skip_dirs ) ) {
						$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
						if ( in_array( $extension, $extensions, true ) ) {
							$buffer[] = \Simply_Static\Util::safe_join_url( $scan['baseurl'], $relative_path );
							if ( count( $buffer ) >= $queue_batch ) {
								$added             = $this->enqueue_urls_batch( $buffer );
								$invocation_added += $added;
								$state['added']    += $added;
								$buffer             = array();
							}
						}
					}
				}
				$iterator->next();

				if ( $processed >= $entry_limit || microtime( true ) >= $deadline ) {
					break;
				}
			}

			if ( ! empty( $buffer ) ) {
				$added             = $this->enqueue_urls_batch( $buffer );
				$invocation_added += $added;
				$state['added']    += $added;
				$buffer             = array();
			}

			if ( $iterator->valid() ) {
				$this->complete = false;
				$this->progress = array(
					'added'   => $state['added'],
					'scanned' => $state['scanned'],
				);
				$this->save_state( $state );
				\Simply_Static\Util::debug_log( sprintf( 'Uploads crawler checkpointed after %d entries with %d URLs queued.', $state['scanned'], $state['added'] ) );

				return $invocation_added;
			}

			$state['current_dir']  = null;
			$state['entry_offset'] = 0;
			if ( $processed >= $entry_limit || microtime( true ) >= $deadline ) {
				$this->complete = false;
				$this->progress = array(
					'added'   => $state['added'],
					'scanned' => $state['scanned'],
				);
				$this->save_state( $state );

				return $invocation_added;
			}
		}

		$this->progress = array(
			'added'   => $state['added'],
			'scanned' => $state['scanned'],
		);
		$this->clear_state();
		\Simply_Static\Util::debug_log( sprintf( 'Uploads crawler added %d URLs across resumable batches.', $state['added'] ) );

		return $invocation_added;
	}

	public function is_complete() : bool {
		return $this->complete;
	}

	public function get_progress() : array {
		return $this->progress;
	}

	/** @return array<int,array{basedir:string,baseurl:string}> */
	protected function get_scan_directories() : array {
		$uploads = wp_upload_dir();
		$dirs    = array();
		if ( isset( $uploads['basedir'], $uploads['baseurl'] ) && is_string( $uploads['basedir'] ) && is_string( $uploads['baseurl'] ) ) {
			$canonical = realpath( $uploads['basedir'] );
			$dirs[]    = array(
				'basedir' => false === $canonical ? $uploads['basedir'] : $canonical,
				'baseurl' => $uploads['baseurl'],
			);
		}
		$additional = apply_filters( 'ss_uploads_additional_directories', array(
			array(
				'basedir' => WP_CONTENT_DIR . '/webp-express/webp-images/uploads',
				'baseurl' => content_url( 'webp-express/webp-images/uploads' ),
			),
			array(
				'basedir' => WP_CONTENT_DIR . '/compressx-nextgen/uploads',
				'baseurl' => content_url( 'compressx-nextgen/uploads' ),
			),
		) );
		foreach ( (array) $additional as $candidate ) {
			if ( is_array( $candidate ) && isset( $candidate['basedir'], $candidate['baseurl'] ) && is_string( $candidate['basedir'] ) && is_string( $candidate['baseurl'] ) && is_dir( $candidate['basedir'] ) ) {
				$canonical = realpath( $candidate['basedir'] );
				$dirs[]    = array(
					'basedir' => false === $canonical ? $candidate['basedir'] : $canonical,
					'baseurl' => $candidate['baseurl'],
				);
			}
		}

		return $dirs;
	}

	/** @return string[] */
	protected function get_media_extensions() : array {
		return array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'tiff', 'heic', 'svg', 'ico', 'css', 'js', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'pdf', 'mp3', 'mp4', 'webm', 'ogg', 'wav', 'mov', 'avi', 'wmv', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'json' );
	}

	protected function get_directory_iterator( string $directory ) : \FilesystemIterator {
		return new \FilesystemIterator( $directory, \FilesystemIterator::SKIP_DOTS );
	}

	/** @param string[] $skip_dirs */
	protected function should_skip_path( string $relative_path, array $skip_dirs ) : bool {
		$path = trim( str_replace( '\\', '/', $relative_path ), '/' );
		if ( \Simply_Static\Util::is_private_backup_path( $path ) ) {
			return true;
		}
		foreach ( $skip_dirs as $skip_dir ) {
			$skip = is_string( $skip_dir ) ? trim( str_replace( '\\', '/', $skip_dir ), '/' ) : '';
			if ( '' !== $skip && ( $path === $skip || 0 === strpos( $path, $skip . '/' ) || false !== strpos( '/' . $path . '/', '/' . $skip . '/' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/** @return string|false */
	protected function resolve_scan_directory( string $base_dir, $relative_dir ) {
		if ( ! is_string( $relative_dir ) || ! $this->is_safe_relative_directory( $relative_dir ) ) {
			return false;
		}
		$root      = realpath( $base_dir );
		$candidate = realpath( rtrim( $base_dir, '/\\' ) . ( '' === $relative_dir ? '' : '/' . $relative_dir ) );
		if ( false === $root || false === $candidate || ! is_dir( $candidate ) ) {
			return false;
		}
		$root = rtrim( str_replace( '\\', '/', $root ), '/' );
		$path = str_replace( '\\', '/', $candidate );
		if ( $path !== $root && 0 !== strpos( $path, $root . '/' ) ) {
			return false;
		}

		return $candidate;
	}

	protected function is_safe_relative_directory( string $path ) : bool {
		if ( false !== strpos( $path, "\0" ) || 0 === strpos( str_replace( '\\', '/', $path ), '/' ) ) {
			return false;
		}
		foreach ( explode( '/', str_replace( '\\', '/', $path ) ) as $segment ) {
			if ( '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/** @param array<int,array{basedir:string,baseurl:string}> $scan_dirs */
	protected function get_state_signature( array $scan_dirs ) : string {
		return hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'scan_dirs'          => $scan_dirs,
		) ) );
	}

	/** @return array<string,mixed> */
	protected function load_state( string $signature ) : array {
		$state = \Simply_Static\Options::instance()->get( 'uploads_crawler_state' );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) ) {
			return $this->new_state( $signature );
		}
		if ( ! isset( $state['scan_index'], $state['entry_offset'], $state['pending_dirs'], $state['added'], $state['scanned'] ) || ! is_array( $state['pending_dirs'] ) ) {
			return $this->new_state( $signature );
		}
		if ( count( $state['pending_dirs'] ) > 50000 || ! is_int( $state['scan_index'] ) || $state['scan_index'] < 0 || ! is_int( $state['entry_offset'] ) || $state['entry_offset'] < 0 ) {
			return $this->new_state( $signature );
		}
		if ( ! is_int( $state['added'] ) || $state['added'] < 0 || ! is_int( $state['scanned'] ) || $state['scanned'] < 0 ) {
			return $this->new_state( $signature );
		}

		$current_dir = $state['current_dir'] ?? null;
		if ( null !== $current_dir && ( ! is_string( $current_dir ) || ! $this->is_safe_relative_directory( $current_dir ) ) ) {
			return $this->new_state( $signature );
		}
		foreach ( $state['pending_dirs'] as $directory ) {
			if ( ! is_string( $directory ) || ! $this->is_safe_relative_directory( $directory ) ) {
				return $this->new_state( $signature );
			}
		}

		return $state;
	}

	/** @return array<string,mixed> */
	protected function new_state( string $signature ) : array {
		return array(
			'version'      => 1,
			'signature'    => $signature,
			'scan_index'   => 0,
			'current_dir'  => null,
			'entry_offset' => 0,
			'pending_dirs' => array( '' ),
			'added'        => 0,
			'scanned'      => 0,
		);
	}

	/** @param array<string,mixed> $state */
	protected function save_state( array $state ) : void {
		if ( ! \Simply_Static\Options::instance()->set( 'uploads_crawler_state', $state )->save() ) {
			throw new \RuntimeException( 'Unable to save the uploads crawler checkpoint.' );
		}
	}

	protected function clear_state() : void {
		$options = \Simply_Static\Options::instance();
		$options->destroy( 'uploads_crawler_state' );
		if ( ! $options->save() ) {
			throw new \RuntimeException( 'Unable to clear the uploads crawler checkpoint.' );
		}
	}

	/**
	 * Enqueue a batch of URLs, returning how many were added.
	 *
	 * @param array $urls
	 *
	 * @return int
	 */
	private function enqueue_urls_batch( array $urls ): int {
		$count = 0;
		\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $urls ), $this->name ) );

		foreach ( $urls as $url ) {
			// Skip URLs that are excluded by settings/patterns to avoid adding them to the DB at all
			if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Uploads crawler skipping excluded URL: %s', $url ) );
				continue;
			}

			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$count ++;
		}

		return $count;
	}

	/**
	 * Scan a directory for media files recursively
	 *
	 * @param string $dir Directory path
	 * @param string $url_base Base URL for the directory
	 *
	 * @return array List of media file URLs
	 */
	private function scan_directory_for_media_files( $dir, $url_base ): array {
		$urls = [];

		// Media file extensions to look for
		$media_extensions = [
			'jpg',
			'jpeg',
			'png',
			'gif',
			'webp',
			'avif',
			'tiff',
			'heic',
			'svg',
			'ico',
			'css',
			'js',
			'woff',
			'woff2',
			'ttf',
			'otf',
			'eot',
			'pdf',
			'mp3',
			'mp4',
			'webm',
			'ogg',
			'wav',
			'mov',
			'avi',
			'wmv',
			'zip',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'json'
		];

		// Skip these directories
		$skip_dirs = apply_filters( 'ss_skip_crawl_uploads_directories', [
			'.git',
			'node_modules',
			'cache',
			'tmp',
			'temp'
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
			$batch_size  = apply_filters( 'simply_static_uploads_batch_size', 500 );
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
					\Simply_Static\Util::debug_log( "Processing uploads batch $batch_count with $batch_size files" );
					$urls       = array_merge( $urls, $this->process_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $media_extensions ) );
					$file_batch = []; // Reset batch
				}
			}

			// Process any remaining files
			if ( ! empty( $file_batch ) ) {
				$batch_count ++;
				\Simply_Static\Util::debug_log( "Processing final uploads batch $batch_count with " . count( $file_batch ) . " files" );
				$urls = array_merge( $urls, $this->process_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $media_extensions ) );
			}

			\Simply_Static\Util::debug_log( "Found " . count( $urls ) . " media URLs in $dir" );
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
	 * @param array $media_extensions Valid media extensions
	 *
	 * @return array List of media URLs
	 */
	private function process_file_batch( $files, $dir, $url_base, $skip_dirs, $media_extensions ): array {
		$urls = [];

		foreach ( $files as $file ) {
			// Build a safe relative path and evaluate skip rules
			$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
			if ( \Simply_Static\Util::is_private_backup_path( $relative_path ) ) {
				continue;
			}

			$should_skip = false;

			foreach ( $skip_dirs as $skip_dir ) {
				if ( strpos( $relative_path, '/' . $skip_dir . '/' ) !== false ) {
					$should_skip = true;
					break;
				}
			}

			if ( $should_skip ) {
				continue;
			}

			// Check if the file has a media extension
			$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
			if ( in_array( $extension, $media_extensions, true ) ) {
				// Convert the file path to a URL and join safely
				$url    = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
