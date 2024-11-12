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
		Util::debug_log( "Trying to transfer: " . $path );
		if ( wp_mkdir_p( $path ) === false ) {
			Util::debug_log( "Cannot create directory: " . $path );
			$static_page->set_error_message( 'Unable to create destination directory' );
		} else {
			chmod( $path, 0755 );
			$origin_file_path      = Util::combine_path( $this->archive_dir, $file_path );
			$destination_file_path = Util::combine_path( $this->destination_dir, $file_path );

			// Check if origin file exists.
			if ( file_exists( $origin_file_path ) ) {
				// Check if destination file exists and is writeable.
				if ( ! file_exists( $destination_file_path ) || is_writable( $destination_file_path ) ) {
					$copy = copy( $origin_file_path, $destination_file_path );

					if ( $copy === false ) {
						Util::debug_log( "Cannot copy " . $origin_file_path . " to " . $destination_file_path );
						$static_page->set_error_message( 'Unable to copy file to destination' );
					} else {
						$static_page->last_transferred_at = Util::formatted_datetime();
						$static_page->save();

						Util::debug_log( 'Successfully transferred: ' . $path );
					}
				}
			} else {
				Util::debug_log( "File exists and is unwriteable: " . $destination_file_path );
				$static_page->set_error_message( 'Destination file exists and is unwriteable' );
			}
		}

		do_action( 'simply_static_page_file_transferred', $static_page, $this->destination_dir );
	}

	/**
	 * Copy temporary static files to a local directory.
	 *
	 * @param string $destination_dir The directory to put the files.
	 *
	 * @depecated Using process_page now.
	 *
	 * @return array
	 */
	public function copy_static_files( $destination_dir ) {
		$batch_size         = apply_filters( 'simply_static_copy_files_batch_size', 50 );
		$archive_dir        = $this->options->get_archive_dir();
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// TODO: also check for recent modification time
		// last_modified_at > ? AND
		$static_pages    = Page::query()
		                       ->where( "file_path IS NOT NULL" )
		                       ->where( "file_path != ''" )
		                       ->where( "( last_transferred_at < ? OR last_transferred_at IS NULL )", $archive_start_time )
		                       ->limit( $batch_size )
		                       ->find();
		$pages_remaining = count( $static_pages );
		$total_pages     = Page::query()
		                       ->where( "file_path IS NOT NULL" )
		                       ->where( "file_path != ''" )
		                       ->count();
		$pages_processed = $total_pages - $pages_remaining;
		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . $pages_remaining );

		while ( $static_page = array_shift( $static_pages ) ) {
			$file_path = $this->get_page_file_path( $static_page );
			$path_info = Util::url_path_info( $file_path );
			$path      = Util::combine_path( $destination_dir, $path_info['dirname'] );

			if ( wp_mkdir_p( $path ) === false ) {
				Util::debug_log( "Cannot create directory: " . $path );
				$static_page->set_error_message( 'Unable to create destination directory' );
			} else {
				chmod( $path, 0755 );
				$origin_file_path      = Util::combine_path( $archive_dir, $file_path );
				$destination_file_path = Util::combine_path( $destination_dir, $file_path );

				// check that destination file doesn't exist or exists but is writeable
				if ( ! file_exists( $destination_file_path ) || is_writable( $destination_file_path ) ) {
					$copy = copy( $origin_file_path, $destination_file_path );
					if ( $copy === false ) {
						Util::debug_log( "Cannot copy " . $origin_file_path . " to " . $destination_file_path );
						$static_page->set_error_message( 'Unable to copy file to destination' );
					}
				} else {
					Util::debug_log( "File exists and is unwriteable: " . $destination_file_path );
					$static_page->set_error_message( 'Destination file exists and is unwriteable' );
				}
			}

					do_action( 'simply_static_page_file_transferred', $static_page, $destination_dir );

					$this->transfer_404_page( $destination_dir );

					$static_page->last_transferred_at = Util::formatted_datetime();
					$static_page->save();
				}

				return array( $pages_processed, $total_pages );
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
			public
			static function delete_local_directory_static_files( $local_dir, $options ) {
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
			public
			function transfer_404_page( $local_dir ) {
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
		}
