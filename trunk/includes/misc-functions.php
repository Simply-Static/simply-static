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


/**
 * Echo the selected value for an option tag if the statement is true.
 * @return null
 */
function sist_selected_if( $statement ) {
	echo ( $statement == true ? 'selected="selected"' : '' );
}

/**
 * Truncate if a string exceeds a certain length (30 chars by default)
 */
function sist_truncate( $string, $length = 30, $omission = '...' ) {
	return ( strlen( $string ) > $length + 3 ) ? ( substr( $string, 0, $length ) . $omission ) : $string;
}


/**
 * Use trailingslashit unless the string is empty
 */
function sist_trailingslashit_unless_blank( $string ) {
	return $string === '' ? $string : trailingslashit( $string );
}

/**
 * Dump an object to error_log
 */
function sist_error_log( $object=null ){
    ob_start();
    var_dump( $object );
    $contents = ob_get_contents();
    ob_end_clean();
    error_log( $contents );
}
