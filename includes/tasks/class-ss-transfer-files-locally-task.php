<?php
namespace Simply_Static;

class Transfer_Files_Locally_Task extends Task {

	/**
	 * @var string
	 */
	protected $action = 'transfer_files_locally';

	public function perform() {
		$local_dir = $this->options->get( 'local_dir' );

		list( $pages_processed, $total_pages ) = $this->copy_static_files( $local_dir );

		if ( $pages_processed !== 0 ) {
			$message = sprintf( __( "Copied %d of %d files", 'simply-static' ), $pages_processed, $total_pages );
			$this->save_status_message( $message );
		}

		if ( is_wp_error( $pages_processed ) ) {
			return $pages_processed;
		} else {
			if ( $pages_processed == $total_pages ) {
				if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
					$destination_url = trailingslashit( $this->options->get_destination_url() );
					$message = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url .'" target="_blank">' . $destination_url . '</a>';
					$this->save_status_message( $message, 'destination_url' );
				}
			}

			// return true when done (no more pages)
			return $pages_processed >= $total_pages;
		}
	}

	/**
	* Copy temporary static files to a local directory
	* @param  string $destination_dir The directory to put the files
	* @return array (# pages processed, # pages remaining)
	*/
	public function copy_static_files( $destination_dir ) {
		$batch_size = 100;

		$archive_dir = $this->options->get_archive_dir();
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// TODO: also check for recent modification time
		// last_modified_at > ? AND
		$static_pages = Page::query()
			->where( "file_path IS NOT NULL" )
			->where( "file_path != ''" )
			->where( "( last_transferred_at < ? OR last_transferred_at IS NULL )", $archive_start_time )
			->limit( $batch_size )
			->find();
		$pages_remaining = count( $static_pages );
		$total_pages = Page::query()
			->where( "file_path IS NOT NULL AND file_path != ''" )
			->count();
		$pages_processed = $total_pages - $pages_remaining;

		while ( $static_page = array_shift( $static_pages ) ) {
			$path_info = sist_url_path_info( $static_page->file_path );
			$create_dir = wp_mkdir_p( $destination_dir . $path_info['dirname'] );
			if ( $create_dir === false ) {
				$static_page->set_error_message( 'Unable to create destination directory' );
			} else {
				$origin_file_path = $archive_dir . $static_page->file_path;
				$destination_file_path = $destination_dir . $static_page->file_path;

				// check that destination file doesn't exist OR exists but is writeable
				if ( ! file_exists( $destination_file_path ) || is_writable( $destination_file_path ) ) {
					$copy = copy( $origin_file_path, $destination_file_path );
					if ( $copy === false ) {
						$static_page->set_error_message( 'Unable to copy file to destination' );
					}
				} else {
					$static_page->set_error_message( 'Destination file exists and is unwriteable' );
				}
			}

			$static_page->last_transferred_at = sist_formatted_datetime();
			$static_page->save();
		}

		return array( $pages_processed, $total_pages );
	}

}
