<?php
namespace Simply_Static;

/**
 * Class which handles task abstraction.
 */
abstract class Task {

	/**
	 * Task name
	 *
	 * @var string
	 */
	protected static $task_name = 'task';

	/**
	 * An instance of the options structure containing all options for this plugin.
	 *
	 * @var \Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * Initiate new task
	 */
	public function __construct() {
		$this->options = Options::instance();
	}

	/**
	 * Require a usable homepage artifact when exporting the entire site.
	 *
	 * A failed request may have no file_path and disappear from delivery queries.
	 * Check the expected file, since a canonical URL may have written it instead
	 * of the original homepage record.
	 *
	 * @param string $directory    Archive or local destination directory.
	 * @param bool   $for_transfer Whether to apply the delivery filename filter.
	 * @return true|\WP_Error
	 */
	protected function validate_homepage_file( $directory, $for_transfer = false ) {
		$type = $this->options->get( 'generate_type' );
		if (
			( $type && 'export' !== $type )
			|| get_option( 'simply-static-use-single' )
			|| get_option( 'simply-static-use-build' )
			|| get_option( 'simply-static-404-only' )
		) {
			return true;
		}

		/**
		 * Filter the homepage entry points required for a complete site export.
		 *
		 * @param string[] $urls Origin homepage and any integration entry points.
		 */
		$homepage_urls = apply_filters( 'ss_required_homepage_urls', array( trailingslashit( Util::origin_url() ) ) );
		foreach ( array_unique( (array) $homepage_urls ) as $homepage_url ) {
			if ( ! is_string( $homepage_url ) || '' === $homepage_url ) {
				continue;
			}
			$result = $this->validate_homepage_url_file( $homepage_url, $directory, $for_transfer );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate one homepage using the same filename rules as generation/delivery.
	 *
	 * @param string $homepage_url Homepage entry point URL.
	 * @param string $directory Archive or local destination directory.
	 * @param bool   $for_transfer Whether to apply the delivery filename filter.
	 * @return true|\WP_Error
	 */
	protected function validate_homepage_url_file( $homepage_url, $directory, $for_transfer ) {
		$homepage = Page::query()->find_or_initialize_by( 'url', $homepage_url );
		// Preserve handler and record metadata used by integration filename filters.
		if ( ! $homepage->content_type ) {
			$homepage->content_type = 'text/html';
		}
		if ( false !== apply_filters( 'ss_find_excludable', Util::is_url_excluded( $homepage->url ), $homepage ) ) {
			return true;
		}

		$file_path = Url_Fetcher::instance()->get_expected_file_path_for_static_page( $homepage );
		// A filename filter may deliberately suppress homepage output.
		if ( ! is_string( $file_path ) || '' === $file_path ) {
			return true;
		}
		if ( $for_transfer ) {
			$homepage->file_path = $file_path;
			$file_path           = ltrim( Util::normalize_slashes( (string) apply_filters( 'ss_get_page_file_path_for_transfer', $file_path, $homepage ) ), '/' );
		}

		$absolute_path = Util::combine_path( $directory, $file_path );
		clearstatcache( true, $absolute_path );
		if ( is_file( $absolute_path ) && is_readable( $absolute_path ) && filesize( $absolute_path ) > 0 ) {
			return true;
		}

		Util::debug_log( 'Missing, empty, or unreadable homepage artifact: ' . $absolute_path . ' (URL: ' . $homepage->url . ')' );

		$message = sprintf(
			/* translators: 1: homepage filename, 2: homepage URL. */
			__( 'Unable to finish the export: the homepage file "%1$s" is missing, empty, or unreadable for %2$s. Check the homepage HTTP response and the export log for errors.', 'simply-static' ),
			$file_path,
			$homepage->url
		);
		if ( $homepage->http_status_code ) {
			/* translators: %d: HTTP response status code for the homepage. */
			$message .= ' ' . sprintf( __( 'Homepage HTTP status: %d.', 'simply-static' ), $homepage->http_status_code );
		}
		if ( $homepage->error_message ) {
			$message .= ' ' . $homepage->error_message;
		}

		return new \WP_Error( 'ss_missing_homepage_file', $message );
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * Providing a unique key for the message is optional. If one isn't
	 * provided, the state_name will be used. Using the same key more than once
	 * will overwrite previous messages.
	 *
	 * @param string     $message Message to display about the status of the job.
	 * @param string     $key     Unique key for the message.
	 * @param array|null $link    Optional link data with URL and label.
	 *
	 * @return void
	 */
	protected function save_status_message( $message, $key = null, $link = null ) {
		$task_name = $key ?: static::$task_name;
		$messages = $this->options->get( 'archive_status_messages' );
		$log_message = $message;

		// Structured links are stored separately from the user-facing message, but
		// the plain-text debug log must remain useful to deployment scripts.
		if (
			is_array( $link ) &&
			! empty( $link['url'] ) &&
			false === strpos( $log_message, (string) $link['url'] )
		) {
			$log_message = rtrim( $log_message ) . ' ' . $link['url'];
		}

		Util::debug_log( 'Status message: [' . $task_name . '] ' . $log_message );

		$messages = Util::add_archive_status_message( $messages, $task_name, $message, false, $link );

		$this->options
			->set( 'archive_status_messages', $messages )
			->save();

        if ( $this->is_wp_cli_running() ) {
            \WP_CLI::line( $message );
        }
	}

	/**
	 * @unused
	 * @deprecated Not used anymore.
	 *
	 * @param $pages_remaining
	 * @param $pages_total
	 *
	 * @return void
	 */
	protected function save_pages_status( $pages_remaining, $pages_total ) {
		Util::debug_log( '[PAGES STATUS] Remaining:' . $pages_remaining . '; Total: ' . $pages_total );

		$this->options
			->set( 'pages_status', array("remaining" => $pages_remaining, "total" => $pages_total) )
			->save();
	}

	/*
	* Override this method to perform the task action.
	* @return boolean|WP_Error true if done, false if not done, WP_Error if error
	*/
	abstract public function perform();

    protected function is_wp_cli_running() {
        return defined( 'WP_CLI' ) && WP_CLI;
    }

	protected function check_if_running() {
		if ( Plugin::instance()->get_archive_creation_job()->is_paused() ) {
			throw new Pause_Exception( 'Job paused' );
		}

		if ( Plugin::instance()->get_archive_creation_job()->is_cancelled() ) {
			throw new Pause_Exception( 'Job cancelled' );
		}
	}
}
