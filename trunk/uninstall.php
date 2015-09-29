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
