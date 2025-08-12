<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Page_Handler {

	/**
	 * Page.
	 * @var Page|null
	 */
	protected $page = null;

	/**
	 * Constructor method.
	 *
	 * @param Page $page
	 */
	public function __construct( Page $page ) {
		$this->page = $page;
	}

	/**
	 * Add query arguments in case it needs it.
	 *
	 * This way we know which page we are fetching in case we need a handler to run actions/filters.
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	public function prepare_url( $url ) {
		// Parse the URL to check if it already has query parameters
		$parsed_url = parse_url( $url );

		// If the URL already has query parameters, append with & instead of ?
		if ( isset( $parsed_url['query'] ) && ! empty( $parsed_url['query'] ) ) {
			// Check if the URL already ends with a query parameter
			if ( substr( $url, -1 ) === '&' ) {
				$url .= 'simply_static_page=' . $this->page->id;
			} else {
				$url .= '&simply_static_page=' . $this->page->id;
			}
		} else {
			// No existing query parameters, use add_query_arg
			$url = add_query_arg( 'simply_static_page', $this->page->id, $url );
		}

		Util::debug_log( 'URL Prepared:' . $url );

		return $url;
	}

	/**
	 * Get Options Instance.
	 *
	 * @return \Simply_Static\Options|null
	 */
	public function get_options() {
		return Options::instance();
	}

	/**
	 * Run hooks on page request.
	 *
	 * Useful in case a type of page requires different hooks to be ran before the static page is generated.
	 *
	 * @return void
	 */
	public function run_hooks() {
	}

	public function after_file_fetch( $destination_dir ) {}

    public function get_path_info( $path_info ) { return $path_info; }
    public function get_relative_dir( $relative_dir ) { return $relative_dir; }
}
