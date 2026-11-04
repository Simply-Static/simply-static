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
		$languages = $this->get_crawl_languages();
		$original_language = apply_filters( 'wpml_current_language', null );
		
		// Get all public taxonomies
		$taxonomies = get_taxonomies( [ 'public' => true ], 'names' );
		
		try {
			foreach ( $languages as $language ) {
				$scanned_in_language = 0;
				if ( null !== $language ) {
					do_action( 'wpml_switch_language', $language );
				}
				foreach ( $taxonomies as $taxonomy ) {
					if ( $language !== $languages[0] && false === apply_filters( 'wpml_is_translated_taxonomy', null, $taxonomy ) ) {
						continue;
					}
					if ( $scanned_in_language >= $max_terms ) {
						break;
					}
					$terms = get_terms( [
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
						'number'     => $max_terms - $scanned_in_language,
					] );
					if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
						continue;
					}
					$scanned_in_language += count( $terms );
					$taxonomy_urls = array_values( array_unique( array_merge(
						$taxonomy_urls,
						$this->get_term_urls( $terms, $taxonomy, $language )
					) ) );
				}
			}
		} finally {
			if ( array( null ) !== $languages ) {
				do_action( 'wpml_switch_language', $original_language );
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
		$languages = $this->get_crawl_languages();
		$signature  = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'taxonomies'         => $taxonomies,
			'languages'          => $languages,
		) ) );
		$state = \Simply_Static\Options::instance()->get( 'taxonomy_crawler_state' );
		if ( ! is_array( $state ) || 2 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) || ! is_int( $state['taxonomy_index'] ?? null ) || $state['taxonomy_index'] < 0 || ! is_int( $state['language_index'] ?? null ) || $state['language_index'] < 0 || $state['language_index'] >= count( $languages ) || ! is_int( $state['offset'] ?? null ) || $state['offset'] < 0 ) {
			$state = array( 'version' => 2, 'signature' => $signature, 'taxonomy_index' => 0, 'language_index' => 0, 'offset' => 0, 'added' => 0, 'scanned' => 0 );
		}

		$batch_size = max( 1, min( 500, (int) apply_filters( 'simply_static_taxonomy_crawler_batch_size', 100 ) ) );
		$added_now  = 0;
		$this->complete = true;
		$original_language = apply_filters( 'wpml_current_language', null );
		try {
			while ( $state['taxonomy_index'] < count( $taxonomies ) ) {
				$taxonomy = $taxonomies[ $state['taxonomy_index'] ];
				$language = $languages[ $state['language_index'] ];
				if ( $state['language_index'] > 0 && false === apply_filters( 'wpml_is_translated_taxonomy', null, $taxonomy ) ) {
					$state['taxonomy_index']++;
					$state['language_index'] = 0;
					$state['offset'] = 0;
					continue;
				}
				if ( null !== $language ) {
					do_action( 'wpml_switch_language', $language );
				}
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
				$urls     = $this->get_term_urls( $term_ids, $taxonomy, $language );

				$added               = $this->enqueue_urls( $urls );
				$added_now          += $added;
				$state['added']     += $added;
				$state['scanned']   += count( $term_ids );
				$state['offset']    += count( $term_ids );
				if ( count( $term_ids ) >= $batch_size ) {
					$this->complete = false;
					break;
				}
				$state['language_index']++;
				if ( $state['language_index'] >= count( $languages ) ) {
					$state['taxonomy_index']++;
					$state['language_index'] = 0;
				}
				$state['offset'] = 0;
			}
		} finally {
			if ( array( null ) !== $languages ) {
				do_action( 'wpml_switch_language', $original_language );
			}
		}

		$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		if ( $this->complete ) {
			$this->clear_crawler_state( 'taxonomy_crawler_state' );
		} else {
			$this->save_crawler_state( 'taxonomy_crawler_state', $state );
		}

		return $added_now;
	}

	/**
	 * Enumerate each language's real term objects instead of prefixing every
	 * source-language URL. A translated term may use a different slug or have
	 * no translation at all; synthetic language URLs become redirect aliases
	 * and can multiply both crawling and Markdown work on large taxonomies.
	 *
	 * @return array<int,string|null>
	 */
	private function get_crawl_languages() : array {
		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		$codes = array();
		foreach ( is_array( $languages ) ? $languages : array() as $key => $language ) {
			$code = is_array( $language ) && isset( $language['language_code'] ) ? $language['language_code'] : $key;
			if ( is_string( $code ) && '' !== trim( $code ) ) {
				$codes[] = $code;
			}
		}

		$codes = array_values( array_unique( $codes ) );
		$default_language = apply_filters( 'wpml_default_language', null );
		if ( is_string( $default_language ) && in_array( $default_language, $codes, true ) ) {
			$codes = array_merge( array( $default_language ), array_values( array_diff( $codes, array( $default_language ) ) ) );
		}

		return empty( $codes ) ? array( null ) : $codes;
	}

	/** @param array $terms @return string[] */
	private function get_term_urls( array $terms, $taxonomy, $language ) : array {
		$urls = array();
		foreach ( $terms as $term ) {
			$url = get_term_link( is_object( $term ) ? $term : (int) $term, $taxonomy );
			if ( is_wp_error( $url ) || ! is_string( $url ) || '' === trim( $url ) ) {
				continue;
			}
			if ( null !== $language && false !== apply_filters( 'wpml_is_translated_taxonomy', null, $taxonomy ) ) {
				$url = apply_filters( 'wpml_permalink', $url, $language, true );
			}
			if ( is_string( $url ) && '' !== trim( $url ) ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
