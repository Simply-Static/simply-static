<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 404 Handler
 *
 * In addition to being a page handler, this class also takes care of copying
 * the generated 404 page from the archive to the Local Directory destination
 * via the ss_before_finish_transferring_files_locally hook.
 */
class Handler_404 extends Page_Handler {

    /**
     * Relative dir for 404 fetches.
     *
     * @param string $dir Base directory.
     * @return string
     */
    public function get_relative_dir( $dir ) {
        return '404/';
    }

    /**
     * Hook callback: copy 404/index.html into Local Directory.
     *
     * @param string                        $destination_dir Absolute Local Directory path.
     * @param string                        $archive_dir     Absolute archive (temp) path.
     * @param Transfer_Files_Locally_Task   $task            Task instance (unused here).
     * @return void
     */
    public static function transfer_404_page( $destination_dir, $archive_dir, $task = null ) : void {
        try {
            $source = untrailingslashit( $archive_dir ) . DIRECTORY_SEPARATOR . '404' . DIRECTORY_SEPARATOR . 'index.html';
            if ( ! file_exists( $source ) ) {
                return;
            }

            $dest_dir = untrailingslashit( $destination_dir ) . DIRECTORY_SEPARATOR . '404';
            if ( ! is_dir( $dest_dir ) ) {
                wp_mkdir_p( $dest_dir );
            }

            $dest = $dest_dir . DIRECTORY_SEPARATOR . 'index.html';
            // Do not overwrite an existing 404 file.
            if ( file_exists( $dest ) ) {
                return;
            }

            if ( ! @copy( $source, $dest ) ) {
                Util::debug_log( '[404 Handler] Failed copying 404: ' . $source . ' -> ' . $dest );
            } else {
                Util::debug_log( '[404 Handler] Copied 404 to: ' . $dest );
            }
        } catch ( \Throwable $e ) {
            Util::debug_log( '[404 Handler] Error copying 404: ' . $e->getMessage() );
        }
    }
}

// Register hook listener for Local Directory transfer finalization.
add_action( 'ss_before_finish_transferring_files_locally', [ '\Simply_Static\Handler_404', 'transfer_404_page' ], 10, 3 );
