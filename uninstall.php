<?php
/**
 * Uninstall Simply Static
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clear any scheduled exports.
if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
	wp_clear_scheduled_hook( 'simply_static_site_export_cron' );
}

// Remove the entire Simply Static directory inside uploads (e.g., wp-content/uploads/simply-static).
// We do this before deleting options so a custom temp path still resolves correctly if needed elsewhere.
$uploads = wp_upload_dir();
$ss_root = trailingslashit( $uploads['basedir'] ) . 'simply-static';

/**
 * Recursively delete a directory and its contents.
 * Lightweight and local to uninstall to avoid loading plugin classes.
 *
 * @param string $dir
 *
 * @return bool True on success or if path does not exist; false on failure.
 */
function ss_rrmdir_uninstall( $dir ) {
	$dir = (string) $dir;
	if ( $dir === '' || ! file_exists( $dir ) ) {
		return true; // nothing to do
	}
	if ( is_file( $dir ) || is_link( $dir ) ) {
		return unlink( $dir );
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $path => $fileinfo ) {
		if ( $fileinfo->isDir() ) {
			if ( ! rmdir( $fileinfo->getRealPath() ) ) {
				return false;
			}
		} else {
			if ( ! unlink( $fileinfo->getRealPath() ) ) {
				return false;
			}
		}
	}

	return rmdir( $dir );
}

ss_rrmdir_uninstall( $ss_root );

// Delete Simply Static's settings after filesystem cleanup.
delete_option( 'simply-static' );

// Drop DB tables used by Simply Static.
require_once plugin_dir_path( __FILE__ ) . 'src/class-ss-plugin.php';
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-model.php';
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-page.php';

Simply_Static\Page::drop_table();
