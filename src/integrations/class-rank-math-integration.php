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

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
		add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );

		$this->include_file( 'handlers/class-ss-rank-math-sitemap-handler.php' );
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

		$url = Router::get_base_url( 'sitemap_index.xml' );
		Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
		/** @var \Simply_Static\Page $static_page */
		$static_page = Page::query()->find_or_initialize_by( 'url', $url );
		$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->handler     = Rank_Math_Sitemap_Handler::class;
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
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return $urls;
		}

		$urls[] = Router::get_base_url( 'sitemap_index.xml' );

		return $urls;
	}

	/**
	 * Can this integration run?
	 *
	 * @return bool
	 */
	public function can_run() {
		return class_exists( 'RankMath' );
	}
}