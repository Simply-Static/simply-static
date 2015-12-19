<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static export log
 *
 * @package Simply_Static
 */
class Simply_Static_Export_Log {

	/**
	 * Array of URLs containing arrays of http/error codes and source URLs
	 *
	 * @var Simply_Static_View
	 */
	protected $log = array();

	function includes( $url ) {
		return array_key_exists( $url, $this->log );
	}

    function set_response_code( $url, $response_code ) {
		$this->set_field_value( $url, 'code', $response_code );
	}

	function set_source_url( $url, $source_url ) {
		$this->set_field_value( $url, 'source_url', $source_url );
	}

	function set_field_value( $url, $field, $value ) {
		// setup an array if one doesn't exist for the url
		if ( ! isset( $this->log[ $url ] ) ) {
			$this->log[ $url ] = array( 'source_url' => '', 'code' => '' );
		}
		// only set a field once
		if ( $this->log[ $url ][ $field ] === '' ) {
			$this->log[ $url ][ $field ] = $value;
		}
	}

	function sort() {
		// sort log by http/error code then url
		foreach ( $this->log as $key => $row ) {
			$code[ $key ] = $row['code'];
		}
		array_multisort( $code, SORT_ASC, $this->log );

		return $this;
	}

	function get_log() {
		return $this->log;
	}
}
