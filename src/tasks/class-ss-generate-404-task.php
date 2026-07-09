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

		$custom_404_id = intval( $this->options->get( 'custom_404_page' ) );
		$targets       = $this->get_404_targets( $custom_404_id );
		$generated_any = false;

		foreach ( $targets as $target ) {
			if ( $this->generate_404_target( $target ) ) {
				$generated_any = true;
			}
		}

		if ( $generated_any ) {
			$this->save_status_message( __( '404 Page generated', 'simply-static' ) );
		}

		return true;
	}

	/**
	 * Get the 404 targets to generate.
	 *
	 * @param int $custom_404_id Custom 404 page ID.
	 *
	 * @return array
	 */
	private function get_404_targets( int $custom_404_id ) : array {
		$default_target = array(
			'file_path'   => '404.html',
			'language'    => '',
			'post_id'     => $custom_404_id,
			'require_404' => empty( $custom_404_id ),
			'url'         => '',
			'url_base'    => Util::origin_url(),
		);

		if ( ! empty( $custom_404_id ) ) {
			$permalink = get_permalink( $custom_404_id );

			if ( $permalink ) {
				$default_target['url'] = $permalink;
			}
		}

		$targets = array( $default_target );

		/**
		 * Filter the 404 pages generated during the 404 task.
		 *
		 * Each target can define file_path, url, url_base, language, post_id,
		 * and require_404. This supports multilingual plugins generating
		 * language-specific files such as en/404.html and fr/404.html.
		 *
		 * @param array $targets 404 generation targets.
		 * @param array $context Generation context.
		 */
		$targets = apply_filters( 'simply_static_404_targets', $targets, array(
			'custom_404_page_id' => $custom_404_id,
			'archive_dir'        => $this->archive_dir,
		) );

		return $this->normalize_404_targets( is_array( $targets ) ? $targets : array( $default_target ) );
	}

	/**
	 * Normalize 404 targets.
	 *
	 * @param array $targets 404 targets.
	 *
	 * @return array
	 */
	private function normalize_404_targets( array $targets ) : array {
		$normalized = array();
		$seen_paths = array();

		foreach ( $targets as $target ) {
			if ( ! is_array( $target ) ) {
				continue;
			}

			$file_path = ! empty( $target['file_path'] ) && is_string( $target['file_path'] ) ? ltrim( wp_normalize_path( $target['file_path'] ), '/' ) : '404.html';

			if ( '404.html' !== basename( $file_path ) || isset( $seen_paths[ $file_path ] ) ) {
				continue;
			}

			$seen_paths[ $file_path ] = true;

			$normalized[] = array(
				'file_path'   => $file_path,
				'language'    => ! empty( $target['language'] ) ? sanitize_text_field( (string) $target['language'] ) : '',
				'post_id'     => ! empty( $target['post_id'] ) ? absint( $target['post_id'] ) : 0,
				'require_404' => ! empty( $target['require_404'] ),
				'url'         => ! empty( $target['url'] ) && is_string( $target['url'] ) ? esc_url_raw( $target['url'] ) : '',
				'url_base'    => ! empty( $target['url_base'] ) && is_string( $target['url_base'] ) ? esc_url_raw( $target['url_base'] ) : Util::origin_url(),
			);
		}

		return $normalized;
	}

	/**
	 * Generate a single 404 target.
	 *
	 * @param array $target 404 target.
	 *
	 * @return bool
	 */
	private function generate_404_target( array $target ) : bool {
		$slug  = time();
		$count = 1;

		do {
			$url = ! empty( $target['url'] ) ? $target['url'] : trailingslashit( $target['url_base'] ) . ( $slug + $count );

			$static_page = $this->create_404_page( $url, $target );
			$success     = Url_Fetcher::instance()->fetch( $static_page );

			$count ++;

			if ( ! $success ) {
				$this->cleanup_failed_404_attempt( $static_page );
				continue;
			}

			if ( ! empty( $target['require_404'] ) && 404 !== (int) $static_page->http_status_code ) {
				$this->cleanup_failed_404_attempt( $static_page );
				continue;
			}

			$this->handle_response( $static_page );

			do_action( 'simply_static_404_generated', $static_page, $target );

			return true;
		} while ( empty( $target['url'] ) && $count <= 50 );

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
	 * Create a page record for a generated 404 target.
	 *
	 * @param string $url    URL to fetch.
	 * @param array  $target 404 target.
	 *
	 * @return Page
	 */
	private function create_404_page( string $url, array $target ) : Page {
		$static_page = Page::initialize( array(
			'post_id'             => $this->get_scoped_post_id( $target ),
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
			'file_path'           => $target['file_path'],
		) );

		$static_page->set_json_data_by_key( 'generated_404', true );

		if ( ! empty( $target['language'] ) ) {
			$static_page->set_json_data_by_key( 'language', $target['language'] );
		}

		$static_page->save();

		return $static_page;
	}

	/**
	 * Get the post ID that keeps generated 404 pages visible to scoped exports.
	 *
	 * @param array $target 404 target.
	 *
	 * @return int
	 */
	private function get_scoped_post_id( array $target ) : int {
		$use_single = get_option( 'simply-static-use-single' );

		if ( ! empty( $use_single ) ) {
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $use_single ) ) ) );

			if ( ! empty( $ids ) ) {
				return $ids[0];
			}
		}

		return ! empty( $target['post_id'] ) ? absint( $target['post_id'] ) : 0;
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
