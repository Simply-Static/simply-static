<?php

namespace Simply_Static;

/**
 * Class which upload files to SimplyCDN.
 */
class Simply_Cdn_Task extends Task {
	/**
	 * The task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'simply_cdn';

	/**
	 * Object containing all data for the CDN.
	 *
	 * @var object|Simply_CDN_Handler|null
	 */
	private $cdn;

	/**
	 * Data for the SimplyCDN project.
	 *
	 * @var bool|object
	 */
	private $data;

	/**
	 * Given temporary directory.
	 *
	 * @var string
	 */
	private $temp_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$options = Options::instance();
		$token   = get_option( 'sch_token' );

		$this->cdn      = Simply_CDN_Handler::get_instance();
		$this->data     = Simply_CDN_Api::get_data( $token );
		$this->options  = $options;
		$this->temp_dir = $options->get_archive_dir();
	}

	/**
	 * Copy a batch of files from the temp dir to the destination dir
	 *
	 * @return boolean true if done, false if not done.
	 */
	public function perform() {
		list( $pages_processed, $total_pages ) = $this->upload_static_files( $this->temp_dir );

		if ( $pages_processed !== 0 ) {
			$message = sprintf( __( "Uploaded %d of %d pages/files", 'simply-static' ), $pages_processed, $total_pages );
			$this->save_status_message( $message );
		}

		if ( $pages_processed >= $total_pages ) {
			if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
				$destination_url = trailingslashit( $this->options->get_destination_url() );
				$message         = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url . '" target="_blank">' . $destination_url . '</a>';
				$this->save_status_message( $message, 'destination_url' );
			}
		}

		// return true when done (no more pages).
		if ( $pages_processed >= $total_pages ) {
			do_action( 'ss_finished_cdn_transfer', $this->temp_dir );

			// Maybe add 404.
			$this->add_404();

			// Clear cache.
			Simply_CDN_Api::clear_cache();
		}

		return $pages_processed >= $total_pages;
	}

	/**
	 * Upload files to CDN.
	 *
	 * @param string $destination_dir The directory to put the files..
	 *
	 * @return array
	 */
	public function upload_static_files( $destination_dir ) {
		$batch_size         = apply_filters( 'sch_upload_files_batch_size', 25 );
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// Subdirectory?
		$cdn_path = '';

		if ( ! empty( $this->cdn->data->cdn->sub_directory ) ) {
			$cdn_path = $this->cdn->data->cdn->sub_directory . '/';
		}

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
			$file_path = $this->temp_dir . $static_page->file_path;

			if ( ! is_dir( $file_path ) && file_exists( $file_path ) ) {
				$this->cdn->upload_file( $this->data->cdn->access_key, $this->data->cdn->pull_zone->name, $cdn_path . $static_page->file_path, $file_path );
			}

			do_action( 'ss_file_transfered_to_cdn', $static_page, $destination_dir );

			$static_page->last_transferred_at = Util::formatted_datetime();
			$static_page->save();
		}

		return array( $pages_processed, $total_pages );
	}

	/**
	 * Maybe add a custom 404 page.
	 *
	 * @return void
	 */
	public function add_404() {
		$cdn_404_path = get_option( 'sch_404_path' );

		if ( ! empty( $cdn_404_path ) && realpath( $this->temp_dir . untrailingslashit( $cdn_404_path ) . '/index.html' ) ) {
			// Rename and copy file.
			$src_error_file  = $this->temp_dir . untrailingslashit( $cdn_404_path ) . '/index.html';
			$dst_error_file  = $this->temp_dir . 'bunnycdn_errors/404.html';
			$error_directory = dirname( $dst_error_file );

			if ( ! is_dir( $error_directory ) ) {
				wp_mkdir_p( $error_directory );
				chmod( $error_directory, 0777 );
			}

			copy( $src_error_file, $dst_error_file );

			// Upload 404 template file.
			$error_file_path     = realpath( $this->temp_dir . 'bunnycdn_errors/404.html' );
			$error_relative_path = str_replace( $this->temp_dir, '', $error_file_path );

			if ( $error_file_path ) {
				$this->cdn->upload_file( $this->data->cdn->access_key, $this->data->cdn->pull_zone->name, $error_relative_path, $error_file_path );
			}
		}
	}
}
