<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL fetcher class
 *
 * @package Simply_Static
 */
class Simply_Static_Archive_Creator {

	/**
	 * The path to the archive directory
	 * @var string
	 */
	protected $archive_dir = null;

	/**
	 * The slug (id) used for the plugin
	 * @var string
	 */
	protected $slug = null;

	/**
	 * The protocol used for the destination URL
	 * @var string
	 */
	protected $destination_scheme = null;

	/**
	 * The host for the destination URL
	 * @var string
	 */
	protected $destination_host = null;

	/**
	 * The directory where static files should be saved
	 * @var string
	 */
	protected $temp_files_dir = null;

	/**
	 * Additional urls to add to the archive
	 * @var string
	 */
	protected $additional_urls = null;

	/**
	 * Additional files to add to the archive
	 * @var string
	 */
	protected $additional_files = null;

	/**
	 * The datetime string for when we started the archive generation process
	 * @var string
	 */
	protected $archive_start_time = null;

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
	public function __construct( $slug, $destination_scheme, $destination_host, $temp_files_dir, $additional_urls, $additional_files ) {
		$this->slug = $slug;
		$this->destination_scheme = $destination_scheme;
		$this->destination_host = $destination_host;
		$this->temp_files_dir = $temp_files_dir;
		$this->additional_urls = $additional_urls;
		$this->additional_files = $additional_files;
	}

	/**
	 * Create a static version of the site
	 * @return void
	 */
	public function create_archive() {
		// TODO: Do ajax calls instead of just running forever and ever
		set_time_limit(0);

		$this->create_archive_dir();

		$this->add_origin_and_additional_urls_to_db();
		$this->add_additional_files_to_db();

		$this->archive_start_time = sist_formatted_datetime();

		while ( $static_pages = Simply_Static_Page::where( 'last_checked_at < ? OR last_checked_at IS NULL LIMIT 10', $this->archive_start_time ) ) {
			while ( $static_page = array_shift( $static_pages ) ) {

				$current_url = $static_page->url;

				$response = Simply_Static_Url_Fetcher::fetch( $current_url );

				// If we get a WP_Error then somehow our request failed (e.g. space in URL)
				if ( is_wp_error( $response ) ) {
					continue;
				}

				$data = array(
					'http_status_code' => $response->code
				);
				$static_page->http_status_code = $response->code;
				$static_page->last_checked_at = sist_formatted_datetime();
				$static_page->save();

				// If we get a 30x redirect...
				if ( in_array( $response->code, array( 301, 302, 303, 307 ) ) ) {
					$this->handle_30x_redirect( $static_page, $response );
					continue;
				}

				// Not a 200 for the response code? Move on.
				if ( $response->code != 200 ) {
					continue;
				}

				$this->handle_200_response( $static_page, $response );
			}
		}
	}

	/**
	 * Process the response for a 200 response (success)
	 * @param  Simply_Static_Page         $static_page Record to update
	 * @param  Simply_Static_Url_Response $response    URL response to process
	 * @return void
	 */
	private function handle_200_response( $static_page, $response ) {
		$content = $response->body;

		// // if the content is identical, move on to the next file
		// if ( $static_page->is_content_identical( $content ) ) {
		// 	continue;
		// }

		// Fetch all URLs from the page and add them to the queue...
		$urls = $response->extract_urls();

		foreach ( $urls as $url ) {
			$this->set_url_found_on( $static_page, $url, $this->archive_start_time );
		}

		// Replace the origin URL with the destination URL within the content
		$response->replace_urls( $this->destination_scheme, $this->destination_host );

		// Save the page to our archive
		$file_path = $this->save_url_to_file( $response->url, $content, $response->is_html() );
		if ( $file_path ) {
			$static_page->file_path = $file_path;
		}

		$static_page->set_content_hash( $content );
		$static_page->save();
	}

	/**
	 * Process the response to a 30x redirection
	 * @param  Simply_Static_Page         $static_page Record to update
	 * @param  Simply_Static_Url_Response $response    URL response to process
	 * @return void
	 */
	private function handle_30x_redirect( $static_page, $response ) {
		$origin_url = sist_origin_url();
		$destination_url = $this->destination_scheme . '://' . $this->destination_host;
		$current_url = $static_page->url;
		$redirect_url = $response->get_redirect_url();

		// WP likes to 301 redirect `/path` to `/path/` -- we want to
		// check for this and just add the trailing slashed version
		if ( $redirect_url === trailingslashit( $current_url ) ) {

			$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );

		} else {

			/// convert our potentially relative URL to an absolute URL
			$redirect_url = sist_relative_to_absolute_url( $redirect_url, $current_url );

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

				// // if the content is identical, move on to the next file
				// if ( $static_page->is_content_identical( $content ) ) {
				// 	continue;
				// }

				$file_path = $this->save_url_to_file( $response->url, $content, $response->is_html() );
				if ( $file_path ) {
					$static_page->file_path = $file_path;
				}

				$static_page->set_content_hash( $content );

				$static_page->save();
			}
		}
	}

	/**
	 * Create the temporary archive directory
	 * @return void
	 */
	private function create_archive_dir() {
		global $blog_id;

		// Create archive directory
		$current_user = wp_get_current_user();
		$archive_name = join( '-', array( $this->slug, $blog_id, time(), $current_user->user_login ) );
		$this->archive_dir = trailingslashit( $this->temp_files_dir . $archive_name );

		if ( ! file_exists( $this->archive_dir ) ) {
			wp_mkdir_p( $this->archive_dir );
		}
	}

	/**
	 * Ensure the Origin URL and user-specified Additional URLs are in the DB
	 * @return void
	 */
	private function add_origin_and_additional_urls_to_db() {
		$urls = array_unique( array_merge(
			array( trailingslashit( sist_origin_url() ) ),
			sist_string_to_array( $this->additional_urls )
		) );
		foreach ( $urls as $url ) {
			$static_page = Simply_Static_Page::find_or_initialize_by( 'url', $url );
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
	private function add_additional_files_to_db() {
		// Convert additional files to URLs and add to queue
		foreach ( sist_string_to_array( $this->additional_files ) as $item ) {

			// If item is a file, convert to url and insert into database.
			// If item is a directory, recursively iterate and grab all files,
			// and for each file, convert to url and insert into database.
			if ( file_exists( $item ) ) {
				if ( is_file( $item ) ) {
					$url = $this->convert_path_to_url( $item );
					$static_page = Simply_Static_Page::find_or_create_by( 'url', $url );
					// setting found_on_id to 0 since this was user-specified
					$static_page->found_on_id = 0;
					$static_page->save();
				} else {
					$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $item, RecursiveDirectoryIterator::SKIP_DOTS ) );

					foreach ( $iterator as $file_name => $file_object ) {
						$url = $this->convert_path_to_url( $file_name );
						$static_page = Simply_Static_Page::find_or_initialize_by( 'url', $url );
						$static_page->found_on_id = 0;
						$static_page->save();
					}
				}
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
		$child_static_page = Simply_Static_Page::find_or_create_by( 'url' , $child_url );
		if ( $child_static_page->found_on_id === null || $child_static_page->updated_at < $start_time ) {
			$child_static_page->found_on_id = $static_page->id;
			$child_static_page->save();
		}
	}

	/**
	 * Convert a directory path into a valid WordPress URL
	 * @param  string $path The path to a directory or a file
	 * @return string       The WordPress URL for the given path
	 */
	private function convert_path_to_url( $path ) {
		$url = $path;
		if ( stripos( $path, WP_PLUGIN_DIR ) === 0 ) {
			$url = str_replace( WP_PLUGIN_DIR, trailingslashit(WP_PLUGIN_URL ), $path );
		} elseif ( stripos( $path, WP_CONTENT_DIR ) === 0 ) {
			$url = str_replace( WP_CONTENT_DIR, trailingslashit( WP_CONTENT_URL ), $path );
		} elseif ( stripos( $path, get_home_path() ) === 0 ) {
			$url = str_replace( get_home_path(), trailingslashit( sist_origin_url() ), $path );
		}
		return $url;
	}

	/**
	 * Create a ZIP file using the archive directory
	 * @return string|WP_Error $temporary_zip The path to the archive zip file
	 */
	public function create_zip() {
		$zip_filename = untrailingslashit( $this->archive_dir ) . '.zip';
		$zip_archive = new PclZip($zip_filename);

		$files = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->archive_dir, RecursiveDirectoryIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file_name => $file_object ) {
			$files[] = realpath( $file_name );
		}

		if ( $zip_archive->create( $files, PCLZIP_OPT_REMOVE_PATH, $this->archive_dir ) === 0 ) {
			return new WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', $this->slug ) );
		}

		$download_url = get_admin_url( null, 'admin.php' ) . '?' . $this->slug . '_zip_download=' . basename( $zip_filename );

		return $download_url;
	}

	/**
	* Copy temporary static files to a local directory
	* @return boolean|WP_Error
	*/
	public function copy_static_files( $local_dir ) {
		$directory_iterator = new RecursiveDirectoryIterator( $this->archive_dir, RecursiveDirectoryIterator::SKIP_DOTS );
		$recursive_iterator = new RecursiveIteratorIterator( $directory_iterator, RecursiveIteratorIterator::SELF_FIRST );

		foreach ( $recursive_iterator as $item ) {
			$path = $local_dir . $recursive_iterator->getSubPathName();
			$success = $item->isDir() ? wp_mkdir_p( $path ) : copy( $item, $path );
			if ( ! $success ) {
				return new WP_Error( 'cannot_create_file_or_dir', sprintf( __( "Could not create file or directory: %s", $this->slug ), $path ) );
			}
		}

		return true;
	}

	/**
	 * Delete temporary, generated static files
	 * @param $archive_dir The archive directory path
	 * @return boolean|WP_Error
	 */
	public function delete_temp_static_files() {
		$directory_iterator = new RecursiveDirectoryIterator( $this->archive_dir, FilesystemIterator::SKIP_DOTS );
		$recursive_iterator = new RecursiveIteratorIterator( $directory_iterator, RecursiveIteratorIterator::CHILD_FIRST );

		// recurse through the entire directory and delete all files / subdirectories
		foreach ( $recursive_iterator as $item ) {
			$success = $item->isDir() ? rmdir( $item ) : unlink( $item );
			if ( ! $success ) {
				return new WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", $this->slug ), $item ) );
			}
		}

		// must make sure to delete the original directory at the end
		$success = rmdir( $this->archive_dir );
		if ( ! $success ) {
			return new WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", $this->slug ), $item ) );
		}

		return true;
	}

	/**
	 * Save the contents of a page to a file in our archive directory
	 * @param string        $url     The URL for the content
	 * @param string        $content The content of the page we want to save
	 * @param boolean       $is_html Is this an html page?
	 * @return string|false $success The path of the file, or false if failed to save
	 */
	protected function save_url_to_file( $url, $content, $is_html ) {
		$url_parts = parse_url( $url );
		// a domain with no trailing slash has no path, so we're giving it one
		$path = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';

		$origin_path_length = strlen( parse_url( sist_origin_url(), PHP_URL_PATH ) );
		if ( $origin_path_length > 1 ) { // prevents removal of '/'
			$path = substr( $path, $origin_path_length );
		}

		$path_info = pathinfo( $path && $path != '/' ? $path : 'index.html' );

		// Create file directory if it doesn't exist
		$file_dir = $this->archive_dir . ( $path_info['dirname'] ? $path_info['dirname'] : '' );
		if ( empty( $path_info['extension'] ) && $path_info['basename'] == $path_info['filename'] ) {
			$file_dir .= DIRECTORY_SEPARATOR . $path_info['basename'];
			$path_info['filename'] = 'index';
		}
		if ( ! file_exists( $file_dir ) ) {
			wp_mkdir_p( $file_dir );
		}

		// Save file contents
		$file_extension = ( $is_html || ! isset( $path_info['extension'] ) ) ? 'html' : $path_info['extension'];
		$file_name = $file_dir . DIRECTORY_SEPARATOR . $path_info['filename'] . '.' . $file_extension;
		$success = file_put_contents( $file_name, $content );
		return $success ? $file_name : false;
	}
}
