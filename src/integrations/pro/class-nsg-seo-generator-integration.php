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
		$this->name = __( 'NSG SEO Generator', 'simply-static' );
		$this->description = __( 'Integrates with the NSG SEO Generator plugin to include generated URLs during export (crawler available in Simply Static Pro).', 'simply-static' );
		$this->active_by_default = false;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		// Try the standard plugin active check.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			@include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_active = false;
		if ( function_exists( 'is_plugin_active' ) ) {
			$plugin_active = (
				is_plugin_active( 'nsg-seo-generator/nsg-seo-generator.php' ) ||
				is_plugin_active( 'nsg-seo-generator/plugin.php' ) ||
				is_plugin_active( 'nsg-seo-generator/index.php' )
			);
		}

		// Heuristic checks for NSG presence even if plugin path differs or is must-use/mu-loaded.
		$has_functions = (
			function_exists( 'nsg_seo_generator_get_urls' ) ||
			function_exists( 'nsg_seo_generator_get_generated_urls' ) ||
			function_exists( 'nsg_get_generated_urls' ) ||
			function_exists( 'nsg_seo_get_urls' )
		);

		$has_class = class_exists( '\\NSG_SEO_Generator' ) || class_exists( 'NSG_SEO_Generator' );

		$has_cpts = (
			post_type_exists( 'nw_seo_page' ) ||
			post_type_exists( 'nsg_seo_page' ) ||
			post_type_exists( 'nsg_seo_generator' ) ||
			post_type_exists( 'nsg-seo' ) ||
			post_type_exists( 'nsg-seo-generator' )
		);

		$has_options = (
			get_option( 'nsg_seo_generator_generated_urls', null ) !== null ||
			get_option( 'nsg_seo_generator_urls', null ) !== null ||
			get_option( 'nsg_generated_urls', null ) !== null ||
			get_option( 'nsg_seo_urls', null ) !== null
		);

		return (bool) ( $plugin_active || $has_functions || $has_class || $has_cpts || $has_options );
	}
}
