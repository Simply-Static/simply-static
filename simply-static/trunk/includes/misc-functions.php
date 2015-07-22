<?php

/**
 * Miscellaneous functions for use across the plugin
 *
 * @package Simply_Static
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Get the protocol used for the origin URL
* @return string http or https
*/
function sist_get_origin_scheme() {
	return is_ssl() ? 'https' : 'http';
}

/**
* Get the host for the origin URL
* @return string host (URL minus the protocol)
*/
function sist_get_origin_host() {
	return untrailingslashit( preg_replace( "(^https?://)", "", home_url() ) );
}
