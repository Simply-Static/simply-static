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
		require_once SIMPLY_STATIC_PATH . 'src/crawler/class-crawlers.php';
	}

	/**
	 * Discover and add URLs to the queue using the crawler system
	 *
	 * @return boolean|WP_Error true if done, false if not done, WP_Error if error.
	 */
	public function perform() {
		$this->save_status_message( __( 'Discovering URLs using crawlers', 'simply-static' ) );

		// Get the crawler manager
		$crawlers = Crawlers::instance();

		// Run all active crawlers
		$urls_added = $crawlers->run();

		// Log the number of URLs added
		Util::debug_log( "Added $urls_added URLs to the queue using crawlers" );

		// Save the status message
		$message = sprintf( __( "Added %d URLs to the queue", 'simply-static' ), $urls_added );
		$this->save_status_message( $message );

		// Trigger an action after URL discovery
		do_action( 'ss_after_discover_urls', $urls_added );

		return true;
	}
}