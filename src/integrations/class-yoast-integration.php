<?php

namespace Simply_Static;

class Yoast_Integration extends Integration {

    protected $id = 'yoast';

    public function run() {
         add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
    }

    public function register_sitemap_page() {
        if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
            return;
        }

        $url = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );
        Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
        /** @var \Simply_Static\Page $static_page */
        $static_page = Page::query()->find_or_initialize_by( 'url', $url );
        $static_page->set_status_message( __( "Sitemap URL", 'simply-static' ) );
        $static_page->found_on_id = 0;
        $static_page->handler = Yoast_Sitemap_Handler::class;
        $static_page->save();
    }

    /**
     * Can this integration run?
     * @return bool
     */
    public function can_run() {die('Running Yoast');
        return defined( 'WPSEO_FILE' );
    }
}