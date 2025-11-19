<?php

namespace Simply_Static;

use RankMath\Sitemap\Router;

class Rank_Math_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'rank-math';

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
      $response   = wp_remote_get( $robots_url, [ 'timeout' => 20 ] );
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
            $response = wp_remote_get( $llms_url, [ 'timeout' => 20 ] );
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
		if ( ! is_string( $content ) ) {
			return $content;
		}
		if ( $content === '' ) {
			return $content;
		}

		$options         = Options::instance();
		$destination_url = rtrim( $options->get_destination_url(), '/' );
		if ( empty( $destination_url ) ) {
			return $content;
		}

		// First pass: regex on host (with optional port) to handle generic cases.
		$origin_host  = Util::origin_host();
		$host_no_port = preg_replace( '/:\\d+$/', '', (string) $origin_host );
		$pattern      = '/(?:https?:)?\\/\\/' . preg_quote( $host_no_port, '/' ) . '(?::\\d+)?/i';
		$replaced     = preg_replace( $pattern, $destination_url, $content );

		// Second pass fallback: replace exact origin home URL prefixes (http, https, protocol-relative),
		// including potential subdirectory installs.
		$home_http  = set_url_scheme( home_url( '/' ), 'http' );
		$home_https = set_url_scheme( home_url( '/' ), 'https' );
		$home_proto = preg_replace( '#^https?:#i', '', $home_https ); // //example.com/...

		$search    = [ rtrim( $home_http, '/' ), rtrim( $home_https, '/' ), rtrim( $home_proto, '/' ) ];
		$replaced2 = str_replace( $search, rtrim( $destination_url, '/' ), $replaced );

		// No logging here; just return the updated content if any replacements were made.

		return $replaced2;
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

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rank_math_redirections", ARRAY_A );


		if ( empty( $results ) ) {
			return null;
		}

		$results = array_map( function ( $item ) {
			$item["sources"] = maybe_unserialize( $item["sources"] );

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

		$redirections = $this->get_redirects();

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {

			if ( empty( $redirection['sources'] ) ) {
				continue;
			}

			foreach ( $redirection['sources'] as $source ) {

				if ( $source['comparison'] !== 'exact' ) {
					continue;
				}

				$url = home_url( $source['pattern'] );
				/** @var \Simply_Static\Page $static_page */
				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( __( 'RankMath Redirection URL', 'simply-static' ) );
				$static_page->found_on_id = 0;
				$static_page->save();
			}

		}

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
		$response = wp_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$xml_content = wp_remote_retrieve_body( $response );

			// Use SimpleXML to parse the XML
			libxml_use_internal_errors( true );
			$xml = simplexml_load_string( $xml_content );

			if ( $xml !== false && isset( $xml->sitemap ) ) {
				foreach ( $xml->sitemap as $sitemap ) {
					if ( isset( $sitemap->loc ) ) {
						$sitemap_url = (string) $sitemap->loc;
						$urls[] = $sitemap_url;
					}
				}
			}
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
			$doc->loadHTML( $dom, $load_options );
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
				$text = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $options->get_destination_url(), $decoded_text );
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
		$response = wp_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return;
		}

		$xml_content = wp_remote_retrieve_body( $response );

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content );
		
		if ( $xml === false ) {
			return;
		}
		
		// Extract sitemap URLs
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) ) {
					$sitemap_url = (string) $sitemap->loc;
					
					// Add the sitemap URL to the queue
					/** @var \Simply_Static\Page $static_page */
					$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
					$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$static_page->handler     = Rank_Math_Sitemap_Handler::class;
					$static_page->save();
				}
			}
		}
	}
}
