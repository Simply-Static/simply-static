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
	 * Performing the action.
	 *
	 * @return string|bool
	 */
	public function perform() {
		$download_url = $this->create_zip();

		if ( is_wp_error( $download_url ) ) {
			return $download_url;
		} else {
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
	}

	/**
	 * Create a ZIP file using the archive directory
	 *
	 * @return string|WP_Error $temporary_zip The path to the archive zip file.
	 */
	public function create_zip() {
		$temp_dir = $this->options->get( 'temp_files_dir' );

		if ( empty( $temp_dir ) ) {
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
		}

		// check if temp directory is empty, if not delete old zip files.
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

		// Now we are creating a new zip file.
		$archive_dir  = $this->options->get_archive_dir();
		$zip_filename = untrailingslashit( $archive_dir ) . '.zip';
		$zip_filename = apply_filters( 'ss_zip_filename', $zip_filename, $archive_dir, $this->options );

		// Ensure target directory exists in case a custom path points elsewhere
		$zip_dir = dirname( $zip_filename );
		if ( ! is_dir( $zip_dir ) ) {
			wp_mkdir_p( $zip_dir );
		}
		// Prefer ZipArchive (ZIP64-capable) when available; fall back to PclZip for legacy environments.
		if ( class_exists( '\\ZipArchive' ) ) {
			Util::debug_log( 'Creating zip archive via ZipArchive (ZIP64 capable if libzip supports it)' );
			$zip = new \ZipArchive();
			$opened = $zip->open( $zip_filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
			if ( true !== $opened ) {
				return new \WP_Error( 'create_zip_failed', __( 'Unable to open ZIP archive for writing', 'simply-static' ) );
			}

			$base_path = untrailingslashit( $archive_dir );
			$base_len  = strlen( $base_path ) + 1; // account for trailing slash in relative names

			$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $archive_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );
			$count    = 0;
			foreach ( $iterator as $path => $file_info ) {
				if ( $file_info->isDir() ) {
					// ZipArchive will create directories implicitly when adding files; skip explicit dir add to match previous behavior.
					continue;
				}
				$local_name = substr( $path, $base_len );
				$zip->addFile( $path, $local_name );

				// Periodic progress logging for very large exports.
				if ( ( ++$count % 1000 ) === 0 ) {
					Util::debug_log( 'Added ' . $count . ' files to zip so far...' );
				}
			}

			$zip->close();

			do_action( 'ss_zip_file_created', (object) array( 'zipname' => $zip_filename ) );

			$download_url = Util::abs_path_to_url( $zip_filename );
			return $download_url;
		}

		// Fallback to PclZip (no ZIP64 support) if ZipArchive is unavailable.
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
}
