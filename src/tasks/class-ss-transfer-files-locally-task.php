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

			$this->transfer_404_page( $this->destination_dir );

			if ( $this->options->get( 'add_feeds' ) ) {
				$this->transfer_feed_redirect( $this->destination_dir );
			}

			if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
				$destination_url = trailingslashit( $this->options->get_destination_url() );
				$message         = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url . '" target="_blank">' . $destination_url . '</a>';
				$this->save_status_message( $message, 'destination_url' );
			}

			do_action( 'ss_finished_transferring_files_locally', $this->destination_dir );

			self::delete_total_pages();
		}

		return $done;
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
