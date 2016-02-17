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
	protected $archive_dir;

	/**
	 * Export log (processed urls, http/error codes, and source URLs)
	 * @var Simply_Static_Export_Log
	 */
	public $export_log = null;

	/**
	 * The slug (id) used for the plugin
	 * @var string
	 */
	protected $slug;

	/**
	 * The protocol used for the destination URL
	 * @var string
	 */
	protected $destination_scheme;

	/**
	 * The host for the destination URL
	 * @var string
	 */
	protected $destination_host;

	/**
	 * The directory where static files should be saved
	 * @var string
	 */
	protected $temp_files_dir;

	/**
	 * Additional urls to add to the archive
	 * @var string
	 */
	protected $additional_urls;

	/**
	 * Additional files to add to the archive
	 * @var string
	 */
	protected $additional_files;

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
		$this->export_log = new Simply_Static_Export_Log();
	}

	/**
	 * Create a static version of the site
	 * @return void
	 */
	public function create_archive() {
		global $blog_id;
		// TODO: Do ajax calls instead of just running forever and ever
		set_time_limit(0);

		// Create archive directory
		$current_user = wp_get_current_user();
		$archive_name = join( '-', array( $this->slug, $blog_id, time(), $current_user->user_login ) );
		$this->archive_dir = trailingslashit( $this->temp_files_dir . $archive_name );

		if ( ! file_exists( $this->archive_dir ) ) {
			wp_mkdir_p( $this->archive_dir );
		}

		// Add URLs to queue
		$origin_url = sist_origin_url();
		$destination_url = $this->destination_scheme . '://' . $this->destination_host;
		$origin_path_length = strlen( parse_url( $origin_url, PHP_URL_PATH ) );
		$urls_queue = array_unique( array_merge(
			array( trailingslashit( $origin_url ) ),
			// using preg_split to intelligently break at newlines
			// see: http://stackoverflow.com/questions/1483497/how-to-put-string-in-array-split-by-new-line
			sist_string_to_array( $this->additional_urls )
		) );

		// Convert additional files to URLs and add to queue
		foreach ( sist_string_to_array( $this->additional_files ) as $item ) {

			// if file is a file, convert to url and add to queue
			// if file is a directory, recursively iterate and grab all files, for each file, convert to url
			if ( file_exists( $item ) ) {
				if ( is_file( $item ) ) {
					$url = $this->convert_path_to_url( $item );
					$urls_queue = $this->add_url_to_queue( $url, $urls_queue );
				} else {
					$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $item, RecursiveDirectoryIterator::SKIP_DOTS ) );

					foreach ( $iterator as $file_name => $file_object ) {
						$url = $this->convert_path_to_url( $file_name );
						$urls_queue = $this->add_url_to_queue( $url, $urls_queue );
					}
				}
			}
		}

		while ( count( $urls_queue ) ) {
			$current_url = array_shift( $urls_queue );

			$response = Simply_Static_Url_Fetcher::fetch( $current_url );

			// If we get a WP_Error then somehow our request failed (e.g. space in URL)
			if ( is_wp_error( $response ) ) {
				continue;
			}

			$data = array(
				'http_status_code' => $response->code
			);
			$static_file = Simply_Static_File::find_or_initialize_by( 'url', $current_url );
			$static_file->save();

			$this->export_log->set_response_code( $current_url, $response->code );

			$url_parts = parse_url( $response->url );
			// a domain with no trailing slash has no path, so we're giving it one
			$path = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';
			if ( $origin_path_length > 1 ) { // prevents removal of '/'
				$path = substr( $path, $origin_path_length );
			}

			$is_html = $response->is_html();

			// If we get a 30x redirect...
			if ( in_array( $response->code, array( 301, 302, 303, 307 ) ) ) {

				$redirect_url = $response->get_redirect_url();

				// WP likes to 301 redirect `/path` to `/path/` -- we want to
				// check for this and just add the trailing slashed version
				if ( $redirect_url === trailingslashit( $current_url ) ) {

					$urls_queue = $this->add_url_to_queue( $redirect_url, $urls_queue );

				} else {

					/// convert our potentially relative URL to an absolute URL
					$redirect_url = sist_relative_to_absolute_url( $redirect_url, $current_url );

					if ( $redirect_url ) {

						// check if this is a local URL
						if ( sist_is_local_url( $redirect_url ) ) {

							// add the redirected page to the queue
							$urls_queue = $this->add_url_to_queue( $redirect_url, $urls_queue );
							$this->export_log->set_source_url( $redirect_url, $current_url );
							// and update the URL
							$redirect_url = str_replace( $origin_url, $destination_url, $redirect_url );

						}

						$view = new Simply_Static_View();

						$content = $view->set_template( 'redirect' )
							->assign( 'redirect_url', $redirect_url )
							->render_to_string();

						$this->save_url_to_file( $path, $content, $is_html );
					}
				}

				continue;
			}

			// Not a 200 for the response code? Move on.
			if ( $response->code != 200 ) {
				continue;
			}

			// Fetch all URLs from the page and add them to the queue...
			$urls = $response->extract_urls();

			foreach ( $urls as $url ) {
				$urls_queue = $this->add_url_to_queue( $url, $urls_queue );
				$this->export_log->set_source_url( $url, $current_url );
			}

			// Replace the origin URL with the destination URL within the content
			$response->replace_urls( $destination_url );

			// Save the page to our archive
			$content = $response->body;
			$this->save_url_to_file( $path, $content, $is_html );
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
	 * Add a URL to the processing queue
	 *
	 * (if we haven't already processed it and if it's not in the queue to be
	 * processed)
	 * 
	 * @param string $url        URL to add to the processing queue
	 * @param array  $urls_queue Queue of URLs to be processed
	 * @return array             Queue of URLs to be processed
	 */
	private function add_url_to_queue( $url, $queue ) {
		if ( ! $this->export_log->includes( $url ) && ! in_array( $url, $queue ) ) {
			$queue[] = $url;
		}

		return $queue;
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
	* Copy static files to a local directory
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
	 * Delete generated static files
	 * @param $archive_dir The archive directory path
	 * @return boolean|WP_Error
	 */
	public function delete_static_files() {
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
	 * @param string $path The relative path for the URL to save
	 * @param string $content The contents of the page we want to save
	 * @param boolean $is_html Is this an html page?
	 * @return boolean $success Did we successfully save the file?
	 */
	protected function save_url_to_file( $path, $content, $is_html ) {
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
		return $success;
	}
}
