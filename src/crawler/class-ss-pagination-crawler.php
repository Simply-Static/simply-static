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
		$pagination_urls = array_merge( $pagination_urls, $this->get_archive_pagination() );

		// Get pagination for posts with <!--nextpage--> tag
		$pagination_urls = array_merge( $pagination_urls, $this->get_post_pagination() );

		// Get pagination for custom page templates (pages with custom WP_Query pagination)
		$pagination_urls = array_merge( $pagination_urls, $this->get_page_template_pagination() );

		return apply_filters( 'simply_static_pagination_urls', $pagination_urls );
	}

	/**
	 * Get pagination URLs for archives
	 *
	 * @return array List of archive pagination URLs
	 */
	private function get_archive_pagination() : array {
		$urls = [];

		// Get selected post types from settings
		$options             = get_option( 'simply-static' );
		$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] )
			? $options['post_types']
			: [];

		// Filter to allow adding more post types to archive pagination (like CPTs)
		$post_types = apply_filters( 'simply_static_archive_pagination_post_types', [ 'post' ] );

		foreach ( $post_types as $post_type ) {
			// If the post type is not in the selected post types, skip it
			if ( ! empty( $selected_post_types ) && ! in_array( $post_type, $selected_post_types ) ) {
				continue;
			}

			// Get the total number of posts for this post type
			$total_posts = wp_count_posts( $post_type )->publish;

			// Get posts per page setting
			$posts_per_page = (int) get_option( 'posts_per_page' );
			if ( $posts_per_page < 1 ) {
				$posts_per_page = 1;
			}

			if ( $posts_per_page > 0 ) {
				// Calculate the number of pages
				$total_pages = ceil( $total_posts / $posts_per_page );

				if ( $post_type === 'post' ) {
					// Add pagination URLs for the main blog page
					$blog_url = get_permalink( get_option( 'page_for_posts' ) );
					if ( ! $blog_url ) {
						$blog_url = home_url( '/' );
					}

					// Add URLs for each page (starting from page 2, as page 1 is the main URL)
					for ( $i = 2; $i <= $total_pages; $i ++ ) {
						// Use /page/N/ format instead of ?paged=N
						$urls[] = trailingslashit( rtrim( $blog_url, '/' ) ) . 'page/' . $i . '/';
					}
				} else {
					// Handle Custom Post Type archives
					$archive_link = get_post_type_archive_link( $post_type );

					if ( $archive_link ) {
						for ( $i = 2; $i <= $total_pages; $i ++ ) {
							$urls[] = trailingslashit( rtrim( $archive_link, '/' ) ) . 'page/' . $i . '/';
						}
					}
				}

				// Get pagination for category archives (only for 'post' type by default)
				if ( $post_type === 'post' ) {
					$categories = get_categories( [ 'hide_empty' => true ] );
					foreach ( $categories as $category ) {
						$category_link = get_category_link( $category->term_id );
						if ( is_wp_error( $category_link ) || empty( $category_link ) ) {
							continue;
						}
						$category_post_count = (int) $category->count;
						$category_pages      = (int) ceil( $category_post_count / $posts_per_page );

						// Add base term link
						$urls[] = trailingslashit( rtrim( $category_link, '/' ) ) . '';

						for ( $i = 2; $i <= $category_pages; $i ++ ) {
							// Use /page/N/ format instead of ?paged=N
							$urls[] = trailingslashit( rtrim( $category_link, '/' ) ) . 'page/' . $i . '/';
						}
					}

					// Get pagination for tag archives
					$tags = get_tags( [ 'hide_empty' => true ] );
					foreach ( $tags as $tag ) {
						$tag_link = get_tag_link( $tag->term_id );
						if ( is_wp_error( $tag_link ) || empty( $tag_link ) ) {
							continue;
						}
						$tag_post_count = (int) $tag->count;
						$tag_pages      = (int) ceil( $tag_post_count / $posts_per_page );

						// Add base term link
						$urls[] = trailingslashit( rtrim( $tag_link, '/' ) ) . '';

						for ( $i = 2; $i <= $tag_pages; $i ++ ) {
							// Use /page/N/ format instead of ?paged=N
							$urls[] = trailingslashit( rtrim( $tag_link, '/' ) ) . 'page/' . $i . '/';
						}
					}

					// Get pagination for author archives (posts only)
					$authors = get_users( [
						'has_published_posts' => [ 'post' ],
						'fields'              => [ 'ID' ],
					] );
					if ( ! empty( $authors ) ) {
						foreach ( $authors as $author ) {
							$author_id    = is_object( $author ) ? (int) $author->ID : (int) $author;
							$post_count   = (int) count_user_posts( $author_id, 'post', true );
							$author_pages = (int) ceil( $post_count / $posts_per_page );
							$author_link  = get_author_posts_url( $author_id );
							if ( empty( $author_link ) ) {
								continue;
							}
							// Base author link
							$urls[] = trailingslashit( rtrim( $author_link, '/' ) ) . '';
							// Pagination pages starting from 2
							for ( $i = 2; $i <= $author_pages; $i ++ ) {
								$urls[] = trailingslashit( rtrim( $author_link, '/' ) ) . 'page/' . $i . '/';
							}
						}
					}
				}
			}
		}

		// Dedupe before returning
		return array_values( array_unique( $urls ) );
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

	/**
	 * Get pagination URLs for custom page templates.
	 *
	 * This method fetches pages and scans their rendered HTML for pagination links.
	 * It detects pagination created by paginate_links() or similar functions in custom templates.
	 *
	 * @return array List of pagination URLs found in page templates
	 */
	private function get_page_template_pagination() : array {
		$urls = [];

		// Get selected post types from settings
		$options             = get_option( 'simply-static' );
		$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ! empty( $options['post_types'] )
			? $options['post_types']
			: [];

		// Only process pages if 'page' is in the selected post types (or if no post types are selected)
		if ( ! empty( $selected_post_types ) && ! in_array( 'page', $selected_post_types ) ) {
			return $urls;
		}

		// Get all published pages
		$pages = get_posts( [
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );

		/**
		 * Filter the list of pages to scan for pagination.
		 *
		 * This allows developers to limit which pages are scanned for pagination links,
		 * which can improve performance on sites with many pages.
		 *
		 * @param array $pages Array of WP_Post objects to scan for pagination.
		 */
		$pages = apply_filters( 'simply_static_pages_to_scan_for_pagination', $pages );

		// Get the site URL for comparison
		$site_url = trailingslashit( home_url() );

		\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Scanning %d pages for custom pagination links', count( $pages ) ) );

		foreach ( $pages as $page ) {
			$page_url = get_permalink( $page->ID );

			if ( ! $page_url ) {
				continue;
			}

			// Extract pagination URLs from this page and its pagination pages
			$page_pagination_urls = $this->extract_pagination_urls_from_page( $page_url, $site_url );

			if ( ! empty( $page_pagination_urls ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Found %d pagination URLs for page %s', count( $page_pagination_urls ), $page_url ) );
				$urls = array_merge( $urls, $page_pagination_urls );
			}
		}

		// Remove duplicates and return
		$urls = array_values( array_unique( $urls ) );

		\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Total custom page template pagination URLs found: %d', count( $urls ) ) );

		return $urls;
	}

	/**
	 * Extract pagination URLs from a page and recursively from its pagination pages.
	 *
	 * @param string $base_page_url The base page URL (without /page/N/).
	 * @param string $site_url      The site URL.
	 *
	 * @return array List of pagination URLs found.
	 */
	private function extract_pagination_urls_from_page( string $base_page_url, string $site_url ) : array {
		$all_pagination_urls = [];
		$scanned_urls        = [];
		$urls_to_scan        = [ $base_page_url ];

		// Get the base page path for pattern matching
		$base_page_path = wp_parse_url( $base_page_url, PHP_URL_PATH );
		$base_page_path = $base_page_path ? rtrim( $base_page_path, '/' ) : '';

		// Pattern to match pagination links for this specific page
		// Matches both absolute URLs and relative paths with /page/N/ format
		$patterns = [
			// Absolute URL pattern: href="https://example.com/articles/page/2/"
			'#href=["\'](' . preg_quote( rtrim( $base_page_url, '/' ), '#' ) . '/page/(\d+)/?)["\']#i',
			// Relative path pattern: href="/articles/page/2/"
			'#href=["\'](' . preg_quote( $base_page_path, '#' ) . '/page/(\d+)/?)["\']#i',
		];

		// Limit the number of pages to scan to prevent infinite loops
		$max_pages_to_scan = apply_filters( 'simply_static_max_pagination_pages_to_scan', 100 );
		$pages_scanned     = 0;

		while ( ! empty( $urls_to_scan ) && $pages_scanned < $max_pages_to_scan ) {
			$current_url = array_shift( $urls_to_scan );

			// Skip if already scanned
			if ( in_array( $current_url, $scanned_urls, true ) ) {
				continue;
			}

			$scanned_urls[] = $current_url;
			$pages_scanned++;

			// Fetch the page content
			$response = wp_remote_get( $current_url, [
				'timeout'   => 30,
				'sslverify' => false,
			] );

			if ( is_wp_error( $response ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Failed to fetch page %s: %s', $current_url, $response->get_error_message() ) );
				continue;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				continue;
			}

			// Extract pagination URLs from the HTML
			foreach ( $patterns as $pattern ) {
				if ( preg_match_all( $pattern, $body, $matches ) ) {
					foreach ( $matches[1] as $match ) {
						// Convert relative URLs to absolute
						if ( strpos( $match, 'http' ) !== 0 ) {
							$match = $site_url . ltrim( $match, '/' );
						}
						// Ensure trailing slash for consistency
						$pagination_url = trailingslashit( rtrim( $match, '/' ) );

						// Add to results if not already found
						if ( ! in_array( $pagination_url, $all_pagination_urls, true ) ) {
							$all_pagination_urls[] = $pagination_url;

							// Add to scan queue if not already scanned
							if ( ! in_array( $pagination_url, $scanned_urls, true ) && ! in_array( $pagination_url, $urls_to_scan, true ) ) {
								$urls_to_scan[] = $pagination_url;
							}
						}
					}
				}
			}
		}

		return $all_pagination_urls;
	}
}
