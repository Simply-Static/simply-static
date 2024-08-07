<?php

namespace Simply_Static;

class AIO_SEO_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'aio-seo';

	public function __construct() {
		$this->name = __( 'All in One SEO', 'simply-static' );
		$this->description = __( 'Adds sitemaps to generated static files.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
        add_filter( 'aioseo_unrecognized_allowed_query_args', [ $this, 'allowed_query_args' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_pages' ] );
		add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
	}

    /**
     * Adding 'simply_static_page' as an allowed query argument.
     *
     * @param array $args Query Arguments that are allowed and won't be removed from urls.
     * @return array|mixed
     */
    public function allowed_query_args( $args ) {
        if ( ! is_array( $args ) ) {
            $args = [];
        }

        $args[] = 'simply_static_page';
        return $args;
    }

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_pages() {
		$url = home_url( 'sitemap.xml' );

		$this->register_sitemap_page( $url );

		if ( function_exists( 'aioseo' ) ) {
			aioseo()->sitemap->type = 'general';
			$post_types             = aioseo()->sitemap->helpers->includedPostTypes();
			foreach ( $post_types as $post_type ) {
				$post_type_url = home_url( $post_type . '-sitemap.xml' );
				$this->register_sitemap_page( $post_type_url );
			}

			$taxonomies = aioseo()->sitemap->helpers->includedTaxonomies();
			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_url = home_url( $taxonomy . '-sitemap.xml' );
				$this->register_sitemap_page( $taxonomy_url );
			}
		}

	}

	/**
	 * Add sitemap URL to database for crawling.
	 *
	 * @param string $url given URL.
	 *
	 * @return void
	 */
	public function register_sitemap_page( $url ) {
		Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
		/** @var \Simply_Static\Page $static_page */
		$static_page = Page::query()->find_or_initialize_by( 'url', $url );
		$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
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
		$urls[] = home_url( 'sitemap.xml' );

		if ( function_exists( 'aioseo' ) ) {
			aioseo()->sitemap->type = 'general';
			$post_types             = aioseo()->sitemap->helpers->includedPostTypes();
			foreach ( $post_types as $post_type ) {
				$post_type_url = home_url( $post_type . '-sitemap.xml' );
				$urls[] = $post_type_url;
			}

			$taxonomies = aioseo()->sitemap->helpers->includedTaxonomies();
			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_url = home_url( $taxonomy . '-sitemap.xml' );
				$urls[] = $taxonomy_url;
			}
		}

		return $urls;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'AIOSEO_FILE' );
	}
}
