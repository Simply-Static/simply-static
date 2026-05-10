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
		$this->name        = __( 'Post Type URLs', 'simply-static' );
		$this->description = __( 'Detects URLs for all public post types (posts, pages, etc.).', 'simply-static' );
	}

	/**
	 * Detect post type URLs.
	 *
	 * @return array List of post type URLs
	 */
	public function detect(): array {
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
				'posts_per_page' => - 1,
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

		// Remove stale pages table entries for posts that are no longer publicly
		$this->cleanup_non_published_pages();

		return $post_urls;
	}

	/**
	 * Remove pages table entries whose post_id refers to a post that is no longer publicly visible.
	 */
	private function cleanup_non_published_pages(): void {
		global $wpdb;
		$table               = $wpdb->prefix . 'simply_static_pages';
		$non_public_statuses = "'trash','draft','pending','private','auto-draft'";

		$stale_rows = $wpdb->get_results(
			"SELECT p.id, p.post_id, p.file_path, p.url
			 FROM {$table} p
			 LEFT JOIN {$wpdb->posts} wp ON p.post_id = wp.ID
			 WHERE p.post_id IS NOT NULL AND p.post_id > 0
			   AND ( wp.ID IS NULL OR wp.post_status IN ({$non_public_statuses}) )"
		);

		if ( empty( $stale_rows ) ) {
			return;
		}

		// Queue stale pages for deletion on the destination (all delivery methods).
		$this->queue_stale_pages_for_deletion( $stale_rows );

		// Remove the stale rows from the pages table.
		$stale_ids       = array_map( function ( $row ) {
			return (int) $row->id;
		}, $stale_rows );
		$ids_placeholder = implode( ',', $stale_ids );
		$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids_placeholder})" );
	}

	/**
	 * Insert stale pages into the Pro deletion-tracker table so the
	 * Delete_Tracked_Pages_Task can remove them from any delivery destination.
	 *
	 * Also fires the `ss_cleanup_stale_static_page` action per row so
	 * third-party code can react.
	 *
	 * @param array $stale_rows Rows from the pages table to process.
	 */
	private function queue_stale_pages_for_deletion( array $stale_rows ): void {
		global $wpdb;

		// Check if the Pro deletion-tracker table exists (single query, cached for the loop).
		$tracker_table  = $wpdb->prefix . 'simply_static_delete_pages';
		$tracker_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tracker_table ) ) === $tracker_table );

		foreach ( $stale_rows as $row ) {
			$url       = ! empty( $row->url ) ? $row->url : '';
			$file_path = ! empty( $row->file_path ) ? ltrim( $row->file_path, '/' ) : '';
			$post_id   = ! empty( $row->post_id ) ? (int) $row->post_id : 0;

			// Skip rows without actionable data.
			if ( '' === $url && '' === $file_path ) {
				continue;
			}

			\Simply_Static\Util::debug_log( sprintf( 'Queueing stale page for deletion: %s (post %d)', $url ?: $file_path, $post_id ) );

			if ( $tracker_exists ) {
				$post         = $post_id > 0 ? get_post( $post_id ) : null;
				$content_type = $post ? (string) $post->post_type : '';
				$unique_hash  = md5( implode( '|', [
					(string) get_current_blog_id(),
					$url,
					$file_path,
					$content_type
				] ) );

				$wpdb->replace(
					$tracker_table,
					[
						'old_url'      => $url,
						'file_path'    => $file_path,
						'content_type' => $content_type,
						'object_id'    => $post_id > 0 ? $post_id : null,
						'object_type'  => $post_id > 0 ? 'post' : '',
						'site_id'      => get_current_blog_id(),
						'deleted_at'   => gmdate( 'Y-m-d H:i:s' ),
						'source'       => 'crawler_cleanup',
						'meta'         => null,
						'unique_hash'  => $unique_hash,
					],
					[ '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ]
				);
			}

			do_action( 'ss_cleanup_stale_static_page', $url, $file_path, $post_id );
		}
	}
}
