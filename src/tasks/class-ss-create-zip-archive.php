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
		$this->options->set( 'zip_files', null );
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
		$files        = $this->get_files_to_include( $archive_dir, $is_first_batch );

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

		if ( empty( $files ) ) {
			return $this->create_empty_zip( $zip_filename );
		}

		// Prefer ZipArchive (ZIP64-capable) when available; fall back to PclZip for legacy environments.
		if ( class_exists( '\\ZipArchive' ) ) {
			return $this->create_zip_batched( $zip_filename, $archive_dir, $batch_offset, $is_first_batch, $files );
		}

		// Fallback to PclZip (no ZIP64 support) if ZipArchive is unavailable.
		// PclZip does not support incremental adds, so we create the archive in one go.
		$zip_archive = new \PclZip( $zip_filename );

		Util::debug_log( 'ZipArchive unavailable; falling back to PclZip (no ZIP64 support). Fetching list of files to include in zip' );

		Util::debug_log( 'Creating zip archive via PclZip' );

		$remove_path = realpath( $archive_dir );
		if ( false === $remove_path ) {
			$remove_path = $archive_dir;
		}

		if ( $zip_archive->create( $files, PCLZIP_OPT_REMOVE_PATH, $remove_path ) === 0 ) {
			return new \WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', 'simply-static' ) );
		}

		do_action( 'ss_zip_file_created', $zip_archive );

		$download_url = Util::abs_path_to_url( $zip_archive->zipname );

		return $download_url;
	}

	/**
	 * Create a valid empty ZIP archive.
	 *
	 * @param string $zip_filename Full path to the zip file.
	 *
	 * @return string|WP_Error Download URL when complete, WP_Error on failure.
	 */
	private function create_empty_zip( $zip_filename ) {
		Util::debug_log( 'Creating empty ZIP archive' );

		$empty_zip = "PK\005\006" . str_repeat( "\0", 18 );
		$written   = file_put_contents( $zip_filename, $empty_zip );

		if ( false === $written ) {
			return new \WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', 'simply-static' ) );
		}

		do_action( 'ss_zip_file_created', (object) array( 'zipname' => $zip_filename ) );

		return Util::abs_path_to_url( $zip_filename );
	}

	/**
	 * Get the archive files that should be included in the ZIP.
	 *
	 * Full exports package the complete archive directory. Update exports keep
	 * the previous archive directory, so they must package only files whose page
	 * records were modified by the current export.
	 *
	 * @param string $archive_dir    The archive source directory.
	 * @param bool   $is_first_batch Whether this is the first ZIP batch.
	 *
	 * @return array Absolute file paths.
	 */
	private function get_files_to_include( $archive_dir, $is_first_batch ) {
		$is_update_export = 'update' === $this->options->get( 'generate_type' );
		$stored_files = $this->options->get( 'zip_files' );
		if ( $is_update_export && ! $is_first_batch && is_array( $stored_files ) ) {
			return $stored_files;
		}

		if ( $is_update_export ) {
			$files = $this->get_modified_archive_files( $archive_dir );
		} else {
			$files = $this->get_all_archive_files( $archive_dir );
		}

		$files = apply_filters( 'ss_zip_files_to_include', $files, $archive_dir, $this->options, $this );
		$files = $this->normalize_zip_file_list( $files, $archive_dir );

		Util::debug_log( 'Prepared ' . count( $files ) . ' file(s) for ZIP archive' );

		if ( $is_update_export && $is_first_batch ) {
			$this->options->set( 'zip_files', $files );
			$this->options->save();
		}

		return $files;
	}

	/**
	 * Get every file currently present in the archive directory.
	 *
	 * @param string $archive_dir The archive source directory.
	 *
	 * @return array Absolute file paths.
	 */
	private function get_all_archive_files( $archive_dir ) {
		$files    = array();
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $archive_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );

		foreach ( $iterator as $path => $file_info ) {
			if ( ! $file_info->isDir() ) {
				$files[] = $path;
			}
		}

		return $files;
	}

	/**
	 * Get files modified by the current update export.
	 *
	 * @param string $archive_dir The archive source directory.
	 *
	 * @return array Absolute file paths.
	 */
	private function get_modified_archive_files( $archive_dir ) {
		$archive_start_time = $this->options->get( 'archive_start_time' );
		if ( empty( $archive_start_time ) ) {
			return array();
		}

		$pages = Page::query()
			->where( "file_path IS NOT NULL" )
			->where( "file_path != ''" )
			->where( "last_modified_at >= ?", $archive_start_time )
			->order( 'file_path ASC' )
			->find();

		if ( empty( $pages ) ) {
			return array();
		}

		$files = array();
		foreach ( $pages as $static_page ) {
			$files[] = $archive_dir . ltrim( $static_page->file_path, '/\\' );
		}

		return $files;
	}

	/**
	 * Normalize and constrain ZIP include paths to files under the archive dir.
	 *
	 * @param array  $files       Candidate absolute or archive-relative paths.
	 * @param string $archive_dir The archive source directory.
	 *
	 * @return array Absolute file paths.
	 */
	private function normalize_zip_file_list( $files, $archive_dir ) {
		if ( ! is_array( $files ) ) {
			return array();
		}

		$base_path = realpath( $archive_dir );
		if ( false === $base_path ) {
			return array();
		}

		$base_path_normalized = rtrim( str_replace( '\\', '/', $base_path ), '/' );
		$normalized_files     = array();

		foreach ( $files as $file ) {
			if ( ! is_string( $file ) || '' === $file ) {
				continue;
			}

			$path = $file;
			if ( ! preg_match( '/^(?:[A-Za-z]:[\/\\\\]|\/)/', $path ) ) {
				$path = $archive_dir . ltrim( $path, '/\\' );
			}

			$real_path = realpath( $path );
			if ( false === $real_path || ! is_file( $real_path ) ) {
				continue;
			}

			$real_path_normalized = str_replace( '\\', '/', $real_path );
			if ( 0 !== strpos( $real_path_normalized, $base_path_normalized . '/' ) ) {
				continue;
			}

			$normalized_files[ $real_path_normalized ] = $real_path;
		}

		ksort( $normalized_files );

		return array_values( $normalized_files );
	}

	/**
	 * Create the zip archive in batches using ZipArchive.
	 *
	 * @param string $zip_filename  Full path to the zip file.
	 * @param string $archive_dir   The archive source directory.
	 * @param int    $batch_offset  The file index offset to start from.
	 * @param bool   $is_first_batch Whether this is the first batch.
	 * @param array  $all_files     Files to add to the ZIP.
	 *
	 * @return string|false|WP_Error Download URL when complete, false if more batches needed, WP_Error on failure.
	 */
	private function create_zip_batched( $zip_filename, $archive_dir, $batch_offset, $is_first_batch, $all_files ) {
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

		$base_path = realpath( $archive_dir );
		if ( false === $base_path ) {
			$base_path = untrailingslashit( $archive_dir );
		}
		$base_len  = strlen( $base_path ) + 1; // account for trailing slash in relative names

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
