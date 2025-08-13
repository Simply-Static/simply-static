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
		
		// Get the total number of posts
		$total_posts = wp_count_posts()->publish;
		
		// Get posts per page setting
		$posts_per_page = get_option('posts_per_page');
		
		if ($posts_per_page <= 0) {
			return $urls;
		}
		
		// Calculate the number of pages
		$total_pages = ceil($total_posts / $posts_per_page);
		
		// Add pagination URLs for the main blog page
		$blog_url = get_permalink(get_option('page_for_posts'));
		if (!$blog_url) {
			$blog_url = home_url('/');
		}
		
		// Add URLs for each page (starting from page 2, as page 1 is the main URL)
		for ($i = 2; $i <= $total_pages; $i++) {
			$urls[] = add_query_arg('paged', $i, $blog_url);
		}
		
		// Get pagination for category archives
		$categories = get_categories(['hide_empty' => true]);
		foreach ($categories as $category) {
			$category_link = get_category_link($category->term_id);
			$category_post_count = $category->count;
			$category_pages = ceil($category_post_count / $posts_per_page);
			
			for ($i = 2; $i <= $category_pages; $i++) {
				$urls[] = add_query_arg('paged', $i, $category_link);
			}
		}
		
		// Get pagination for tag archives
		$tags = get_tags(['hide_empty' => true]);
		foreach ($tags as $tag) {
			$tag_link = get_tag_link($tag->term_id);
			$tag_post_count = $tag->count;
			$tag_pages = ceil($tag_post_count / $posts_per_page);
			
			for ($i = 2; $i <= $tag_pages; $i++) {
				$urls[] = add_query_arg('paged', $i, $tag_link);
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
		
		// Get posts that might have pagination
		$posts = get_posts([
			'post_type'      => 'any',
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
					$urls[] = add_query_arg('page', $i, $permalink);
				}
			}
		}
		
		return $urls;
	}
}