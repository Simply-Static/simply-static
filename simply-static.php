<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        https://simplystatic.com
 * Description:       A static site generator to create fast and secure static versions of your WordPress website.
 * Version:           3.8.2
 * Author:            Patrick Posner
 * Author URI:        https://patrickposner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

define( 'SIMPLY_STATIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLY_STATIC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SIMPLY_STATIC_VERSION', '3.8.2' );

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

// Block incompatible Pro integrations before either plugin boots.
require_once SIMPLY_STATIC_PATH . 'src/class-ss-pro-compatibility.php';
add_action( 'plugins_loaded', array( 'Simply_Static\Pro_Compatibility', 'enforce' ), 1 );

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
}

register_deactivation_hook( __FILE__, 'simply_static_plugin_deactivate' );

/**
 * Stop scheduled work on deactivation without deleting user data.
 *
 * WordPress deactivation is reversible. Generated files, logs, and page
 * records are removed only by the explicit uninstall flow.
 */
function simply_static_plugin_deactivate() {
	wp_clear_scheduled_hook( 'simply_static_site_export_cron' );
	wp_clear_scheduled_hook( 'wp_archive_creation_job_cron' );
	do_action( 'simply_static_deactivated' );
}
