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
			'ss-uam'           => SS_UAM_Integration::class,
			'ss-adminbar'       => SS_Adminbar_Integration::class,
			'yoast'             => Yoast_Integration::class,
			'rank-math'         => Rank_Math_Integration::class,
			'aio-seo'           => AIO_SEO_Integration::class,
			'seopress'          => SEOPress_Integration::class,
			'elementor'         => Elementor_Integration::class,
			'divi'              => Divi_Integration::class,
			'cookieyes'         => CookieYes_Integration::class,
			'complianz'         => Complianz_Integration::class,
			//'search-and-filter' => SearchAndFilter_Integration::class,
			'multilingual'      => Multilingual_Integration::class,
			'github'            => Github_Integration::class,
			'shortpixel'        => Shortpixel_Integration::class,
			'redirection'       => Redirection_Integration::class,
			'environments'      => Environments_Integration::class,
			'nsg-seo-generator' => Nsg_SEO_Generator_Integration::class,
			'delay'             => Delay_Integration::class,
			'multisite'         => Multisite_Integration::class,
			'the-events-calendar' => The_Events_Calendar_Integration::class,
		] );
	}

	public function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . 'src/integrations/';
		require_once $path . 'class-ss-integration.php';

		// Core Integrations.
		require_once $path . 'class-ss-adminbar-integration.php';
		require_once $path . 'class-ss-delay-integration.php';

		// Third-Party Integrations.
		require_once $path . 'class-ss-yoast-integration.php';
		require_once $path . 'class-ss-rank-math-integration.php';
		require_once $path . 'class-ss-aio-seo-integration.php';
		require_once $path . 'class-ss-seopress-integration.php';
		require_once $path . 'class-ss-elementor-integration.php';
		require_once $path . 'class-ss-divi-integration.php';
		require_once $path . 'class-ss-cookie-yes-integration.php';

		// Simply Static Pro integrations (visible in Free as samples; runnable in Pro).
		require_once $path . 'class-ss-pro-integration.php';
		require_once $path . 'pro/class-ss-uam-integration.php';
		require_once $path . 'pro/class-ss-github-integration.php';
		require_once $path . 'pro/class-ss-multilingual-integration.php';
		require_once $path . 'pro/class-ss-shortpixel-integration.php';
		require_once $path . 'pro/class-ss-complianz-integration.php';
		require_once $path . 'pro/class-ss-search-and-filter-integration.php';
		require_once $path . 'pro/class-ss-redirection-integration.php';
		require_once $path . 'pro/class-ss-environments-integration.php';
		require_once $path . 'pro/class-ss-nsg-seo-generator-integration.php';
		require_once $path . 'pro/class-ss-multisite-integration.php';
		require_once $path . 'pro/class-ss-the-events-calendar-integration.php';
	}
}
