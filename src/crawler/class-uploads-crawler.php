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
			'svg',
			'ico',
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
			$batch_size = apply_filters( 'simply_static_uploads_batch_size', 500 );
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
					\Simply_Static\Util::debug_log( "Processing uploads batch $batch_count with $batch_size files" );
					$urls = array_merge( $urls, $this->process_file_batch( $file_batch, $dir, $url_base, $skip_dirs, $media_extensions ) );
					$file_batch = []; // Reset batch
				}
			}

			// Process any remaining files
			if ( ! empty( $file_batch ) ) {
				$batch_count++;
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
			// Skip files in directories we want to ignore
			$relative_path = str_replace( $dir, '', $file->getPathname() );
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
			$extension = strtolower( $file->getExtension() );
			if ( in_array( $extension, $media_extensions ) ) {
				// Convert the file path to a URL
				$relative_url = str_replace( '\\', '/', $relative_path );
				$url          = $url_base . $relative_url;

				$urls[] = $url;
			}
		}

		return $urls;
	}
}