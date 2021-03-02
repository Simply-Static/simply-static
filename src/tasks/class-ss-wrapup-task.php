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
		Util::debug_log( "Deleting temporary files" );
		$this->save_status_message( __( 'Wrapping up', 'simply-static' ) );

		// Unschedule cron first.
		wp_clear_scheduled_hook( 'simply_static_site_export_cron' );

		// Delete files in temp dir.
		$this->delete_temp_static_files();

		return true;
	}

	/**
	 * Delete temporary, generated static files.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public function delete_temp_static_files() {
		$options = Options::instance();
		$dir     = $options->get( 'temp_files_dir' );

		if ( false === file_exists( $dir ) ) {
			return false;
		}

		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $files as $fileinfo ) {
			if ( $fileinfo->isDir() ) {
				if ( false === rmdir( $fileinfo->getRealPath() ) ) {
					return false;
				}
			} else {
				if ( false === unlink( $fileinfo->getRealPath() ) ) {
					return false;
				}
			}
		}
		return true;
	}
}
