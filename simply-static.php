<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        https://patrickposner.dev
 * Description:       A static site generator to create fast and secure static versions of your WordPress website.
 * Version:           3.0.2
 * Author:            Patrick Posner
 * Author URI:        https://patrickposner.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

define( 'SIMPLY_STATIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLY_STATIC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SIMPLY_STATIC_VERSION', '3.0.2' );

// Check PHP version.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( esc_html__( 'Simply Static requires PHP 7.4 or higher.', 'simply-static' ), 'Plugin dependency check', array( 'back_link' => true ) );
}

// Localize.
$textdomain_dir = plugin_basename( dirname( __FILE__ ) ) . '/languages';
load_plugin_textdomain( 'simply-static', false, $textdomain_dir );

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
	}

	// Update required?
	if ( defined( 'SIMPLY_STATIC_PRO_VERSION' ) && version_compare( SIMPLY_STATIC_PRO_VERSION, '1.4', '<' ) ) {
		// Site notice.
		add_action(
			'admin_notices',
			function () {
				$message = esc_html__( 'You need to update Simply Static Pro to version 1.4 before continuing to use Simply Static, as we made significant changes requiring an upgrade.', 'simply-static' );
				echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
			}
		);

		// Network notice.
		if ( function_exists( 'is_network_admin' ) ) {
			if ( is_network_admin() ) {
				add_action(
					'network_admin_notices',
					function () {
						$message = esc_html__( 'You need to update Simply Static Pro to version 1.4 before continuing to use Simply Static, as we made significant changes requiring an upgrade.', 'simply-static' );
						echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
					}
				);
			}
		}
	}
}
