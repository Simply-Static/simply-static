<?php
/**
 * Uninstall Simply Static.
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Recursively delete a directory and its contents without following symlinks.
 *
 * @param string $dir Directory to remove.
 *
 * @return bool True on success or if the path does not exist; false on failure.
 */
function ss_rrmdir_uninstall( $dir ) {
	$dir = (string) $dir;

	if ( '' === $dir ) {
		return true;
	}

	// Check links before file_exists() so broken symlinks are removed too.
	if ( is_link( $dir ) ) {
		return unlink( $dir );
	}

	if ( ! file_exists( $dir ) ) {
		return true;
	}

	if ( is_file( $dir ) ) {
		return unlink( $dir );
	}

	try {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file_info ) {
			$path = $file_info->getPathname();

			if ( $file_info->isLink() || $file_info->isFile() ) {
				if ( ! unlink( $path ) ) {
					return false;
				}
			} elseif ( $file_info->isDir() && ! rmdir( $path ) ) {
				return false;
			}
		}
	} catch ( \Throwable $exception ) {
		return false;
	}

	return rmdir( $dir );
}

/**
 * Restore Divi settings left in their temporary export state on the current site.
 *
 * @return void
 */
function ss_restore_divi_options_uninstall() {
	$backup = get_option( 'simply_static_divi_performance_options_backup' );

	if ( is_array( $backup ) ) {
		$containers = array( 'et_divi', 'et_pb_options', 'et_core_options', 'et_core_option' );

		foreach ( $backup as $option_name => $values ) {
			if ( ! in_array( $option_name, $containers, true ) || ! is_array( $values ) ) {
				continue;
			}

			$current   = get_option( $option_name );
			$original  = isset( $values['original'] ) && is_array( $values['original'] ) ? $values['original'] : array();
			$temporary = isset( $values['temporary'] ) && is_array( $values['temporary'] ) ? $values['temporary'] : array();

			if ( ! is_array( $current ) ) {
				continue;
			}

			foreach ( $original as $key => $original_value ) {
				if (
					array_key_exists( $key, $temporary )
					&& array_key_exists( $key, $current )
					&& $current[ $key ] === $temporary[ $key ]
				) {
					$current[ $key ] = $original_value;
				}
			}

			update_option( $option_name, $current );
		}
	}

	delete_option( 'simply_static_divi_performance_options_backup' );
}

/**
 * Delete queued batches and per-task progress caches from the current site.
 *
 * @return void
 */
function ss_delete_runtime_rows_uninstall() {
	global $wpdb;

	if (
		! isset( $wpdb->options )
		|| ! method_exists( $wpdb, 'esc_like' )
		|| ! method_exists( $wpdb, 'prepare' )
		|| ! method_exists( $wpdb, 'query' )
	) {
		return;
	}

	$batch_pattern    = $wpdb->esc_like( 'wp_archive_creation_job_batch_' ) . '%';
	$progress_pattern = $wpdb->esc_like( 'simply_static_' ) . '%' . $wpdb->esc_like( '_total_pages' );
	$query            = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s";

	$wpdb->query( $wpdb->prepare( $query, $batch_pattern, $progress_pattern ) );
}

/**
 * Get the network that owns a site.
 *
 * @param int $site_id Site ID.
 *
 * @return int Network ID, or zero when it cannot be determined.
 */
function ss_get_site_network_id_uninstall( $site_id ) {
	if ( ! is_multisite() || ! function_exists( 'get_site' ) ) {
		return 0;
	}

	$site = get_site( absint( $site_id ) );

	return $site && isset( $site->network_id ) ? absint( $site->network_id ) : 0;
}

/**
 * Delete an option from an explicit network, with a single-network fallback.
 *
 * @param int    $network_id Network ID.
 * @param string $option     Option name.
 *
 * @return void
 */
function ss_delete_network_option_uninstall( $network_id, $option ) {
	if ( is_multisite() && $network_id && function_exists( 'delete_network_option' ) ) {
		delete_network_option( $network_id, $option );
		return;
	}

	delete_site_option( $option );
}

/**
 * Delete a site transient from an explicit network.
 *
 * WordPress' site-transient helpers implicitly use the current network. Using
 * delete_network_option() avoids leaving locks behind on multi-network setups,
 * where switch_to_blog() alone does not change that implicit network context.
 *
 * @param int    $network_id Network ID.
 * @param string $transient  Transient name without its storage prefix.
 *
 * @return void
 */
function ss_delete_network_transient_uninstall( $network_id, $transient ) {
	if ( is_multisite() && $network_id && function_exists( 'delete_network_option' ) ) {
		delete_network_option( $network_id, '_site_transient_' . $transient );
		delete_network_option( $network_id, '_site_transient_timeout_' . $transient );

		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $transient, 'site-transient' );
		}

		return;
	}

	delete_site_transient( $transient );
}

/**
 * Delete all plugin-owned state for the current site.
 *
 * This function must run after switch_to_blog() for the requested site so the
 * options table, uploads directory, cron array, and custom tables are scoped
 * correctly.
 *
 * @param int $site_id Current site ID.
 *
 * @return void
 */
function ss_cleanup_site_uninstall( $site_id ) {
	$site_id    = absint( $site_id );
	$network_id = ss_get_site_network_id_uninstall( $site_id );

	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'simply_static_site_export_cron' );
		wp_clear_scheduled_hook( 'wp_archive_creation_job_cron' );
	}

	// Do not create an uploads directory just to remove the plugin's directory.
	$uploads = wp_upload_dir( null, false );
	if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) ) {
		ss_rrmdir_uninstall( trailingslashit( $uploads['basedir'] ) . 'simply-static' );
	}

	ss_restore_divi_options_uninstall();

	$options = array(
		'simply-static',
		'simply_static_zip_files',
		'simply-static-404-only',
		'simply-static-use-single',
		'simply-static-use-build',
		'simply-static-use-language',
		'wp_archive_creation_job_status',
		'simply_static_fetch_urls_total_pages',
		'simply_static_transfer_files_locally_total_pages',
		'simply_static_github_deploy_total_pages',
		'simply_static_aws_deploy_total_pages',
		'simply_static_sftp_deploy_total_pages',
		'simply_static_sftp_bulk_deploy_total_pages',
	);

	foreach ( $options as $option_name ) {
		delete_option( $option_name );
	}

	ss_delete_runtime_rows_uninstall();

	// These values live in network storage but are scoped to one site ID.
	if ( is_multisite() ) {
		ss_delete_network_transient_uninstall( $network_id, 'wp_archive_creation_job_process_lock_site_' . $site_id );
		ss_delete_network_transient_uninstall( $network_id, 'wp_archive_creation_job_loopback_available_site_' . $site_id );
		ss_delete_network_option_uninstall( $network_id, 'simply-static-' . $site_id );
	}

	Simply_Static\Page::drop_table();
	Simply_Static\Deploy_Manifest_Url::drop_table();
	Simply_Static\Deploy_Manifest::drop_table();
}

/**
 * Remove values shared by all Simply Static sites in a network.
 *
 * @param int $network_id Network ID, or zero to use the current network.
 *
 * @return void
 */
function ss_cleanup_network_state_uninstall( $network_id = 0 ) {
	ss_delete_network_transient_uninstall( $network_id, 'wp_archive_creation_job_process_lock' );
	ss_delete_network_transient_uninstall( $network_id, 'wp_archive_creation_job_loopback_available' );
	ss_delete_network_option_uninstall( $network_id, 'simply-static_multisite_export_running' );
	ss_delete_network_option_uninstall( $network_id, 'simply-static_blog_exported' );
}

/**
 * Run an uninstall callback in a site's context and always restore the caller.
 *
 * Errors are contained per site so one damaged site cannot prevent the rest of
 * a network from being cleaned.
 *
 * @param int      $site_id  Site ID.
 * @param callable $callback Cleanup callback.
 *
 * @return bool Whether the callback completed successfully.
 */
function ss_run_site_uninstall( $site_id, $callback ) {
	$site_id = absint( $site_id );
	if ( ! $site_id || ! is_callable( $callback ) ) {
		return false;
	}

	$switched = is_multisite() && get_current_blog_id() !== $site_id;
	if ( $switched && ! switch_to_blog( $site_id ) ) {
		return false;
	}

	try {
		call_user_func( $callback, $site_id );
		return true;
	} catch ( \Throwable $exception ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Simply Static uninstall failed for site %d: %s', $site_id, $exception->getMessage() ) );
		}

		return false;
	} finally {
		if ( $switched ) {
			restore_current_blog();
		}
	}
}

// Load model definitions once; table_name() resolves the active site's prefix.
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-model.php';
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-page.php';
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-deploy-manifest.php';
require_once plugin_dir_path( __FILE__ ) . 'src/models/class-ss-deploy-manifest-url.php';

$original_site_id = get_current_blog_id();
$network_ids      = array();

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	$offset     = 0;
	$batch_size = 100;

	do {
		$sites = get_sites(
			array(
				'fields'  => 'ids',
				'number'  => $batch_size,
				'offset'  => $offset,
				'orderby' => 'id',
				'order'   => 'ASC',
			)
		);

		foreach ( $sites as $site ) {
			$site_id = is_object( $site ) && isset( $site->blog_id ) ? absint( $site->blog_id ) : absint( $site );
			if ( ! $site_id ) {
				continue;
			}

			$network_id = ss_get_site_network_id_uninstall( $site_id );
			$network_ids[ $network_id ] = true;

			ss_run_site_uninstall( $site_id, 'ss_cleanup_site_uninstall' );
		}

		$count  = count( $sites );
		$offset += $count;
	} while ( $count === $batch_size );

	// Clear each network's shared locks and export markers after its sites.
	foreach ( array_keys( $network_ids ) as $network_id ) {
		ss_cleanup_network_state_uninstall( $network_id );
	}
} else {
	ss_run_site_uninstall( $original_site_id, 'ss_cleanup_site_uninstall' );
	ss_cleanup_network_state_uninstall();
}
