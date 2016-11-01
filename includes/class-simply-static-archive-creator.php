<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static archive creator class
 * @package Simply_Static
 */
class Simply_Static_Archive_Creator {

	/** @const */
	public static $processable_status_codes = array(
		200, 301, 302, 303, 307, 308
	);

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static_Options
	 */
	protected $options = null;

	/**
	 * The path to the temporary archive directory
	 * @var string
	 */
	protected $archive_dir = null;

	/**
	 * The datetime string for when we started the archive generation process
	 * @var string
	 */
	protected $archive_start_time = null;

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
	public function __construct() {
		$this->options = Simply_Static_Options::instance();
		$this->archive_dir = $this->options->get_archive_dir();
		$this->archive_start_time = $this->options->get( 'archive_start_time' );
	}

	/**
	 * Fetch and save pages for the static archive
	 * @return array                         ( # pages processed, # pages remaining )
	 */
	public function fetch_pages() {
		$batch_size = 10;

		$static_pages = Simply_Static_Page::query()
			->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
			->limit( $batch_size )
			->find();
		$pages_remaining = Simply_Static_Page::query()
			->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
			->count();
		$total_pages = Simply_Static_Page::query()->count();
		$pages_processed = $total_pages - $pages_remaining;

		while ( $static_page = array_shift( $static_pages ) ) {

			// $filename = $this->get_filename_for_static_page( $static_page );
			$success = Simply_Static_Url_Fetcher::instance()->fetch( $static_page, $this->archive_dir );

			if ( ! $success ) {
				continue;
			}

			// If we get a 30x redirect...
			if ( in_array( $static_page->http_status_code, array( 301, 302, 303, 307, 308 ) ) ) {
				$this->handle_30x_redirect( $static_page );
				continue;
			}

			// Not a 200 for the response code? Move on.
			if ( $static_page->http_status_code != 200 ) {
				continue;
			}

			$this->handle_200_response( $static_page );
		}

		return array( $pages_processed, $total_pages );
	}

	/**
	 * Process the response for a 200 response (success)
	 * @param  Simply_Static_Page         $static_page Record to update
	 * @return void
	 */
	private function handle_200_response( $static_page ) {
		// Fetch all URLs from the page and add them to the queue...
		$extractor = new Simply_Static_Url_Extractor( $static_page );
		$urls = $extractor->extract_and_update_urls();

		foreach ( $urls as $url ) {
			$this->set_url_found_on( $static_page, $url, $this->archive_start_time );
		}

		$sha1 = sha1_file( $this->archive_dir . $static_page->file_path );

		// if the content is identical, move on to the next file
		if ( $static_page->is_content_identical( $sha1 ) ) {
			// continue;
		} else {
			$static_page->set_content_hash( $sha1 );
		}

		$static_page->save();
	}

	/**
	 * Process the response to a 30x redirection
	 * @param  Simply_Static_Page         $static_page Record to update
	 * @return void
	 */
	private function handle_30x_redirect( $static_page ) {
		$origin_url = sist_origin_url();
		$destination_url = $this->options->get( 'destination_scheme' ) . $this->options->get( 'destination_host' );
		$current_url = $static_page->url;
		$redirect_url = $static_page->redirect_url;

		// convert our potentially relative URL to an absolute URL
		$redirect_url = sist_relative_to_absolute_url( $redirect_url, $current_url );

		// WP likes to 301 redirect `/path` to `/path/` -- we want to
		// check for this and just add the trailing slashed version
		if ( $redirect_url === trailingslashit( $current_url ) ) {

			$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );

		// Don't create a redirect page if it's just a redirect from
		// http to https. Instead just add the new url to the queue.
		// TODO: Make this less horrible.
		} else if (
		sist_strip_index_filenames_from_url( sist_remove_params_and_fragment( sist_strip_protocol_from_url( $redirect_url ) ) ) ===
		sist_strip_index_filenames_from_url( sist_remove_params_and_fragment( sist_strip_protocol_from_url( $current_url ) ) ) ) {

			$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );

		} else {

			if ( $redirect_url ) {

				// check if this is a local URL
				if ( sist_is_local_url( $redirect_url ) ) {

					$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );
					// and update the URL
					$redirect_url = str_replace( $origin_url, $destination_url, $redirect_url );

				}

				$view = new Simply_Static_View();

				$content = $view->set_template( 'redirect' )
					->assign( 'redirect_url', $redirect_url )
					->render_to_string();

				$filename = $this->save_static_page_content_to_file( $static_page, $content );
				if ( $filename ) {
					$static_page->file_path = $filename;
				}

				$sha1 = sha1_file( $this->archive_dir . $filename );

				// if the content is identical, move on to the next file
				if ( $static_page->is_content_identical( $sha1 ) ) {
					// continue;
				} else {
					$static_page->set_content_hash( $sha1 );
				}

				$static_page->save();
			}
		}
	}

	/**
	 * Set ID for which page a URL was found on (& create page if not in DB yet)
	 *
	 * Given a URL, find the associated Simply_Static_Page, and then set the ID
	 * for which page it was found on if the ID isn't yet set or if the record
	 * hasn't been updated in this instance of static generation yet.
	 * @param Simply_Static_Page $static_page The record for the parent page
	 * @param string             $child_url   The URL of the child page
	 * @param string             $start_time  Static generation start time
	 * @return void
	 */
	private function set_url_found_on( $static_page, $child_url, $start_time ) {
		$child_static_page = Simply_Static_Page::query()
			->find_or_create_by( 'url' , $child_url );
		if ( $child_static_page->found_on_id === null || $child_static_page->updated_at < $start_time ) {
			$child_static_page->found_on_id = $static_page->id;
			$child_static_page->save();
		}
	}

	/**
	 * Create a ZIP file using the archive directory
	 * @return string|WP_Error $temporary_zip The path to the archive zip file
	 */
	public function create_zip() {
		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		$zip_filename = untrailingslashit( $this->archive_dir ) . '.zip';
		$zip_archive = new PclZip( $zip_filename );

		$files = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->archive_dir, RecursiveDirectoryIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file_name => $file_object ) {
			$files[] = realpath( $file_name );
		}

		if ( $zip_archive->create( $files, PCLZIP_OPT_REMOVE_PATH, $this->archive_dir ) === 0 ) {
			return new WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', 'simply-static' ) );
		}

		$download_url = get_admin_url( null, 'admin.php' ) . '?' . Simply_Static::SLUG . '_zip_download=' . basename( $zip_filename );

		return $download_url;
	}

	/**
	* Copy temporary static files to a local directory
	* @param  string $destination_dir The directory to put the files
	* @return array (# pages processed, # pages remaining)
	*/
	public function copy_static_files( $destination_dir ) {
		$batch_size = 100;

		// TODO: also check for recent modification time
		// last_modified_at > ? AND
		$static_pages = Simply_Static_Page::query()
			->where( "file_path IS NOT NULL" )
			->where( "file_path != ''" )
			->where( "( last_transferred_at < ? OR last_transferred_at IS NULL )", $this->archive_start_time )
			->limit( $batch_size )
			->find();
		$pages_remaining = count( $static_pages );
		$total_pages = Simply_Static_Page::query()
			->where( "file_path IS NOT NULL AND file_path != ''" )
			->count();
		$pages_processed = $total_pages - $pages_remaining;

		while ( $static_page = array_shift( $static_pages ) ) {
			$path_info = sist_url_path_info( $static_page->file_path );
			$create_dir = wp_mkdir_p( $destination_dir . $path_info['dirname'] );
			if ( $create_dir === false ) {
				$static_page->set_error_message( 'Unable to create destination directory' );
			} else {
				$origin_file_path = $this->archive_dir . $static_page->file_path;
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

	/**
	 * Delete temporary, generated static files
	 * @return true|WP_Error True on success, WP_Error otherwise
	 */
	public function delete_temp_static_files() {
		$directory_iterator = new RecursiveDirectoryIterator( $this->archive_dir, FilesystemIterator::SKIP_DOTS );
		$recursive_iterator = new RecursiveIteratorIterator( $directory_iterator, RecursiveIteratorIterator::CHILD_FIRST );

		// recurse through the entire directory and delete all files / subdirectories
		foreach ( $recursive_iterator as $item ) {
			$success = $item->isDir() ? rmdir( $item ) : unlink( $item );
			if ( ! $success ) {
				return new WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $item ) );
			}
		}

		// must make sure to delete the original directory at the end
		$success = rmdir( $this->archive_dir );
		if ( ! $success ) {
			return new WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $item ) );
		}

		return true;
	}

	/**
	 * Save the contents of a page to a file in our archive directory
	 * @param Simply_Static_Page $static_page The Simply_Static_Page record
	 * @param string             $content The content of the page we want to save
	 * @return string|null                The file path of the saved file
	 */
	protected function save_static_page_content_to_file( $static_page, $content ) {
		$relative_filename = Simply_Static_Url_Fetcher::instance()->create_directories_for_static_page( $static_page );

		if ( $relative_filename ) {
			$file_path = $this->archive_dir . $relative_filename;

			$write = file_put_contents( $file_path, $content );
			if ( $write === false ) {
				$static_page->set_error_message( 'Unable to write temporary file' );
			} else {
				return $relative_filename;
			}
		} else {
			return null;
		}
	}

	/**
	 * Ensure the Origin URL and user-specified Additional URLs are in the DB
	 * @return void
	 */
	public static function add_origin_and_additional_urls_to_db( $additional_urls ) {
		$urls = array_unique( array_merge(
			array( trailingslashit( sist_origin_url() ) ),
			sist_string_to_array( $additional_urls )
		) );
		foreach ( $urls as $url ) {
			$static_page = Simply_Static_Page::query()
				->find_or_initialize_by( 'url', $url );
			// setting to 0 for "not found anywhere" since it's either the origin
			// or something the user specified
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}

	/**
	 * Convert Additional Files/Directories to URLs and add them to the database
	 * @return void
	 */
	public static function add_additional_files_to_db( $additional_files ) {
		// Convert additional files to URLs and add to queue
		foreach ( sist_string_to_array( $additional_files ) as $item ) {

			// If item is a file, convert to url and insert into database.
			// If item is a directory, recursively iterate and grab all files,
			// and for each file, convert to url and insert into database.
			if ( file_exists( $item ) ) {
				if ( is_file( $item ) ) {
					$url = self::convert_path_to_url( $item );
					$static_page = Simply_Static_Page::query()
						->find_or_create_by( 'url', $url );
					// setting found_on_id to 0 since this was user-specified
					$static_page->found_on_id = 0;
					$static_page->save();
				} else {
					$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $item, RecursiveDirectoryIterator::SKIP_DOTS ) );

					foreach ( $iterator as $file_name => $file_object ) {
						$url = self::convert_path_to_url( $file_name );
						$static_page = Simply_Static_Page::query()
							->find_or_initialize_by( 'url', $url );
						$static_page->found_on_id = 0;
						$static_page->save();
					}
				}
			}
		}
	}

	/**
	 * Convert a directory path into a valid WordPress URL
	 * @param  string $path The path to a directory or a file
	 * @return string       The WordPress URL for the given path
	 */
	private static function convert_path_to_url( $path ) {
		$url = $path;
		if ( stripos( $path, WP_PLUGIN_DIR ) === 0 ) {
			$url = str_replace( WP_PLUGIN_DIR, WP_PLUGIN_URL, $path );
		} elseif ( stripos( $path, WP_CONTENT_DIR ) === 0 ) {
			$url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );
		} elseif ( stripos( $path, get_home_path() ) === 0 ) {
			$url = str_replace( get_home_path(), sist_origin_url(), $path );
		}
		return $url;
	}
}
