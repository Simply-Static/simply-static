<?php

namespace Simply_Static;

class Yoast_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'yoast';

	public function __construct() {
		$this->name = __( 'Yoast', 'simply-static' );
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

  // Allow Simply Static setting to control sitemap inclusion on Single Export.
  // Register early so that other plugins/themes (default priority 10+) can override via the same filter.
  add_filter( 'ssp_single_export_add_xml_sitemap', [ $this, 'maybe_enable_sitemap_single_export' ], 1 );

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-yoast-sitemap-handler.php' );
	}

	/**
	 * Maybe enable sitemap inclusion on Single Export based on plugin setting.
	 * Developers can still override via filters with higher priority.
	 *
	 * @param bool $enabled Current enabled flag from filters.
	 *
	 * @return bool
	 */
	public function maybe_enable_sitemap_single_export( $enabled ) {
		$settings = get_option( 'simply-static' );
		if ( is_array( $settings ) && array_key_exists( 'ss_single_export_add_xml_sitemap', $settings ) ) {
			return (bool) $settings['ss_single_export_add_xml_sitemap'];
		}
		return $enabled;
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

		$redirections = get_option("wpseo-premium-redirects-base");

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {

			if ( strpos( $redirection['origin'], 'http' ) === 0 ) {
				continue;
			}

			$url = home_url( $redirection['origin'] );
			Util::debug_log( 'Adding Yoast redirection URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Yoast Redirection URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}

	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_page() {
		if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
			return;
		}

		$urls = array(
			\WPSEO_Sitemaps_Router::get_base_url( 'sitemap.xml' ),
			\WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' ),
			\WPSEO_Sitemaps_Router::get_base_url( 'main-sitemap.xsl' ),
		);

		foreach ( $urls as $url ) {
			Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->handler     = Yoast_Sitemap_Handler::class;
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
		if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
			return $urls;
		}

		$urls[] = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap.xml' );
		$urls[] = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );
		$urls[] = \WPSEO_Sitemaps_Router::get_base_url( 'main-sitemap.xsl' );

		// Extract individual sitemap URLs from sitemap_index.xml
		$sitemap_index_url = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );
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
						Util::debug_log( 'Adding individual sitemap URL to single export: ' . $sitemap_url );
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
			$load_options = defined('LIBXML_HTML_NOIMPLIED') ? (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD) : 0;
			$doc->loadHTML( $dom, $load_options );
			libxml_clear_errors();
		} elseif ( $dom instanceof \DOMDocument ) {
			$doc = $dom;
		} else {
			// Unknown type; nothing to do.
			return $dom;
		}

		// Use DOMXPath to find script elements with class 'yoast-schema-graph'
		$xpath = new \DOMXPath( $doc );
		$scripts = $xpath->query( '//script[contains(@class, "yoast-schema-graph")]' );

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
		return defined( 'WPSEO_FILE' );
	}

	/**
	 * Extract sitemap URLs from sitemap_index.xml and add them to the queue.
	 *
	 * @return void
	 */
	protected function extract_sitemap_urls_from_index() {
		if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
			return;
		}

		$sitemap_index_url = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );
		$response = wp_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			Util::debug_log( 'Failed to fetch sitemap index: ' . $sitemap_index_url );
			return;
		}

		$xml_content = wp_remote_retrieve_body( $response );

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content );

		if ( $xml === false ) {
			Util::debug_log( 'Failed to parse sitemap index XML: ' . $sitemap_index_url );
			return;
		}

		// Extract sitemap URLs
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) ) {
					$sitemap_url = (string) $sitemap->loc;

					// Add the sitemap URL to the queue
					Util::debug_log( 'Adding sitemap URL to queue: ' . $sitemap_url );
					/** @var \Simply_Static\Page $static_page */
					$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
					$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$static_page->handler     = Yoast_Sitemap_Handler::class;
					$static_page->save();
				}
			}
		}
	}

	/**
	 * Maybe add robots.txt and llms.txt to additional files when Yoast is active.
	 *
	 * Yoast may generate robots.txt dynamically (via WP core) and doesn't provide llms.txt by default,
	 * but we still attempt to fetch both endpoints to maintain parity with other SEO plugins.
	 *
	 * @param array $additional_files
	 * @return array
	 */
	public function maybe_add_text_files( $additional_files ) {
		$additional_files = is_array( $additional_files ) ? $additional_files : [];

		// Global flags to allow disabling robots.txt and llms.txt entirely.
		$include_robots = (bool) apply_filters( 'ss_include_robots_txt_in_export', true );
		$include_llms   = (bool) apply_filters( 'ss_include_llms_txt_in_export', true );

		$robots_physical = ABSPATH . 'robots.txt';
		$llms_physical   = ABSPATH . 'llms.txt';

		// If a physical llms.txt exists in the WP root, ensure it is exported with URL replacements.
		if ( $include_llms && file_exists( $llms_physical ) ) {
			$body = @file_get_contents( $llms_physical );
			if ( is_string( $body ) && $body !== '' ) {
				$body = $this->replace_urls_in_text( $body );
				$path = $this->write_archive_file( 'llms.txt', $body );
				if ( $path ) {
					$this->run_text_file_handler( 'llms.txt' );
				}
			}
		} elseif ( ! $include_llms ) {
			Util::debug_log( '[Yoast] llms.txt generation disabled via ss_include_llms_txt_in_export' );
		}

		$archive_dir = Options::instance()->get_archive_dir();
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}

		// robots.txt via public endpoint
		if ( $include_robots && ! file_exists( $robots_physical ) ) {
			$robots_url = home_url( '/robots.txt' );
			$response   = wp_remote_get( $robots_url, [ 'timeout' => 20 ] );
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$body = is_string( $body ) ? $body : '';
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'robots.txt', $body );
					if ( $path ) {
						$this->run_text_file_handler( 'robots.txt' );
					}
				}
			}
		} elseif ( ! $include_robots ) {
			Util::debug_log( '[Yoast] robots.txt generation disabled via ss_include_robots_txt_in_export' );
		}

		// llms.txt via public endpoint (if available)
		if ( $include_llms && ! file_exists( $llms_physical ) ) {
			$llms_url = home_url( '/llms.txt' );
			$response = wp_remote_get( $llms_url, [ 'timeout' => 20 ] );
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$body = is_string( $body ) ? $body : '';
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'llms.txt', $body );
					if ( $path ) {
						$this->run_text_file_handler( 'llms.txt' );
					}
				}
			}
		} elseif ( ! $include_llms ) {
			Util::debug_log( '[Yoast] llms.txt generation disabled via ss_include_llms_txt_in_export' );
		}

		return $additional_files;
	}

	/**
	 * Write a file directly into the current archive directory.
	 *
	 * @param string $filename
	 * @param string $content
	 * @return string|null
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
	 *
	 * @param string $content
	 * @return string
	 */
	private function replace_urls_in_text( $content ) {
		if ( ! is_string( $content ) || $content === '' ) {
			return $content;
		}
		$options         = Options::instance();
		$destination_url = rtrim( $options->get_destination_url(), '/' );
		if ( empty( $destination_url ) ) {
			return $content;
		}
		$origin_host  = Util::origin_host();
		$host_no_port = preg_replace( '/:\\d+$/', '', (string) $origin_host );
		$pattern      = '/(?:https?:)?\\/\\/' . preg_quote( $host_no_port, '/' ) . '(?::\\d+)?/i';
		$replaced     = preg_replace( $pattern, $destination_url, $content );

		$home_http  = set_url_scheme( home_url( '/' ), 'http' );
		$home_https = set_url_scheme( home_url( '/' ), 'https' );
		$home_proto = preg_replace( '#^https?:#i', '', $home_https );
		$search    = [ rtrim( $home_http, '/' ), rtrim( $home_https, '/' ), rtrim( $home_proto, '/' ) ];
		$replaced2 = str_replace( $search, rtrim( $destination_url, '/' ), $replaced );
		return $replaced2;
	}

	/**
	 * Run the Text_File_Handler on a relative file within the archive directory.
	 *
	 * @param string $relative_filename
	 * @return void
	 */
	private function run_text_file_handler( $relative_filename ) {
		if ( ! class_exists( __NAMESPACE__ . '\\Text_File_Handler', false ) ) {
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
			$page->file_path = ltrim( $relative_filename, '/\\' );
			$handler = new Text_File_Handler( $page );
			$handler->after_file_fetch( $archive_dir );
		} catch ( \Throwable $e ) {
			// silent
		}
	}

}
