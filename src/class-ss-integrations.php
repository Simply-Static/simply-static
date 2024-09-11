<?php

namespace Simply_Static;

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
			'ss-adminbar'   => SS_Adminbar_Integration::class,
			'yoast'         => Yoast_Integration::class,
			'rank-math'     => Rank_Math_Integration::class,
			'aio-seo'       => AIO_SEO_Integration::class,
			'seopress'      => SEOPress_Integration::class,
			'elementor'     => Elementor_Integration::class,
			'elementor-pro' => Elementor_Pro_Integration::class,
			'cookieyes'     => CookieYes_Integration::class,
			'brizy'         => Brizy_Integration::class,
			'complianz'     => Complianz_Integration::class,
			'jetpack'       => Jetpack_Integration::class,
			'multilingual'  => Multilingual_Integration::class,
			'github'        => Github_Integration::class,
			'shortpixel'    => Shortpixel_Integration::class,
			'redirection'   => Redirection_Integration::class
		] );
	}

	public function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . 'src/integrations/';
		require_once $path . 'class-integration.php';

		// Core Integrations.
		require_once $path . 'class-ss-adminbar-integration.php';

		// Third-Party Integrations.
		require_once $path . 'class-yoast-integration.php';
		require_once $path . 'class-rank-math-integration.php';
		require_once $path . 'class-aio-seo-integration.php';
		require_once $path . 'class-seopress-integration.php';
		require_once $path . 'class-elementor-integration.php';
		require_once $path . 'class-elementor-pro-integration.php';
		require_once $path . 'class-cookie-yes-integration.php';
		require_once $path . 'class-brizy-integration.php';
		require_once $path . 'class-jetpack-integration.php';

		// Simply Static Pro integrations.
		require_once $path . 'class-pro-integration.php';
		require_once $path . 'pro/class-github-integration.php';
		require_once $path . 'pro/class-multilingual-integration.php';
		require_once $path . 'pro/class-shortpixel-integration.php';
		require_once $path . 'pro/class-complianz-integration.php';
		require_once $path . 'pro/class-redirection-integration.php';
	}
}