<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Taxonomy Crawler class
 *
 * This crawler detects URLs for all public taxonomies.
 */
class Taxonomy_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'taxonomy';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Taxonomy URLs', 'simply-static' );
		$this->description = __( 'Detects URLs for all public taxonomies (categories, tags, etc.).', 'simply-static' );
	}

	/**
	 * Detect taxonomy URLs.
	 *
	 * @return array List of taxonomy URLs
	 */
	public function detect() : array {
		$taxonomy_urls = [];
		
		// Get all public taxonomies
		$taxonomies = get_taxonomies( [ 'public' => true ], 'names' );
		
		foreach ( $taxonomies as $taxonomy ) {
			// Get all terms for this taxonomy
			$terms = get_terms( [
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			] );
			
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			
			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );
				
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				
				$taxonomy_urls[] = $term_link;
			}
		}
		
		return $taxonomy_urls;
	}
}