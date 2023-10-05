<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Handlers.
 */
class Page_Handlers {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		add_action( 'init', [ $this, 'run_page_handlers_from_request' ], 1 );
	}

	/**
	 * Includes
	 *
	 * @return void
	 */
	public function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . 'src/handlers/';
		require_once $path . 'class-ss-page-handler.php';
		require_once $path . 'class-ss-handler-404.php';
	}

	/**
	 * Check if we are currently in a Static Page Request.
	 * This means that we are retrieving the page to create a static one.
	 *
	 * @return bool
	 */
	protected function is_static_page_request() {
		return isset( $_GET['simply_static_page'] ) && absint( $_GET['simply_static_page'] ) > 0;
	}

	/**
	 * Get Static Page.
	 *
	 * @return \Simply_Static\Page|null
	 */
	public function get_static_page() {
		return Page::query()->find_by( 'id', absint( $_GET['simply_static_page'] ) );
	}

	/**
	 * Get a Page Handler and run hooks.
	 *
	 * @return void
	 */
	public function run_page_handlers_from_request() {
		if ( ! $this->is_static_page_request() ) {
			return;
		}

		$page = $this->get_static_page();

		if (!$page) {
			return;
		}

		$handler = $page->get_handler();

		$handler->run_hooks();
	}
}