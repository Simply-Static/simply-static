<?php
namespace Simply_Static;

/**
 * Class which handles cancelling tasks.
 */
class Cancel_Task extends Task {

	/**
	 * Current task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'cancel';

	/**
	 * Perform action to run on cancel task.
	 *
	 * @return bool
	 */
	public function perform() {
		$this->save_status_message( __( 'Cancelling job', 'simply-static' ) );

		$wrapup_task = new Wrapup_Task();
		$wrapup_task->perform();

		return true;
	}
}
