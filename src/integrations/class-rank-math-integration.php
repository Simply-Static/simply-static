<?php

namespace Simply_Static;

use RankMath\Sitemap\Router;

class Rank_Math_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'rank-math';

	public function __construct() {
		$this->name        = __( 'Rank Math', 'simply-static' );
		$this->description = __( 'Automatically includes your XML sitemaps, handles URL replacements in schema.org markup, and creates redirects on your static site for you.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_redirections' ] );
		add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		add_action( 'ss_dom_before_save', [ $this, 'replace_json_schema' ], 10, 2 );

		$this->include_file( 'handlers/class-ss-rank-math-sitemap-handler.php' );
	}

	/**
	 * Get Redirections.
	 * @return array|object|\stdClass[]|null
	 */
	protected function get_redirects() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rank_math_redirections", ARRAY_A );


		if ( empty( $results ) ) {
			return null;
		}

		$results = array_map( function ( $item ) {
			$item["sources"] = maybe_unserialize( $item["sources"] );

			return $item;
		}, $results );

		return $results;
	}

	/**
	 * Register all redirections.
	 *
	 * @return void
	 */
	public function register_redirections() {
		// Only on full or update exports.
		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );

		if ( ! empty( $use_build ) || ! empty( $use_single ) ) {
			return;
		}

		$redirections = $this->get_redirects();

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {

			if ( empty( $redirection['sources'] ) ) {
				continue;
			}

			foreach ( $redirection['sources'] as $source ) {

				if ( $source['comparison'] !== 'exact' ) {
					continue;
				}

				$url = home_url( $source['pattern'] );
				Util::debug_log( 'Adding RankMath redirection URL to queue: ' . $url );
				/** @var \Simply_Static\Page $static_page */
				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( __( 'RankMath Redirection URL', 'simply-static' ) );
				$static_page->found_on_id = 0;
				$static_page->save();
			}

		}

	}


	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_page() {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return;
		}

		$url = Router::get_base_url( 'sitemap_index.xml' );
		Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
		/** @var \Simply_Static\Page $static_page */
		$static_page = Page::query()->find_or_initialize_by( 'url', $url );
		$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->handler     = Rank_Math_Sitemap_Handler::class;
		$static_page->save();
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return $urls;
		}

		$urls[] = Router::get_base_url( 'sitemap_index.xml' );

		return $urls;
	}

	/**
	 * Replace JSON schema for schema.org
	 *
	 * @param object $dom given dom element.
	 * @param string $url given URL.
	 *
	 * @return object
	 */
	public function replace_json_schema( $dom, $url ) {
		$options = Options::instance();

		foreach ( $dom->find( 'script.rank-math-schema' ) as $script ) {
			$decoded_text      = html_entity_decode( $script->outertext, ENT_NOQUOTES );
			$text              = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $options->get_destination_url(), $decoded_text );
			$script->outertext = $text;
		}

		return $dom;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return class_exists( 'RankMath' );
	}
}