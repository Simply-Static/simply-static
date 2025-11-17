<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static URL fetcher class
 */
class Url_Fetcher {
	/**
	 * Timeout for fetching URLs
	 * @var string
	 */
	const TIMEOUT = 30;

	/**
	 * Singleton instance
	 * @var Simply_Static\Url_Fetcher
	 */
	protected static $instance = null;

	/**
	 * Directory to save the body of the URL to
	 * @var string
	 */
	protected $archive_dir = null;

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Return an instance of Simply_Static\Url_Fetcher
	 * @return Simply_Static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance              = new self();
			self::$instance->archive_dir = Options::instance()->get_archive_dir();
		}

		return self::$instance;
	}

	/**
	 * Fetch the URL and return a \WP_Error if we get one, otherwise a Response class.
	 *
	 * @param \Simply_Static\Page $static_page URL to fetch
	 *
	 * @return boolean                        Was the fetch successful?
	 */
	public function fetch( Page $static_page, $prepare_url = true ) {
		$url = $static_page->url;

		// Windows support.
		$url = Util::normalize_slashes( $url );

		$static_page->last_checked_at = Util::formatted_datetime();

		// Don't process URLs that don't match the URL of this WordPress installation
		if ( ! Util::is_local_url( $url ) ) {
			Util::debug_log( "Not fetching URL because it is not a local URL" );
			$static_page->http_status_code = null;
			$message                       = sprintf( __( "An error occurred: %s", 'simply-static' ), __( "Attempted to fetch a remote URL", 'simply-static' ) );
			$static_page->set_error_message( $message );
			$static_page->save();

			return false;
		}

		$temp_filename = wp_tempnam();

		Util::debug_log( "Fetching URL and saving it to: " . $temp_filename );

		// Check if the URL is a local asset (file) that we can copy directly
		// We do this check before prepare_url to avoid query parameters interfering with extension detection
		$original_url   = $url;
		$is_local_asset = Util::is_local_asset_url( $original_url );

		Util::debug_log( "URL: " . $original_url . " - Is local asset: " . ( $is_local_asset ? 'Yes' : 'No' ) );

		if ( $prepare_url ) {
			$url = $static_page->get_handler()->prepare_url( $url );
		}

		// Check if the URL is a local asset (file) that we can copy directly
		if ( $is_local_asset ) {
			// Get the local path for the URL using the original URL without query parameters
			$local_path = Util::get_path_from_local_url( $original_url );
			$file_path  = ABSPATH . ltrim( $local_path, '/' );

			Util::debug_log( "Local path: " . $local_path . " - Full file path: " . $file_path );

			// Check if the file exists
			if ( file_exists( $file_path ) ) {
				Util::debug_log( "Copying local file directly: " . $file_path );

				// Copy the file to the temporary location
				if ( copy( $file_path, $temp_filename ) ) {
					// Create a response-like array to match what remote_get would return
					$response = array(
						'response' => array(
							'code' => 200
						),
						'headers'  => array(
							'content-type' => $this->detect_mime_type( $file_path, $original_url )
						)
					);
				} else {
					// If copy fails, fall back to remote_get
					Util::debug_log( "Failed to copy local file, falling back to remote_get" );
					$response = self::remote_get( $url, $temp_filename );
				}
			} else {
				// If file doesn't exist, fall back to remote_get
				Util::debug_log( "Local file not found, falling back to remote_get" );
				$response = self::remote_get( $url, $temp_filename );
			}
		} else {
			// Not a local asset, use remote_get as before
			$response = self::remote_get( $url, $temp_filename );
		}

		$filesize = file_exists( $temp_filename ) ? filesize( $temp_filename ) : 0;
		Util::debug_log( "Filesize: " . $filesize . ' bytes' );

		// Fallback: Sometimes streamed requests create an empty file despite 200 OK.
		// If we got 200 but the file is empty, try alternative strategies to populate it.
		if ( ! is_wp_error( $response ) ) {
			$code = isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
			if ( $code === 200 && $filesize === 0 ) {
				Util::debug_log( 'Streamed file is empty after 200 response; attempting fallback to recover content.' );
				$recovered = false;
				// Attempt 1: If it is a local asset, try copying directly from disk again.
				if ( isset( $is_local_asset ) && $is_local_asset ) {
					$local_path = Util::get_path_from_local_url( $original_url );
					$file_path  = ABSPATH . ltrim( $local_path, '/' );
					if ( file_exists( $file_path ) && is_readable( $file_path ) ) {
						$recovered = copy( $file_path, $temp_filename );
						if ( $recovered ) {
							Util::debug_log( 'Recovered by copying local asset from disk.' );
							$filesize = filesize( $temp_filename );
						}
					}
				}
				// Attempt 2: Do a non-streamed request and write body manually.
				if ( ! $recovered ) {
					$alt_args          = array(
						'timeout'     => self::TIMEOUT,
						'user-agent'  => 'Simply Static/' . SIMPLY_STATIC_VERSION,
						'sslverify'   => false,
						'redirection' => 0,
						'blocking'    => true,
					);
					$basic_auth_digest = base64_encode( Options::instance()->get( 'http_basic_auth_username' ) . ':' . Options::instance()->get( 'http_basic_auth_password' ) );
					if ( $basic_auth_digest ) {
						$alt_args['headers'] = array( 'Authorization' => 'Basic ' . $basic_auth_digest );
					}
					$alt_resp = wp_remote_get( $url, apply_filters( 'ss_remote_get_args', $alt_args ) );
					if ( ! is_wp_error( $alt_resp ) ) {
						$body = wp_remote_retrieve_body( $alt_resp );
						if ( strlen( $body ) > 0 ) {
							file_put_contents( $temp_filename, $body );
							$filesize  = filesize( $temp_filename );
							$response  = $alt_resp; // use headers from non-streamed response
							$recovered = true;
							Util::debug_log( 'Recovered by non-streamed request. Size: ' . $filesize . ' bytes' );
						}
					}
				}
			}
		}

		if ( is_wp_error( $response ) ) {
			Util::debug_log( "We encountered an error when fetching: " . $response->get_error_message() );
			Util::debug_log( $response );
			$static_page->http_status_code = null;
			$message                       = sprintf( __( "An error occurred: %s", 'simply-static' ), $response->get_error_message() );
			$static_page->set_error_message( $message );
			$static_page->save();

			return false;
		} else {
			$static_page->http_status_code = $response['response']['code'];

			// Check if this is a JavaScript or CSS file based on the URL extension
			$path_info = Util::url_path_info( $static_page->url );
			if ( isset( $path_info['extension'] ) && $path_info['extension'] === 'js' ) {
				// Force the correct MIME type for JavaScript files
				$static_page->content_type = 'application/javascript';
			} elseif ( isset( $path_info['extension'] ) && $path_info['extension'] === 'css' ) {
				// Force the correct MIME type for CSS files
				$static_page->content_type = 'text/css';
			} else {
				// Use the content type from the response headers
				$static_page->content_type = $response['headers']['content-type'];
			}

			$static_page->redirect_url = isset( $response['headers']['location'] ) ? $response['headers']['location'] : null;

			Util::debug_log( "http_status_code: " . $static_page->http_status_code . " | content_type: " . $static_page->content_type );

			$relative_filename = null;
			if ( $this->can_create_directories_for_page( $static_page ) ) {
				// pclzip doesn't like 0 byte files (fread error), so we're
				// going to fix that by putting a single space into the file
				if ( $filesize === 0 ) {
					file_put_contents( $temp_filename, ' ' );
				}

				$relative_filename = $this->create_directories_for_static_page( $static_page );
			}

			if ( $relative_filename !== null ) {
				$relative_filename      = apply_filters( 'simply_static_relative_filename', $relative_filename, $static_page );
				$static_page->file_path = $relative_filename;
				$file_path              = $this->archive_dir . $relative_filename;

				// Windows support.
				if ( strpos( $file_path, '\/' ) !== false || strpos( $temp_filename, '\/' ) !== false ) {
					$file_path     = str_replace( '\/', '/', $file_path );
					$temp_filename = str_replace( '\/', '/', $temp_filename );
				}

				Util::debug_log( "Renaming temp file from " . $temp_filename . " to " . $file_path );
				rename( $temp_filename, $file_path );
				$static_page->get_handler()->after_file_fetch( $this->archive_dir );
			} else {
				Util::debug_log( "We weren't able to establish a filename; deleting temp file" );
				unlink( $temp_filename );
			}

			$static_page->save();

			return true;
		}
	}

	/**
	 * Detect MIME type for a given local file path with multiple fallbacks.
	 *
	 * Priority:
	 * 1) finfo_file (fileinfo extension)
	 * 2) WordPress wp_check_filetype by extension
	 * 3) Manual extension map
	 *
	 * @param string $file_path Absolute path to the local file
	 * @param string $url Original URL (used to determine extension when needed)
	 *
	 * @return string MIME type
	 */
	protected function detect_mime_type( $file_path, $url ) {
		// 1) Try PHP's fileinfo if available
		if ( function_exists( '\\finfo_open' ) && function_exists( '\\finfo_file' ) && is_readable( $file_path ) ) {
			$fi = \finfo_open( FILEINFO_MIME_TYPE );

			if ( $fi ) {
				$type = \finfo_file( $fi, $file_path );
				\finfo_close( $fi );
				if ( is_string( $type ) && $type !== '' ) {
					return $type;
				}
			}
		}

		// Determine extension from URL or file path
		$ext       = '';
		$path_info = Util::url_path_info( $url );

		if ( isset( $path_info['extension'] ) && $path_info['extension'] ) {
			$ext = strtolower( $path_info['extension'] );
		} else {
			$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		}

		// 2) Try WordPress helper (by extension)
		if ( function_exists( 'wp_check_filetype' ) ) {
			$checked = wp_check_filetype( 'dummy.' . $ext );
			if ( ! empty( $checked['type'] ) ) {
				return $checked['type'];
			}
		}

		// 3) Default manual map. This can be extended via the `ss_mime_type_map` filter.
		$map = array(
			'js'    => 'application/javascript',
			'css'   => 'text/css',
			'json'  => 'application/json',
			'html'  => 'text/html',
			'htm'   => 'text/html',
			'xml'   => 'application/xml',
			'svg'   => 'image/svg+xml',
			'png'   => 'image/png',
			'jpg'   => 'image/jpeg',
			'jpeg'  => 'image/jpeg',
			'gif'   => 'image/gif',
			'webp'  => 'image/webp',
			'avif'  => 'image/avif',
			'heic'  => 'image/heic',
			'tif'   => 'image/tiff',
			'tiff'  => 'image/tiff',
			'bmp'   => 'image/bmp',
			'ico'   => 'image/x-icon',
			'pdf'   => 'application/pdf',
			'zip'   => 'application/zip',
			'gz'    => 'application/gzip',
			'rar'   => 'application/vnd.rar',
			'7z'    => 'application/x-7z-compressed',
			'woff'  => 'font/woff',
			'woff2' => 'font/woff2',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'eot'   => 'application/vnd.ms-fontobject',
		);

		$map = apply_filters( 'ss_mime_type_map', $map, $ext, $file_path, $url );

		if ( $ext && isset( $map[ $ext ] ) ) {
			return $map[ $ext ];
		}

		return 'application/octet-stream';
	}

	/**
	 * @param Page $static_page
	 *
	 * @return boolean
	 */
	protected function can_create_directories_for_page( $static_page ) {
		if ( $static_page->http_status_code == 200 ) {
			return true;
		}

		$page_handler = $static_page->get_handler();
		if ( $static_page->http_status_code === 404 && $page_handler && is_a( $page_handler, Handler_404::class ) ) {
			return true;
		}

		return apply_filters( 'simply_static_can_create_directories_for_page', false, $static_page );
	}

	/**
	 * Given a Static_Page, return a relative filename based on the URL
	 *
	 * This will also create directories as needed so that a file could be
	 * created at the returned file path.
	 *
	 * @param \Simply_Static\Page $static_page The Simply_Static\Page
	 *
	 * @return string|null                The relative file path of the file
	 */
	public function create_directories_for_static_page( $static_page ) {
		$url_parts = parse_url( $static_page->url );
		// a domain with no trailing slash has no path, so we're giving it one
		$path = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';

		$origin_path = wp_parse_url( Util::origin_url(), PHP_URL_PATH );

		if ( null !== $origin_path && '' !== $origin_path ) {
			$origin_path_length = strlen( $origin_path );

			if ( $origin_path_length > 1 ) { // prevents removal of '/'.
				$path = substr( $path, $origin_path_length );
			}
		}

		$path_info = Util::url_path_info( $path );

		$relative_file_dir = $path_info['dirname'];
		$relative_file_dir = Util::remove_leading_directory_separator( $relative_file_dir );

		// If there's no extension, we're going to create a directory with the
		// filename and place an index.html/xml file in there.
		if ( $path_info['extension'] === '' && ! $static_page->is_binary_file() ) {
			if ( $path_info['filename'] !== '' ) {
				// the filename would be blank for the root url, in that
				// instance we don't want to add an extra slash
				$relative_file_dir .= $path_info['filename'];
				$relative_file_dir = Util::add_trailing_directory_separator( $relative_file_dir );
			}
			$path_info['filename'] = 'index';
			if ( $static_page->is_type( 'xml' ) ) {
				$path_info['extension'] = 'xml';
			} else {
				$path_info['extension'] = apply_filters( 'ss_default_extension_type', 'html' );
			}
		}

		// Prevent query-string URLs from overwriting base paths by placing them in a deterministic subdirectory based on the query string.
		// Exception: native WordPress search (query parameter `s`) should NOT use a hash subdirectory.
		if ( ! empty( $url_parts['query'] ) ) {
			$relative_file_dir = Util::add_trailing_directory_separator( $relative_file_dir );
			$use_hash_dir      = true;
			parse_str( (string) $url_parts['query'], $qs_args );
			if ( is_array( $qs_args ) && array_key_exists( 's', $qs_args ) ) {
				$use_hash_dir = false;
			}
			/**
			 * Filter whether Simply Static should use a hash directory for query-string URLs.
			 *
			 * Returning false writes query-string URLs directly under `__qs/` without the hash subdirectory.
			 *
			 * @param bool $use_hash_dir Whether to use the hash directory. Default true (except for native search URLs).
			 * @param array<string,mixed> $qs_args Parsed query-string arguments.
			 * @param \Simply_Static\Page $static_page The current static page.
			 */
			$use_hash_dir = apply_filters( 'simply_static_use_qs_hash_dir', $use_hash_dir, $qs_args, $static_page );
			if ( $use_hash_dir ) {
				$qs_hash           = substr( md5( $url_parts['query'] ), 0, 12 );
				$relative_file_dir .= '__qs/' . $qs_hash . '/';
			} else {
				$relative_file_dir .= '__qs/';
				Util::debug_log( '[SS][SEARCH_QS] Using non-hashed __qs/ directory for URL: ' . $static_page->url );
			}
		}

		$page_handler = $static_page->get_handler();

		$path_info         = apply_filters( 'simply_static_page_path_info', $page_handler->get_path_info( $path_info ), $static_page );
		$relative_file_dir = apply_filters( 'simple_static_page_relative_file_dir', $page_handler->get_relative_dir( $relative_file_dir ), $static_page );

		$create_dir = wp_mkdir_p( $this->archive_dir . urldecode( $relative_file_dir ) );
		if ( $create_dir === false ) {
			Util::debug_log( "Unable to create temporary directory: " . $this->archive_dir . urldecode( $relative_file_dir ) );
			$static_page->set_error_message( 'Unable to create temporary directory' );
		} else {
			$relative_filename = urldecode( $relative_file_dir ) . $path_info['filename'] . ( $path_info['extension'] ? '.' . $path_info['extension'] : '' );
			Util::debug_log( "New filename for static page: " . $relative_filename );

			// check that file doesn't exist OR exists but is writeable
			// (generally, we'd expect it to never exist)
			if ( ! file_exists( $this->archive_dir . $relative_filename ) || is_writable( $this->archive_dir . $relative_filename ) ) {
				return $relative_filename;
			} else {
				Util::debug_log( "File exists and is unwriteable" );
				$static_page->set_error_message( 'File exists and is unwriteable' );
			}
		}

		return null;
	}

	public static function remote_get( $url, $filename = null ) {
		$basic_auth_digest = base64_encode( Options::instance()->get( 'http_basic_auth_username' ) . ':' . Options::instance()->get( 'http_basic_auth_password' ) );

		Util::debug_log( "Fetching URL: " . $url );

		$args = array(
			'timeout'     => self::TIMEOUT,
			'user-agent'  => 'Simply Static/' . SIMPLY_STATIC_VERSION,
			'sslverify'   => false,
			'redirection' => 0, // disable redirection.
			'blocking'    => true,
		);

		if ( $filename ) {
			$args['stream']   = true; // stream body content to a file.
			$args['filename'] = $filename;
		}

		if ( $basic_auth_digest ) {
			$args['headers'] = array( 'Authorization' => 'Basic ' . $basic_auth_digest );
		}

		return wp_remote_get( $url, apply_filters( 'ss_remote_get_args', $args ) );
	}

}
