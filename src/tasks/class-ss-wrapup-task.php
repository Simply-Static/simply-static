<?php

namespace Simply_Static;

/**
 * Class which handles wrap up task.
 */
class Wrapup_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'wrapup';

	/**
	 * Perform the task.
	 *
	 * @return bool
	 */
	public function perform() {
		// Always clear the 404-only runtime flag here so subsequent exports are unaffected.
		delete_option( 'simply-static-404-only' );

		// Optionally clear the temporary directory (disabled by default).
		$clear_temp_dir = apply_filters( 'ss_clear_temp_dir_on_wrapup', false );

		if ( $clear_temp_dir ) {
			try {
				Util::debug_log( 'Clearing temporary directory on wrapup' );
				$setup_task = new Setup_Task();
				$deleted    = $setup_task->delete_temp_static_files();
				do_action( 'ss_after_clear_temp_dir_on_wrapup', $deleted );
			} catch ( \Throwable $e ) {
				Util::debug_log( 'Error clearing temporary directory on wrapup: ' . $e->getMessage() );
			}
		}

		$this->save_status_message( __( 'Wrapping up', 'simply-static' ) );

		// Unschedule cron first.
		wp_clear_scheduled_hook( 'simply_static_site_export_cron' );

		// Clear WP object cache.
		$flush_cache = apply_filters( 'ss_flush_cache', true );

		if ( $flush_cache ) {
			wp_cache_flush();
		}

		do_action( 'ss_after_cleanup' );

		return true;
	}
}
