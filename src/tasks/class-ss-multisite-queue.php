<?php

namespace Simply_Static;

/**
 * Class to handle setup task.
 */
class Multisite_Queue_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'multisite_queue';

	/**
	 * Do the initial setup for generating a static archive
	 *
	 * @return boolean true this always completes in one run, so returns true.
	 */
	public function perform() {
		$message = __( 'Waiting in queue...', 'simply-static' );
		$this->save_status_message( $message, 'setup' );

		if ( Multisite::can_run_export( get_current_blog_id() ) || Multisite::is_queue_empty() ) {
			$message = __( 'Starting soon...', 'simply-static' );
			$this->save_status_message( $message, 'setup' );

			// Making sure it's set just in case.
			Multisite::set_queued_export_as_running( get_current_blog_id() );
			return true;
		}


		return false;
	}
}
