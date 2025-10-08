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

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-yoast-sitemap-handler.php' );
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
}
