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
	 * Option key used to track the current crawler between background requests.
	 *
	 * @var string
	 */
	private $crawler_index_option = 'discover_urls_crawler_index';

	/**
	 * Option key used to track the initial URL count between background requests.
	 *
	 * @var string
	 */
	private $initial_count_option = 'discover_urls_initial_count';

	/**
	 * Option key used to track the total added URLs between background requests.
	 *
	 * @var string
	 */
	private $total_added_option = 'discover_urls_total_added';

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
		$active_crawlers = array_values( $crawlers->get_active_crawlers() );

		// Get the archive start time
		$archive_start_time = $this->options->get( 'archive_start_time' );

		if ( empty( $active_crawlers ) ) {
			$initial_count = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
			$this->options->set( $this->total_added_option, 0 );

			return $this->complete_discovery( $archive_start_time, (int) $initial_count );
		}

		$crawler_index = (int) $this->options->get( $this->crawler_index_option );
		$initial_count = $this->options->get( $this->initial_count_option );

		if ( null === $initial_count ) {
			// Count URLs that will be processed in this export before running crawlers.
			$initial_count = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
			$this->options->set( $this->initial_count_option, $initial_count );
		}

		if ( ! isset( $active_crawlers[ $crawler_index ] ) ) {
			return $this->complete_discovery( $archive_start_time, (int) $initial_count );
		}

		$crawler = $active_crawlers[ $crawler_index ];
		$crawler_info = $crawler->js_object();
		$crawler_name = $crawler_info['name'];
		$crawler_number = $crawler_index + 1;
		$crawler_count = count( $active_crawlers );

		$this->save_status_message(
			sprintf(
				__( 'Discovering URLs with %1$s Crawler (%2$d of %3$d)', 'simply-static' ),
				$crawler_name,
				$crawler_number,
				$crawler_count
			)
		);

		// Run the current crawler.
		$urls_added       = $crawler->add_urls_to_queue();
		$total_urls_added = (int) $this->options->get( $this->total_added_option ) + $urls_added;
		$this->options->set( $this->total_added_option, $total_urls_added );

		// Log the number of URLs added.
		Util::debug_log( "Added $urls_added URLs via " . $crawler_name . " Crawler" );

		// Only show individual crawler messages for full exports.
		$generate_type = $this->options->get( 'generate_type' );
		if ( $generate_type === 'export' ) {
			$message = sprintf(
				_n( 'Added %d URL via %s Crawler', 'Added %d URLs via %s Crawler', $urls_added, 'simply-static' ),
				$urls_added,
				$crawler_name
			);
			$this->save_status_message( $message );
		}

		$crawler_index++;
		$this->options->set( $this->crawler_index_option, $crawler_index )->save();
		$this->yield_after_current_crawler();

		if ( $crawler_index < count( $active_crawlers ) ) {
			return false;
		}

		return $this->complete_discovery( $archive_start_time, (int) $initial_count );
	}

	/**
	 * Complete URL discovery and persist the final status.
	 *
	 * @param string $archive_start_time Export start time.
	 * @param int    $initial_count      Initial URL count before crawler discovery.
	 *
	 * @return bool
	 */
	private function complete_discovery( $archive_start_time, $initial_count ) {
		$total_urls_added = (int) $this->options->get( $this->total_added_option );

		// Trigger an action after URL discovery
		do_action( 'ss_after_discover_urls', $total_urls_added );

		// Count URLs that will be processed in this export after running crawlers
		$urls_for_current_export = Page::query()->where( 'last_checked_at < ? OR last_checked_at IS NULL', $archive_start_time )->count();
		$new_urls_for_export = $urls_for_current_export - $initial_count;

		// Only show the "Added X URLs via Crawler" message for full exports
		$generate_type = $this->options->get( 'generate_type' );
		if ( $generate_type === 'export' ) {
			// Save the final status message
			$message = sprintf(
				_n( 'Added %d URL via Crawler', 'Added %d URLs via Crawler', $new_urls_for_export, 'simply-static' ),
				$new_urls_for_export
			);
			$this->save_status_message( $message );
		}

		$this->cleanup();

		return true;
	}

	/**
	 * Stop the current background process after one crawler has run.
	 *
	 * @return void
	 */
	private function yield_after_current_crawler() {
		$filter = 'wp_archive_creation_job_should_continue';
		$job    = Plugin::instance()->get_archive_creation_job();

		if ( $job && method_exists( $job, 'get_identifier' ) ) {
			$filter = $job->get_identifier() . '_should_continue';
		}

		add_filter( $filter, static function () {
			return false;
		}, PHP_INT_MAX );
	}

	/**
	 * Clean up discovery batching state.
	 *
	 * @return void
	 */
	public function cleanup() {
		$this->options->destroy( $this->crawler_index_option );
		$this->options->destroy( $this->initial_count_option );
		$this->options->destroy( $this->total_added_option );

		$this->options->save();
	}
}
