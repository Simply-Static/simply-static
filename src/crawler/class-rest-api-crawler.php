<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static REST API Crawler class
 *
 * This crawler detects WordPress REST API endpoints.
 * It is only active when the 'add_rest_api' option is enabled.
 */
class Rest_Api_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'rest_api';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'REST API', 'simply-static' );
		$this->description = __( 'Detects WordPress REST API endpoints.', 'simply-static' );
	}

	/**
	 * Check if the crawler is active.
	 * This crawler is only active when the 'add_rest_api' option is enabled.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$options = \Simply_Static\Options::instance();

		// Only active if the add_rest_api option is enabled
		if ( ! $options->get( 'add_rest_api' ) ) {
			return false;
		}

		// Otherwise, use the default active check from the parent class
		return parent::is_active();
	}

	/**
	 * Detect REST API URLs.
	 *
	 * @return array List of REST API URLs
	 */
	public function detect() : array {
		$rest_urls = [];

		// Get the REST API base URL
		$rest_url = get_rest_url();

		// Add the base REST API URL
		$rest_urls[] = $rest_url;

		// Get all registered REST routes
		$rest_server = rest_get_server();
		$routes = $rest_server->get_routes();

		// Add URLs for each route
		foreach ( $routes as $route => $handlers ) {
			// Skip internal WordPress routes that aren't typically needed
			if ( $this->should_skip_route( $route ) ) {
				continue;
			}

			// Build the full URL for this route
			$route_clean = $route !== null ? ltrim( $route, '/' ) : '';
			$route_url = $rest_url . $route_clean;

			// Remove any regex patterns from the URL
			$route_url = preg_replace( '/\(\?[^)]+\)/', '', $route_url );
			$route_url = str_replace( '//', '/', $route_url );

			// Add the route URL if it's valid
			if ( filter_var( $route_url, FILTER_VALIDATE_URL ) ) {
				$rest_urls[] = $route_url;
			}
		}

		return array_unique( $rest_urls );
	}

	/**
	 * Check if a route should be skipped.
	 *
	 * @param string $route The route to check.
	 * @return boolean Whether the route should be skipped.
	 */
	private function should_skip_route( $route ) {
		// Skip internal WordPress routes that aren't typically needed
		$skip_patterns = [
			'/wp/v2/block-renderer',
			'/wp/v2/block-types',
			'/wp/v2/search',
			'/wp/v2/settings',
			'/wp/v2/themes',
			'/wp/v2/plugins',
			'/wp/v2/users/me',
			'/wp/v2/users/(?P<id>[\d]+)/application-passwords',
		];

		foreach ( $skip_patterns as $pattern ) {
			if ( strpos( $route, $pattern ) === 0 ) {
				return true;
			}
		}

		return false;
	}
}
