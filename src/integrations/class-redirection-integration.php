<?php
namespace Simply_Static;

class Redirection_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'redirection';

	public function __construct() {
		$this->name = __( 'Redirection', 'simply-static' );
		$this->description = __( 'Integrates redirections from the "Redirection" Plugin automatically on each export.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'REDIRECTION_FILE' );
	}

	/**
	 * Adding Redirection URLs
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_redirections' ] );
	}

	/**
	 * Register all redirections.
	 *
	 * @return void
	 */
	public function register_redirections() {

		$redirections = $this->get_redirects();

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {
			$url = home_url( $redirection['url'] );
			Util::debug_log( 'Adding redirection URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Redirection URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}

	}

	/**
	 * Get Redirections.
	 * @return array|object|\stdClass[]|null
	 */
	protected function get_redirects() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT url FROM {$wpdb->prefix}redirection_items", ARRAY_A );

		return empty( $results ) ? null : $results;
	}
}