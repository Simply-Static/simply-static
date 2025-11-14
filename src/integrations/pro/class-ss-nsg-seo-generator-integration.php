<?php
namespace Simply_Static;

class Nsg_SEO_Generator_Integration extends Pro_Integration {
	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'nsg-seo-generator';

	public function __construct() {
		$this->name = __( 'SEO Generator', 'simply-static' );
		$this->description = __( 'Efficiently crawl, queue and export pSEO websites with this integration.', 'simply-static' );
		$this->active_by_default = false;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return function_exists( 'is_plugin_active' ) && (
			is_plugin_active( 'nsg-seo-generator/nsg-seo-generator.php' ) ||
			is_plugin_active( 'nsg-seo-generator/plugin.php' ) ||
			is_plugin_active( 'nsg-seo-generator/index.php' )
		);
	}
}
