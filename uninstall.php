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

require plugin_dir_path( __FILE__ ) . 'includes/class-ss-plugin.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-ss-model-model.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-ss-page.php';

Simply_Static\Page::drop_table();
