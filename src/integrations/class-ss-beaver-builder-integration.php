<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Beaver_Builder_Integration extends Integration {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'beaver-builder';

	/**
	 * Run outside the saved integrations array so existing sites get the defaults.
	 *
	 * @var bool
	 */
	protected $always_active = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Beaver Builder', 'simply-static' );
		$this->description = __( 'Includes Beaver Builder cache files when exporting static sites.', 'simply-static' );
	}

	/**
	 * Check if Beaver Builder is active.
	 *
	 * @return bool
	 */
	public function dependency_active() {
		return defined( 'FL_BUILDER_VERSION' );
	}

	/**
	 * Run automatically, but only when Beaver Builder is present.
	 *
	 * @return bool
	 */
	public function can_run() {
		return $this->dependency_active();
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! $this->dependency_active() ) {
			return;
		}

		add_filter( 'ss_get_option_crawlers', [ $this, 'include_beaver_builder_crawler' ] );
		add_filter( 'ss_get_settings', [ $this, 'include_beaver_builder_crawler_in_settings' ] );
		add_filter( 'ss_additional_files', [ $this, 'include_beaver_builder_cache_directory' ] );
	}

	/**
	 * Automatically enable the Beaver Builder cache crawler on Beaver Builder sites.
	 *
	 * @param mixed $crawlers Saved crawler option.
	 *
	 * @return mixed
	 */
	public function include_beaver_builder_crawler( $crawlers ) {
		if ( ! is_array( $crawlers ) || empty( $crawlers ) ) {
			return $crawlers;
		}

		if ( ! in_array( 'beaver-builder', $crawlers, true ) ) {
			$crawlers[] = 'beaver-builder';
		}

		return array_values( array_unique( $crawlers ) );
	}

	/**
	 * Reflect the computed crawler selection in REST settings used by the admin UI.
	 *
	 * @param array $settings Settings returned to the admin app.
	 *
	 * @return array
	 */
	public function include_beaver_builder_crawler_in_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		if ( isset( $settings['crawlers'] ) && is_array( $settings['crawlers'] ) ) {
			$settings['crawlers'] = $this->include_beaver_builder_crawler( $settings['crawlers'] );
		}

		return $settings;
	}

	/**
	 * Add Beaver Builder's generated cache directory to Additional Files.
	 *
	 * @param array $additional_files Additional files/directories to export.
	 *
	 * @return array
	 */
	public function include_beaver_builder_cache_directory( $additional_files ) {
		$additional_files = is_array( $additional_files ) ? $additional_files : [];
		$cache_directory  = $this->get_cache_directory();

		if ( empty( $cache_directory ) || ! is_dir( $cache_directory ) ) {
			return $additional_files;
		}

		$cache_directory = trailingslashit( Util::normalize_slashes( $cache_directory ) );
		$existing       = array_map(
			function ( $path ) {
				return trailingslashit( Util::normalize_slashes( (string) $path ) );
			},
			$additional_files
		);

		if ( ! in_array( $cache_directory, $existing, true ) ) {
			$additional_files[] = $cache_directory;
		}

		return $additional_files;
	}

	/**
	 * Get Beaver Builder's generated cache directory.
	 *
	 * @return string
	 */
	protected function get_cache_directory() {
		$uploads = wp_upload_dir();

		if ( empty( $uploads['basedir'] ) ) {
			return '';
		}

		return trailingslashit( $uploads['basedir'] ) . 'bb-plugin/cache';
	}
}
