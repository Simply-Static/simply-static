<?php

namespace Simply_Static;

require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

/**
 * Class which handles the archive task.
 */
class Create_Zip_Archive_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'create_zip_archive';

	/**
	 * Number of files to add to the zip archive per batch.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 2500;

	/**
	 * Performing the action.
	 *
	 * @return string|bool|WP_Error
	 */
	public function perform() {
		$result = $this->create_zip();

		if ( is_wp_error( $result ) ) {
			$this->cleanup_batch_state();
			return $result;
		}

		// If create_zip returned false, we're not done yet (more batches to process).
		if ( false === $result ) {
			return false;
		}

		// $result is the download URL — we're done.
		$download_url = $result;

		$this->cleanup_batch_state();

		if ( defined( 'SS_WASM' ) ) {
			// Force download link to be SSL.
			if ( strpos( $download_url, 'http://' ) !== false ) {
				$download_url = str_replace( 'http://', 'https://', $download_url );
			}
		}

		$message = __( 'ZIP archive created: ', 'simply-static' );
		if ( $this->is_wp_cli_running() ) {
			$message .= $download_url;
		} else {
			$message .= ' <a href="' . $download_url . '">' . __( 'Click here to download', 'simply-static' ) . '</a>';
		}

		$this->save_status_message( $message );

		return true;
	}

	/**
	 * Clean up batch tracking state from options.
	 *
	 * @return void
	 */
	private function cleanup_batch_state() {
		$this->options->set( 'zip_batch_offset', null );
		$this->options->set( 'zip_total_files', null );
		$this->options->save();
	}

	/**
	 * Create a ZIP file using the archive directory.
	 *
	 * Supports batched creation: on each call, adds up to BATCH_SIZE files to the
	 * zip archive. Returns false if more files remain, the download URL when done,
	 * or WP_Error on failure.
	 *
	 * @return string|false|WP_Error Download URL when complete, false if more batches needed, WP_Error on failure.
	 */
	public function create_zip() {
		$batch_offset = (int) $this->options->get( 'zip_batch_offset' );
		$is_first_batch = ( 0 === $batch_offset );

		$temp_dir = $this->options->get( 'temp_files_dir' );

		if ( empty( $temp_dir ) ) {
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
		}

		// Only clean temp dir on the first batch.
		if ( $is_first_batch ) {
			$temp_dir_empty = ! ( new \FilesystemIterator( $temp_dir ) )->valid();

			if ( ! $temp_dir_empty ) {
				foreach ( new \DirectoryIterator( $temp_dir ) as $file ) {
					if ( ! $file->isDir() ) {
						$can_delete_file = apply_filters( 'ss_can_delete_file', true, $file, $temp_dir );

						if ( ! $can_delete_file ) {
							continue;
						}

						unlink( $file->getPathname() );
					}
				}
			}
		}

		// Now we are creating a new zip file.
		$archive_dir  = $this->options->get_archive_dir();
		$zip_filename = untrailingslashit( $archive_dir ) . '.zip';
		$zip_filename = apply_filters( 'ss_zip_filename', $zip_filename, $archive_dir, $this->options );

		// Ensure target directory exists in case a custom path points elsewhere
		$zip_dir = dirname( $zip_filename );
		if ( ! is_dir( $zip_dir ) ) {
			wp_mkdir_p( $zip_dir );
		}
		// Ensure the directory is writable, otherwise ZipArchive::close() may emit warnings
		if ( ! is_writable( $zip_dir ) ) {
			return new \WP_Error(
				'zip_dir_not_writable',
				sprintf(
					/* translators: 1: directory path */
					__( 'The ZIP destination directory is not writable: %s', 'simply-static' ),
					$zip_dir
				)
			);
		}

		// If a leftover file exists but is not writable/removable, surface a clear error early
		if ( file_exists( $zip_filename ) && ! is_writable( $zip_filename ) ) {
			return new \WP_Error(
				'zip_file_not_writable',
				sprintf(
					/* translators: 1: zip file path */
					__( 'Cannot overwrite ZIP file: %s (permission denied)', 'simply-static' ),
					$zip_filename
				)
			);
		}

		// Prefer ZipArchive (ZIP64-capable) when available; fall back to PclZip for legacy environments.
		if ( class_exists( '\\ZipArchive' ) ) {
			return $this->create_zip_batched( $zip_filename, $archive_dir, $batch_offset, $is_first_batch );
		}

		// Fallback to PclZip (no ZIP64 support) if ZipArchive is unavailable.
		// PclZip does not support incremental adds, so we create the archive in one go.
		$zip_archive = new \PclZip( $zip_filename );

		Util::debug_log( 'ZipArchive unavailable; falling back to PclZip (no ZIP64 support). Fetching list of files to include in zip' );

		$files    = array();
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $archive_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file_name => $file_object ) {
			$files[] = realpath( $file_name );
		}

		Util::debug_log( 'Creating zip archive via PclZip' );

		if ( $zip_archive->create( $files, PCLZIP_OPT_REMOVE_PATH, $archive_dir ) === 0 ) {
			return new \WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', 'simply-static' ) );
		}

		do_action( 'ss_zip_file_created', $zip_archive );

		$download_url = Util::abs_path_to_url( $zip_archive->zipname );

		return $download_url;
	}

	/**
	 * Create the zip archive in batches using ZipArchive.
	 *
	 * @param string $zip_filename  Full path to the zip file.
	 * @param string $archive_dir   The archive source directory.
	 * @param int    $batch_offset  The file index offset to start from.
	 * @param bool   $is_first_batch Whether this is the first batch.
	 *
	 * @return string|false|WP_Error Download URL when complete, false if more batches needed, WP_Error on failure.
	 */
	private function create_zip_batched( $zip_filename, $archive_dir, $batch_offset, $is_first_batch ) {
		$batch_size = apply_filters( 'ss_zip_batch_size', self::BATCH_SIZE );

		if ( $is_first_batch ) {
			Util::debug_log( 'Creating zip archive via ZipArchive (ZIP64 capable if libzip supports it)' );
		} else {
			Util::debug_log( 'Resuming zip archive creation at offset ' . $batch_offset );
		}

		// Open the zip: CREATE | OVERWRITE for first batch, CREATE only for subsequent batches (append).
		$zip = new \ZipArchive();
		$flags = $is_first_batch ? ( \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) : \ZipArchive::CREATE;
		$opened = $zip->open( $zip_filename, $flags );
		if ( true !== $opened ) {
			return new \WP_Error(
				'create_zip_failed',
				sprintf(
					/* translators: 1: zip file path */
					__( 'Unable to open ZIP archive for writing: %s', 'simply-static' ),
					$zip_filename
				)
			);
		}

		$base_path = untrailingslashit( $archive_dir );
		$base_len  = strlen( $base_path ) + 1; // account for trailing slash in relative names

		// Collect all file paths from the archive directory.
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $archive_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );
		$all_files = array();
		foreach ( $iterator as $path => $file_info ) {
			if ( ! $file_info->isDir() ) {
				$all_files[] = $path;
			}
		}

		$total_files = count( $all_files );

		// Store total for status messages.
		if ( $is_first_batch ) {
			$this->options->set( 'zip_total_files', $total_files );
			$this->options->save();
		}

		// Determine the slice of files for this batch.
		$batch_files = array_slice( $all_files, $batch_offset, $batch_size );
		$count       = 0;

		foreach ( $batch_files as $path ) {
			$local_name = substr( $path, $base_len );
			// ZIP specification requires forward slashes as directory separators.
			$local_name = str_replace( '\\', '/', $local_name );
			$added = $zip->addFile( $path, $local_name );
			if ( true !== $added ) {
				Util::debug_log( 'Failed to add file to zip: ' . $path . ' as ' . $local_name );
				continue;
			}

			// Periodic progress logging for very large exports.
			if ( ( ++$count % 1000 ) === 0 ) {
				Util::debug_log( 'Added ' . ( $batch_offset + $count ) . ' of ' . $total_files . ' files to zip so far...' );
			}
		}

		// Close the zip to flush this batch to disk.
		$closed = $zip->close();
		if ( true !== $closed ) {
			$status = method_exists( $zip, 'getStatusString' ) ? $zip->getStatusString() : 'unknown error';
			return new \WP_Error(
				'zip_close_failed',
				sprintf(
					/* translators: 1: zip file path, 2: status message */
					__( 'Failed to finalize ZIP archive: %1$s (%2$s)', 'simply-static' ),
					$zip_filename,
					$status
				)
			);
		}

		$new_offset = $batch_offset + count( $batch_files );

		Util::debug_log( 'Batch complete: added files ' . ( $batch_offset + 1 ) . '-' . $new_offset . ' of ' . $total_files );

		// Check if there are more files to process.
		if ( $new_offset < $total_files ) {
			$this->options->set( 'zip_batch_offset', $new_offset );
			$this->options->save();

			$this->save_status_message(
				sprintf(
					/* translators: 1: files processed so far, 2: total files */
					__( 'Creating ZIP archive (%1$d of %2$d files)...', 'simply-static' ),
					$new_offset,
					$total_files
				)
			);

			// Not done yet — returning false triggers another perform() call.
			return false;
		}

		// All files added and zip finalized — we're done.
		Util::debug_log( 'ZIP archive complete: ' . $total_files . ' files' );

		do_action( 'ss_zip_file_created', (object) array( 'zipname' => $zip_filename ) );

		$download_url = Util::abs_path_to_url( $zip_filename );
		return $download_url;
	}
}
