<?php
namespace Simply_Static;

class Wrapup_Task extends Task {

	/**
	 * @var string
	 */
	protected static $task_name = 'wrapup';

	public function perform() {
		if ( $this->options->get( 'delete_temp_files' ) === '1' ) {
			$this->save_status_message( __( 'Wrapping up', 'simply-static' ) );
			$deleted_successfully = $this->delete_temp_static_files();
		}

		return true;
	}

	/**
	 * Delete temporary, generated static files
	 * @return true|\WP_Error True on success, WP_Error otherwise
	 */
	public function delete_temp_static_files() {
		$archive_dir = $this->options->get_archive_dir();

		if ( file_exists( $archive_dir ) ) {
			$directory_iterator = new \RecursiveDirectoryIterator( $archive_dir, \FilesystemIterator::SKIP_DOTS );
			$recursive_iterator = new \RecursiveIteratorIterator( $directory_iterator, \RecursiveIteratorIterator::CHILD_FIRST );

			// recurse through the entire directory and delete all files / subdirectories
			foreach ( $recursive_iterator as $item ) {
				$success = $item->isDir() ? rmdir( $item ) : unlink( $item );
				if ( ! $success ) {
					return new \WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $item ) );
				}
			}

			// must make sure to delete the original directory at the end
			$success = rmdir( $archive_dir );
			if ( ! $success ) {
				return new \WP_Error( 'cannot_delete_file_or_dir', sprintf( __( "Could not delete temporary file or directory: %s", 'simply-static' ), $item ) );
			}
		}

		return true;
	}
}
