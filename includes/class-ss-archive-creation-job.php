<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-admin/admin-functions.php' );

/**
 * Simply Static archive manager class
 */
class Archive_Creation_Job extends \WP_Background_Process {

	/** @const */
	public static $task_list = array(
		'setup',
		'fetch_urls',
		'transfer_files_locally',
		//'create_zip_archive',
		'wrapup'
	);

	/**
	* @var string
	 */
	protected $action = 'archive_creation_process';

	/**
	* @var string
	 */
	protected $current_task = null;

	/**
	 * Performs initializion of the options structure
	 * @param string $option_key The options key name
	 */
	public function __construct() {
		register_shutdown_function( array( $this, 'shutdown_handler' ) );
		parent::__construct();
	}

	/**
	 * Helper method for starting the Archive_Creation_Job
	 * @return boolean true if we were able to successfully start generating an archive
	 */
	public function start() {
		if ( ! $this->is_process_running() ) {
			global $blog_id;

			$first_task = self::$task_list[0];
			$archive_name = join( '-', array( Plugin::SLUG, $blog_id, time() ) );

			Options::instance()
				->set( 'archive_status_messages', array() )
				->set( 'archive_name', $archive_name )
				->set( 'archive_start_time', sist_formatted_datetime() )
				->set( 'archive_end_time', null )
				->save();

			$this->push_to_queue( $first_task )
				->save()
				->dispatch();

			return true;
		} else {
			// looks like we're in the middle of creating an archive...
			return false;
		}

	}

	/**
	 * Perform the task at hand
	 * @param string        $task Task name to process
	 * @return false|string       task name to process, or false if done
	 */
	protected function task( $task_name ) {
		$this->set_current_task( $task_name );

		// error_log( '$task_name: ' . $task_name );

		// convert 'an_example' to 'An_Example_Task'
		$class_name = 'Simply_Static\\' . ucwords( $task_name ) . '_Task';

		// this shouldn't ever happen, but just in case...
		if ( ! class_exists( $class_name ) ) {
			$this->save_status_message( "Class doesn't exist: " . $class_name, 'error' );
			return false;
		}

		$task = new $class_name();

		try {
			$is_done = $task->perform();
		} catch ( \Exception $e ) {
			$this->exception_occurred( $e );
		}

		if ( is_wp_error( $is_done ) ) {
			$this->error_occurred( $is_done );
			// we've hit an error, time to quit
			return false;
		} else if ( $is_done === true ) {
			$next_task = $this->find_next_task( $task_name );
			if ( $next_task === null ) {
				// we're done; returning false to remove item from queue
				return false;
			} else {
				// start the next task
				return $next_task;
			}
		} else { // $is_done === false
			// returning current task name to continue processing
			return $task_name;
		}

		return false; // remove item from queue
	}

	/**
	 * This is run at the end of the job, after task() has returned false
	 */
	protected function complete() {
		$this->set_current_task( 'done' );

		$end_time = sist_formatted_datetime();
		$start_time = Options::instance()->get( 'archive_start_time' );
		$duration = strtotime( $end_time ) - strtotime( $start_time );
		$time_string = gmdate( "H:i:s", $duration );

		Options::instance()->set( 'archive_end_time', $end_time );

		$this->save_status_message( sprintf( __( 'Done! Finished in %s', 'simply-static' ), $time_string ) );
		parent::complete();
	}

	public function cancel() {
		if ( ! $this->is_queue_empty() ) {
			$batch = $this->get_batch();
			$batch->data = array( 'cancel' );
			$this->update( $batch->key, $batch->data );
		}
	}

	/**
	 * Is the job running?
	 * @return boolean True if running, false if not
	 */
	public function is_process_running() {
		return parent::is_process_running();
	}

	/**
	 * Return the current task
	 * @return string The current task
	 */
	public function get_current_task() {
		return $this->current_task;
	}

	protected function set_current_task( $task_name ) {
		$this->current_task = $task_name;
	}

	/**
	 * Find the next task on our task list
	 * @param  string $task_name The name of the current task
	 * @return string|null       The name of the next task, or null if none
	 */
	protected function find_next_task( $task_name ) {
		$index = array_search( $task_name, self::$task_list );
		if ( $index === false ) {
			return null;
		}

		$index += 1;
		if ( ( $index ) >= count( self::$task_list ) ) {
			return null;
		} else {
			return self::$task_list[ $index ];
		}
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
	protected function save_status_message( $message, $key = null ) {
		$task_name = $key ?: $this->get_current_task();
		$messages = Options::instance()->get( 'archive_status_messages' );

		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $task_name, $messages ) ) {
			$messages[ $task_name ] = array(
				'message' => $message,
				'datetime' => sist_formatted_datetime()
			);
		} else { // otherwise just update the message
			$messages[ $task_name ]['message'] = $message;
		}

		Options::instance()
			->set( 'archive_status_messages', $messages )
			->save();
	}

	private function exception_occurred( $exception ) {
		$this->complete();
		$message = sprintf( __( "An exception occurred: %s", 'simply-static' ), $exception->getMessage() );
		$this->save_status_message( $message, 'error' );
	}

	/**
	 * Change to the error state and immediately process it
	 * @return void
	 */
	private function error_occurred( $wp_error ) {
		$this->complete();
		$message = sprintf( __( "An error occurred: %s", 'simply-static' ), $wp_error->get_error_message() );
		$this->save_status_message( $message, 'error' );
	}

	/**
	 * Shutdown handler for fatal error reporting
	 *
	 * Note: this function must be public in order to function properly.
	 *
	 * @return void
	 */
	public function shutdown_handler() {
		$error = error_get_last();
		// only trigger on actual errors, not warnings or notices
		if ( $error && in_array( $error['type'], array( E_ERROR, E_CORE_ERROR, E_USER_ERROR ) ) ) {
			$this->clear_scheduled_event();
			$this->cancel_process();
			$this->unlock_process();

			$error_message = '(' . $error['type'] . ') ' . $error['message'];
			$error_message .= ' in <b>' . $error['file'] . '</b>';
			$error_message .= ' on line <b>' . $error['line'] . '</b>';

			$message = sprintf( __( "Error: %s", 'simply-static' ), $error_message );
			$this->save_status_message( $message, 'error' );
		}
	}

 }
