<?php

namespace Simply_Static;

use RankMath\Sitemap\Router;

class Rank_Math_Integration extends Integration {
	const REDIRECT_JSON_KEY = 'rank_math_static_redirect';
	const LEGACY_REDIRECT_EXPORT_JSON_KEY = 'rank_math_static_redirect_export_started_at';

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'rank-math';

	/**
	 * Exportable redirects keyed by their WordPress source URL.
	 *
	 * @var array|null
	 */
	protected $static_redirects = null;

	public function __construct() {
		$this->name        = __( 'Rank Math', 'simply-static' );
		$this->description = __( 'Automatically includes your XML sitemaps, handles URL replacements in schema.org markup, and creates redirects on your static site for you.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_redirections' ] );
		add_filter( 'simply_static_registered_redirect', [ $this, 'get_registered_redirect' ], 10, 2 );
		add_action( 'ss_dom_before_save', [ $this, 'replace_json_schema' ], 10, 2 );
		add_filter( 'ss_additional_files', [ $this, 'maybe_add_text_files' ] );

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-rank-math-sitemap-handler.php' );
	}

	/**
	 * Maybe add robots.txt and llms.txt to additional files when RankMath is active.
	 *
	 * RankMath stores robots.txt content in settings and serves llms.txt dynamically.
	 * We generate temporary files so Simply Static can export them.
	 *
	 * @param array $additional_files
	 * @return array
	 */
 public function maybe_add_text_files( $additional_files ) {
        // Ensure we have an array to work with.
        $additional_files = is_array( $additional_files ) ? $additional_files : [];

        // Global flags to allow disabling robots.txt and llms.txt entirely.
        $include_robots = (bool) apply_filters( 'ss_include_robots_txt_in_export', true );
        $include_llms   = (bool) apply_filters( 'ss_include_llms_txt_in_export', true );

        // If physical files exist, Setup_Task will already include them.
        $robots_physical = ABSPATH . 'robots.txt';
        $llms_physical   = ABSPATH . 'llms.txt';

		$archive_dir = Options::instance()->get_archive_dir();
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}

  // robots.txt via public endpoint (fetch like llms.txt to ensure consistency with RankMath output).
  if ( $include_robots && ! file_exists( $robots_physical ) ) {
      $robots_url = home_url( '/robots.txt' );
      $response   = $this->auth_remote_get( $robots_url, [ 'timeout' => 20 ] );
      if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
          $body = wp_remote_retrieve_body( $response );
          $body = is_string( $body ) ? $body : '';
				// Basic sanity check: ensure it looks like plain text and not an HTML 404.
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					// Replace URLs directly in the content before writing the file.
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'robots.txt', $body );
					if ( $path ) {
    						// Run URL replacements using the new Text_File_Handler (fallback, just in case).
						$this->run_text_file_handler( 'robots.txt' );
					}
				}
            }
        } elseif ( ! $include_robots ) {
            Util::debug_log( '[RankMath] robots.txt generation disabled via ss_include_robots_txt_in_export' );
        }

        // llms.txt via public endpoint served by RankMath.
        if ( $include_llms && ! file_exists( $llms_physical ) ) {
            $llms_url = home_url( '/llms.txt' );
            $response = $this->auth_remote_get( $llms_url, [ 'timeout' => 20 ] );
            if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
                $body = wp_remote_retrieve_body( $response );
                $body = is_string( $body ) ? $body : '';
				// Basic sanity check: ensure it looks like plain text and not an HTML 404.
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					// Replace URLs directly in the content before writing the file.
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'llms.txt', $body );
					if ( $path ) {
							// Run URL replacements using the new Text_File_Handler (fallback, just in case).
							$this->run_text_file_handler( 'llms.txt' );
					}
				}
            }
        } elseif ( ! $include_llms ) {
            Util::debug_log( '[RankMath] llms.txt generation disabled via ss_include_llms_txt_in_export' );
        }

        return $additional_files;
    }

	/**
	 * Write a file directly into the current archive directory (no prefix).
	 * This ensures the file becomes part of the static export output and any local transfer.
	 *
	 * @param string $filename Filename such as 'robots.txt' or 'llms.txt'.
	 * @param string $content  File contents.
	 * @return string|null Full path on success, null on failure.
	 */
	private function write_archive_file( $filename, $content ) {
		$archive_dir = Options::instance()->get_archive_dir();
		if ( empty( $archive_dir ) ) {
			return null;
		}
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}
		$path = trailingslashit( $archive_dir ) . ltrim( $filename, '/\\' );
		$result = @file_put_contents( $path, $content );
		return $result !== false ? $path : null;
	}

	/**
	 * Replace origin URLs with destination URL inside a plain-text string.
	 * Mirrors Text_File_Handler logic but operates on raw content before writing.
	 *
	 * @param string $content
	 * @return string
	 */
	private function replace_urls_in_text( $content ) {
		return Util::replace_origin_urls_in_text( $content );
	}

	/**
	 * Run the Text_File_Handler on a relative file within the archive directory
	 * to perform destination URL replacements (robots.txt, llms.txt).
	 *
	 * @param string $relative_filename e.g. 'robots.txt' or 'llms.txt'.
	 * @return void
	 */
	private function run_text_file_handler( $relative_filename ) {
		// Ensure required classes are available.
		if ( ! class_exists( __NAMESPACE__ . '\\Text_File_Handler', false ) ) {
			// Handlers are located under src/handlers/. Ensure base class is loaded first.
			$this->include_file( 'handlers/class-ss-page-handler.php' );
			$this->include_file( 'handlers/class-ss-text-file-handler.php' );
		}
		if ( ! class_exists( __NAMESPACE__ . '\\Page', false ) ) {
			$this->include_file( 'models/class-ss-page.php' );
		}

		try {
			$archive_dir = Options::instance()->get_archive_dir();
			if ( empty( $archive_dir ) ) {
				return;
			}

			$page = new Page();
			// Only file_path is used by Text_File_Handler::after_file_fetch().
			$page->file_path = ltrim( $relative_filename, '/\\' );

			$handler = new Text_File_Handler( $page );
			$handler->after_file_fetch( $archive_dir );
		} catch ( \Throwable $e ) {
			// Fail silently.
		}
	}


	/**
	 * Get Redirections.
	 * @return array|object|\stdClass[]|null
	 */
	protected function get_redirects() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rank_math_redirections WHERE status = 'active' ORDER BY updated DESC", ARRAY_A );


		if ( null === $results ) {
			return null;
		}

		if ( empty( $results ) ) {
			return array();
		}

		$results = array_map( function ( $item ) {
			$item['sources'] = maybe_unserialize( $item['sources'] );

			return $item;
		}, $results );

		return $results;
	}

	/**
	 * Register all redirections.
	 *
	 * @return void
	 */
	public function register_redirections() {
		// Only on full or update exports.
		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );

		if ( ! empty( $use_build ) || ! empty( $use_single ) ) {
			return;
		}

		$redirections = $this->get_static_redirects();

		// Do not clear stored metadata when the redirect table could not be read.
		if ( null === $redirections ) {
			return;
		}

		$this->clear_stale_static_redirects( $redirections );

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $url => $redirection ) {
			Util::debug_log( 'Adding Rank Math redirection URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'RankMath Redirection URL', 'simply-static' ) );

			// wpdb hydrates numeric columns as strings. Avoid dirtying an unchanged
			// redirect page by assigning integer 0 over an existing string "0".
			if ( null === $static_page->found_on_id || 0 !== (int) $static_page->found_on_id ) {
				$static_page->found_on_id = 0;
			}

			$this->set_static_page_redirect( $static_page, $redirection );
			$static_page->save();
		}
	}

	/**
	 * Return a redirect registered for the current static page.
	 *
	 * @param mixed $redirect Existing redirect supplied by another integration.
	 * @param mixed $static_page Static page being processed.
	 *
	 * @return mixed
	 */
	public function get_registered_redirect( $redirect, $static_page ) {
		if (
			! is_object( $static_page )
			|| ! method_exists( $static_page, 'get_json_data_by_key' )
		) {
			return $redirect;
		}

		// Single and build exports do not refresh registered redirect metadata.
		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );

		if ( ! empty( $use_build ) || ! empty( $use_single ) ) {
			return $redirect;
		}

		$registered_redirect = $static_page->get_json_data_by_key( self::REDIRECT_JSON_KEY );

		return is_array( $registered_redirect ) && ! empty( $registered_redirect['url'] )
			? $registered_redirect
			: $redirect;
	}

	/**
	 * Get exact, active Rank Math redirects that can be represented by a static file.
	 *
	 * @return array<string,array{url:string,status_code:int}>|null
	 */
	protected function get_static_redirects() {
		if ( null !== $this->static_redirects ) {
			return $this->static_redirects;
		}

		$redirections = $this->get_redirects();

		if ( ! is_array( $redirections ) ) {
			return null;
		}

		$this->static_redirects = array();

		foreach ( $redirections as $redirection ) {
			$status_code      = isset( $redirection['header_code'] ) ? (int) $redirection['header_code'] : 301;
			$target_url       = $this->prepare_redirection_url( isset( $redirection['url_to'] ) ? $redirection['url_to'] : '' );
			$add_query_string = true === apply_filters( 'rank_math/redirection/add_query_string', true, $redirection );

			if (
				! $target_url
				|| ! in_array( $status_code, array( 301, 302, 303, 307, 308 ), true )
				|| empty( $redirection['sources'] )
				|| ! is_array( $redirection['sources'] )
			) {
				continue;
			}

			foreach ( $redirection['sources'] as $source ) {
				if (
					! is_array( $source )
					|| 'exact' !== ( $source['comparison'] ?? '' )
					|| empty( $source['pattern'] )
				) {
					continue;
				}

				$source_url = $this->prepare_redirection_url( $source['pattern'] );
				if ( ! $source_url || isset( $this->static_redirects[ $source_url ] ) ) {
					continue;
				}

				$this->static_redirects[ $source_url ] = array(
					'url'         => $add_query_string ? $this->add_source_query_to_target( $target_url, $source_url ) : $target_url,
					'status_code' => $status_code,
				);
			}
		}

		return $this->static_redirects;
	}

	/**
	 * Normalize a Rank Math source or target against the configured WordPress origin.
	 *
	 * @param mixed $url URL or path.
	 *
	 * @return string
	 */
	protected function prepare_redirection_url( $url ) {
		$url = is_string( $url ) ? trim( html_entity_decode( $url, ENT_QUOTES ) ) : '';

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$origin_scheme = wp_parse_url( Util::origin_url(), PHP_URL_SCHEME );
			$url = ( $origin_scheme ? $origin_scheme : 'https' ) . ':' . $url;
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = untrailingslashit( Util::origin_url() ) . '/' . ltrim( $url, '/' );
		}

		return remove_query_arg( 'simply_static_page', $url );
	}

	/**
	 * Preserve an exact source rule's query string in its redirect target.
	 *
	 * Rank Math appends the incoming query string to the configured target by default.
	 * Registered redirects bypass Rank Math's HTTP response, so reproduce that behavior
	 * for query-specific source rules before storing the redirect metadata.
	 *
	 * @param string $target_url Redirect target.
	 * @param string $source_url Exact source URL.
	 *
	 * @return string
	 */
	protected function add_source_query_to_target( $target_url, $source_url ) {
		$source_query = wp_parse_url( $source_url, PHP_URL_QUERY );

		if ( ! is_string( $source_query ) || '' === $source_query ) {
			return $target_url;
		}

		$fragment = '';
		$fragment_position = strpos( $target_url, '#' );
		if ( false !== $fragment_position ) {
			$fragment   = substr( $target_url, $fragment_position );
			$target_url = substr( $target_url, 0, $fragment_position );
		}

		if ( false === strpos( $target_url, '?' ) ) {
			$separator = '?';
		} elseif ( in_array( substr( $target_url, -1 ), array( '?', '&' ), true ) ) {
			$separator = '';
		} else {
			$separator = '&';
		}

		return $target_url . $separator . $source_query . $fragment;
	}

	/**
	 * Store redirect metadata and invalidate an existing page when the rule changed.
	 *
	 * @param Page  $static_page Static page.
	 * @param array $redirection Redirect data.
	 *
	 * @return void
	 */
	protected function set_static_page_redirect( $static_page, $redirection ) {
		$json = $static_page->get_json();
		$json = is_array( $json ) ? $json : array();
		$current = array_key_exists( self::REDIRECT_JSON_KEY, $json ) ? $json[ self::REDIRECT_JSON_KEY ] : null;

		if ( $current !== $redirection ) {
			$json[ self::REDIRECT_JSON_KEY ] = $redirection;
			unset( $json[ self::LEGACY_REDIRECT_EXPORT_JSON_KEY ] );
			$static_page->set_json( $json );
			$static_page->last_modified_at = Util::formatted_datetime();
		}
	}

	/**
	 * Clear redirect metadata for rules that are no longer exportable.
	 *
	 * This is done once during setup in ID-based batches so fetch workers can use
	 * page-local metadata without loading the complete Rank Math redirect table.
	 *
	 * @param array<string,array{url:string,status_code:int}> $redirections Current redirects.
	 *
	 * @return void
	 */
	protected function clear_stale_static_redirects( $redirections ) {
		$batch_size = max( 1, (int) apply_filters( 'simply_static_rank_math_redirect_cleanup_batch_size', 500 ) );
		$last_id    = 0;

		do {
			$static_pages = Page::query()
				->where( 'id > ?', $last_id )
				->where( 'json LIKE ?', '%"' . self::REDIRECT_JSON_KEY . '"%' )
				->order( 'id ASC' )
				->limit( $batch_size )
				->find();

			if ( ! is_array( $static_pages ) || empty( $static_pages ) ) {
				return;
			}

			$previous_last_id = $last_id;

			foreach ( $static_pages as $static_page ) {
				$last_id = max( $last_id, (int) $static_page->id );

				if ( ! empty( $static_page->url ) && array_key_exists( $static_page->url, $redirections ) ) {
					continue;
				}

				if ( $this->clear_static_page_redirect( $static_page ) ) {
					$static_page->save();
				}
			}

			if ( $last_id <= $previous_last_id ) {
				return;
			}
		} while ( count( $static_pages ) === $batch_size );
	}

	/**
	 * Remove Rank Math redirect metadata from a static page.
	 *
	 * @param Page $static_page Static page.
	 *
	 * @return bool Whether metadata was removed.
	 */
	protected function clear_static_page_redirect( $static_page ) {
		$json = $static_page->get_json();

		if ( ! is_array( $json ) || ! array_key_exists( self::REDIRECT_JSON_KEY, $json ) ) {
			return false;
		}

		unset( $json[ self::REDIRECT_JSON_KEY ], $json[ self::LEGACY_REDIRECT_EXPORT_JSON_KEY ] );
		$static_page->set_json( $json );
		$static_page->last_modified_at = Util::formatted_datetime();

		return true;
	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_page() {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return;
		}

		$urls = array(
			Router::get_base_url( 'sitemap_index.xml' ),
			Router::get_base_url( 'main-sitemap.xsl' )
	);

		foreach ( $urls as $url ) {

			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->handler     = Rank_Math_Sitemap_Handler::class;
			$static_page->save();
		}

		// Extract and add individual sitemap URLs from sitemap_index.xml
		$this->extract_sitemap_urls_from_index();
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return $urls;
		}

		$urls[] = Router::get_base_url( 'sitemap_index.xml' );
		$urls[] = Router::get_base_url( 'main-sitemap.xsl' );

		// Extract individual sitemap URLs from sitemap_index.xml
		$sitemap_index_url = Router::get_base_url( 'sitemap_index.xml' );
		$response = $this->auth_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		foreach ( $this->extract_sitemap_index_urls( $response ) as $sitemap_url ) {
			$urls[] = $sitemap_url;
		}

		return $urls;
	}

	/**
	 * Replace JSON schema for schema.org
	 *
	 * @param mixed  $dom DOMDocument or HTML string.
	 * @param string $url given URL.
	 *
	 * @return mixed DOMDocument or HTML string (same type as input)
	 */
	public function replace_json_schema( $dom, $url ) {
		$options = Options::instance();

		// Normalize input to DOMDocument while keeping track of original type.
		$original_was_string = is_string( $dom );

		if ( $original_was_string ) {
			$doc = new \DOMDocument();
			libxml_use_internal_errors( true );
			// Suppress implied html/body to better preserve fragments if possible.
			$load_options = defined('LIBXML_HTML_NOIMPLIED') ? (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD) : 0;
			$doc->loadHTML( Util::strip_bom( $dom ), $load_options );
			libxml_clear_errors();
		} elseif ( $dom instanceof \DOMDocument ) {
			$doc = $dom;
		} else {
			// Unknown type; nothing to do.
			return $dom;
		}

		// Use DOMXPath to find script elements with class 'rank-math-schema'
		$xpath = new \DOMXPath( $doc );
		$scripts = $xpath->query( '//script[contains(@class, "rank-math-schema")]' );

		if ( $scripts ) {
			foreach ( $scripts as $script ) {
				$decoded_text = html_entity_decode( $script->nodeValue, ENT_NOQUOTES );
				$text = preg_replace( '/(https?:)?\/\/' . Util::origin_host_pattern() . '/i', $options->get_destination_url(), $decoded_text );
				$script->nodeValue = $text;
			}
		}

		// Return the same type that was provided.
		if ( $original_was_string ) {
			return $doc->saveHTML();
		}

		return $doc;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		// Rank Math exposes various identifiers; use multiple checks for reliability.
		return ( defined( 'RANK_MATH_VERSION' )
			|| class_exists( '\\RankMath\\Helper' )
			|| class_exists( '\\RankMath\\Sitemap\\Router' ) );
	}

	/**
	 * Extract sitemap URLs from sitemap_index.xml and add them to the queue.
	 *
	 * @return void
	 */
	protected function extract_sitemap_urls_from_index() {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return;
		}

		$sitemap_index_url = Router::get_base_url( 'sitemap_index.xml' );
		$response = $this->auth_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return;
		}

		foreach ( $this->extract_sitemap_index_urls( $response ) as $sitemap_url ) {
			// Add the sitemap URL to the queue.
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
			$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->handler     = Rank_Math_Sitemap_Handler::class;
			$static_page->save();
		}
	}
}
