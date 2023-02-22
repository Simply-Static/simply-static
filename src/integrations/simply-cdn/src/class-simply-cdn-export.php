<?php

namespace Simply_Static;

use voku\helper\HtmlDomParser;

/**
 * Class to handle automatic exports.
 */
class Simply_CDN_Export {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Export.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Simply_CDN_Export.
	 */
	public function __construct() {
		$use_auto_publish = get_option( 'sch_use_auto_publish' );

		if ( $use_auto_publish && ! class_exists( '\simply_static_pro\Single' ) ) {
			add_action( 'save_post', array( $this, 'run_single_export' ) );
			add_action( 'elementor/editor/after_save', array( $this, 'run_single_export' ) );
			add_filter( 'ss_static_pages', array( $this, 'filter_static_pages' ), 10, 2 );
			add_filter( 'ss_remaining_pages', array( $this, 'filter_remaining_pages' ), 10, 2 );
			add_filter( 'ss_total_pages', array( $this, 'filter_total_pages' ) );
			add_filter( 'ss_total_pages_log', array( $this, 'filter_total_pages_log' ) );
			add_action( 'ss_after_cleanup', array( $this, 'clear_single' ) );
		}
	}

	/**
	 * Automatically run a static export after post is saved.
	 *
	 * @param int $post_id given post id.
	 *
	 * @return void
	 */
	public function run_single_export( $post_id ) {
		$current_status = get_post_status( $post_id );

		if ( 'publish' === $current_status ) {
			$additional_urls = apply_filters( 'ssp_single_export_additional_urls', array_merge( $this->get_related_urls( $post_id ), $this->get_related_attachements( $post_id ) ) );

			// Update option for using a single post.
			update_option( 'simply-static-use-single', $post_id );

			// Clear records before run the export.
			Page::query()->delete_all();

			// Add URls for static export.
			$this->add_url( $post_id );
			$this->add_additional_urls( $additional_urls, $post_id );

			do_action( 'sch_before_run_single' );

			// Start static export.
			$ss = Plugin::instance();
			$ss->run_static_export();
		}
	}

	/**
	 * Get related URls to include in single export.
	 *
	 * @param int $single_id single post id.
	 *
	 * @return array
	 */
	public function get_related_urls( $single_id ) {
		$related_urls = array();

		// Get category URLs.
		$categories = get_the_terms( $single_id, 'category' );

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$related_urls[] = get_term_link( $category );
			}
		}

		// Get tag URLs.
		$tags = get_the_terms( $single_id, 'post_tag' );

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$related_urls[] = get_term_link( $tag );
			}
		}

		// Add blogpage.
		$blog_id        = get_option( 'page_for_posts' );
		$related_urls[] = get_permalink( $blog_id );

		// Add frontpage.
		$front_id       = get_option( 'page_on_front' );
		$related_urls[] = get_permalink( $front_id );

		// Get archive URL.
		$post_type      = get_post_type( $single_id );
		$related_urls[] = get_post_type_archive_link( $post_type );

		return $related_urls;
	}

	/**
	 * Get related URls to include in single export.
	 *
	 * @param int $single_id single post id.
	 *
	 * @return array
	 */
	public function get_related_attachements( $single_id ): array {
		$related_files = array();

		// Get all images from that post.
		$response = Url_Fetcher::remote_get( get_permalink( $single_id ) );

		if ( ! is_wp_error( $response ) ) {
			$dom = HtmlDomParser::str_get_html( wp_remote_retrieve_body( $response ) );

			foreach ( $dom->find( 'img' ) as $img ) {
				$related_files[] = $img->getAttribute( 'src' );
				$related_files[] = $img->getAttribute( 'srcset' );
			}
		}

		return $related_files;
	}

	/**
	 * Add single URL.
	 *
	 * @param int $single_id current single id.
	 *
	 * @return void
	 */
	public function add_url( $single_id ) {
		// Add URL.
		$url = get_permalink( $single_id );

		if ( Util::is_local_url( $url ) ) {
			Util::debug_log( 'Adding additional URL to queue: ' . $url );

			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( "Additional URL", 'simply-static' ) );
			$static_page->post_id     = $single_id;
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}

	/**
	 * Ensure the user-specified Additional URLs are in the DB.
	 *
	 * @param array $additional_urls array of additional urls.
	 *
	 * @return void
	 */
	public function add_additional_urls( $additional_urls, $single_id ) {
		foreach ( $additional_urls as $url ) {
			if ( Util::is_local_url( $url ) ) {
				Util::debug_log( 'Adding additional URL to queue: ' . $url );

				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( __( "Additional URL", 'simply-static' ) );
				$static_page->found_on_id = $single_id;
				$static_page->post_id     = $single_id;
				$static_page->save();
			}
		}
	}

	/**
	 * Clear selected single after export.
	 *
	 * @return void
	 */
	public function clear_single() {
		delete_option( 'simply-static-use-single' );
	}

	/**
	 * Filter static pages.
	 *
	 * @param array $results results from database.
	 * @param array $archive_start_time timestamp.
	 *
	 * @return array
	 */
	public function filter_static_pages( $results, $archive_start_time ) {
		$batch_size = apply_filters( 'simply_static_fetch_urls_batch_size', 500 );
		$use_single = get_option( 'simply-static-use-single' );

		if ( empty( $use_single ) ) {
			return $results;
		}

		$post_id = intval( $use_single );

		return Page::query()
		           ->where( 'last_checked_at < ? AND post_id = ?', $archive_start_time, $post_id )
		           ->limit( $batch_size )
		           ->find();
	}

	/**
	 * Filter remaining pages.
	 *
	 * @param array $results results from database.
	 * @param array $archive_start_time timestamp.
	 *
	 * @return int|array
	 */
	public function filter_remaining_pages( $results, $archive_start_time ) {
		$use_single = get_option( 'simply-static-use-single' );

		if ( empty( $use_single ) ) {
			return $results;
		}

		$post_id = intval( $use_single );

		return Page::query()
		           ->where( 'last_checked_at < ? AND post_id = ?', $archive_start_time, $post_id )
		           ->count();
	}


	/**
	 * Filter total pages.
	 *
	 * @param array $results results from the database.
	 *
	 * @return int|mixed|null
	 * @throws \Exception
	 */
	public function filter_total_pages( $results ) {
		$use_single = get_option( 'simply-static-use-single' );

		if ( empty( $use_single ) ) {
			return $results;
		}

		$post_id = intval( $use_single );

		return Page::query()
		           ->where( 'post_id = ?', $post_id )
		           ->count();
	}

	/**
	 * Filter total pages for log.
	 *
	 * @param array $results results from database.
	 *
	 * @return array
	 */
	public function filter_total_pages_log( $results ) {
		$per_page     = $_POST['per_page'];
		$current_page = $_POST['page'];
		$offset       = ( intval( $current_page ) - 1 ) * intval( $per_page );
		$use_single   = get_option( 'simply-static-use-single' );

		if ( empty( $use_single ) ) {
			return $results;
		}

		$post_id = intval( $use_single );

		return Page::query()
		           ->where( 'post_id = ?', $post_id )
		           ->limit( $per_page )
		           ->offset( $offset )
		           ->order( 'http_status_code' )
		           ->find();
	}
}