<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rule File Handler
 *
 * Copies rule text files (e.g., robots.txt, llms.txt) from the archive root
 * into the Local Directory destination at the end of a Local Directory transfer.
 */
class Rule_File_Handler {

    /**
     * Copy known rule files from archive to destination.
     *
     * @param string $destination_dir Absolute Local Directory path.
     * @param string $archive_dir     Absolute archive (temp) path.
     * @return void
     */
    public static function transfer_rule_files( $destination_dir, $archive_dir ) : void {
        $filenames = apply_filters( 'ss_rule_files_to_transfer', array( 'robots.txt', 'llms.txt' ) );

        // Respect global include flags so site owners can disable transferring these files entirely.
        $include_robots = (bool) apply_filters( 'ss_include_robots_txt_in_export', true );
        $include_llms   = (bool) apply_filters( 'ss_include_llms_txt_in_export', true );

        if ( ! $include_robots ) {
            $filenames = array_values( array_diff( (array) $filenames, array( 'robots.txt' ) ) );
            Util::debug_log( '[Rule File] robots.txt transfer disabled via ss_include_robots_txt_in_export' );
        }
        if ( ! $include_llms ) {
            $filenames = array_values( array_diff( (array) $filenames, array( 'llms.txt' ) ) );
            Util::debug_log( '[Rule File] llms.txt transfer disabled via ss_include_llms_txt_in_export' );
        }
        if ( ! is_array( $filenames ) || empty( $filenames ) ) {
            return;
        }

        foreach ( $filenames as $filename ) {
            $filename = ltrim( (string) $filename, '/\\' );
            if ( '' === $filename ) {
                continue;
            }

            $source = trailingslashit( $archive_dir ) . $filename;
            $dest   = trailingslashit( $destination_dir ) . $filename;

            if ( ! file_exists( $source ) ) {
                continue;
            }

            $dest_dir = dirname( $dest );
            if ( ! is_dir( $dest_dir ) ) {
                wp_mkdir_p( $dest_dir );
            }

            if ( ! @copy( $source, $dest ) ) {
                Util::debug_log( '[Rule File] Failed to copy ' . $source . ' to ' . $dest );
            } else {
                Util::debug_log( '[Rule File] Copied: ' . $dest );
            }
        }
    }
}

// Hook into Local Directory transfer before finish.
add_action( 'ss_before_finish_transferring_files_locally', [ '\Simply_Static\Rule_File_Handler', 'transfer_rule_files' ], 10, 2 );
