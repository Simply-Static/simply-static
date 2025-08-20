<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Post Type Crawler class
 *
 * This crawler detects URLs for all public post types.
 */
class Post_Type_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'post_type';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Post Type URLs', 'simply-static' );
		$this->description = __( 'Detects URLs for all public post types (posts, pages, etc.).', 'simply-static' );
	}

	/**
	 * Detect post type URLs.
	 *
	 * @return array List of post type URLs
	 */
	public function detect() : array {
		$post_urls = [];

		// Get all public post types
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		// Filter post types to allow exclusion of specific post types
		$post_types = apply_filters( 'simply_static_post_types_to_crawl', $post_types );

		// Exclude Elementor's element_library post type
		if ( isset( $post_types['elementor_library'] ) ) {
			unset( $post_types['elementor_library'] );
		}

		// Exclude ssp-form post type
		if ( isset( $post_types['ssp-form'] ) ) {
			unset( $post_types['ssp-form'] );
		}

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		if ( isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] ) ) {
			// Filter post types to only include those selected in settings
			$post_types = array_intersect( $post_types, $options['post_types'] );
		}

		foreach ( $post_types as $post_type ) {
			// Skip attachments as they're handled differently
			if ( $post_type === 'attachment' ) {
				continue;
			}

			// Get all published posts of this type
			$posts = get_posts( [
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			] );

			foreach ( $posts as $post ) {
				$permalink = get_permalink( $post->ID );

				if ( ! is_string( $permalink ) ) {
					continue;
				}

				$post_urls[] = $permalink;
			}
		}

		return $post_urls;
	}
}
