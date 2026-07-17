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
	 * Run after the plugin's priority-10 integration bootstrap. Integrations load
	 * handler classes used by stored Page records, so running earlier can make a
	 * valid integration handler fall back to the generic Page_Handler.
	 */
	const INIT_PRIORITY = 20;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		add_action( 'init', [ $this, 'run_page_handlers_from_request' ], self::INIT_PRIORITY );
	}

	/**
	 * Includes
	 *
	 * @return void
	 */
	public function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . 'src/handlers/';
		require_once $path . 'class-ss-page-handler.php';
		require_once $path . 'class-ss-404-handler.php';
		require_once $path . 'class-ss-additional-file-handler.php';
		require_once $path . 'class-ss-text-file-handler.php';
		require_once $path . 'class-ss-rule-file-handler.php';
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

		do_action( 'simply_static_page_handler_request_after_hooks', $handler );

		$this->remove_page_id_from_request_uri();
	}

	/**
	 * Remove the internal page identifier from the public request URL.
	 *
	 * The query argument is needed long enough to resolve and initialize the
	 * page handler. Leaving it in REQUEST_URI after that point allows plugins
	 * which serialize the current request URL to expose the internal identifier
	 * in the generated static page. Keep $_GET intact for integrations which use
	 * it to detect a Simply Static request.
	 *
	 * @return void
	 */
	protected function remove_page_id_from_request_uri() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$_SERVER['REQUEST_URI'] = remove_query_arg(
			'simply_static_page',
			wp_unslash( $_SERVER['REQUEST_URI'] )
		);
	}
}
