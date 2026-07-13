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

		$pagination_urls = apply_filters( 'simply_static_pagination_urls', $pagination_urls );
		$pagination_urls = is_array( $pagination_urls ) ? array_values( array_unique( $pagination_urls ) ) : [];
		$max_urls        = $this->get_max_generated_urls();
		if ( count( $pagination_urls ) > $max_urls ) {
			$this->report_truncation( 'final_urls', $max_urls, count( $pagination_urls ) );
			$pagination_urls = array_slice( $pagination_urls, 0, $max_urls );
		}

		return $pagination_urls;
	}

	/**
	 * Get pagination URLs for archives
	 *
	 * @return array List of archive pagination URLs
	 */
	private function get_archive_pagination() : array {
		$urls                = [];
		$max_generated_urls  = $this->get_max_generated_urls();
		$max_archive_objects = max( 1, min( 100000, (int) apply_filters( 'simply_static_pagination_max_archive_objects', 5000 ) ) );

		// Get selected post types from settings
		$options             = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		$selected_post_types = $has_post_type_selection
			? $options['post_types']
			: [];

		// Filter to allow adding more post types to archive pagination (like CPTs)
		$post_types = apply_filters( 'simply_static_archive_pagination_post_types', [ 'post' ] );

		foreach ( $post_types as $post_type ) {
			// If the post type is not in the selected post types, skip it
			if ( $has_post_type_selection && ! in_array( $post_type, $selected_post_types ) ) {
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
					for ( $i = 2; $i <= $total_pages && count( $urls ) < $max_generated_urls; $i ++ ) {
						// Use /page/N/ format instead of ?paged=N
						$urls[] = trailingslashit( rtrim( $blog_url, '/' ) ) . 'page/' . $i . '/';
					}
				} else {
					// Handle Custom Post Type archives
					$archive_link = get_post_type_archive_link( $post_type );

					if ( $archive_link ) {
						for ( $i = 2; $i <= $total_pages && count( $urls ) < $max_generated_urls; $i ++ ) {
							$urls[] = trailingslashit( rtrim( $archive_link, '/' ) ) . 'page/' . $i . '/';
						}
					}
				}

				// Get pagination for category archives (only for 'post' type by default)
				if ( $post_type === 'post' ) {
					$categories = get_categories( [ 'hide_empty' => true, 'number' => $max_archive_objects ] );
					if ( count( $categories ) >= $max_archive_objects ) {
						$this->report_truncation( 'category_candidates', $max_archive_objects, count( $categories ) );
					}
					foreach ( $categories as $category ) {
						if ( count( $urls ) >= $max_generated_urls ) {
							break;
						}
						$category_link = get_category_link( $category->term_id );
						if ( is_wp_error( $category_link ) || empty( $category_link ) ) {
							continue;
						}
						$category_post_count = (int) $category->count;
						$category_pages      = (int) ceil( $category_post_count / $posts_per_page );

						// Add base term link
						$urls[] = trailingslashit( rtrim( $category_link, '/' ) ) . '';

						for ( $i = 2; $i <= $category_pages && count( $urls ) < $max_generated_urls; $i ++ ) {
							// Use /page/N/ format instead of ?paged=N
							$urls[] = trailingslashit( rtrim( $category_link, '/' ) ) . 'page/' . $i . '/';
						}
					}

					// Get pagination for tag archives
					$tags = get_tags( [ 'hide_empty' => true, 'number' => $max_archive_objects ] );
					if ( count( $tags ) >= $max_archive_objects ) {
						$this->report_truncation( 'tag_candidates', $max_archive_objects, count( $tags ) );
					}
					foreach ( $tags as $tag ) {
						if ( count( $urls ) >= $max_generated_urls ) {
							break;
						}
						$tag_link = get_tag_link( $tag->term_id );
						if ( is_wp_error( $tag_link ) || empty( $tag_link ) ) {
							continue;
						}
						$tag_post_count = (int) $tag->count;
						$tag_pages      = (int) ceil( $tag_post_count / $posts_per_page );

						// Add base term link
						$urls[] = trailingslashit( rtrim( $tag_link, '/' ) ) . '';

						for ( $i = 2; $i <= $tag_pages && count( $urls ) < $max_generated_urls; $i ++ ) {
							// Use /page/N/ format instead of ?paged=N
							$urls[] = trailingslashit( rtrim( $tag_link, '/' ) ) . 'page/' . $i . '/';
						}
					}

					// Get pagination for author archives (posts only)
					$authors = get_users( [
						'has_published_posts' => [ 'post' ],
						'fields'              => [ 'ID' ],
						'number'              => $max_archive_objects,
					] );
					if ( ! empty( $authors ) ) {
						if ( count( $authors ) >= $max_archive_objects ) {
							$this->report_truncation( 'author_candidates', $max_archive_objects, count( $authors ) );
						}
						foreach ( $authors as $author ) {
							if ( count( $urls ) >= $max_generated_urls ) {
								break;
							}
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
							for ( $i = 2; $i <= $author_pages && count( $urls ) < $max_generated_urls; $i ++ ) {
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
		$urls               = [];
		$batch_size         = max( 10, min( 1000, (int) apply_filters( 'simply_static_pagination_post_query_batch_size', 250 ) ) );
		$max_posts_to_scan  = max( 1, min( 1000000, (int) apply_filters( 'simply_static_pagination_max_posts_to_scan', 10000 ) ) );
		$max_generated_urls = $this->get_max_generated_urls();

		// Get selected post types from settings
		$options = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		$selected_post_types = $has_post_type_selection
			? $options['post_types'] 
			: [];

		// If no post types are selected, use 'any' to include all post types
		$post_type_param = $has_post_type_selection ? $selected_post_types : 'any';
		if ( $has_post_type_selection && empty( $selected_post_types ) ) {
			return $urls;
		}

		$posts_scanned = 0;
		$posts         = [];
		$query_size    = 0;
		for ( $offset = 0; $offset < $max_posts_to_scan && count( $urls ) < $max_generated_urls; $offset += $batch_size ) {
			$query_size = min( $batch_size, $max_posts_to_scan - $offset );
			$posts      = get_posts( [
				'post_type'              => $post_type_param,
				'posts_per_page'         => $query_size,
				'offset'                 => $offset,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'post_status'            => 'publish',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			] );
			$posts_scanned += count( $posts );

			foreach ( $posts as $post ) {
				// Check if the post content has the <!--nextpage--> tag.
				$content = $post->post_content;

				if ( strpos( $content, '<!--nextpage-->' ) !== false ) {
					$pages    = substr_count( $content, '<!--nextpage-->' ) + 1;
					$permalink = get_permalink( $post->ID );

					for ( $i = 2; $i <= $pages && count( $urls ) < $max_generated_urls; $i++ ) {
						$urls[] = trailingslashit( rtrim( $permalink, '/' ) ) . $i . '/';
					}
				}
			}

			if ( count( $posts ) < $query_size ) {
				break;
			}
		}
		if ( $posts_scanned >= $max_posts_to_scan && $query_size > 0 && count( $posts ) >= $query_size ) {
			$this->report_truncation( 'post_candidates', $max_posts_to_scan, $posts_scanned );
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
		$urls               = [];
		$max_generated_urls = $this->get_max_generated_urls();

		// Get selected post types from settings
		$options             = get_option( 'simply-static' );
		$has_post_type_selection = isset( $options['post_types'] ) && is_array( $options['post_types'] ) && ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );
		$selected_post_types = $has_post_type_selection
			? $options['post_types']
			: [];

		// Only process pages if 'page' is in the selected post types (or if no post types are selected)
		if ( $has_post_type_selection && ! in_array( 'page', $selected_post_types ) ) {
			return $urls;
		}

		$batch_size        = max( 10, min( 1000, (int) apply_filters( 'simply_static_pagination_page_query_batch_size', 100 ) ) );
		$max_pages_to_scan = max( 1, min( 10000, (int) apply_filters( 'simply_static_pagination_max_page_templates_to_scan', 100 ) ) );
		$pages             = [];
		$batch             = [];
		$query_size        = 0;
		for ( $offset = 0; $offset < $max_pages_to_scan; $offset += $batch_size ) {
			$query_size = min( $batch_size, $max_pages_to_scan - $offset );
			$batch      = get_posts( [
				'post_type'              => 'page',
				'posts_per_page'         => $query_size,
				'offset'                 => $offset,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'post_status'            => 'publish',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			] );
			$pages      = array_merge( $pages, $batch );
			if ( count( $batch ) < $query_size ) {
				break;
			}
		}
		if ( count( $pages ) >= $max_pages_to_scan && $query_size > 0 && count( $batch ) >= $query_size ) {
			$this->report_truncation( 'page_template_candidates', $max_pages_to_scan, count( $pages ) );
		}

		/**
		 * Filter the list of pages to scan for pagination.
		 *
		 * This allows developers to limit which pages are scanned for pagination links,
		 * which can improve performance on sites with many pages.
		 *
		 * @param array $pages Array of WP_Post objects to scan for pagination.
		 */
		$pages = apply_filters( 'simply_static_pages_to_scan_for_pagination', $pages );
		$pages = is_array( $pages ) ? array_slice( $pages, 0, $max_pages_to_scan ) : [];

		// Get the site URL for comparison
		$site_url = trailingslashit( home_url() );

		\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Scanning %d pages for custom pagination links', count( $pages ) ) );

		$global_scan_seconds = max( 1, min( 300, (int) apply_filters( 'simply_static_pagination_total_scan_seconds', 30 ) ) );
		$scan_deadline       = microtime( true ) + $global_scan_seconds;
		foreach ( $pages as $page ) {
			if ( microtime( true ) >= $scan_deadline ) {
				$this->report_truncation( 'page_template_deadline', count( $urls ), count( $pages ) );
				break;
			}
			$page_url = get_permalink( $page->ID );

			if ( ! $page_url ) {
				continue;
			}

			// Extract pagination URLs from this page and its pagination pages
			$page_pagination_urls = $this->extract_pagination_urls_from_page( $page_url, $site_url, $scan_deadline );

			if ( ! empty( $page_pagination_urls ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Found %d pagination URLs for page %s', count( $page_pagination_urls ), $page_url ) );
				$remaining = $max_generated_urls - count( $urls );
				if ( $remaining < count( $page_pagination_urls ) ) {
					$this->report_truncation( 'page_template_urls', $max_generated_urls, count( $urls ) + count( $page_pagination_urls ) );
				}
				$urls = array_merge( $urls, array_slice( $page_pagination_urls, 0, max( 0, $remaining ) ) );
				if ( count( $urls ) >= $max_generated_urls ) {
					break;
				}
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
	private function extract_pagination_urls_from_page( string $base_page_url, string $site_url, $scan_deadline = null ) : array {
		$all_pagination_urls = [];
		$discovered_urls     = [];
		$scanned_urls        = [];
		$queued_urls         = [ $base_page_url => true ];
		$urls_to_scan        = [ $base_page_url ];

		if ( ! \Simply_Static\Util::is_local_origin_url( $base_page_url ) || ! \Simply_Static\Util::is_local_origin_url( $site_url ) ) {
			return $all_pagination_urls;
		}

		// Get the base page path for pattern matching
		$base_page_path = wp_parse_url( $base_page_url, PHP_URL_PATH );
		$base_page_path = $base_page_path ? rtrim( $base_page_path, '/' ) : '';

		// Pattern to match pagination links for this specific page.
		// WordPress archives commonly use /page/N/, while page-builder widgets
		// and custom WP_Query instances may use the shorter /N/ format.
		$patterns = [
			// Absolute URL: https://example.com/articles/page/2/ or /articles/2/.
			'#href=["\'](' . preg_quote( rtrim( $base_page_url, '/' ), '#' ) . '/(?:page/)?(\d+)/?)["\']#i',
			// Relative path: /articles/page/2/ or /articles/2/.
			'#href=["\'](' . preg_quote( $base_page_path, '#' ) . '/(?:page/)?(\d+)/?)["\']#i',
		];

		// Limit the number of pages to scan to prevent infinite loops
		$max_pages_to_scan = max( 1, min( 1000, (int) apply_filters( 'simply_static_max_pagination_pages_to_scan', 100 ) ) );
		$max_body_bytes    = max( 1024, min( 10 * 1024 * 1024, (int) apply_filters( 'simply_static_pagination_max_response_bytes', 2 * 1024 * 1024 ) ) );
		$max_run_seconds   = max( 1, min( 300, (int) apply_filters( 'simply_static_pagination_max_scan_seconds', 30 ) ) );
		$request_timeout   = max( 1, min( 30, (int) apply_filters( 'simply_static_pagination_request_timeout', 10 ) ) );
		$max_generated_urls = $this->get_max_generated_urls();
		$started_at        = microtime( true );
		$deadline          = is_numeric( $scan_deadline )
			? min( (float) $scan_deadline, $started_at + $max_run_seconds )
			: $started_at + $max_run_seconds;
		$pages_scanned     = 0;

		while (
			! empty( $urls_to_scan )
			&& $pages_scanned < $max_pages_to_scan
			&& count( $all_pagination_urls ) < $max_generated_urls
			&& microtime( true ) < $deadline
		) {
			$current_url = array_shift( $urls_to_scan );
			unset( $queued_urls[ $current_url ] );

			// Skip if already scanned
			if ( isset( $scanned_urls[ $current_url ] ) || ! \Simply_Static\Util::is_local_origin_url( $current_url ) ) {
				continue;
			}

			$scanned_urls[ $current_url ] = true;
			$pages_scanned++;

			// Fetch the page content
			$args = [
				'timeout'             => min( $request_timeout, max( 1, $deadline - microtime( true ) ) ),
				'redirection'         => 0,
				'sslverify'           => (bool) apply_filters( 'ss_remote_get_sslverify', true, $current_url ),
				'limit_response_size' => $max_body_bytes + 1,
			];
			$authorization = \Simply_Static\Util::get_basic_auth_header_for_url( $current_url );
			if ( null !== $authorization ) {
				$args['headers'] = [ 'Authorization' => $authorization ];
			}

			$response = wp_remote_get( $current_url, apply_filters( 'ss_remote_get_args', $args ) );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				$error_message = is_wp_error( $response )
					? $response->get_error_message()
					: sprintf( 'HTTP %d', (int) wp_remote_retrieve_response_code( $response ) );
				\Simply_Static\Util::debug_log( sprintf( 'Pagination Crawler: Failed to fetch page %s: %s', $current_url, $error_message ) );
				continue;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( ! is_string( $body ) || '' === $body || strlen( $body ) > $max_body_bytes ) {
				continue;
			}

			// Extract pagination URLs from the HTML
			foreach ( $patterns as $pattern ) {
				if ( preg_match_all( $pattern, $body, $matches ) ) {
					foreach ( $matches[1] as $match ) {
						if ( count( $all_pagination_urls ) >= $max_generated_urls ) {
							$this->report_truncation( 'single_page_urls', $max_generated_urls, count( $matches[1] ) );
							break 2;
						}
						// Convert relative URLs to absolute
						if ( 0 === strpos( $match, '/' ) ) {
							$match = $site_url . ltrim( $match, '/' );
						}
						// Ensure trailing slash for consistency
						$pagination_url = trailingslashit( rtrim( $match, '/' ) );
						if ( ! \Simply_Static\Util::is_local_origin_url( $pagination_url ) ) {
							continue;
						}

						// Add to results if not already found
						if ( ! isset( $discovered_urls[ $pagination_url ] ) ) {
							$discovered_urls[ $pagination_url ] = true;
							$all_pagination_urls[] = $pagination_url;

							// Add to scan queue if not already scanned
							if ( ! isset( $scanned_urls[ $pagination_url ] ) && ! isset( $queued_urls[ $pagination_url ] ) ) {
								$queued_urls[ $pagination_url ] = true;
								$urls_to_scan[] = $pagination_url;
							}
						}
					}
				}
			}
		}

		return $all_pagination_urls;
	}

	/** @return int Maximum pagination URLs contributed by this crawler. */
	private function get_max_generated_urls() : int {
		return max( 100, min( 1000000, (int) apply_filters( 'simply_static_pagination_max_generated_urls', 50000 ) ) );
	}

	/**
	 * Make an intentional safety truncation observable to logs and integrations.
	 *
	 * @param string $context Truncation context.
	 * @param int    $limit   Applied limit.
	 * @param int    $seen    Number of candidates observed, when known.
	 */
	private function report_truncation( $context, $limit, $seen ) : void {
		$message = sprintf(
			'Pagination Crawler: safety limit reached (%s, limit %d, observed %d). Adjust the pagination safety filters for this site if complete discovery requires a higher limit.',
			(string) $context,
			(int) $limit,
			(int) $seen
		);
		\Simply_Static\Util::debug_log( $message );
		if ( class_exists( '\\Simply_Static\\Options' ) ) {
			\Simply_Static\Options::instance()
				->add_status_message( $message, 'pagination_warning' )
				->save();
		}
		do_action( 'simply_static_pagination_truncated', $context, (int) $limit, (int) $seen, $message );
	}
}
