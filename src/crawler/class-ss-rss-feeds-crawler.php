<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static RSS Feeds Crawler class
 *
 * This crawler detects WordPress RSS feed URLs.
 * It is only active when the 'add_feeds' option is enabled.
 */
class Rss_Feeds_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'rss_feeds';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'RSS Feeds', 'simply-static' );
		$this->description = __( 'Detects WordPress RSS feed URLs.', 'simply-static' );
	}

	/**
	 * Check if the crawler is active.
	 * This crawler is only active when the 'add_feeds' option is enabled.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$options = \Simply_Static\Options::instance();

		// Only active if the add_feeds option is enabled
		if ( ! $options->get( 'add_feeds' ) ) {
			return false;
		}

		// Otherwise, use the default active check from the parent class
		return parent::is_active();
	}

	/**
	 * Detect RSS feed URLs.
	 *
	 * @return array List of RSS feed URLs
	 */
	public function detect(): array {
		$feed_urls = [];
		$max_objects = max( 1, min( 100000, (int) apply_filters( 'simply_static_rss_feed_detection_limit', 1000 ) ) );

		$options                 = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		$selected_post_types     = $has_post_type_selection ? $options['post_types'] : [];

		if ( $has_post_type_selection && empty( $selected_post_types ) ) {
			return $feed_urls;
		}

		$include_post_feeds = ! $has_post_type_selection || in_array( 'post', $selected_post_types, true );

		if ( $include_post_feeds ) {
			// Add the main feed URL
			$feed_urls[] = get_feed_link();

			// Add the comments feed URL
			$feed_urls[] = get_feed_link( 'comments_' );

			// Add category feeds
			$categories = get_categories( [ 'hide_empty' => true, 'number' => $max_objects ] );
			foreach ( $categories as $category ) {
				$feed_urls[] = get_category_feed_link( $category->term_id );
			}

			// Add tag feeds
			$tags = get_tags( [ 'hide_empty' => true, 'number' => $max_objects ] );
			foreach ( $tags as $tag ) {
				$feed_urls[] = get_tag_feed_link( $tag->term_id );
			}

			// Add author feeds
			$users = get_users( array( 'number' => $max_objects, 'orderby' => 'ID', 'order' => 'ASC' ) );
			foreach ( $users as $user ) {
				$feed_urls[] = get_author_feed_link( $user->ID );
			}
		}

		// Add post type feeds
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		$post_types = array_diff( $post_types, [ 'attachment', 'elementor_library', 'ssp-form' ] );
		foreach ( $post_types as $post_type ) {
			if ( $post_type === 'post' ) {
				continue; // Already covered by the main feed
			}

			if ( $has_post_type_selection && ! in_array( $post_type, $selected_post_types, true ) ) {
				continue;
			}

			$feed_url    = add_query_arg( 'post_type', $post_type, get_feed_link() );
			$feed_urls[] = $feed_url;
		}

		if ( $include_post_feeds ) {
			// Add search feeds (example: /?s=query&feed=rss2)
			// This is a bit speculative as it depends on actual search terms
			// We'll add a generic one for demonstration
			$feed_urls[] = add_query_arg( [ 's' => 'example', 'feed' => 'rss2' ], home_url() );
		}

		// Filter out any invalid URLs
		$feed_urls = array_filter( $feed_urls, function ( $url ) {
			return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
		} );

		return array_unique( $feed_urls );
	}

	/**
	 * Traverse term and author feeds across bounded background requests.
	 *
	 * @return int Number of URLs added by this invocation.
	 */
	public function add_urls_to_queue() : int {
		$options                 = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		$selected_post_types     = $has_post_type_selection ? $options['post_types'] : array();
		if ( $has_post_type_selection && empty( $selected_post_types ) ) {
			$this->complete = true;
			$this->clear_crawler_state( 'rss_feeds_crawler_state' );
			return 0;
		}

		$include_post_feeds = ! $has_post_type_selection || in_array( 'post', $selected_post_types, true );
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_values( array_diff( $post_types, array( 'attachment', 'elementor_library', 'ssp-form', 'post' ) ) );
		if ( $has_post_type_selection ) {
			$post_types = array_values( array_intersect( $post_types, $selected_post_types ) );
		}
		$signature = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'include_posts'      => $include_post_feeds,
			'post_types'         => $post_types,
		) ) );
		$state = \Simply_Static\Options::instance()->get( 'rss_feeds_crawler_state' );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) || ! in_array( $state['stage'] ?? null, array( 'categories', 'tags', 'authors' ), true ) || ! is_int( $state['offset'] ?? null ) || $state['offset'] < 0 ) {
			$state = array( 'version' => 1, 'signature' => $signature, 'stage' => 'categories', 'offset' => 0, 'base_added' => false, 'added' => 0, 'scanned' => 0 );
		}

		$added_now = 0;
		if ( empty( $state['base_added'] ) ) {
			$base_urls = array();
			if ( $include_post_feeds ) {
				$base_urls[] = get_feed_link();
				$base_urls[] = get_feed_link( 'comments_' );
				$base_urls[] = add_query_arg( array( 's' => 'example', 'feed' => 'rss2' ), home_url() );
			}
			foreach ( $post_types as $post_type ) {
				$base_urls[] = add_query_arg( 'post_type', $post_type, get_feed_link() );
			}
			$base_urls = array_values( array_unique( array_filter( $base_urls, static function ( $url ) {
				return is_string( $url ) && false !== filter_var( $url, FILTER_VALIDATE_URL );
			} ) ) );
			$added                 = $this->enqueue_urls( $base_urls );
			$added_now            += $added;
			$state['added']        += $added;
			$state['base_added']    = true;
			$this->save_crawler_state( 'rss_feeds_crawler_state', $state );
		}

		if ( ! $include_post_feeds ) {
			$this->complete = true;
			$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
			$this->clear_crawler_state( 'rss_feeds_crawler_state' );
			return $added_now;
		}

		$batch_size = max( 1, min( 500, (int) apply_filters( 'simply_static_rss_feeds_crawler_batch_size', 100 ) ) );
		$this->complete = true;
		while ( true ) {
			$urls = array();
			if ( 'authors' === $state['stage'] ) {
				$ids = get_users( array( 'fields' => 'ID', 'number' => $batch_size, 'offset' => $state['offset'], 'orderby' => 'ID', 'order' => 'ASC' ) );
				$ids = is_array( $ids ) ? $ids : array();
				foreach ( $ids as $id ) {
					$urls[] = get_author_feed_link( (int) $id );
				}
			} else {
				$taxonomy = 'categories' === $state['stage'] ? 'category' : 'post_tag';
				$ids = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'fields' => 'ids', 'number' => $batch_size, 'offset' => $state['offset'], 'orderby' => 'term_id', 'order' => 'ASC' ) );
				$ids = is_wp_error( $ids ) || ! is_array( $ids ) ? array() : $ids;
				foreach ( $ids as $id ) {
					$urls[] = 'category' === $taxonomy ? get_category_feed_link( (int) $id ) : get_tag_feed_link( (int) $id );
				}
			}

			$urls = array_values( array_filter( $urls, static function ( $url ) {
				return is_string( $url ) && false !== filter_var( $url, FILTER_VALIDATE_URL );
			} ) );
			$added                = $this->enqueue_urls( $urls );
			$added_now           += $added;
			$state['added']       += $added;
			$state['scanned']     += count( $ids );
			$state['offset']      += count( $ids );
			if ( count( $ids ) >= $batch_size ) {
				$this->complete = false;
				break;
			}
			if ( 'categories' === $state['stage'] ) {
				$state['stage'] = 'tags';
				$state['offset'] = 0;
				continue;
			}
			if ( 'tags' === $state['stage'] ) {
				$state['stage'] = 'authors';
				$state['offset'] = 0;
				continue;
			}
			break;
		}

		$this->progress = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		if ( $this->complete ) {
			$this->clear_crawler_state( 'rss_feeds_crawler_state' );
		} else {
			$this->save_crawler_state( 'rss_feeds_crawler_state', $state );
		}

		return $added_now;
	}
}
