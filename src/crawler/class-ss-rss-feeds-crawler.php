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

		// Add the main feed URL
		$feed_urls[] = get_feed_link();

		// Add the comments feed URL
		$feed_urls[] = get_feed_link( 'comments_' );

		// Add category feeds
		$categories = get_categories( [ 'hide_empty' => true ] );
		foreach ( $categories as $category ) {
			$feed_urls[] = get_category_feed_link( $category->term_id );
		}

		// Add tag feeds
		$tags = get_tags( [ 'hide_empty' => true ] );
		foreach ( $tags as $tag ) {
			$feed_urls[] = get_tag_feed_link( $tag->term_id );
		}

		// Add author feeds
		$users = get_users();
		foreach ( $users as $user ) {
			$feed_urls[] = get_author_feed_link( $user->ID );
		}

		// Add post type feeds
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		foreach ( $post_types as $post_type ) {
			if ( $post_type === 'post' ) {
				continue; // Already covered by the main feed
			}

			$feed_url    = add_query_arg( 'post_type', $post_type, get_feed_link() );
			$feed_urls[] = $feed_url;
		}

		// Add search feeds (example: /?s=query&feed=rss2)
		// This is a bit speculative as it depends on actual search terms
		// We'll add a generic one for demonstration
		$feed_urls[] = add_query_arg( [ 's' => 'example', 'feed' => 'rss2' ], home_url() );

		// Filter out any invalid URLs
		$feed_urls = array_filter( $feed_urls, function ( $url ) {
			return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
		} );

		return array_unique( $feed_urls );
	}
}