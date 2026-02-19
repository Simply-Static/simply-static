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
	 * @return boolean|\WP_Error true if done, false if not done, WP_Error if error.
	 */
	public function perform() {
		$this->save_status_message( __( 'Discovering URLs', 'simply-static' ) );

		// Get the discovery state
		$state = $this->options->get( 'discovery_state' );

		if ( ! $state ) {
			$state = [
				'index'            => 0,
				'offset'           => 0,
				'total_urls_added' => 0,
				'initial_count'    => 0,
			];
		}

		// Get the crawler manager
		$crawlers = Crawlers::instance();

		// Get active crawlers
		$active_crawlers = array_values( $crawlers->get_active_crawlers() );

		// If no active crawlers, we're done
		if ( empty( $active_crawlers ) ) {
			return true;
		}

		// Get the current crawler
		if ( ! isset( $active_crawlers[ $state['index'] ] ) ) {
			return $this->finalize_discovery( $state );
		}

		$crawler = $active_crawlers[ $state['index'] ];

		// Get the archive start time
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// If this is the very first start, get initial count
		if ( $state['index'] === 0 && $state['offset'] === 0 ) {
			$state['initial_count'] = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
		}

		// Run the current crawler
		$result = $crawler->add_urls_to_queue( $state['offset'] );

		$state['total_urls_added'] += $result['urls_added'];

		// Log the number of URLs added
		Util::debug_log( sprintf( 'Added %d URLs via %s Crawler (Offset: %d)', $result['urls_added'], $crawler->js_object()['name'], $state['offset'] ) );

		// Only show individual crawler messages for full exports
		$generate_type = $this->options->get( 'generate_type' );
		if ( $generate_type === 'export' ) {
			// Save the status message
			$message = sprintf( __( "Added %d URLs via %s Crawler", 'simply-static' ),
				$result['urls_added'],
				$crawler->js_object()['name']
			);
			$this->save_status_message( $message );
		}

		if ( $result['is_done'] ) {
			$state['index']++;
			$state['offset'] = 0;
		} else {
			$state['offset'] = $result['new_offset'];
		}

		// Save state
		$this->options->set( 'discovery_state', $state )->save();

		// If we've processed all crawlers and the last one is done
		if ( $state['index'] >= count( $active_crawlers ) ) {
			return $this->finalize_discovery( $state );
		}

		// Not done yet
		return false;
	}

	/**
	 * Finalize the discovery process
	 *
	 * @param array $state Discovery state.
	 * @return bool
	 */
	protected function finalize_discovery( array $state ) {
		// Trigger an action after URL discovery
		do_action( 'ss_after_discover_urls', $state['total_urls_added'] );

		// Get the archive start time
		$archive_start_time = $this->options->get( 'archive_start_time' );

		// Count URLs that will be processed in this export after running crawlers
		$urls_for_current_export = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
		$new_urls_for_export     = $urls_for_current_export - $state['initial_count'];

		// Only show the "Added X URLs via Crawler" message for full exports
		$generate_type = $this->options->get( 'generate_type' );
		if ( $generate_type === 'export' ) {
			// Save the final status message
			$message = sprintf( __( "Added %d URLs via Crawler", 'simply-static' ), $new_urls_for_export );
			$this->save_status_message( $message );
		}

		// Clear state
		$this->options->set( 'discovery_state', null )->save();

		return true;
	}

	/**
	 * Cleanup discovery state
	 *
	 * @return void
	 */
	public function cleanup() {
		$this->options->set( 'discovery_state', null )->save();
	}
}
