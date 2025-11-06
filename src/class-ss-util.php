<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static utility class
 */
class Util {

	/**
	 * Parse a list of user-provided lines into literals and regex patterns.
	 * Lines wrapped like /pattern/flags are treated as regex; others as literals.
	 *
	 * @param array $lines
	 * @return array{literals: string[], regex: string[]}
	 */
	public static function parse_patterns( array $lines ): array {
		$literals = [];
		$regex    = [];
		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( $line === '' ) { continue; }
			// Regex if starts and ends with /, allow optional flags like i,m,s,u
			if ( strlen( $line ) >= 2 && $line[0] === '/' && strrpos( $line, '/' ) !== 0 ) {
				$last = strrpos( $line, '/' );
				$pattern = substr( $line, 0, $last + 1 );
				$flags   = substr( $line, $last + 1 );
				// Validate pattern
				$valid = @preg_match( $pattern . $flags, '' );
				if ( $valid !== false ) {
					$regex[] = $pattern . $flags;
					continue;
				}
			}
			$literals[] = $line;
		}
		return [ 'literals' => array_values( array_unique( $literals ) ), 'regex' => array_values( array_unique( $regex ) ) ];
	}

	/**
	 * Build a candidate URL list used when resolving regex in Additional URLs.
	 * This is a best-effort set: home/front page, all public posts, term links, author links,
	 * and common archive/pagination URLs. Filterable and capped by limits.
	 *
	 * @return string[]
	 */
	public static function candidate_urls_for_regex(): array {
		$limit = (int) apply_filters( 'ss_regex_candidate_url_limit', 5000 );
		$urls  = [];
		$urls[] = home_url( '/' );
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_id = (int) get_option( 'page_on_front' );
			if ( $front_id ) {
				$u = get_permalink( $front_id ); if ( is_string( $u ) ) { $urls[] = $u; }
			}
		}
		// Posts and pages of public types
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		unset( $post_types['attachment'] );
		$post_types = apply_filters( 'simply_static_post_types_to_crawl', $post_types );
		$q = [ 'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ];
		$ids = get_posts( $q );
		foreach ( (array) $ids as $pid ) {
			$u = get_permalink( $pid ); if ( is_string( $u ) ) { $urls[] = $u; }
			if ( count( $urls ) >= $limit ) { break; }
		}
		if ( count( $urls ) < $limit ) {
			// Terms
			$taxes = get_taxonomies( [ 'public' => true ], 'names' );
			foreach ( $taxes as $tx ) {
				$terms = get_terms( [ 'taxonomy' => $tx, 'hide_empty' => true ] );
				if ( is_wp_error( $terms ) ) { continue; }
				foreach ( $terms as $term ) {
					$u = get_term_link( $term ); if ( ! is_wp_error( $u ) ) { $urls[] = $u; }
					if ( count( $urls ) >= $limit ) { break 2; }
				}
			}
		}
		if ( count( $urls ) < $limit ) {
			// Authors
			$users = get_users( [ 'who' => 'authors' ] );
			foreach ( (array) $users as $user ) {
				$u = get_author_posts_url( $user->ID ); if ( is_string( $u ) ) { $urls[] = $u; }
				if ( count( $urls ) >= $limit ) { break; }
			}
		}
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Determine if a URL should be excluded based on settings and patterns.
	 * Centralized helper used by crawlers and fetch tasks.
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_url_excluded( string $url ): bool {
		$excluded = array( '.php' );
		$opts = Options::instance();

		// Exclude debug files (.log, .txt) but not robots.txt
		if ( preg_match( '/\.(log|txt)$/i', $url ) && strpos( $url, 'debug' ) !== false && strpos( $url, 'robots.txt' ) === false ) {
			return true;
		}

		// Exclude feeds if add_feeds is not true.
		if ( ! $opts->get( 'add_feeds' ) ) {
			// Only exclude WordPress feed-style URLs
			if ( preg_match( '/(\/feed\/?$|\?feed=|\/feed\/|\/rss\/?$|\/atom\/?$)/i', $url ) ) {
				return true;
			}
		}

		// Exclude Rest API if add_rest_api is not true.
		if ( ! $opts->get( 'add_rest_api' ) ) {
			$excluded[] = 'wp-json';
		}

		$urls_to_exclude = $opts->get( 'urls_to_exclude' );
		$regex_patterns  = [];
		if ( ! empty( $urls_to_exclude ) ) {
			if ( is_array( $urls_to_exclude ) ) {
				$excluded_by_option = wp_list_pluck( $urls_to_exclude, 'url' );
			} else {
				$excluded_by_option = explode( "\n", $urls_to_exclude );
			}

			if ( is_array( $excluded_by_option ) ) {
				// Normalize: trim whitespace/CRLF, drop empties, unique
				$excluded_by_option = array_filter( array_map( 'trim', $excluded_by_option ), function ( $v ) {
					return $v !== '';
				} );
				$excluded_by_option = array_unique( $excluded_by_option );
				$parsed = self::parse_patterns( $excluded_by_option );
				$excluded = array_merge( $excluded, $parsed['literals'] );
				$regex_patterns = $parsed['regex'];
			}
		}

		if ( apply_filters( 'simply_static_exclude_temp_dir', true ) ) {
			$excluded[] = self::get_temp_dir_url();
		}

		$excluded = apply_filters( 'ss_excluded_by_default', $excluded );

		if ( $excluded ) {
			$excluded = array_filter( $excluded );
		}

		// First test regex patterns if provided
		foreach ( (array) $regex_patterns as $pattern ) {
			if ( @preg_match( $pattern, $url ) ) {
				if ( preg_match( $pattern, $url ) ) { return true; }
			}
		}

		// Then test literal contains (case-insensitive)
		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $excludable ) {
				if ( stripos( $url, $excludable ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get all active plugins for the current site, including network-activated plugins on multisite.
	 *
	 * Returns a list of plugin basenames (e.g. akismet/akismet.php).
	 *
	 * @return array
	 */
	public static function get_all_active_plugins(): array {
		$active = (array) get_option( 'active_plugins', [] );
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// Network-activated plugins are stored as an associative array with plugin file as the key.
			$network = (array) get_site_option( 'active_sitewide_plugins', [] );
			$network_plugins = array_keys( $network );
			$active = array_merge( $active, $network_plugins );
		}
		$active = array_values( array_unique( $active ) );
		sort( $active );
		return $active;
	}

	/**
	 * Compute the target Static Site URL based on Simply Static settings.
	 * Returns an empty string if it cannot be determined.
	 *
	 * Logic:
	 * - destination_url_type = 'relative' and relative_path not empty:
	 *   Use the current site's scheme (https if wp_is_using_https() or is_ssl()),
	 *   then build home_url( '/', $scheme ) + relative_path.
	 * - destination_url_type = 'absolute' with non-empty destination_scheme and destination_host:
	 *   Normalize and return scheme://host.
	 *
	 * @return string The static site URL or empty string when unavailable.
	 */
	public static function get_static_site_url() {
		$options = get_option( 'simply-static' );
		if ( empty( $options ) || ! is_array( $options ) ) {
			return '';
		}

		$type = isset( $options['destination_url_type'] ) ? strtolower( trim( $options['destination_url_type'] ) ) : '';
		$target_url = '';

		if ( 'relative' === $type ) {
			$relative_path = isset( $options['relative_path'] ) ? trim( $options['relative_path'] ) : '';
			if ( $relative_path !== '' ) {
				$scheme   = ( function_exists( 'wp_is_using_https' ) && wp_is_using_https() ) ? 'https' : ( is_ssl() ? 'https' : 'http' );
				$base_url = home_url( '/', $scheme );
				$target_url = trailingslashit( $base_url ) . ltrim( $relative_path, '/' );
			}
		} elseif ( 'absolute' === $type ) {
			$scheme = isset( $options['destination_scheme'] ) ? trim( $options['destination_scheme'] ) : '';
			$host   = isset( $options['destination_host'] ) ? trim( $options['destination_host'] ) : '';
			if ( $scheme !== '' && $host !== '' ) {
				$scheme = preg_replace( '/:\\/*$/', '', $scheme );
				$host   = preg_replace( '/^\\/*/', '', $host );
				$target_url = $scheme . '://' . $host;
			}
		}

		return $target_url;
	}

	/**
	 * Get the protocol used for the origin URL
	 * @return string http or https
	 */
	public static function origin_scheme() {
		$pattern = '/:\/\/.*/';

		return preg_replace( $pattern, '', self::origin_url() );
	}

	/**
	 * Get the host for the origin URL
	 * @return string host (URL minus the protocol)
	 */
	public static function origin_host() {
		return untrailingslashit( self::strip_protocol_from_url( self::origin_url() ) );
	}

	/**
	 * Wrapper around home_url(). Useful for swapping out the URL during debugging.
	 * @return string home URL
	 */
	public static function origin_url() {
		$options = Options::instance();

		if ( $options->get( 'origin_url' ) ) {
			return apply_filters( 'ss_origin_url', esc_url( untrailingslashit( $options->get( 'origin_url' ) ) ) );
		}

		return apply_filters( 'ss_origin_url', untrailingslashit( home_url() ) );
	}

	/**
	 * Truncate if a string exceeds a certain length (30 chars by default)
	 * @return string
	 */
	public static function truncate( $string, $length = 30, $omission = '...' ) {
		return ( strlen( $string ) > $length + 3 ) ? ( substr( $string, 0, $length ) . $omission ) : $string;
	}

	/**
	 * Dump an object to error_log
	 *
	 * @param mixed $object Object to dump to the error log
	 *
	 * @return void
	 */
	public static function error_log( $object = null ) {
		$contents = self::get_contents_from_object( $object );
		error_log( $contents );
	}

	/**
	 * Clear the debug log
	 * @return void
	 */
	public static function clear_debug_log() {
		$debug_file = self::get_debug_log_filename();
		if ( file_exists( $debug_file ) ) {
			// Clear file
			file_put_contents( $debug_file, '' );
		}
	}

	/**
	 * Save an object/string to the debug log
	 *
	 * @param mixed $object Object to save to the debug log
	 *
	 * @return void
	 */
	public static function debug_log( $object = null ) {
		$options = Options::instance();
		if ( ! $options->get( 'debugging_mode' ) ) {
			return;
		}

		$debug_file = self::get_debug_log_filename();

		if ( ! file_exists( $debug_file ) ) {
			wp_mkdir_p( dirname( $debug_file ) );
		}

		// add timestamp and newline
		$message = '[' . date( 'Y-m-d H:i:s' ) . '] ';

		$trace = debug_backtrace();
		if ( isset( $trace[0]['file'] ) ) {
			$file = basename( $trace[0]['file'] );
			if ( isset( $trace[0]['line'] ) ) {
				$file .= ':' . $trace[0]['line'];
			}
			$message .= '[' . $file . '] ';
		}

		$contents = self::get_contents_from_object( $object );

		// get message onto a single line
		$contents = preg_replace( "/\r|\n/", "", $contents );

		$message .= $contents . "\n";

		// log the message to the debug file instead of the usual error_log location
		error_log( $message, 3, $debug_file );
	}

	/**
	 * Return the filename for the debug log
	 * @return string Filename for the debug log
	 */
	public static function get_debug_log_filename() {
		// Get directories.
		$uploads_dir       = wp_upload_dir();
		$simply_static_dir = $uploads_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR;

		// Set name for debug file.
		$options = get_option( 'simply-static' );

		if ( isset( $options['encryption_key'] ) ) {
			return apply_filters( 'ss_debug_log_file', $simply_static_dir . $options['encryption_key'] . '-debug.txt', $options['encryption_key'] );
		} else {
			return apply_filters( 'ss_debug_log_file', $simply_static_dir . 'debug.txt', '' );
		}
	}

	/**
	 * Get contents of an object as a string
	 *
	 * @param mixed $object Object to get string for
	 *
	 * @return string         String containing the contents of the object
	 */
	protected static function get_contents_from_object( $object ) {
		// Handle common scalar types early and safely
		if ( is_string( $object ) ) {
			// Prevent huge memory usage by truncating very large strings
			return self::truncate( $object, 5000 );
		}
		if ( is_null( $object ) ) {
			return 'NULL';
		}
		if ( is_bool( $object ) ) {
			return $object ? 'TRUE' : 'FALSE';
		}
		if ( is_int( $object ) || is_float( $object ) ) {
			return (string) $object;
		}
		if ( is_resource( $object ) ) {
			return 'resource(' . get_resource_type( $object ) . ')';
		}

		// For arrays/objects, avoid var_dump which can explode memory usage.
		// Prefer JSON with partial output on error; fall back to print_r.
		$max_length = apply_filters( 'simply_static_debug_max_length', 100000 ); // 100 KB by default
		$json_opts  = defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
		$encoded    = function_exists( 'wp_json_encode' ) ? wp_json_encode( $object, $json_opts, 5 ) : json_encode( $object, $json_opts, 5 );

		if ( $encoded === false || $encoded === null ) {
			$encoded = print_r( $object, true );
		}

		if ( strlen( $encoded ) > $max_length ) {
			$encoded = substr( $encoded, 0, $max_length ) . '... [truncated]';
		}

		return $encoded;
	}

	public static function is_valid_scheme( $scheme ) {
		$valid_schemes = apply_filters( 'simply_static_valid_schemes', [
			'http',
			'https',
		] );

		return in_array( $scheme, $valid_schemes );
	}

	/**
	 * Given a URL extracted from a page, return an absolute URL
	 *
	 * Takes a URL (e.g. /test) extracted from a page (e.g. http://foo.com/bar/) and
	 * returns an absolute URL (e.g. http://foo.com/bar/test). Absolute URLs are
	 * returned as-is. Exception: links beginning with a # (hash) are left as-is.
	 *
	 * A null value is returned in the event that the extracted_url is blank or it's
	 * unable to be parsed.
	 *
	 * @param string $extracted_url Relative or absolute URL extracted from page
	 * @param string $page_url URL of page
	 *
	 * @return string|null                   Absolute URL, or null
	 */
	public static function relative_to_absolute_url( $extracted_url, $page_url ) {

		// we can't do anything with null or blank urls
		if ( $extracted_url === null ) {
			return null;
		}

		$extracted_url = trim( $extracted_url );

		// we can't do anything with blank urls
		if ( $extracted_url === '' ) {
			return null;
		}

		// if we get a hash, e.g. href='#section-three', just return it as-is
		if ( strpos( $extracted_url, '#' ) === 0 ) {
			return $extracted_url;
		}

		// check for a protocol-less URL
		// (Note: there's a bug in PHP <= 5.4.7 where parsed URLs starting with //
		// are treated as a path. So we're doing this check upfront.)
		// http://php.net/manual/en/function.parse-url.php#example-4617
		if ( strpos( $extracted_url, '//' ) === 0 ) {

			// if this is a local URL, add the protocol to the URL
			if ( stripos( $extracted_url, '//' . self::origin_host() ) === 0 ) {
				$extracted_url = self::origin_scheme() . ':' . $extracted_url;
			}

			return $extracted_url;

		}

		$parsed_extracted_url = parse_url( $extracted_url );

		// parse_url can sometimes return false; bail if it does
		if ( $parsed_extracted_url === false ) {
			return null;
		}

		// if no path, check for an ending slash; if there isn't one, add one
		if ( ! isset( $parsed_extracted_url['path'] ) ) {
			if ( isset( $parsed_extracted_url['scheme'] ) && ! self::is_valid_scheme( $parsed_extracted_url['scheme'] ) ) {
				return $extracted_url;
			}
			$clean_url     = self::remove_params_and_fragment( $extracted_url );
			$fragment      = substr( $extracted_url, strlen( $clean_url ) );
			$extracted_url = trailingslashit( $clean_url ) . $fragment;
		}

		if ( isset( $parsed_extracted_url['host'] ) ) {

			return $extracted_url;

		} elseif ( isset( $parsed_extracted_url['scheme'] ) ) {

			// examples of schemes without hosts: java:, data:
			return $extracted_url;

		} else { // no host on extracted page (might be relative url)

			$path = isset( $parsed_extracted_url['path'] ) ? $parsed_extracted_url['path'] : '';

			$query    = isset( $parsed_extracted_url['query'] ) ? '?' . $parsed_extracted_url['query'] : '';
			$fragment = isset( $parsed_extracted_url['fragment'] ) ? '#' . $parsed_extracted_url['fragment'] : '';

			// turn our relative url into an absolute url
			$extracted_url = PhpUri::parse( $page_url )->join( $path . $query . $fragment );

			return $extracted_url;

		}
	}

	/**
	 * Recursively create a path from one page to another
	 *
	 * Takes a path (e.g. /blog/foobar/) extracted from a page (e.g. /blog/page/3/)
	 * and returns a path to get to the extracted page from the current page
	 * (e.g. ./../../foobar/index.html). Since this is for offline use, the path
	 * return will include a /index.html if the extracted path doesn't contain
	 * an extension.
	 *
	 * The function recursively calls itself, cutting off sections of the page path
	 * until the base matches the extracted path or it runs out of parts to remove,
	 * then it builds out the path to the extracted page.
	 *
	 * @param string $extracted_path Relative or absolute URL extracted from page.
	 * @param string $page_path URL of page.
	 * @param int $iterations Number of times the page path has been chopped.
	 *
	 * @return string|null                 Absolute URL, or null
	 */
	public static function create_offline_path( $extracted_path, $page_path, $iterations = 0 ) {
		// We're done if we get a match between the path of the page and the extracted URL
		// OR if there are no more slashes to remove
		if ( strpos( $page_path, '/' ) === false || strpos( $extracted_path, trailingslashit( $page_path ) ) === 0 ) {
			$extracted_path = substr( $extracted_path, strlen( $page_path ) );
			$iterations     = ( $iterations == 0 ) ? 0 : $iterations - 1;
			$new_path       = '.' . str_repeat( '/..', $iterations ) . self::add_leading_slash( $extracted_path );

			return $new_path;
		} else {
			// match everything before the last slash
			$pattern = '/(.*)\/[^\/]*$/';
			// remove the last slash and anything after it
			$new_page_path = preg_replace( $pattern, '$1', $page_path );

			return self::create_offline_path( $extracted_path, $new_page_path, ++ $iterations );
		}
	}

	/**
	 * Check if URL starts with same URL as WordPress installation
	 *
	 * Both http and https are assumed to be the same domain.
	 *
	 * @param string $url URL to check
	 *
	 * @return boolean      true if URL is local, false otherwise
	 */
	public static function is_local_url( $url ) {
		return apply_filters( 'ss_is_local_url', ( stripos( self::strip_protocol_from_url( $url ), self::origin_host() ) === 0 ) );
	}

	/**
	 * Check if WP-Cron is running.
	 *
	 * @return bool
	 */
	public static function is_cron(): bool {
		if ( ! defined( 'DISABLE_WP_CRON' ) || DISABLE_WP_CRON !== true || defined( 'SS_CRON' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the path from a local URL, removing the protocol and host
	 *
	 * @param string $url URL to strip protocol/host from
	 *
	 * @return string       URL sans protocol/host
	 */
	public static function get_path_from_local_url( $url ) {
		$url = self::strip_protocol_from_url( $url );
		$url = str_replace( self::origin_host(), '', $url );

		return $url;
	}

	/**
	 * Returns a URL w/o the query string or fragment (i.e. nothing after the path)
	 *
	 * @param string $url URL to remove query string/fragment from
	 *
	 * @return string      URL without query string/fragment
	 */
	public static function remove_params_and_fragment( $url ) {
		return preg_replace( '/(\?|#).*/', '', $url );
	}

	/**
	 * Converts a textarea into an array w/ each line being an entry in the array
	 *
	 * @param string $textarea Textarea to convert
	 *
	 * @return array            Converted array
	 */
	public static function string_to_array( $textarea ) {
		if ( ! is_string( $textarea ) ) {
			return array();
		}

		// using preg_split to intelligently break at newlines
		// see: http://stackoverflow.com/questions/1483497/how-to-put-string-in-array-split-by-new-line
		$lines = preg_split( "/\r\n|\n|\r/", $textarea );
		array_walk( $lines, 'trim' );
		$lines = array_filter( $lines );

		return $lines;
	}

	/**
	 * Remove the //, http://, https:// protocols from a URL
	 *
	 * @param string $url URL to remove protocol from
	 *
	 * @return string      URL sans http/https protocol
	 */
	public static function strip_protocol_from_url( $url ) {
		$pattern = '/^(https?:)?\/\//';

		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Remove index.html/index.php from a URL
	 *
	 * @param string $url URL to remove index file from
	 *
	 * @return string      URL sans index file
	 */
	public static function strip_index_filenames_from_url( $url ) {
		$pattern = '/index.(html?|php)$/';

		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Get the current datetime formatted as a string for entry into MySQL
	 * @return string MySQL formatted datetime
	 */
	public static function formatted_datetime() {
		return current_time( 'Y-m-d H:i:s' );
	}

	/**
	 * Similar to PHP's pathinfo(), but designed with URL paths in mind (instead of directories)
	 *
	 * Example:
	 *   $info = self::url_path_info( '/manual/en/function.pathinfo.php?test=true' );
	 *     $info['dirname']   === '/manual/en/'
	 *     $info['basename']  === 'function.pathinfo.php'
	 *     $info['extension'] === 'php'
	 *     $info['filename']  === 'function.pathinfo'
	 *
	 * @param string $path The URL path
	 *
	 * @return array        Array containing info on the parts of the path
	 */
	public static function url_path_info( $path ) {
		$info = array(
			'dirname'   => '',
			'basename'  => '',
			'filename'  => '',
			'extension' => ''
		);

		$path = self::remove_params_and_fragment( $path );

		// everything after the last slash is the filename
		$last_slash_location = strrpos( $path, '/' );
		if ( $last_slash_location === false ) {
			$info['basename'] = $path;
		} else {
			$info['dirname']  = substr( $path, 0, $last_slash_location + 1 );
			$info['basename'] = substr( $path, $last_slash_location + 1 );
		}

		// finding the dot for the extension
		$last_dot_location = strrpos( $info['basename'], '.' );
		if ( $last_dot_location === false ) {
			$info['filename'] = $info['basename'];
		} else {
			$info['filename']  = substr( $info['basename'], 0, $last_dot_location );
			$info['extension'] = substr( $info['basename'], $last_dot_location + 1 );
		}

		// substr sets false if it fails, we're going to reset those values to ''
		foreach ( $info as $name => $value ) {
			if ( $value === false ) {
				$info[ $name ] = '';
			}
		}

		return $info;
	}

	public static function is_local_asset_url( $url ) {
		if ( ! self::is_local_url( $url ) ) {
			return false;
		}

		$allowed_asset_extensions = apply_filters( 'simply_static_allowed_local_asset_extensions', [
			// Images
			'webp', 'gif', 'jpg', 'jpeg', 'png', 'svg', 'ico', 'cur',
			// Media
			'mp4', 'webm', 'ogg', 'ogv', 'mp3', 'wav',
			// Data/Docs
			'json', 'xml', 'csv', 'pdf', 'txt',
			// Web assets
			'js', 'css',
			// Fonts
			'woff2', 'woff', 'ttf', 'eot', 'otf'
		] );

		$path_info = self::url_path_info( $url );

		if ( empty( $path_info['extension'] ) ) {
			return false;
		}

		return in_array( $path_info['extension'], $allowed_asset_extensions, true );
	}

	/**
	 * Ensure there is a single trailing directory separator on the path
	 *
	 * @param string $path File path to add trailing directory separator to
	 */
	public static function add_trailing_directory_separator( $path ) {
		return self::remove_trailing_directory_separator( $path ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Remove all trailing directory separators
	 *
	 * @param string $path File path to remove trailing directory separators from
	 */
	public static function remove_trailing_directory_separator( $path ) {
		return rtrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Remove all leading directory separators
	 *
	 * @param string $path File path to remove leading directory separators from
	 */
	public static function remove_leading_directory_separator( $path ) {
		if ( $path === null ) {
			return '';
		}
		return ltrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Add a slash to the beginning of a path
	 *
	 * @param string $path URL path to add leading slash to
	 */
	public static function add_leading_slash( $path ) {
		return '/' . self::remove_leading_slash( $path );
	}

	/**
	 * Remove a slash from the beginning of a path
	 *
	 * @param string $path URL path to remove leading slash from
	 */
	public static function remove_leading_slash( $path ) {
		if ( $path === null ) {
			return '';
		}
		return ltrim( $path, '/' );
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * @param array $messages Array of messages to add the message to
	 * @param string $task_name Name of the task
	 * @param string $message Message to display about the status of the job
	 * @param boolean $unique If unique, the task_name/key will get a prefix if the same exists.
	 *
	 * @return array
	 */
	public static function add_archive_status_message( $messages, $task_name, $message, $unique = false ) {
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $task_name, $messages ) || $unique ) {
			if ( $unique ) {
				$task_name = $task_name . '_' . uniqid();
			}
			$messages[ $task_name ] = array(
				'message'  => $message,
				'datetime' => self::formatted_datetime()
			);
		} else { // otherwise just update the message
			$messages[ $task_name ]['message'] = $message;
		}

		return $messages;
	}

	/**
	 * Get full URL from path.
	 *
	 * @param string $path given path.
	 *
	 * @return string
	 */
	public static function abs_path_to_url( $path = '' ) {
		$normalized_path = wp_normalize_path( $path );

		// Check if the path is within WP_CONTENT_DIR
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$normalized_content_dir = wp_normalize_path( untrailingslashit( WP_CONTENT_DIR ) );

			// If the path starts with the content directory, use WP_CONTENT_URL for replacement
			if ( strpos( $normalized_path, $normalized_content_dir ) === 0 ) {
				$url = str_replace(
					$normalized_content_dir,
					untrailingslashit( WP_CONTENT_URL ),
					$normalized_path
				);

				return esc_url_raw( $url );
			}
		}

		// Default behavior for paths not in WP_CONTENT_DIR
		$url = str_replace(
			wp_normalize_path( untrailingslashit( ABSPATH ) ),
			site_url(),
			$normalized_path
		);

		return esc_url_raw( $url );
	}

	/**
	 * Combine multiple paths into a single path while handling varying slashes and trailing slashes
	 *
	 * @param string ...$paths Each path to combine. You can pass as many paths as you want.
	 *
	 * @return string The combined path
	 */
	public static function combine_path(): string {
		$paths = func_get_args();

		if ( count( $paths ) < 1 ) {
			return '';
		}

		$paths = array_map( fn( $path ) => self::normalize_slashes( $path ), $paths );

		// We don't strip the slashes from the first path because on Linux, paths start with a slash.
		$trimmed_path = array_map( fn( $path ) => trim( trim( $path ), '/' ), array_slice( $paths, 1 ) );
		array_unshift( $trimmed_path, untrailingslashit( $paths[0] ) );

		return implode( '/', $trimmed_path );
	}

	/**
	 * Normalize slashes in a path to forward slashes
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string The normalized path.
	 */
	public static function normalize_slashes( string $path ): string {
		return strpos( $path, '\\' ) !== false ? str_replace( '\\', '/', $path ) : $path;
	}

	/**
	 * Build a safe relative path from an absolute path and its base directory.
	 * Ensures forward slashes and a leading slash for consistent URL building.
	 *
	 * @param string $base_dir      The base directory (prefix) of the absolute path.
	 * @param string $absolute_path The absolute path to the file.
	 * @return string               The normalized relative path starting with '/'.
	 */
	public static function safe_relative_path( string $base_dir, string $absolute_path ): string {
		$dir_norm = rtrim( $base_dir, DIRECTORY_SEPARATOR );
		$rel      = substr( $absolute_path, strlen( $dir_norm ) );
		if ( $rel === false ) {
			$rel = str_replace( $base_dir, '', $absolute_path );
		}
		$rel = str_replace( '\\', '/', $rel );
		if ( $rel === '' || $rel[0] !== '/' ) {
			$rel = '/' . ltrim( $rel, '/' );
		}
		return $rel;
	}

	/**
	 * Join a base URL and a relative path with exactly one slash.
	 *
	 * @param string $base_url      Base URL (may end with or without a slash).
	 * @param string $relative_path Relative path (may start with or without a slash).
	 * @return string               The joined URL.
	 */
	public static function safe_join_url( string $base_url, string $relative_path ): string {
		return rtrim( $base_url, '/' ) . '/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the global $wp_filesystem with credentials set.
	 * Returns null in case of any errors.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	public static function get_file_system() {
		global $wp_filesystem;

		$success = true;

		// Initialize the file system if it has not been done yet.
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			$constants = array(
				'hostname'    => 'FTP_HOST',
				'username'    => 'FTP_USER',
				'password'    => 'FTP_PASS',
				'public_key'  => 'FTP_PUBKEY',
				'private_key' => 'FTP_PRIKEY',
			);

			$credentials = array();

			// We provide credentials based on wp-config.php constants.
			// Reference https://developer.wordpress.org/apis/wp-config-php/#wordpress-upgrade-constants
			foreach ( $constants as $key => $constant ) {
				if ( defined( $constant ) ) {
					$credentials[ $key ] = constant( $constant );
				}
			}

			$success = WP_Filesystem( $credentials );
		}

		if ( ! $success || $wp_filesystem->errors->has_errors() ) {
			return null;
		}

		return $wp_filesystem;
	}

	/**
	 * Clear all transients used in Simply Static.
	 *
	 * @return void
	 */
	public static function clear_transients() {
		// Diagnostics.
		delete_transient( 'simply_static_checks' );
		delete_transient( 'simply_static_failed_tests' );

		// Tasks.
		$tasks = [
			'fetch_urls',
			'search',
			'minify',
			'optimize_directories',
			'shortpixel',
			'shortpixel_download',
			'aws_empty',
			'create_zip_archive',
			'transfer_files_locally',
			'github_blobs',
			'github_commit',
			'bunny_deploy',
			'tiiny_deploy',
			'aws_deploy',
			'sftp_deploy',
		];

		foreach ( $tasks as $task ) {
			delete_option( 'simply_static_' . $task . '_total_pages' );
		}
	}

	public static function get_temp_dir_url() {
		$dir = self::get_temp_dir();

		return self::abs_path_to_url( $dir );
	}

	/*
	 * Get the absolute path to the temporary file directory.
	 *
	 */
	public static function get_temp_dir() {
		$options = get_option( 'simply-static' );

		if ( empty( $options['temp_files_dir'] ) ) {
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';

			// Check if directory exists.
			if ( ! is_dir( $temp_dir ) ) {
				wp_mkdir_p( $temp_dir );
			}

		} else {
			$temp_dir = $options['temp_files_dir'];
		}

		return trailingslashit( $temp_dir );
	}

	/**
	 * Recursively delete contents of a directory but keep the directory itself.
	 *
	 * Rules:
	 * - No error suppression operators (@). We perform checks before FS calls to avoid warnings.
	 * - Very defensive: do nothing for empty/non-dirs and for very shallow paths.
	 *
	 * @param string $dir Absolute path to the directory whose contents should be cleared.
	 * @return void
	 */
	public static function delete_dir_contents( string $dir ): void {
		$dir = (string) $dir;
		if ( $dir === '' || ! is_dir( $dir ) ) {
			return;
		}
		$normalized = str_replace( '\\', '/', $dir );
		// Safety guard: do not operate on very shallow paths (like root-level). Require at least 3 path segments.
		if ( substr_count( trim( $normalized, '/' ), '/' ) < 2 ) {
			return;
		}
		$items = scandir( $dir );
		if ( $items === false ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) && ! is_link( $path ) ) {
				self::delete_dir_contents( $path );
				// Remove the now-empty directory if possible.
				if ( is_dir( $path ) && is_writable( $path ) ) {
					rmdir( $path );
				}
			} else {
				// Files or links
				if ( ( is_file( $path ) || is_link( $path ) ) && ( file_exists( $path ) || is_link( $path ) ) ) {
					if ( is_writable( $path ) ) {
						unlink( $path );
					}
				}
			}
		}
	}
}
