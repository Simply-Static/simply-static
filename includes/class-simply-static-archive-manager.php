<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL manager class
 * @package Simply_Static
 */
class Simply_Static_Archive_Manager {

	/** @const */
	private static $states = array(
		'idle' => array(
			'type' => 'final',
			'transitions' => array(
				'start' => 'setup',
				'error' => 'error'
			)
		),
		'setup' => array(
			'type' => 'normal',
			'transitions' => array(
				'next' => 'fetching',
				'cancel' => 'cancelled',
				'error' => 'error'
			)
		),
		'fetching' => array(
			'type' => 'normal',
			'transitions' => array(
				'next' => 'transferring',
				'cancel' => 'cancelled',
				'error' => 'error'
			)
		),
		'transferring' => array(
			'type' => 'normal',
			'transitions' => array(
				'next' => 'wrapup',
				'cancel' => 'cancelled',
				'error' => 'error'
			)
		),
		'wrapup' => array(
			'type' => 'normal',
			'transitions' => array(
				'next' => 'finished',
				'cancel' => 'cancelled',
				'error' => 'error'
			)
		),
		'finished' => array(
			'type' => 'normal',
			'transitions' => array(
				'next' => 'idle',
				'cancel' => 'cancel',
				'error' => 'error'
			)
		),
		'cancelled' => array(
			'type' => 'final',
			'transitions' => array(
				'start' => 'setup',
				'error' => 'error'
			)
		),
		'error' => array(
			'type' => 'final',
			'transitions' => array(
				'start' => 'setup'
			)
		)
	);

	/**
	 * Stores options for the archive manager using Simply_Static_Options
	 * @var Simply_Static_Options
	 */
	protected $options = null;

    /**
	 * Performs initializion of the options structure
	 * @param string $option_key The options key name
	 */
	public function __construct( $options ) {
		$this->options = $options;

		// Set the initial archive state to 'idle'
		if ( $this->options->get( 'archive_state_name' ) === null ) {
			$this->options
				->set( 'archive_state_name', 'idle' )
				->set( 'archive_status_messages', array() )
				->save();
		}

		register_shutdown_function( array( $this, 'shutdown_handler' ) );
	}

	/**
	 * Take an AJAX action and dole it out to a corresponding function
	 * @return void
	 */
	public function perform( $action ) {
		try {
			$function_name = 'handle_ajax_' . $action;
			$this->$function_name();
		} catch ( Exception $e ) {
			$this->exception_occurred( $e );
		}
	}

	/**
	 * Determine whether to move to the next state, stay put, or throw an error
	 *
	 * Check the $result, if it's...
	 * - true = state completed successfully (move to next state)
	 * - false = state not yet done (stay in same state)
	 * - WP_Error = something failed (set error state)
	 * @param boolean|WP_Error $result The result of processing from Archive_Creator
	 * @return void
	 */
	private function next_or_error( $result ) {
		if ( is_wp_error( $result ) ) {
			$this->error_occurred( $result );
		} else {
			if ( $result == true && ! $this->has_finished() ) {
				$this->apply( 'next' );
			} // else: keep the same state
		}
	}

	/**
	 * Handle an ajax 'start' request (or set error if not possible)
	 * @return void
	 */
	private function handle_ajax_start() {
		if ( $this->can( 'start' ) ) {
			$this->apply( 'start' );
			$this->next_or_error( $this->handle_setup_state() );
		} else {
			// unknown action or transition to wrong state
			$this->error_occurred( new WP_Error( 'invalid_state_transition' ) );
		}
	}

	/**
	 * Handle an ajax 'continue' request
	 * @return void
	 */
	private function handle_ajax_continue() {
		$state_name = $this->get_state_name();
		$function_name = 'handle_' . $state_name . '_state';
		$this->next_or_error( $this->$function_name() );
	}

	/**
	 * Handle an ajax 'cancel' request
	 * @return void
	 */
	private function handle_ajax_cancel() {
		$this->apply( 'cancel' );
		$this->handle_cancelled_state();
	}

	/**
	 * Get an array of status messages for the current job
	 * @return array
	 */
	public function get_status_messages() {
		return $this->options->get( 'archive_status_messages' );
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * Providing a unique key for the message is optional. If one isn't
	 * provided, the state_name will be used. Using the same key more than once
	 * will overwrite previous messages.
	 * @param  string $message Message to display about the status of the job
	 * @param  string $key     Unique key for the message
	 * @return void
	 */
	private function save_status_message( $message, $key = null ) {
		$state_name = $key ?: $this->get_state_name();
		$messages = $this->get_status_messages();

		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $state_name, $messages ) ) {
			$messages[ $state_name ] = array(
				'message' => $message,
				'datetime' => sist_formatted_datetime()
			);
		} else { // otherwise just update the message
			$messages[ $state_name ]['message'] = $message;
		}

		$this->options
			->set( 'archive_status_messages', $messages )
			->save();
	}

	/**
	 * Get the name of the current state
	 * @return string The name of the current state
	 */
	public function get_state_name() {
		return $this->options->get( 'archive_state_name' );
	}

	/**
	 * Return the array of info on the current state
	 * @return array Returns info from static $states for the current state
	 */
	private function get_state() {
		return self::$states[ $this->get_state_name() ];
	}

	/**
	 * Get the current path to the temp static archive directory
	 * @return string The path to the temp static archive directory
	 */
	private function get_archive_dir() {
		return sist_add_trailing_directory_separator( $this->options->get( 'temp_files_dir' ) . $this->options->get( 'archive_name' )  );
	}

	/**
	 * Get the datetime that archive creation started
	 * @return string Starting datetime for archive creation
	 */
	private function get_start_time() {
		return $this->options->get( 'archive_start_time' );
	}

	/**
	 * Are we done building the static archive?
	 * @return boolean Return true if we're done building the archive, false otherwise
	 */
	public function has_finished() {
		$state = $this->get_state();
		return $state['type'] == 'final';
	}

	/**
	 * Can we transition to a new state from our current state?
	 * @param  string  $transition_name The name of the transition
	 * @return boolean                  Return true if we can transition, false otherwise
	 */
	private function can( $transition_name ) {
		$state = $this->get_state();
		return isset( $state['transitions'][ $transition_name ] );
	}

	/**
	 * Apply a transition to a new state
	 * @param  string $transition_name The name of the transition
	 * @return boolean                 Was the transition successful?
	 */
	private function apply( $transition_name ) {
		$state = $this->get_state();
		if ( $this->can( $transition_name ) ) {
			$new_state_name = $state['transitions'][ $transition_name ];
			$this->options->set( 'archive_state_name', $new_state_name )->save();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Do processing for the 'setup' state
	 * @return true|WP_Error Returns true if successful or WP_Error if there's a problem
	 */
	private function handle_setup_state() {
		global $blog_id;

		$current_user = wp_get_current_user();
		$archive_name = join( '-', array( Simply_Static::SLUG, $blog_id, time(), $current_user->user_login ) );

		$this->options
			->set( 'archive_status_messages', array() )
			->set( 'archive_name', $archive_name )
			->set( 'archive_creator_id', $current_user->ID )
			->set( 'archive_blog_id', $blog_id )
			->set( 'archive_start_time', sist_formatted_datetime() )
			->set( 'archive_end_time', null )
			->save();

		$message = sprintf( __( "%s has started generating static files", 'simply-static' ), $current_user->user_login );
		$this->save_status_message( $message, 'initiated_by_user' );

		$message = __( 'Setting up', 'simply-static' );
		$this->save_status_message( $message );

		$archive_dir = $this->get_archive_dir();

		// create temp archive directory
		if ( ! file_exists( $archive_dir ) ) {
			$create_dir = wp_mkdir_p( $archive_dir );
			if ( $create_dir === false ) {
				return new WP_Error( 'cannot_create_archive_dir' );
			}
		}

		// TODO: Add a way for the user to perform this, optionally, so that we
		// don't need to do it every time. Then enable the two commented-out
		// sections below.
		Simply_Static_Page::query()->delete_all();

		// clear out any saved error messages on pages
		//Simply_Static_Page::query()
		// ->update_all( 'error_message', null );

		// delete pages that we can't process
		//Simply_Static_Page::query()
		// ->where( 'http_status_code IS NULL OR http_status_code NOT IN (?)', implode( ',', Simply_Static_Archive_Creator::$processable_status_codes ) )
		// ->delete_all();

		// add origin url and additional urls/files to database
		Simply_Static_Archive_Creator::add_origin_and_additional_urls_to_db( $this->options->get( 'additional_urls' ) );
		Simply_Static_Archive_Creator::add_additional_files_to_db( $this->options->get( 'additional_files' ) );

		return true;
	}

	/**
	 * Do processing for the 'fetching' state
	 * @return boolean|WP_Error true if done processing, false if more processing, WP_Error if problem
	 */
	private function handle_fetching_state() {
		$archive_creator = new Simply_Static_Archive_Creator(
			$this->options->get( 'destination_scheme' ),
			$this->options->get( 'destination_host' ),
			$this->get_archive_dir(),
			$this->get_start_time()
		);

		$destination_url_type = $this->options->get( 'destination_url_type' );
		$relative_path = $this->options->get( 'relative_path' );
		list( $pages_processed, $total_pages ) = $archive_creator->fetch_pages( $destination_url_type, $relative_path );

		$message = sprintf( __( "Fetched %d of %d pages/files", 'simply-static' ), $pages_processed, $total_pages );
		$this->save_status_message( $message );

		if ( is_wp_error( $pages_processed ) ) {
			return $pages_processed;
		} else {
			// return true when done (no more pages)
			return $pages_processed == $total_pages;
		}
	}

	/**
	 * Do processing for the 'transferring' state
	 * @return boolean|WP_Error true if done processing, false if more processing, WP_Error if problem
	 */
	private function handle_transferring_state() {
		$archive_creator = new Simply_Static_Archive_Creator(
			$this->options->get( 'destination_scheme' ),
			$this->options->get( 'destination_host' ),
			$this->get_archive_dir(),
			$this->get_start_time()
		);

		if ( $this->options->get( 'delivery_method' ) == 'zip' ) {

			$download_url = $archive_creator->create_zip();
			if ( is_wp_error( $download_url ) ) {
				return $download_url;
			} else {
				$message = __( 'ZIP archive created: ', 'simply-static' );
				$message .= ' <a href="' . $download_url . '">' . __( 'Click here to download', 'simply-static' ) . '</a>';
				$this->save_status_message( $message );
				return true;
			}

		} elseif ( $this->options->get( 'delivery_method' ) == 'local' ) {

			$local_dir = $this->options->get( 'local_dir' );

			list( $pages_processed, $total_pages ) = $archive_creator->copy_static_files( $local_dir );

			if ( $pages_processed !== 0 ) {
				$message = sprintf( __( "Copied %d of %d files", 'simply-static' ), $pages_processed, $total_pages );
				$this->save_status_message( $message );
			}

			if ( is_wp_error( $pages_processed ) ) {
				return $pages_processed;
			} else {
				if ( $pages_processed == $total_pages ) {
					if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
						$destination_url = $this->options->get( 'destination_scheme' ) . $this->options->get( 'destination_host' );
						$message = __( 'Destination URL:', 'simply-static' ) . ' <a href="' . $destination_url .'" target="_blank">' . $destination_url . '</a>';
						$this->save_status_message( $message, 'destination_url' );
					}
				}

				// return true when done (no more pages)
				return $pages_processed == $total_pages;
			}
		}
	}

	/**
	 * Do processing for the 'wrapup' state
	 * @return true Continue on to next step
	 */
	private function handle_wrapup_state() {
		$this->save_status_message( __( 'Wrapping up', 'simply-static' ) );

		if ( $this->options->get( 'delete_temp_files' ) === '1' ) {
			$archive_creator = new Simply_Static_Archive_Creator(
				$this->options->get( 'destination_scheme' ),
				$this->options->get( 'destination_host' ),
				$this->get_archive_dir(),
				$this->get_start_time()
			);

			$deleted_successfully = $archive_creator->delete_temp_static_files();
		}

		return true;
	}

	/**
	 * Do processing for the 'finished' state
	 * @return true Continue on to next step
	 */
	private function handle_finished_state() {
		$end_time = sist_formatted_datetime();
		$start_time = $this->get_start_time();
		$duration = strtotime( $end_time ) - strtotime( $start_time );
		$time_string = gmdate( "H:i:s", $duration );

		$this->options->set( 'archive_end_time', $end_time );
		$this->save_status_message( sprintf( __( 'Done! Finished in %s', 'simply-static' ), $time_string ) );

		return true;
	}

	/**
	 * Do processing for the 'cancelled' state
	 * @return false Do not do additional processing
	 */
	private function handle_cancelled_state() {
		$this->save_status_message( __( 'Cancelled', 'simply-static' ) );

		return false;
	}

	private function exception_occurred( $exception ) {
		$this->apply( 'error' );
		$message = sprintf( __( "An exception occurred: %s", 'simply-static' ), $exception->getMessage() );
		$this->save_status_message( $message );
	}

	/**
	 * Change to the error state and immediately process it
	 * @return void
	 */
	private function error_occurred( $wp_error ) {
		$this->apply( 'error' );
		$message = sprintf( __( "An error occurred: %s", 'simply-static' ), $wp_error->get_error_message() );
		$this->save_status_message( $message );
	}

	/**
	 * Do processing for the 'error' state
	 * @param  WP_Error $wp_error WP_Error to process
	 * @return false              Do not do additional processing
	 */
	private function handle_error_state() {
		return false;
	}

	/**
	 * Shutdown handler for fatal error reporting
	 * @return void
	 */
	public function shutdown_handler() {
		$error = error_get_last();
		// only trigger on actual errors, not warnings or notices
		if ( $error && in_array( $error['type'], array( E_ERROR, E_CORE_ERROR, E_USER_ERROR ) ) ) {
			$error_message = '(' . $error['type'] . ') ' . $error['message'];
			$error_message .= ' in <b>' . $error['file'] . '</b>';
			$error_message .= ' on line <b>' . $error['line'] . '</b>';

			$this->apply( 'error' );
			$message = sprintf( __( "Error: %s", 'simply-static' ), $error_message );
			$this->save_status_message( $message );
		}
	}
}
