<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

	/**
	 * @return void
	 */
	public function load() {
		$this->includes();
		$integrations = $this->get_integrations();

		foreach ( $integrations as $integration ) {
			$object = new $integration();
			$object->load();
		}
	}

	public function get_integrations() {
		return apply_filters( 'simply_static_integrations', [
			'yoast'     => Yoast_Integration::class,
			'rank-math' => Rank_Math_Integration::class,
			'aio-seo'   => AIO_SEO_Integration::class,
			'seopress'  => SEOPress_Integration::class,
			'elementor' => Elementor_Integration::class,
			'cookieyes' => CookieYes_Integration::class,
			'brizy'     => Brizy_Integration::class
		] );
	}

	public function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . 'src/integrations/';
		require_once $path . 'class-integration.php';
		require_once $path . 'class-yoast-integration.php';
		require_once $path . 'class-rank-math-integration.php';
		require_once $path . 'class-aio-seo-integration.php';
		require_once $path . 'class-seopress-integration.php';
		require_once $path . 'class-elementor-integration.php';
		require_once $path . 'class-cookieyes-integration.php';
		require_once $path . 'class-brizy-integration.php';
	}
}