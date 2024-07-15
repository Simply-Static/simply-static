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
		$this->description = __( 'Adds sitemaps to generated static files.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
		add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );

		$this->include_file( 'handlers/class-ss-yoast-sitemap-handler.php' );
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

		return $urls;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'WPSEO_FILE' );
	}
}
