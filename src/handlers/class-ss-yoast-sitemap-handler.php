<?php

namespace Simply_Static;

class Yoast_Sitemap_Handler extends Page_Handler {

    /**
     * Run hooks on page request.
     *
     * Useful in case a type of page requires different hooks to be ran before the static page is generated.
     *
     * @return void
     */
    public function run_hooks() {
        // Filter XSL
        add_filter( 'wpseo_stylesheet_url', [ $this, 'stylesheet_url' ] );
    }

    /**
     * Get Stylesheet URL for XSL.
     *
     * @param $xsl_string
     * @return string
     */
    public function stylesheet_url( $xsl_string ) {
        return '<?xml-stylesheet type="text/xsl" href="' . esc_url( trailingslashit( $this->get_options()->get_destination_url() ) . 'main-sitemap.xsl' ) . '"?>';
    }

}