<?php

namespace Simply_Static;

use voku\helper\SimpleHtmlDom;

class CookieYes_Integration extends  Integration {

    /**
     * Given plugin handler ID.
     *
     * @var string Handler ID.
     */
    protected $id = 'cookieyes';

    /**
     * Can this integration run?
     *
     * @return bool
     */
    public function can_run() {
        return defined( 'CKY_APP_URL' );
    }

    /**
     * Run the integration.
     *
     * @return void
     */
    public function run() {
        add_action( 'ss_after_extract_and_replace_urls_in_html', [ $this, 'fix_cookieyes_template' ] );
    }

    /**
     * @param SimpleHtmlDom$dom
     * @return void
     */
    public function fix_cookieyes_template( $dom ) {
        $tags = $dom->find( 'script' );

        foreach ( $tags as $tag ) {
            if ( 'ckyBannerTemplate' !== $tag->getAttribute( 'id' ) ) {
                continue;
            }

            $tag->innertext = preg_replace('/<\\\/i', '<', $tag->innertext );
        }
    }
}