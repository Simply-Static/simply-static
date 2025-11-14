<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Sitemap Crawler class
 *
 * This crawler detects URLs from XML sitemaps.
 */
class Sitemap_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'sitemap';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Sitemap URLs', 'simply-static' );
		$this->description = __( 'Detects URLs from XML sitemaps.', 'simply-static' );
	}

	/**
	 * Detect sitemap URLs.
	 *
	 * @return array List of URLs from sitemaps
	 */
	public function detect() : array {
		$urls = [];

		// First, find sitemap URLs
		$sitemap_urls = $this->find_sitemap_urls();

		// Then extract URLs from each sitemap
		foreach ($sitemap_urls as $sitemap_url) {
			$extracted_urls = $this->extract_urls_from_sitemap($sitemap_url);
			$urls = array_merge($urls, $extracted_urls);
		}

		return array_unique($urls);
	}

	/**
	 * Find sitemap URLs
	 *
	 * @return array List of sitemap URLs
	 */
	private function find_sitemap_urls() : array {
		$sitemap_urls = [];

		// Common sitemap locations
		$common_sitemaps = [
			home_url('/sitemap.xml'),
			home_url('/sitemap_index.xml'),
			home_url('/wp-sitemap.xml'), // WordPress default sitemap
		];

		foreach ($common_sitemaps as $sitemap_url) {
			$response = wp_remote_head($sitemap_url, ['timeout' => 5]);

			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
				$sitemap_urls[] = $sitemap_url;
			}
		}

		// Check for Yoast SEO sitemap
		if (defined('WPSEO_VERSION')) {
			$sitemap_urls[] = home_url('/sitemap_index.xml');
		}

		// Check for Rank Math sitemap
		if (class_exists('RankMath')) {
			$sitemap_urls[] = home_url('/sitemap_index.xml');
		}

		// Check for All in One SEO sitemap
		if (class_exists('AIOSEO')) {
			$sitemap_urls[] = home_url('/sitemap.xml');
		}

		// Check for SEOPress sitemap
		if (defined('SEOPRESS_VERSION')) {
			$sitemap_urls[] = home_url('/sitemaps.xml');
		}

		// Check for WordPress default sitemap (WP 5.5+)
		if (function_exists('wp_sitemaps_get_server')) {
			$sitemap_urls[] = home_url('/wp-sitemap.xml');
		}

		return array_unique($sitemap_urls);
	}

	/**
	 * Extract URLs from a sitemap
	 *
	 * @param string $sitemap_url URL of the sitemap
	 * @return array List of URLs found in the sitemap
	 */
	private function extract_urls_from_sitemap($sitemap_url) : array {
		$urls = [];

		$response = wp_remote_get($sitemap_url, ['timeout' => 10]);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return $urls;
		}

		$xml_content = wp_remote_retrieve_body($response);

		// Check if this is a sitemap index
		if (strpos($xml_content, '<sitemapindex') !== false) {
			// Extract sitemap URLs from the index
			$urls = $this->extract_urls_from_sitemap_index($xml_content);
		} else {
			// Extract URLs from a regular sitemap
			$urls = $this->extract_urls_from_sitemap_content($xml_content);
		}

		return $urls;
	}

	/**
	 * Extract sitemap URLs from a sitemap index
	 *
	 * @param string $xml_content XML content of the sitemap index
	 * @return array List of URLs found in the sitemap index
	 */
	private function extract_urls_from_sitemap_index($xml_content) : array {
		$urls = [];

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xml_content);

		if ($xml === false) {
			return $urls;
		}

		// Extract sitemap URLs and recursively process them
		if (isset($xml->sitemap)) {
			foreach ($xml->sitemap as $sitemap) {
				if (isset($sitemap->loc)) {
					$sitemap_url = (string) $sitemap->loc;
					$extracted_urls = $this->extract_urls_from_sitemap($sitemap_url);
					$urls = array_merge($urls, $extracted_urls);
				}
			}
		}

		return $urls;
	}

	/**
	 * Extract URLs from a regular sitemap
	 *
	 * @param string $xml_content XML content of the sitemap
	 * @return array List of URLs found in the sitemap
	 */
	private function extract_urls_from_sitemap_content($xml_content) : array {
		$urls = [];

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xml_content);

		if ($xml === false) {
			return $urls;
		}

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] ) 
			? $options['post_types'] 
			: [];

		// Extract URLs from the sitemap
		if (isset($xml->url)) {
			foreach ($xml->url as $url) {
				if (isset($url->loc)) {
					$page_url = (string) $url->loc;

					// If post types are selected, only include URLs for those post types
					if (!empty($selected_post_types)) {
						// Skip this URL if it doesn't match any selected post type
						$should_include = false;

						// Try to determine the post type from the URL
						$post_id = url_to_postid($page_url);
						if ($post_id) {
							$post_type = get_post_type($post_id);
							if (in_array($post_type, $selected_post_types)) {
								$should_include = true;
							}
						} else {
							// If we can't determine the post type, include the URL
							// This ensures we don't miss important URLs
							$should_include = true;
						}

						if (!$should_include) {
							continue;
						}
					}

					$urls[] = $page_url;
				}
			}
		}

		return $urls;
	}
}
