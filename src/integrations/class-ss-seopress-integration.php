<?php

namespace Simply_Static;

class SEOPress_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'seopress';

	public function __construct() {
		$this->name = __( 'SEOPress', 'simply-static' );
		$this->description = __( 'Adds XML sitemaps to generated static files.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_pages' ] );

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-seopress-sitemap-handler.php' );
	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_pages() {
		$url = home_url( 'sitemaps.xml' );

		$this->register_sitemap_page( $url );

		// Extract and add individual sitemap URLs from sitemaps.xml
		$this->extract_sitemap_urls_from_index();
	}

	/**
	 * Add sitemap URL to database for crawling.
	 *
	 * @param string $url given URL.
	 *
	 * @return void
	 */
	public function register_sitemap_page( $url ) {
		Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
		/** @var \Simply_Static\Page $static_page */
		$static_page = Page::query()->find_or_initialize_by( 'url', $url );
		$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->handler     = SEOPress_Sitemap_Handler::class;
		$static_page->save();
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		$urls[] = home_url( 'sitemaps.xml' );
		$urls[] = home_url( 'main-sitemap.xsl' );

		// Extract individual sitemap URLs from sitemaps.xml
		$sitemap_url = home_url( 'sitemaps.xml' );
		$response = wp_remote_get( $sitemap_url, array( 'timeout' => 30 ) );

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
	 * Extract sitemap URLs from sitemaps.xml and add them to the queue.
	 *
	 * @return void
	 */
	protected function extract_sitemap_urls_from_index() {
		$sitemap_url = home_url( 'sitemaps.xml' );
		$response = wp_remote_get( $sitemap_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			Util::debug_log( 'Failed to fetch sitemap index: ' . $sitemap_url );
			return;
		}

		$xml_content = wp_remote_retrieve_body( $response );

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content );

		if ( $xml === false ) {
			Util::debug_log( 'Failed to parse sitemap index XML: ' . $sitemap_url );
			return;
		}

		// Extract sitemap URLs
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) ) {
					$sitemap_url = (string) $sitemap->loc;

					// Add the sitemap URL to the queue
					Util::debug_log( 'Adding individual sitemap URL to queue: ' . $sitemap_url );
					/** @var \Simply_Static\Page $static_page */
					$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
					$static_page->set_status_message( __( 'Individual Sitemap URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$static_page->handler     = SEOPress_Sitemap_Handler::class;
					$static_page->save();
				}
			}
		}
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'SEOPRESS_VERSION' );
	}
}
