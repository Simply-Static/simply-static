<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Home Crawler class
 *
 * This crawler detects the home page URL.
 */
class Home_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'home';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Homepage URL', 'simply-static' );
		$this->description = __( 'Detects the homepage URL.', 'simply-static' );
		$this->active_by_default = true;
	}

	/**
	 * Detect home page URL.
	 *
	 * @return array List containing the home page URL
	 */
	public function detect() : array {
		$home_urls = [];

		// Get the home page URL
		$home_url = home_url( '/' );
		$home_urls[] = $home_url;

		// If using a static front page, also include that URL
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = get_option( 'page_on_front' );
			if ( $front_page_id ) {
				// Always include the front page URL, regardless of post type settings
				$front_page_url = get_permalink( $front_page_id );
				if ( is_string( $front_page_url ) && $front_page_url !== $home_url ) {
					$home_urls[] = $front_page_url;
				}
			}
		}

		return $home_urls;
	}
}
