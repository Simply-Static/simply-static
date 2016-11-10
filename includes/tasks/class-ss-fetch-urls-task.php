<?php
namespace Simply_Static;

class Fetch_Urls_Task extends Task {

	/**
	 * @var string
	 */
	protected $action = 'fetch_urls';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->archive_dir = $this->options->get_archive_dir();
		$this->archive_start_time = $this->options->get( 'archive_start_time' );
	}

	/**
	 * Fetch and save pages for the static archive
	 * @return boolean|WP_Error true if done, false if not done, WP_Error if error
	 */
	public function perform() {
		$batch_size = 10;

		$static_pages = Page::query()
			->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
			->limit( $batch_size )
			->find();
		$pages_remaining = Page::query()
			->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
			->count();
		$total_pages = Page::query()->count();
		$pages_processed = $total_pages - $pages_remaining;

		while ( $static_page = array_shift( $static_pages ) ) {

			// TODO: Add page IDs to a queue that fetches pages
			// run query again when process is complete()?

			// $filename = $this->get_filename_for_static_page( $static_page );
			$success = Url_Fetcher::instance()->fetch( $static_page, $this->archive_dir );

			if ( ! $success ) {
				continue;
			}

			// If we get a 30x redirect...
			if ( in_array( $static_page->http_status_code, array( 301, 302, 303, 307, 308 ) ) ) {
				$this->handle_30x_redirect( $static_page );
				continue;
			}

			// Not a 200 for the response code? Move on.
			if ( $static_page->http_status_code != 200 ) {
				continue;
			}

			$this->handle_200_response( $static_page );
		}

		// if we haven't processed any additional pages, we're done
		return $pages_remaining == 0;
	}

	/**
	 * Process the response for a 200 response (success)
	 * @param  Simply_Static\Page         $static_page Record to update
	 * @return void
	 */
	protected function handle_200_response( $static_page ) {
		// Fetch all URLs from the page and add them to the queue...
		$extractor = new Url_Extractor( $static_page );
		$urls = $extractor->extract_and_update_urls();

		foreach ( $urls as $url ) {
			$this->set_url_found_on( $static_page, $url, $this->archive_start_time );
		}

		$sha1 = sha1_file( $this->archive_dir . $static_page->file_path );

		// if the content is identical, move on to the next file
		if ( $static_page->is_content_identical( $sha1 ) ) {
			// continue;
		} else {
			$static_page->set_content_hash( $sha1 );
		}

		$static_page->save();
	}

	/**
	 * Process the response to a 30x redirection
	 * @param  Simply_Static\Page         $static_page Record to update
	 * @return void
	 */
	protected function handle_30x_redirect( $static_page ) {
		$origin_url = sist_origin_url();
		$destination_url = $this->options->get_destination_url();
		$current_url = $static_page->url;
		$redirect_url = $static_page->redirect_url;

		// convert our potentially relative URL to an absolute URL
		$redirect_url = sist_relative_to_absolute_url( $redirect_url, $current_url );

		// WP likes to 301 redirect `/path` to `/path/` -- we want to
		// check for this and just add the trailing slashed version
		if ( $redirect_url === trailingslashit( $current_url ) ) {

			$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );

		// Don't create a redirect page if it's just a redirect from
		// http to https. Instead just add the new url to the queue.
		// TODO: Make this less horrible.
		} else if (
		sist_strip_index_filenames_from_url( sist_remove_params_and_fragment( sist_strip_protocol_from_url( $redirect_url ) ) ) ===
		sist_strip_index_filenames_from_url( sist_remove_params_and_fragment( sist_strip_protocol_from_url( $current_url ) ) ) ) {

			$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );

		} else {

			if ( $redirect_url ) {

				// check if this is a local URL
				if ( sist_is_local_url( $redirect_url ) ) {

					$this->set_url_found_on( $static_page, $redirect_url, $this->archive_start_time );
					// and update the URL
					$redirect_url = str_replace( $origin_url, $destination_url, $redirect_url );

				}

				$view = new View();

				$content = $view->set_template( 'redirect' )
					->assign( 'redirect_url', $redirect_url )
					->render_to_string();

				$filename = $this->save_static_page_content_to_file( $static_page, $content );
				if ( $filename ) {
					$static_page->file_path = $filename;
				}

				$sha1 = sha1_file( $this->archive_dir . $filename );

				// if the content is identical, move on to the next file
				if ( $static_page->is_content_identical( $sha1 ) ) {
					// continue;
				} else {
					$static_page->set_content_hash( $sha1 );
				}

				$static_page->save();
			}
		}
	}

	/**
	 * Set ID for which page a URL was found on (& create page if not in DB yet)
	 *
	 * Given a URL, find the associated Simply_Static\Page, and then set the ID
	 * for which page it was found on if the ID isn't yet set or if the record
	 * hasn't been updated in this instance of static generation yet.
	 * @param Simply_Static\Page $static_page The record for the parent page
	 * @param string             $child_url   The URL of the child page
	 * @param string             $start_time  Static generation start time
	 * @return void
	 */
	protected function set_url_found_on( $static_page, $child_url, $start_time ) {
		$child_static_page = Page::query()
			->find_or_create_by( 'url' , $child_url );
		if ( $child_static_page->found_on_id === null || $child_static_page->updated_at < $start_time ) {
			$child_static_page->found_on_id = $static_page->id;
			$child_static_page->save();
		}
	}

	/**
	 * Save the contents of a page to a file in our archive directory
	 * @param Simply_Static\Page $static_page The Simply_Static\Page record
	 * @param string             $content The content of the page we want to save
	 * @return string|null                The file path of the saved file
	 */
	protected function save_static_page_content_to_file( $static_page, $content ) {
		$relative_filename = Url_Fetcher::instance()->create_directories_for_static_page( $static_page );

		if ( $relative_filename ) {
			$file_path = $this->archive_dir . $relative_filename;

			$write = file_put_contents( $file_path, $content );
			if ( $write === false ) {
				$static_page->set_error_message( 'Unable to write temporary file' );
			} else {
				return $relative_filename;
			}
		} else {
			return null;
		}
	}
}
