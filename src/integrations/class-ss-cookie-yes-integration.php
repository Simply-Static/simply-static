<?php

namespace Simply_Static;

use DOMDocument;
use DOMXPath;

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
	 * @param DOMDocument $dom
	 *
	 * @return void
	 */
	public function fix_cookieyes_template( $dom ) {
		// Create a DOMXPath object to query the DOM
		$xpath = new DOMXPath( $dom );

		// Find all script tags
		$script_tags = $xpath->query( '//script' );

		if ( $script_tags ) {
			foreach ( $script_tags as $tag ) {
				// Check if the script tag has the specific ID
				if ( $tag->hasAttribute( 'id' ) && 'ckyBannerTemplate' === $tag->getAttribute( 'id' ) ) {
					// Get the content of the script tag
					$content = $tag->textContent;

					// Apply the regex replacement
					$updated_content = preg_replace( '/<\\\/i', '<', $content );

					// Update the content of the script tag
					$tag->textContent = $updated_content;
				}
			}
		}
	}
}
