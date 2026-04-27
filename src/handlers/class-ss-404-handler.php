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
        return '';
    }

    public function get_path_info( $path_info ) {
        $path_info['filename'] = '404';
        $path_info['extension'] = 'html';
        return $path_info;
    }

    /**
     * Hook callback: copy 404.html into Local Directory.
     *
     * @param string                        $destination_dir Absolute Local Directory path.
     * @param string                        $archive_dir     Absolute archive (temp) path.
     * @param Transfer_Files_Locally_Task   $task            Task instance (unused here).
     * @return void
     */
    public static function transfer_404_page( $destination_dir, $archive_dir, $task = null ) : void {
        try {
            $source = untrailingslashit( $archive_dir ) . DIRECTORY_SEPARATOR . '404.html';
            if ( ! file_exists( $source ) ) {
                return;
            }

            $dest = untrailingslashit( $destination_dir ) . DIRECTORY_SEPARATOR . '404.html';
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
