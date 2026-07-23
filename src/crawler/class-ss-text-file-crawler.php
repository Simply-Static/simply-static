<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static public text file crawler.
 *
 * Discovers conventional text endpoints which are normally not linked from an
 * HTML page and may be generated dynamically rather than stored on disk.
 */
class Text_File_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 *
	 * @var string
	 */
	protected $id = 'text_file';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Public Text Files', 'simply-static' );
		$this->description = __( 'Detects conventional public text files such as llms.txt.', 'simply-static' );
	}

	/**
	 * Detect public text file URLs.
	 *
	 * @return array List of live, local text file URLs.
	 */
	public function detect() : array {
		if ( ! (bool) apply_filters( 'ss_include_llms_txt_in_export', true ) ) {
			return array();
		}

		$llms_url = apply_filters( 'simply_static_llms_txt_url', home_url( '/llms.txt' ) );
		if ( ! is_string( $llms_url ) || '' === trim( $llms_url ) || ! \Simply_Static\Util::is_local_origin_url( $llms_url ) ) {
			return array();
		}

		return $this->is_live_text_endpoint( $llms_url ) ? array( $llms_url ) : array();
	}

	/**
	 * Assign URL replacement support to discovered plain-text files.
	 *
	 * @param \Simply_Static\Page $static_page Page record being queued.
	 * @param string               $url         Detected URL.
	 *
	 * @return void
	 */
	protected function configure_static_page( $static_page, $url ) {
		$static_page->handler = \Simply_Static\Text_File_Handler::class;
	}

	/**
	 * Probe a local text endpoint without downloading an unbounded response.
	 *
	 * @param string $url Endpoint URL.
	 *
	 * @return bool
	 */
	private function is_live_text_endpoint( $url ) : bool {
		$timeout = (float) apply_filters( 'simply_static_text_file_request_timeout', 5.0 );
		$timeout = max( 0.1, min( 30.0, $timeout ) );

		$max_bytes = (int) apply_filters( 'simply_static_text_file_probe_size', 4096 );
		$max_bytes = max( 1, min( 1024 * 1024, $max_bytes ) );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => $timeout,
				'redirection'         => 0,
				'sslverify'           => (bool) apply_filters( 'ss_remote_get_sslverify', \Simply_Static\Util::should_verify_ssl( $url ), $url ),
				'limit_response_size' => $max_bytes,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! is_string( $body ) || '' === trim( $body ) ) {
			return false;
		}

		// Avoid queueing a soft-404 HTML document as llms.txt.
		$sample = ltrim( \Simply_Static\Util::strip_bom( $body ) );
		$valid  = 1 !== preg_match( '/^(?:<!doctype\s+html\b|<html\b)/i', $sample );

		return (bool) apply_filters( 'simply_static_text_file_probe_is_valid', $valid, $response, $url );
	}
}
