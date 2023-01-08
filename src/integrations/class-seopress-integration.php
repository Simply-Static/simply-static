<?php

namespace Simply_Static;

class SEOPress_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'seopress';

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_pages' ] );
	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_pages() {
		$url = home_url( 'sitemaps.xml' );

		$this->register_sitemap_page( $url );
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
		$static_page->save();
	}

	/**
	 * Can this integration run?
	 *
	 * @return bool
	 */
	public function can_run() {
		return defined( 'SEOPRESS_VERSION' );
	}
}