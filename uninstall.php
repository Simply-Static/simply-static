<?php
/**
 * Uninstall Simply Static
 *
 * @package Simply_Static
 */

// exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Delete Simply Static's settings
delete_option( 'simply-static' );

require plugin_dir_path( __FILE__ ) . 'includes/class-simply-static.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-simply-static-model.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-simply-static-page.php';

Simply_Static_Page::drop_table();
