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
		do_action( 'ss_integrations_before_load' );
		$integrations = $this->get_integrations();

		foreach ( $integrations as $integration ) {
			$object = new $integration();
			$object->load();
		}
	}

	public function get_integrations() {
		return apply_filters( 'simply_static_integrations', [
			'yoast'         => Yoast_Integration::class,
			'rank-math'     => Rank_Math_Integration::class,
			'aio-seo'       => AIO_SEO_Integration::class,
			'seopress'      => SEOPress_Integration::class,
			'elementor'     => Elementor_Integration::class,
			'elementor-pro' => Elementor_Pro_Integration::class,
			'cookieyes'     => CookieYes_Integration::class,
			'brizy'         => Brizy_Integration::class,
			'jetpack'       => Jetpack_Integration::class
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
		require_once $path . 'class-elementor-pro-integration.php';
		require_once $path . 'class-cookie-yes-integration.php';
		require_once $path . 'class-brizy-integration.php';
		require_once $path . 'class-jetpack-integration.php';

		// SimplyCDN.
		require_once $path . 'simply-cdn/class-simply-cdn-integration.php';
		Simply_CDN_Integration::get_instance();
	}
}