<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL fetcher class
 *
 * @package Simply_Static
 */
class Simply_Static_Archive_Creator {

	/**
	 * The path to the archive directory
	 *
	 * @var string
	 */
	protected $archive_dir;

	/**
	 * Export log (list of processed urls)
	 *
	 * @var array
	 */
	protected $export_log = array();

	/**
	 * The slug (id) used for the plugin
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * The protocol used for the destination URL
	 *
	 * @var string
	 */
	protected $destination_scheme;

	/**
	 * The host for the destination URL
	 *
	 * @var string
	 */
	protected $destination_host;

	/**
	 * The directory where static files should be saved
	 *
	 * @var string
	 */
	protected $temp_files_dir;

	/**
	 * Additional urls to add to the archive
	 *
	 * @var string
	 */
	protected $additional_urls;

	/**
	 * Constructor
	 *
	 * @param string $url URI resource
	 */
	public function __construct( $slug, $destination_scheme, $destination_host, $temp_files_dir, $additional_urls ) {
		$this->slug = $slug;
		$this->destination_scheme = $destination_scheme;
		$this->destination_host = $destination_host;
		$this->temp_files_dir = $temp_files_dir;
		$this->additional_urls = $additional_urls;
	}

	/**
	 * Get the list of URLs in the archive
	 *
	 * @param array $export_log URLs that were successfully added to the archive
	 */
	public function get_export_log() {
		return $this->export_log;
	}

	/**
	 * Create a static version of the site
	 *
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
		$origin_url = sist_home_url();
		$destination_url = $this->destination_scheme . '://' . $this->destination_host;
		$origin_path_length = strlen( parse_url( $origin_url, PHP_URL_PATH ) );
		$urls_queue = array_unique( array_merge(
			array( trailingslashit( $origin_url ) ),
			// using preg_split to intelligently break at newlines
			// see: http://stackoverflow.com/questions/1483497/how-to-put-string-in-array-split-by-new-line
			preg_split( "/\r\n|\n|\r/", $this->additional_urls )
		) );

		while ( count( $urls_queue ) ) {
			$current_url = array_shift( $urls_queue );

			$response = Simply_Static_Url_Fetcher::fetch( $current_url );

			// If we get a WP_Error then somehow our request failed (e.g. space in URL)
			// TODO: Keep a queue of failed urls too
			if ( is_wp_error( $response ) ) {
				continue;
			}

			$url_parts = parse_url( $response->url );
			// TODO: This could throw an `Undefined index` error on URLs without
			// a path, e.g. http://www.example.com (no trailing slash)
			$path = $url_parts['path'];
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

					$urls_queue = $this->add_url_to_queue( $urls_queue, $redirect_url );

				} else {

					/// convert our potentially relative URL to an absolute URL
					$redirect_url = sist_relative_to_absolute_url( $redirect_url, $current_url );

					if ( $redirect_url ) {

						// check if this is a local URL
						if ( sist_is_local_url( $redirect_url ) ) {

							// add the redirected page to the queue
							$urls_queue = $this->add_url_to_queue( $urls_queue, $redirect_url );
							// and update the URL
							$redirect_url = str_replace( $origin_url, $destination_url, $redirect_url );

						}

						$view = new Simply_Static_View();

						$content = $view->set_template( 'redirect' )
							->assign( 'redirect_url', $redirect_url )
							->render_to_string();

						$this->save_url_to_file( $path, $content, $is_html );

						$this->export_log[] = $current_url;

					}
				}

				continue;
			}

			// Not a 200 for the response code? Move on.
			// TODO: Keep a queue of failed urls too
			if ( $response->code != 200 ) {
				continue;
			}

			$this->export_log[] = $current_url;

			// Fetch all URLs from the page and add them to the queue...
			$urls = $response->extract_urls();
			foreach ( $urls as $url ) {
				$urls_queue = $this->add_url_to_queue( $urls_queue, $url );
			}

			// Replace the origin URL with the destination URL
			$response->replace_url( $origin_url, $destination_url );

			// Save the page to our archive
			$content = $response->body;
			$this->save_url_to_file( $path, $content, $is_html );
		}
	}

	/**
	 * Add a URL to the processing queue if we haven't already processed it and
	 * if it's not in the queue to be processed
	 *
	 * @param array  $urls_queue Queue of URLs to be processed
	 * @param string $url        URL to add to the processing queue
	 * @return array             Queue of URLs to be processed
	 */
	private function add_url_to_queue( $urls_queue, $url ) {
		if ( ! in_array( $url, $this->export_log ) && ! in_array( $url, $urls_queue ) ) {
			$urls_queue[] = $url;
		}

		return $urls_queue;
	}

	/**
	 * Create a ZIP file using the archive directory
	 *
	 * @return string|WP_Error $temporary_zip The path to the archive zip file
	 */
	public function create_zip() {
		$temporary_zip = untrailingslashit( $this->archive_dir ) . '.tmp';
		$zip_archive = new ZipArchive();

		if ( $zip_archive->open( $temporary_zip, ZIPARCHIVE::CREATE ) !== true ) {
			return new WP_Error( 'create_zip_failed', __( 'Unable to create ZIP archive', $this->slug ) );
		}

		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->archive_dir ) );
		foreach ( $iterator as $file_name => $file_object ) {
			$base_name = basename( $file_name );

			if ( $base_name != '.' && $base_name != '..' ) {
				if ( ! $zip_archive->addFile( realpath( $file_name ), str_replace( $this->archive_dir, '', $file_name ) ) ) {
					return new WP_Error( 'cannot_add_file_to_zip', sprintf( __( "Could not add file: %s", $this->slug ), $file_name ) );
				}
			}
		}

		$zip_archive->close();
		$zip_file = untrailingslashit( $this->archive_dir ) . '.zip';
		rename( $temporary_zip, $zip_file );

		$download_url = get_admin_url( null, 'admin.php' ) . '?download=' . basename( $zip_file );

		return $download_url;
	}

	/**
	* Copy static files to a local directory
	*
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
	 *
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
	 *
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
			$file_dir .= '/' . $path_info['basename'];
			$path_info['filename'] = 'index';
		}
		if ( ! file_exists( $file_dir ) ) {
			wp_mkdir_p( $file_dir );
		}

		// Save file contents
		$file_extension = ( $is_html || ! isset( $path_info['extension'] ) ) ? 'html' : $path_info['extension'];
		$file_name = $file_dir . '/' . $path_info['filename'] . '.' . $file_extension;
		$success = file_put_contents( $file_name, $content );
		return $success;
	}
}
