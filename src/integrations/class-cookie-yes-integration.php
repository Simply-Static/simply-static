<?php

namespace Simply_Static;

use voku\helper\SimpleHtmlDom;

class CookieYes_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'cookieyes';

	public function __construct() {
		$this->name = __( 'CookieYes | GDPR Cookie Consent', 'simply-static' );
		$this->description = __( 'Fixes scripts given by CookieYes to work on exported pages.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
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
	 * @param SimpleHtmlDom $dom
	 *
	 * @return void
	 */
	public function fix_cookieyes_template( $dom ) {
		$tags = $dom->find( 'script' );

		foreach ( $tags as $tag ) {
			if ( 'ckyBannerTemplate' !== $tag->getAttribute( 'id' ) ) {
				continue;
			}

			$tag->innerhtmlKeep = preg_replace( '/<\\\/i', '<', $tag->innerhtmlKeep );
		}
	}
}