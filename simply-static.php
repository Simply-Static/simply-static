<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        https://simplystatic.com
 * Description:       A static site generator to create fast and secure static versions of your WordPress website.
 * Version:           3.5.2.2
 * Author:            Patrick Posner
 * Author URI:        https://patrickposner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

define( 'SIMPLY_STATIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLY_STATIC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SIMPLY_STATIC_VERSION', '3.5.2.2' );

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

 // Enforce Pro compatibility for Simply Static >= 3.5.2.
 // When Simply Static is >= 3.5.2, we require Simply Static Pro >= 2.0.1.
 add_action( 'plugins_loaded', function () {
     if ( ! defined( 'SIMPLY_STATIC_VERSION' ) || version_compare( SIMPLY_STATIC_VERSION, '3.5.2', '<' ) ) {
         return;
     }

     // Bail if Pro isn't active at all.
     if ( ! function_exists( 'deactivate_plugins' ) || ! function_exists( 'is_plugin_active' ) || ! function_exists( 'is_plugin_active_for_network' ) ) {
         require_once ABSPATH . 'wp-admin/includes/plugin.php';
     }

     $pro_basename  = 'simply-static-pro/simply-static-pro.php';
     $pro_active    = function_exists( 'is_plugin_active' ) && is_plugin_active( $pro_basename );
     $pro_netactive = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $pro_basename );

     if ( ! $pro_active && ! $pro_netactive ) {
         return; // Pro not active; nothing to enforce.
     }

     // If Pro is active but version is missing or too low, deactivate and notify.
     if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) || version_compare( SIMPLY_STATIC_PRO_VERSION, '2.0.1', '<' ) ) {
         $network_wide = $pro_netactive;
         deactivate_plugins( $pro_basename, false, $network_wide );

         // Prevent Pro boot during current request if it already hooked into plugins_loaded.
         if ( function_exists( 'ssp_run_plugin' ) ) {
             remove_action( 'plugins_loaded', 'ssp_run_plugin' );
         }

         // Site admin notice.
         add_action(
             'admin_notices',
             function () {
                 $message = sprintf(
                     /* translators: 1: required Simply Static Pro version */
                     esc_html__( 'Simply Static Pro has been deactivated because it is not compatible with this version of Simply Static. Please update Simply Static Pro to at least version %1$s and then reactivate it.', 'simply-static' ),
                     '2.0.1'
                 );
                 echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
             }
         );

         // Network admin notice (multisite).
         add_action(
             'network_admin_notices',
             function () {
                 $message = sprintf(
                     /* translators: 1: required Simply Static Pro version */
                     esc_html__( 'Simply Static Pro has been deactivated network-wide because it is not compatible with this version of Simply Static. Please update Simply Static Pro to at least version %1$s and then reactivate it.', 'simply-static' ),
                     '2.0.1'
                 );
                 echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
             }
         );
     }
 }, 1 );
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
