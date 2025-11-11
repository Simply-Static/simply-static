<?php

namespace Simply_Static;

/**
 * Class which handles transfer files task.
 */
class Transfer_Files_Locally_Task extends Task {

	use canProcessPages;

	use canTransfer;

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'transfer_files_locally';

	/**
	 * Destination Folder.
	 *
	 * @var string
	 */
	protected $destination_dir = '';

	/**
	 * Archive Folder.
	 *
	 * @var string
	 */
	protected $archive_dir = '';

	/**
	 * Copy a batch of files from the temp dir to the destination dir
	 *
	 * @return boolean true if done, false if not done.
	 */
	public function perform() {
		$this->destination_dir = apply_filters( 'ss_local_dir', $this->options->get( 'local_dir' ) );
		$this->archive_dir     = $this->options->get_archive_dir();

		$create_dir = $this->maybe_create_local_directory();

		if ( ! $create_dir ) {
			return true; // Make sure we're out of the loop and finish it.
		}

		$done = $this->process_pages();

		if ( $done ) {
			// Ensure rule text files created in the archive root are copied to the local directory as well.
			$this->transfer_rule_file( 'robots.txt' );
			$this->transfer_rule_file( 'llms.txt' );
			
			$this->transfer_404_page( $this->destination_dir );
			
			if ( $this->options->get( 'add_feeds' ) ) {
				$this->transfer_feed_redirect( $this->destination_dir );
			}

			// Copy Fuse.js search artifacts (index/config) for Local Directory deployment.
			$this->transfer_search_artifacts( $this->destination_dir );
			
			if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
				$destination_url = trailingslashit( $this->options->get_destination_url() );
				$message         = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url . '" target="_blank">' . $destination_url . '</a>';
				$this->save_status_message( $message, 'destination_url' );
			}
			
			// If this is a 404-only export, ensure the activity/export log reflects a single transferred file.
			$only_404 = get_option( 'simply-static-404-only' );
			if ( ! empty( $only_404 ) ) {
				// Update pages status to 1 total, 0 remaining and add a clear transfer message.
				$this->save_pages_status( 0, 1 );
				$this->save_status_message( sprintf( __( 'Transferred %d of %d files', 'simply-static' ), 1, 1 ) );
			}
			
			do_action( 'ss_finished_transferring_files_locally', $this->destination_dir );
			
			self::delete_total_pages();
		}

		return $done;
	}

	/**
	 * Mirror Fuse.js search artifacts from the archive into the Local Directory destination.
	 *
	 * This ensures fuse-index.json (and fuse-config.json if present) are deployed even if
	 * they were not part of the regular pages manifest. Runs only for Local Directory transfer.
	 *
	 * @param string $destination_dir Absolute path to the Local Directory destination.
	 * @return void
	 */
	protected function transfer_search_artifacts( $destination_dir ) {
		// Read Simply Static options to determine if search is enabled and using Fuse.
		$ss_options  = get_option( 'simply-static' );
		$use_search  = isset( $ss_options['use_search'] ) ? (bool) $ss_options['use_search'] : false;
		$search_type = isset( $ss_options['search_type'] ) ? $ss_options['search_type'] : 'fuse';

		$enabled = apply_filters( 'ssp_fuse_copy_to_destination', ( $use_search && 'fuse' === $search_type ), $destination_dir );
		if ( ! $enabled ) {
			return;
		}

		// Determine source and destination paths.
		$relative_dir = 'wp-content/uploads/simply-static/configs/';
		$source_dir   = trailingslashit( $this->archive_dir ) . $relative_dir;
		$dest_dir     = trailingslashit( $destination_dir ) . $relative_dir;

		// Ensure destination directory exists.
		if ( ! is_dir( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
		}

		$files_to_copy = array( 'fuse-index.json', 'fuse-config.json' );

		foreach ( $files_to_copy as $basename ) {
			$src = $source_dir . $basename;
			$dst = $dest_dir . $basename;
			if ( file_exists( $src ) ) {
				$ok = @copy( $src, $dst );
				if ( $ok ) {
					Util::debug_log( '[Transfer][Fuse] Copied search artifact to Local Directory: ' . $dst );
				} else {
					Util::debug_log( '[Transfer][Fuse] Failed copying search artifact to Local Directory: ' . $src . ' -> ' . $dst );
				}
			} else {
				Util::debug_log( '[Transfer][Fuse] Source search artifact not found in archive: ' . $src );
			}
		}
	}

	public function maybe_create_local_directory() {
		if ( is_dir( $this->destination_dir ) ) {
			return true;
		}

		if ( wp_mkdir_p( $this->destination_dir ) === false ) {
			Util::debug_log( "Cannot create directory: " . $this->destination_dir );
			$this->save_status_message( 'Unable to create destination directory: ' . $this->destination_dir );

			return false;
		}

		return true;
	}

	/**
	 * Message to set when processed pages.
	 *
	 * @param integer $processed Number of pages processed.
	 * @param integer $total Number of total pages to process.
	 *
	 * @return string
	 */
	protected function processed_pages_message( $processed, $total ) {
		Util::debug_log( '[Transfer] Total Pages:' . $total . '. Processed Pages: ' . $processed );
		if ( ! $total && 'update' === $this->get_generate_type() ) {
			return __( 'No new/updated pages to transfer', 'simply-static' );
		}

		return sprintf( __( "Transferred %d of %d files", 'simply-static' ), $processed, $total );
	}

	/**
	 * @param Page $static_page Page object.
	 *
	 * @return void
	 */
	protected function process_page( $static_page ) {
		$file_path = $this->get_page_file_path( $static_page );
		$path_info = Util::url_path_info( $file_path );
		$path      = Util::combine_path( $this->destination_dir, $path_info['dirname'] );

		Util::debug_log( "Trying to transfer: " . $file_path );

		if ( wp_mkdir_p( $path ) === false ) {
			Util::debug_log( "Cannot create directory: " . $path );
			$static_page->set_error_message( 'Unable to create destination directory' );
		} else {
			chmod( $path, 0755 );
			$origin_file_path      = Util::combine_path( $this->archive_dir, $file_path );
			$destination_file_path = Util::combine_path( $this->destination_dir, $file_path );

			// Check if origin file exists.
			if ( file_exists( $origin_file_path ) ) {
				$copy = copy( $origin_file_path, $destination_file_path );

				if ( $copy === false ) {
					Util::debug_log( "Cannot copy " . $origin_file_path . " to " . $destination_file_path );
					$static_page->set_error_message( 'Unable to copy file to destination' );
				} else {
					$static_page->last_transferred_at = Util::formatted_datetime();
					$static_page->save();

					Util::debug_log( 'Successfully transferred: ' . $path );
				}
			} else {
				Util::debug_log( "Cannot find file: " . $origin_file_path );
				$static_page->set_error_message( 'Unable to find file in archive' );
			}
		}

		do_action( 'simply_static_page_file_transferred', $static_page, $this->destination_dir );
	}

	/**
	 * Delete previously generated static files from the local directory.
	 *
	 * @param string $local_dir The directory to delete files from.
	 *
	 * @param object $options given options.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public static function delete_local_directory_static_files( $local_dir, $options ) {
		$temp_dir = $options->get( 'temp_files_dir' );

		if ( empty( $temp_dir ) ) {
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
		}

		if ( false === file_exists( $temp_dir ) ) {
			return false;
		}

		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $local_dir, \RecursiveDirectoryIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $files as $fileinfo ) {
			if ( $fileinfo->isDir() ) {
				if ( false === rmdir( $fileinfo->getRealPath() ) ) {
					return false;
				}
			} else {
				if ( false === unlink( $fileinfo->getRealPath() ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Transfer the 404 page if it exists.
	 *
	 * @param string $local_dir Path to local dir.
	 *
	 * @return void
	 */
	public function transfer_rule_file( $filename ) {
		$archive_dir = $this->options->get_archive_dir();
		$source      = trailingslashit( $archive_dir ) . ltrim( $filename, '/\\' );
		$dest        = trailingslashit( $this->destination_dir ) . ltrim( $filename, '/\\' );
		
		if ( ! file_exists( $source ) ) {
			Util::debug_log( '[Transfer] Rule file not found in archive: ' . $source );
			return;
		}
		
		// Ensure destination directory exists (root already ensured by maybe_create_local_directory)
		$dest_dir = dirname( $dest );
		if ( ! is_dir( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
		}
		
		if ( ! @copy( $source, $dest ) ) {
			Util::debug_log( '[Transfer] Failed to copy rule file from ' . $source . ' to ' . $dest );
			return;
		}
		
		Util::debug_log( '[Transfer] Copied rule file: ' . $filename . ' => ' . $dest );
	}

	public function transfer_404_page( $local_dir ) {
		$archive_dir = $this->options->get_archive_dir();
		$file_path   = untrailingslashit( $archive_dir ) . DIRECTORY_SEPARATOR . '404' . DIRECTORY_SEPARATOR . 'index.html';

		Util::debug_log( 'Transferring 404 Page' );

		if ( ! file_exists( $file_path ) ) {
			Util::debug_log( 'No 404 Page found at ' . $file_path );

			return;
		}

		$folder_404 = untrailingslashit( $local_dir ) . DIRECTORY_SEPARATOR . '404';

		if ( ! is_dir( $folder_404 ) ) {
			wp_mkdir_p( $folder_404 );
		}

		$destination_file = $folder_404 . DIRECTORY_SEPARATOR . 'index.html';

		if ( file_exists( $destination_file ) ) {
			return;
		}

		Util::debug_log( 'Destination 404 Page found at ' . $destination_file );

		$copied = copy( $file_path, $destination_file );

		Util::debug_log( 'Copy: ' . $copied ? 'Success' : 'No sucess' );
	}

	/**
	 * Transfer the feed redirect page if it exists.
	 *
	 * @param string $local_dir Path to local dir.
	 *
	 * @return void
	 */
	public function transfer_feed_redirect( $local_dir ) {
		$archive_dir = $this->options->get_archive_dir();

		$file_path             = untrailingslashit( $archive_dir ) . DIRECTORY_SEPARATOR . 'feed' . DIRECTORY_SEPARATOR . 'index.html';
		$destination_file_path = untrailingslashit( $local_dir ) . DIRECTORY_SEPARATOR . 'feed' . DIRECTORY_SEPARATOR . 'index.html';

		if ( ! file_exists( $file_path ) ) {
			return;
		}

		copy( $file_path, $destination_file_path );
	}
}
