<?php

namespace Simply_Static;

/**
 * Class which handles fetch url task.
 */
class Generate_404_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	public static $task_name = 'generate_404';

	/**
	 * The path to the archive directory.
	 *
	 * @var string
	 */
	public string $archive_dir;

	/**
	 * The time the archive was started.
	 *
	 * @var string
	 */
	public string $archive_start_time;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->archive_dir        = $this->options->get_archive_dir();
		$this->archive_start_time = $this->options->get( 'archive_start_time' );
	}

	/**
	 * Fetch and save pages for the static archive
	 *
	 * @return boolean|WP_Error true if done, false if not done, WP_Error if error.
	 */
	public function perform() {

		$message = __( 'Generating 404 Page.', 'simply-static' );
		$this->save_status_message( $message );

		$custom_404_id  = intval( $this->options->get( 'custom_404_page' ) );
		$custom_404_url = $custom_404_id ? get_permalink( $custom_404_id ) : '';
		$custom_404_url = is_string( $custom_404_url ) ? $custom_404_url : '';
		$generated      = false;

		if ( $custom_404_url ) {
			$generated = $this->generate_404_page( $custom_404_url, $custom_404_id );
		}

		if ( ! $generated ) {
			$generated = $this->generate_404_page( '', 0 );
		}

		if ( $generated ) {
			$this->save_status_message( __( '404 Page generated', 'simply-static' ) );
		}

		return true;
	}

	/**
	 * Generate the root 404 page.
	 *
	 * @param string $custom_404_url Custom 404 page URL, or an empty string for the theme 404.
	 * @param int    $custom_404_id  Custom 404 page ID.
	 *
	 * @return bool
	 */
	private function generate_404_page( string $custom_404_url, int $custom_404_id ) : bool {
		$slug  = time();
		$count = 1;

		do {
			$url = $custom_404_url ?: trailingslashit( Util::origin_url() ) . ( $slug + $count );

			$static_page = $this->create_404_page( $url, $custom_404_id );
			$success     = Url_Fetcher::instance()->fetch( $static_page );

			$count ++;

			if ( ! $success ) {
				$this->cleanup_failed_404_attempt( $static_page );
				continue;
			}

			if ( ! $custom_404_url && 404 !== (int) $static_page->http_status_code ) {
				$this->cleanup_failed_404_attempt( $static_page );
				continue;
			}

			$file = $this->archive_dir . ltrim( (string) $static_page->file_path, '/\\' );

			if ( empty( $static_page->file_path ) || ! file_exists( $file ) ) {
				$this->cleanup_failed_404_attempt( $static_page );
				continue;
			}

			$this->handle_response( $static_page );

			return true;
		} while ( ! $custom_404_url && $count <= 50 );

		return false;
	}

	/**
	 * Remove a failed 404 attempt record and generated file.
	 *
	 * @param Page $static_page Static page.
	 *
	 * @return void
	 */
	private function cleanup_failed_404_attempt( Page $static_page ) : void {
		if ( ! empty( $static_page->file_path ) ) {
			$file = $this->archive_dir . ltrim( $static_page->file_path, '/\\' );

			if ( file_exists( $file ) ) {
				@unlink( $file );
			}
		}

		if ( ! empty( $static_page->id ) ) {
			$static_page->delete();
		}
	}

	/**
	 * Create a page record for the generated root 404.
	 *
	 * @param string $url           URL to fetch.
	 * @param int    $custom_404_id Custom 404 page ID.
	 *
	 * @return Page
	 */
	private function create_404_page( string $url, int $custom_404_id ) : Page {
		$static_page = Page::initialize( array(
			'post_id'             => $this->get_scoped_post_id( $custom_404_id ),
			'build_id'            => $this->get_scoped_build_id(),
			'url'                 => $url,
			'handler'             => Handler_404::class,
			'error_message'       => '',
			'found_on_id'         => 0,
			'redirect_url'        => '',
			'status_message'      => '',
			'content_type'        => 'text/html',
			'content_hash'        => '',
			'last_checked_at'     => current_time( 'mysql' ),
			'last_transferred_at' => '0000-00-00 00:00:00',
			'last_modified_at'    => current_time( 'mysql' ),
			'updated_at'          => current_time( 'mysql' ),
			'created_at'          => current_time( 'mysql' ),
			'file_path'           => '404.html',
		) );

		$static_page->set_json_data_by_key( 'generated_404', true );

		$static_page->save();

		return $static_page;
	}

	/**
	 * Get the post ID that keeps generated 404 pages visible to scoped exports.
	 *
	 * @param int $custom_404_id Custom 404 page ID.
	 *
	 * @return int
	 */
	private function get_scoped_post_id( int $custom_404_id ) : int {
		$use_single = get_option( 'simply-static-use-single' );

		if ( ! empty( $use_single ) ) {
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $use_single ) ) ) );

			if ( ! empty( $ids ) ) {
				return $ids[0];
			}
		}

		return absint( $custom_404_id );
	}

	/**
	 * Get the build ID that keeps generated 404 pages visible to build exports.
	 *
	 * @return int
	 */
	private function get_scoped_build_id() : int {
		$use_build = get_option( 'simply-static-use-build' );

		return ! empty( $use_build ) ? absint( $use_build ) : 0;
	}


	/**
	 * Process the response for a 200 response (success)
	 *
	 * @param Simply_Static\Page $static_page Record to update.
	 * @param boolean $save_file Save a static copy of the page.
	 * @param boolean $follow_urls Save found URLs to database.
	 *
	 * @return void
	 */
	public function handle_response( $static_page ) {

		Util::debug_log( "Replacing URLs in the static file" );
		$extractor = new Url_Extractor( $static_page );
		$extractor->extract_and_update_urls();

		$file = $this->archive_dir . $static_page->file_path;

		Util::debug_log( "We're saving this URL; keeping the static file" );

		try {
			$sha1 = sha1_file( $file );
			if ( false === $sha1 ) {
				$sha1 = '';
			}
		} catch ( \Exception $e ) {
			$sha1 = '';
		}

		// if the content is identical, move on to the next file
		if ( $static_page->is_content_identical( $sha1 ) ) {
			// continue;
		} else {
			$static_page->set_content_hash( $sha1 );
		}


		$static_page->save();
	}
}
