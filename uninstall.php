<?php
/**
 * Uninstall Simply Static
 */

// exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Delete Simply Static's settings
delete_option( 'simply-static' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-ss-plugin.php';

// Drop the Pages table
Simply_Static\Page::drop_table();
