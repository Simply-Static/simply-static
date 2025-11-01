<?php

namespace Simply_Static;

/**
 * Class which handles fetch url task.
 */
class Fetch_Urls_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	public static $task_name = 'fetch_urls';

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
		$batch_size = apply_filters( 'simply_static_fetch_urls_batch_size', 50 );

		$static_pages = apply_filters(
			'ss_static_pages',
			Page::query()
			    ->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
				->limit( $batch_size )
				->find(),
			$this->archive_start_time
		);

		// Compute remaining and total using the dedicated filters so Single/Build exports report correctly
		$pages_remaining = (int) apply_filters(
			'ss_remaining_pages',
			Page::query()
			    ->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
			    ->count(),
			$this->archive_start_time
		);

		$total_pages = (int) apply_filters( 'ss_total_pages', Page::query()->count() );

		// Note: We will recalculate these values again after processing this batch so the
		// status message always reflects the latest progress.
		$pages_processed = $total_pages - $pages_remaining;
		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . $pages_remaining );

		// Track remaining pages locally so we can update progress accurately without extra DB queries.
		$remaining_counter = (int) $pages_remaining;

		while ( $static_page = array_shift( $static_pages ) ) {
			$this->check_if_running();
			Util::debug_log( "URL: " . $static_page->url );
			$this->save_pages_status( $remaining_counter, (int) $total_pages );
			// Decrement after scheduling processing of this page.
			$remaining_counter = max( 0, $remaining_counter - 1 );

			$excludable = apply_filters( 'ss_find_excludable', $this->find_excludable( $static_page ), $static_page );
			if ( $excludable !== false ) {
				$save_file   = false;
				$follow_urls = false;
				Util::debug_log( "Excludable found: URL: " . $static_page->url );
			} else {
				$save_file   = true;
				$follow_urls = true;
				Util::debug_log( "URL is not being excluded" );
			}

			// If we're not saving a copy of the page or following URLs on that
			// page, then we don't need to bother fetching it.
			if ( $save_file === false && $follow_urls === false ) {
				Util::debug_log( "Skipping URL because it is no-save and no-follow" );
				$static_page->last_checked_at = Util::formatted_datetime();
				$static_page->set_status_message( __( "Do not save or follow", 'simply-static' ) );
				$static_page->save();
				continue;
			} else {
				$success = Url_Fetcher::instance()->fetch( $static_page );
			}

			if ( ! $success ) {
				continue;
			}

			// Not found? It's maybe a redirection page. Let's try it without our param.
			if ( $static_page->http_status_code === 404 ) {
				$success = Url_Fetcher::instance()->fetch( $static_page, false );

				if ( ! $success ) {
					continue;
				}
			}

			// If we get a 30x redirect...
			if ( in_array( $static_page->http_status_code, array( 301, 302, 303, 307, 308 ) ) ) {
				$this->handle_30x_redirect( $static_page, $save_file, $follow_urls );
				continue;
			}

			// Not a 200 for the response code? Move on.
			if ( $static_page->http_status_code != 200 ) {
				continue;
			}

			$this->handle_200_response( $static_page, $save_file, $follow_urls );

			do_action( 'ss_after_setup_static_page', $static_page, $pages_remaining );

		}

		// Recalculate progress after processing this batch to avoid stale counters.
		$pages_remaining = (int) apply_filters(
			'ss_remaining_pages',
			Page::query()
		        ->where( 'last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time )
		        ->count(),
			$this->archive_start_time
		);
		$total_pages = (int) apply_filters( 'ss_total_pages', Page::query()->count() );
		$pages_processed = $total_pages - $pages_remaining;

		$message = sprintf( __( "Fetched %d of %d pages/files", 'simply-static' ), $pages_processed, $total_pages );
		$this->save_status_message( $message );

		// If we've processed all pages for this export, signal completion of this task.
		if ( $pages_remaining == 0 ) {
			do_action( 'ss_finished_fetching_pages' );
		}

		return $pages_remaining == 0;
	}


	/**
	 * Process the response for a 200 response (success)
	 *
	 * @param \Simply_Static\Page $static_page Record to update.
	 * @param boolean $save_file Save a static copy of the page.
	 * @param boolean $follow_urls Save found URLs to database.
	 *
	 * @return void
	 */
	public function handle_200_response( $static_page, $save_file, $follow_urls ) {
		if ( $save_file || $follow_urls ) {
			Util::debug_log( "Extracting URLs and replacing URLs in the static file" );
			// Fetch all URLs from the page and add them to the queue...
			$extractor = new Url_Extractor( $static_page );
			$urls      = $extractor->extract_and_update_urls();
		}

		if ( $follow_urls ) {
			Util::debug_log( "Adding " . sizeof( $urls ) . " URLs to the queue" );
			foreach ( $urls as $url ) {
				$this->set_url_found_on( $static_page, $url );
			}
		} else {
			Util::debug_log( "Not following URLs from this page" );
			$static_page->set_status_message( __( "Do not follow", 'simply-static' ) );
		}

		$file = $this->archive_dir . $static_page->file_path;
		if ( $save_file ) {
			Util::debug_log( "We're saving this URL; keeping the static file" );
			$sha1 = sha1_file( $file );

			// if the content is identical, move on to the next file
			if ( $static_page->is_content_identical( $sha1 ) ) {
				// continue;
			} else {
				$static_page->set_content_hash( $sha1 );
			}
		} else {
			Util::debug_log( "Not saving this URL; deleting the static file" );
			unlink( $file ); // delete saved file
			$static_page->file_path = null;
			$static_page->set_status_message( __( "Do not save", 'simply-static' ) );
		}

		$static_page->save();
	}

	/**
	 * Process the response to a 30x redirection
	 *
	 * @param Simply_Static\Page $static_page Record to update
	 * @param boolean $save_file Save a static copy of the page?
	 * @param boolean $follow_urls Save redirect URL to database?
	 *
	 * @return void
	 */
	public function handle_30x_redirect( $static_page, $save_file, $follow_urls ) {
		$origin_url      = Util::origin_url();
		$destination_url = $this->options->get_destination_url();
		$current_url     = $static_page->url;

		// Remove simply_static_page parameter from the redirect URL
		$redirect_url = $static_page->redirect_url;

		// First try standard removal for normal query parameters
		$redirect_url = remove_query_arg( 'simply_static_page', $redirect_url );

		// Also handle cases where simply_static_page is embedded in another parameter
		// Look for patterns like %3Fsimply_static_page%3D12345 (URL-encoded ?simply_static_page=12345)
		$redirect_url = preg_replace( '/%3Fsimply_static_page%3D\d+/i', '', $redirect_url );

		// Handle standard query string formats
		$redirect_url = preg_replace( '/\?simply_static_page=\d+/i', '', $redirect_url );
		$redirect_url = preg_replace( '/&simply_static_page=\d+/i', '', $redirect_url );

		Util::debug_log( "redirect_url: " . $redirect_url );

		// convert our potentially relative URL to an absolute URL
		$redirect_url = Util::relative_to_absolute_url( $redirect_url, $current_url );

		if ( $redirect_url ) {
			// WP likes to 301 redirect `/path` to `/path/` -- we want to
			// check for this and just add the trailing slashed version
			if ( $redirect_url === trailingslashit( $current_url ) || untrailingslashit( $redirect_url ) === untrailingslashit( $current_url ) ) {
				Util::debug_log( "This is a redirect to a trailing slashed version of the same page; adding new URL to the queue" );
				$this->set_url_found_on( $static_page, $redirect_url );

				// Don't create a redirect page if it's just a redirect from
				// http to https. Instead just add the new url to the queue.
				// TODO: Make this less horrible.
			} else if (
				Util::strip_index_filenames_from_url( Util::remove_params_and_fragment( Util::strip_protocol_from_url( $redirect_url ) ) ) ===
				Util::strip_index_filenames_from_url( Util::remove_params_and_fragment( Util::strip_protocol_from_url( $current_url ) ) ) ) {

				Util::debug_log( "This looks like a redirect from http to https (or visa versa); adding new URL to the queue" );
				$this->set_url_found_on( $static_page, $redirect_url );

			} else {
				// check if this is a local URL
				if ( Util::is_local_url( $redirect_url ) ) {

					if ( $follow_urls ) {
						Util::debug_log( "Redirect URL is on the same domain; adding the URL to the queue" );
						$this->set_url_found_on( $static_page, $redirect_url );
					} else {
						Util::debug_log( "Not following the redirect URL for this page" );
						$static_page->set_status_message( __( "Do not follow", 'simply-static' ) );
					}
					// and update the URL
					$redirect_url = str_replace( $origin_url, $destination_url, $redirect_url );

				}

				if ( $save_file ) {
					Util::debug_log( "Creating a redirect page" );

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
				} else {
					Util::debug_log( "Not creating a redirect page" );
					$static_page->set_status_message( __( "Do not save", 'simply-static' ) );
				}

				$static_page->save();
			}
		}
	}

	/**
	 * Find excludable.
	 *
	 * @param object $static_page current page.
	 *
	 * @return bool
	 */
 public function find_excludable( $static_page ) {
 		// Delegate exclusion decision to central utility for consistency with crawlers
 		if ( \Simply_Static\Util::is_url_excluded( $static_page->url ) ) {
 			return true;
 		}
		$excluded = array( '.php' );
		$url = $static_page->url;

		// Exclude debug files (.log, .txt) but not robots.txt
		if ( preg_match( '/\.(log|txt)$/i', $url ) && strpos( $url, 'debug' ) !== false && strpos( $url, 'robots.txt' ) === false ) {
			return true;
		}

		// Exclude feeds if add_feeds is not true.
		if ( ! $this->options->get( 'add_feeds' ) ) {
			// Only exclude WordPress XML feeds (ending with /feed/ or ?feed= parameter)
			if ( preg_match( '/(\/feed\/?$|\?feed=|\/feed\/|\/rss\/?$|\/atom\/?$)/i', $url ) ) {
				return true;
			}
		}

		// Exclude Rest API if add_rest_api is not true.
		if ( ! $this->options->get( 'add_rest_api' ) ) {
			$excluded[] = 'wp-json';
		}

		if ( ! empty( $this->options->get( 'urls_to_exclude' ) ) ) {
			if ( is_array( $this->options->get( 'urls_to_exclude' ) ) ) {
				$excluded_by_option = wp_list_pluck( $this->options->get( 'urls_to_exclude' ), 'url' );
			} else {
				$excluded_by_option = explode( "\n", $this->options->get( 'urls_to_exclude' ) );
			}

			if ( is_array( $excluded_by_option ) ) {
				$excluded = array_merge( $excluded, $excluded_by_option );
			}

		}

		if ( apply_filters( 'simply_static_exclude_temp_dir', true ) ) {
			$excluded[] = Util::get_temp_dir_url();
		}

		$excluded = apply_filters( 'ss_excluded_by_default', $excluded );

		if ( $excluded ) {
			$excluded = array_filter( $excluded );
		}

		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $excludable ) {
				if ( strpos( $url, $excludable ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Set ID for which page a URL was found on (& create page if not in DB yet)
	 *
	 * Given a URL, find the associated Simply_Static\Page, and then set the ID
	 * for which page it was found on if the ID isn't yet set or if the record
	 * hasn't been updated in this instance of static generation yet.
	 *
	 * @param \Simply_Static\Page $static_page The record for the parent page.
	 * @param string $child_url The URL of the child page.
	 * @param string $start_time Static generation start time.
	 *
	 * @return void
	 */
	public function set_url_found_on( $static_page, $child_url ) {
		// Skip adding the selected custom 404 page as a regular page
		$exclude_url = '';
		if ( $this->options->get( 'generate_404' ) && (int) $this->options->get( 'custom_404_page' ) ) {
			$permalink = get_permalink( (int) $this->options->get( 'custom_404_page' ) );
			if ( $permalink ) {
				$exclude_url = untrailingslashit( $permalink );
			}
		}
		if ( ! empty( $exclude_url ) && 0 === strcasecmp( untrailingslashit( $child_url ), $exclude_url ) ) {
			Util::debug_log( sprintf( 'Skipping link-follow to custom 404 page URL "%s"', $child_url ) );
			return;
		}

		// Do not add excluded URLs to the database at all
		if ( Util::is_url_excluded( $child_url ) ) {
			Util::debug_log( sprintf( 'Skipping excluded child URL: %s', $child_url ) );
			return;
		}

		$child_static_page = Page::query()->find_or_create_by( 'url', $child_url );
		if ( $child_static_page->found_on_id === null || $child_static_page->updated_at < $this->archive_start_time ) {
			$child_static_page->found_on_id = $static_page->id;
			if ( ! $child_static_page->post_id ) {
				$id = url_to_postid( $child_url );
				if ( $id ) {
					$child_static_page->post_id = $id;
				}
			}

			$child_static_page->handler = apply_filters( 'simply_static_handler_class_on_url_found', $static_page->get_handler_class(), $child_url, $static_page );

			do_action( 'simply_static_child_page_found_on_url_before_save', $child_static_page, $static_page );

			$child_static_page->save();
		}
	}

	/**
	 * Save the contents of a page to a file in our archive directory
	 *
	 * @param Simply_Static\Page $static_page The Simply_Static\Page record.
	 * @param string $content The content of the page we want to save.
	 *
	 * @return string|null                The file path of the saved file.
	 */
	public function save_static_page_content_to_file( $static_page, $content ) {
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
