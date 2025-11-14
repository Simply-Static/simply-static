<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        https://simplystatic.com
 * Description:       A static site generator to create fast and secure static versions of your WordPress website.
 * Version:           3.5.2
 * Author:            Patrick Posner
 * Author URI:        https://patrickposner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

define( 'SIMPLY_STATIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLY_STATIC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SIMPLY_STATIC_VERSION', '3.5.2' );

// Check PHP version.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( esc_html__( 'Simply Static requires PHP 7.4 or higher.', 'simply-static' ), 'Plugin dependency check', array( 'back_link' => true ) );
}

// Check WordPress version.
if ( version_compare( get_bloginfo( 'version' ), '6.2', '<' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( esc_html__( 'Simply Static requires WordPress 6.2 or higher.', 'simply-static' ), 'Plugin dependency check', array( 'back_link' => true ) );
}

// Run autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) && ! class_exists( 'Simply_Static\Plugin' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Boot Simply Static.
if ( ! function_exists( 'simply_static_run_plugin' ) ) {
	add_action( 'plugins_loaded', 'simply_static_run_plugin' );

	/**
	 * Run plugin
	 *
	 * @return void
	 */
	function simply_static_run_plugin() {
		require_once SIMPLY_STATIC_PATH . 'src/class-ss-plugin.php';

		Simply_Static\Plugin::instance();

		$options = get_option( 'simply-static' );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		// Server-side cron?
		if ( isset( $options['server_cron'] ) && true === $options['server_cron'] ) {
			define( 'SS_CRON', true );
		}

		// Generate a secure unique key.
		if ( ! isset( $options['encryption_key'] ) ) {
			$options['encryption_key'] = bin2hex( random_bytes( 16 ) );
			update_option( 'simply-static', $options );
		}
	}

	// Update required?
	if ( defined( 'SIMPLY_STATIC_PRO_VERSION' ) && version_compare( SIMPLY_STATIC_PRO_VERSION, '1.6.3.2', '<' ) ) {
		// Site notice.
		add_action(
			'admin_notices',
			function () {
				$message = esc_html__( 'You need to update Simply Static Pro to version 1.6.3.2 before continuing to use Simply Static, as we made significant changes requiring an upgrade.', 'simply-static' );
				echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
			}
		);

		// Network notice.
		if ( function_exists( 'is_network_admin' ) ) {
			if ( is_network_admin() ) {
				add_action(
					'network_admin_notices',
					function () {
						$message = esc_html__( 'You need to update Simply Static Pro to version  1.6.3.2 before continuing to use Simply Static, as we made significant changes requiring an upgrade.', 'simply-static' );
						echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
					}
				);
			}
		}
	}
}

register_deactivation_hook( __FILE__, 'simply_static_plugin_deactivate' );

/**
 * Clean up on deactivation.
 */
function simply_static_plugin_deactivate() {
	// Ensure Util, Query, Model and Page classes are available.
	if ( ! class_exists( 'Simply_Static\\Util' ) ) {
		require_once SIMPLY_STATIC_PATH . 'src/class-ss-util.php';
	}
	if ( ! class_exists( 'Simply_Static\\Query' ) && file_exists( SIMPLY_STATIC_PATH . 'src/class-ss-query.php' ) ) {
		require_once SIMPLY_STATIC_PATH . 'src/class-ss-query.php';
	}
	if ( ! class_exists( 'Simply_Static\\Model' ) && file_exists( SIMPLY_STATIC_PATH . 'src/models/class-ss-model.php' ) ) {
		require_once SIMPLY_STATIC_PATH . 'src/models/class-ss-model.php';
	}
	// Load Page model to access its table
	if ( ! class_exists( 'Simply_Static\\Page' ) && file_exists( SIMPLY_STATIC_PATH . 'src/models/class-ss-page.php' ) ) {
		require_once SIMPLY_STATIC_PATH . 'src/models/class-ss-page.php';
	}

	// Clear temp dir.
	$temp_dir = \Simply_Static\Util::get_temp_dir();
	\Simply_Static\Util::delete_dir_contents( $temp_dir );

	// Clear DB table.
	\Simply_Static\Page::query()->delete_all();

	// Remove debug log file.
	$debug_file = \Simply_Static\Util::get_debug_log_filename();

	if ( ( file_exists( $debug_file ) || is_link( $debug_file ) ) && is_writable( $debug_file ) ) {
		unlink( $debug_file );
	}
}
