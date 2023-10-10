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

		$found_404   = false;
		$static_page = null;
		$slug        = time();
		$count       = 1;

		$message = __( 'Generating 404 Page.', 'simply-static' );
		$this->save_status_message( $message );


		do {
			$page_slug             = $slug + $count;
			$static_page           = new Page();
			$static_page->post_id  = 0;
			$static_page->id  = 0;
			$static_page->build_id = 0;
			$static_page->url      = home_url( (string) $page_slug );
			$static_page->handler  = Handler_404::class;
			$static_page->error_message = '';
			$static_page->found_on_id = 0;
			$static_page->redirect_url = '';
			$static_page->status_message = '';
			$static_page->content_type = 'text/html';
			$static_page->content_hash = '';
			$static_page->last_checked_at = current_time('mysql');
			$static_page->last_transferred_at = current_time('mysql');
			$static_page->last_modified_at = current_time('mysql');
			$static_page->updated_at = current_time('mysql');
			$static_page->created_at = current_time('mysql');
			$static_page->site_id = 0;
			$static_page->file_path = 'index.html';

			$success = Url_Fetcher::instance()->fetch( $static_page );

			$count++;

			if ( ! $success ) {
				continue;
			}

			if ( $static_page->http_status_code !== 404 ) {
				continue;
			}


			$this->handle_response( $static_page );

			$found_404 = true;

			$message = __( '404 Page generated', 'simply-static' );

			$this->save_status_message( $message );


		} while( ! $found_404 );


		return true;
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

		$file = $this->archive_dir . $static_page->file_path;

		Util::debug_log( "We're saving this URL; keeping the static file" );
		$sha1 = sha1_file( $file );

		// if the content is identical, move on to the next file
		if ( $static_page->is_content_identical( $sha1 ) ) {
			// continue;
		} else {
			$static_page->set_content_hash( $sha1 );
		}


		$static_page->save();
	}
}
