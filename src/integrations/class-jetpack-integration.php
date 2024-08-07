<?php

namespace Simply_Static;

class Jetpack_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'jetpack';

	public function __construct() {
		$this->name = __( 'Jetpack', 'simply-static' );
		$this->description = __( 'Adds scripts for carousels and sliders to the static site.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_jetpack_files' ] );
	}

	/**
	 * Register files for Jetpack.
	 *
	 * @return void
	 */
	public function register_jetpack_files() {
		// Add necessary files for the carousel.
		$urls = [
			plugin_dir_url( 'jetpack' ) . 'jetpack/_inc/build/carousel/swiper-bundle.min.js',
			plugin_dir_url( 'jetpack' ) . 'jetpack/_inc/blocks/swiper.css',
		];

		foreach ( $urls as $url ) {
			Util::debug_log( 'Adding Jetpack file to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Jetpack Integration', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return class_exists( 'Jetpack' );
	}
}