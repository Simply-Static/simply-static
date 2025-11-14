<?php

namespace Simply_Static;

/**
 * Class which handles URL discovery using the crawler system.
 */
class Discover_Urls_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	public static $task_name = 'discover_urls';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		// Load the Crawlers class
		require_once SIMPLY_STATIC_PATH . 'src/crawler/class-ss-crawlers.php';
	}

	/**
	 * Discover and add URLs to the queue using the crawler system
	 *
	 * @return boolean|WP_Error true if done, false if not done, WP_Error if error.
	 */
	public function perform() {
		$this->save_status_message( __( 'Discovering URLs', 'simply-static' ) );

		// Get the crawler manager
		$crawlers = Crawlers::instance();

		// Get active crawlers
		$active_crawlers = $crawlers->get_active_crawlers();

		// Run active crawlers
		$total_urls_added = 0;

		// Get the archive start time
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// Count URLs that will be processed in this export before running crawlers
		$initial_count = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();

		foreach ( $active_crawlers as $crawler ) {
			// Run the current crawler
			$urls_added = $crawler->add_urls_to_queue();
			$total_urls_added += $urls_added;

			// Log the number of URLs added
			Util::debug_log( "Added $urls_added URLs via " . $crawler->js_object()['name'] . " Crawler" );

			// Only show individual crawler messages for full exports
			$generate_type = $this->options->get( 'generate_type' );
			if ( $generate_type === 'export' ) {
				// Save the status message
				$message = sprintf( __( "Added %d URLs via %s Crawler", 'simply-static' ),
					$urls_added, 
					$crawler->js_object()['name']
				);
				$this->save_status_message( $message );
			}
		}

		// Trigger an action after URL discovery
		do_action( 'ss_after_discover_urls', $total_urls_added );

		// Count URLs that will be processed in this export after running crawlers
		$urls_for_current_export = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
		$new_urls_for_export = $urls_for_current_export - $initial_count;

		// Only show the "Added X URLs via Crawler" message for full exports
		$generate_type = $this->options->get( 'generate_type' );
		if ( $generate_type === 'export' ) {
			// Save the final status message
			$message = sprintf( __( "Added %d URLs via Crawler", 'simply-static' ), $new_urls_for_export );
			$this->save_status_message( $message );
		}

		return true;
	}
}
