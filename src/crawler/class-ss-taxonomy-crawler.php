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
		$max_terms = max( 1, min( 100000, (int) apply_filters( 'simply_static_taxonomy_detection_limit', 1000 ) ) );
		
		// Get all public taxonomies
		$taxonomies = get_taxonomies( [ 'public' => true ], 'names' );
		
		foreach ( $taxonomies as $taxonomy ) {
			if ( count( $taxonomy_urls ) >= $max_terms ) {
				break;
			}
			// Get all terms for this taxonomy
			$terms = get_terms( [
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => $max_terms - count( $taxonomy_urls ),
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

	/**
	 * Traverse public terms across bounded background requests.
	 *
	 * @return int Number of URLs added by this invocation.
	 */
	public function add_urls_to_queue() : int {
		$taxonomies = array_values( (array) get_taxonomies( array( 'public' => true ), 'names' ) );
		$signature  = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'taxonomies'         => $taxonomies,
		) ) );
		$state = \Simply_Static\Options::instance()->get( 'taxonomy_crawler_state' );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) || ! is_int( $state['taxonomy_index'] ?? null ) || $state['taxonomy_index'] < 0 || ! is_int( $state['offset'] ?? null ) || $state['offset'] < 0 ) {
			$state = array( 'version' => 1, 'signature' => $signature, 'taxonomy_index' => 0, 'offset' => 0, 'added' => 0, 'scanned' => 0 );
		}

		$batch_size = max( 1, min( 500, (int) apply_filters( 'simply_static_taxonomy_crawler_batch_size', 100 ) ) );
		$added_now  = 0;
		$this->complete = true;
		while ( $state['taxonomy_index'] < count( $taxonomies ) ) {
			$taxonomy = $taxonomies[ $state['taxonomy_index'] ];
			$term_ids = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'fields'     => 'ids',
				'number'     => $batch_size,
				'offset'     => $state['offset'],
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );
			if ( is_wp_error( $term_ids ) ) {
				$term_ids = array();
			}
			$term_ids = is_array( $term_ids ) ? $term_ids : array();
			$urls     = array();
			foreach ( $term_ids as $term_id ) {
				$url = get_term_link( (int) $term_id, $taxonomy );
				if ( ! is_wp_error( $url ) && is_string( $url ) ) {
					$urls[] = $url;
				}
			}

			$added               = $this->enqueue_urls( $urls );
			$added_now          += $added;
			$state['added']     += $added;
			$state['scanned']   += count( $term_ids );
			$state['offset']    += count( $term_ids );
			if ( count( $term_ids ) >= $batch_size ) {
				$this->complete = false;
				break;
			}
			$state['taxonomy_index']++;
			$state['offset'] = 0;
		}

		$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		if ( $this->complete ) {
			$this->clear_crawler_state( 'taxonomy_crawler_state' );
		} else {
			$this->save_crawler_state( 'taxonomy_crawler_state', $state );
		}

		return $added_now;
	}
}
