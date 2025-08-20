<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Pagination Crawler class
 *
 * This crawler detects pagination URLs for archives, posts, and other paginated content.
 */
class Pagination_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'pagination';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Pagination URLs', 'simply-static' );
		$this->description = __( 'Detects pagination URLs for archives, posts, and other paginated content.', 'simply-static' );
	}

	/**
	 * Detect pagination URLs.
	 *
	 * @return array List of pagination URLs
	 */
	public function detect() : array {
		$pagination_urls = [];

		// Get pagination for archives
		$pagination_urls = array_merge($pagination_urls, $this->get_archive_pagination());

		// Get pagination for posts with <!--nextpage--> tag
		$pagination_urls = array_merge($pagination_urls, $this->get_post_pagination());

		return $pagination_urls;
	}

	/**
	 * Get pagination URLs for archives
	 *
	 * @return array List of archive pagination URLs
	 */
	private function get_archive_pagination() : array {
		$urls = [];

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] ) 
			? $options['post_types'] 
			: [];

		// If 'post' is not in the selected post types, don't include blog pagination
		// as blog pages typically only show posts, not other post types
		if (empty($selected_post_types) || in_array('post', $selected_post_types)) {
			// Get the total number of posts (only 'post' type)
			$total_posts = wp_count_posts('post')->publish;

			// Get posts per page setting
			$posts_per_page = get_option('posts_per_page');

			if ($posts_per_page > 0) {
				// Calculate the number of pages
				$total_pages = ceil($total_posts / $posts_per_page);

				// Add pagination URLs for the main blog page
				$blog_url = get_permalink(get_option('page_for_posts'));
				if (!$blog_url) {
					$blog_url = home_url('/');
				}

				// Add URLs for each page (starting from page 2, as page 1 is the main URL)
				for ($i = 2; $i <= $total_pages; $i++) {
					// Use /page/N/ format instead of ?paged=N
					$urls[] = trailingslashit(rtrim($blog_url, '/')) . 'page/' . $i . '/';
				}

				// Get pagination for category archives
				$categories = get_categories(['hide_empty' => true]);
				foreach ($categories as $category) {
					$category_link = get_category_link($category->term_id);
					$category_post_count = $category->count;
					$category_pages = ceil($category_post_count / $posts_per_page);

					for ($i = 2; $i <= $category_pages; $i++) {
						// Use /page/N/ format instead of ?paged=N
						$urls[] = trailingslashit(rtrim($category_link, '/')) . 'page/' . $i . '/';
					}
				}

				// Get pagination for tag archives
				$tags = get_tags(['hide_empty' => true]);
				foreach ($tags as $tag) {
					$tag_link = get_tag_link($tag->term_id);
					$tag_post_count = $tag->count;
					$tag_pages = ceil($tag_post_count / $posts_per_page);

					for ($i = 2; $i <= $tag_pages; $i++) {
						// Use /page/N/ format instead of ?paged=N
						$urls[] = trailingslashit(rtrim($tag_link, '/')) . 'page/' . $i . '/';
					}
				}
			}
		}

		return $urls;
	}

	/**
	 * Get pagination URLs for posts with <!--nextpage--> tag
	 *
	 * @return array List of post pagination URLs
	 */
	private function get_post_pagination() : array {
		$urls = [];

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] ) 
			? $options['post_types'] 
			: [];

		// If no post types are selected, use 'any' to include all post types
		$post_type_param = empty($selected_post_types) ? 'any' : $selected_post_types;

		// Get posts that might have pagination
		$posts = get_posts([
			'post_type'      => $post_type_param,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		]);

		foreach ($posts as $post) {
			// Check if the post content has the <!--nextpage--> tag
			$content = $post->post_content;

			if (strpos($content, '<!--nextpage-->') !== false) {
				// Count the number of pages
				$pages = substr_count($content, '<!--nextpage-->') + 1;

				// Get the permalink
				$permalink = get_permalink($post->ID);

				// Add URLs for each page (starting from page 2, as page 1 is the main URL)
				for ($i = 2; $i <= $pages; $i++) {
					// Use /N/ format for post pagination
					$urls[] = trailingslashit(rtrim($permalink, '/')) . $i . '/';
				}
			}
		}

		return $urls;
	}
}
