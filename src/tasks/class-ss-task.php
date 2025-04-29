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
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * Initiate new task
	 */
	public function __construct() {
		$this->options = Options::instance();
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * Providing a unique key for the message is optional. If one isn't
	 * provided, the state_name will be used. Using the same key more than once
	 * will overwrite previous messages.
	 *
	 * @param  string $message Message to display about the status of the job.
	 * @param  string $key     Unique key for the message.
	 * @return void
	 */
	protected function save_status_message( $message, $key = null ) {
		$task_name = $key ?: static::$task_name;
		$messages = $this->options->get( 'archive_status_messages' );
		Util::debug_log( 'Status message: [' . $task_name . '] ' . $message );

		$messages = Util::add_archive_status_message( $messages, $task_name, $message );

		$this->options
			->set( 'archive_status_messages', $messages )
			->save();

        if ( $this->is_wp_cli_running() ) {
            \WP_CLI::line( $message );
        }
	}

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
