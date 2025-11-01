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

		// Skip if the uploads directory doesn't exist
		if ( ! is_dir( $uploads_dir['basedir'] ) ) {
			\Simply_Static\Util::debug_log( "Uploads directory does not exist: " . $uploads_dir['basedir'] );

			return $media_urls;
		}

		// Scan the uploads directory for media files
		$media_urls = $this->scan_directory_for_media_files( $uploads_dir['basedir'], $uploads_dir['baseurl'] );

		return $media_urls;
	}

	/**
	 * Override add_urls_to_queue to stream URLs directly into the queue in batches.
	 * This avoids building a massive array in memory for large media libraries.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		$count = 0;

		$uploads_dir = wp_upload_dir();
		$base_dir    = $uploads_dir['basedir'];
		$base_url    = $uploads_dir['baseurl'];

		if ( ! is_dir( $base_dir ) ) {
			\Simply_Static\Util::debug_log( "Uploads directory does not exist: " . $base_dir );

			return 0;
		}

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
			'pptx'
		];
		$media_extensions = apply_filters( 'ss_uploads_media_extensions', $media_extensions );

		// Directories to skip
		$skip_dirs = [ '.git', 'node_modules', 'cache', 'tmp', 'temp' ];
		$skip_dirs = apply_filters( 'ss_skip_crawl_uploads_directories', $skip_dirs );

		$batch_size = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );
		$buffer     = [];

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $base_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $file ) {
				if ( $file->isDir() ) {
					continue;
				}

				$relative_path = \Simply_Static\Util::safe_relative_path( $base_dir, $file->getPathname() );

				// Skip files in ignored directories
				$skip = false;
				foreach ( (array) $skip_dirs as $skip_dir ) {
					if ( $skip_dir && strpos( $relative_path, '/' . trim( $skip_dir, '/' ) . '/' ) !== false ) {
						$skip = true;
						break;
					}
				}
				if ( $skip ) {
					continue;
				}

				$ext = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, (array) $media_extensions, true ) ) {
					continue;
				}

				$url      = \Simply_Static\Util::safe_join_url( $base_url, $relative_path );
				$buffer[] = $url;

				if ( count( $buffer ) >= $batch_size ) {
					$count  += $this->enqueue_urls_batch( $buffer );
					$buffer = [];
					// Yield to allow other processes to run
					usleep( 100000 );
				}
			}

			// Process remaining
			if ( ! empty( $buffer ) ) {
				$count += $this->enqueue_urls_batch( $buffer );
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( 'Error streaming uploads crawl: ' . $e->getMessage() );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Uploads crawler added %d URLs (streamed)', $count ) );

		return $count;
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
			'pptx'
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
			$should_skip   = false;

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