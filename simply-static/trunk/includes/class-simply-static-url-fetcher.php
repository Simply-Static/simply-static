<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static URL fetcher class
 */
class Simply_Static_Url_Fetcher {
	/**
	 * Timeout for fetching URLs
	 * @var string
	 */
	const TIMEOUT = 300;

	/**
	 * The URI resource
	 * @var string
	 */
	protected $url;

	/**
	 * The raw response from the HTTP request
	 * @var string
	 */
	protected $response;

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
	public function __construct( $url ) {
		$this->url = filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Fetch the URL and set the response. Return the WP_Error if we get one.
	 * @return boolean|WP_Error
	 */
	public function fetch() {
		$response = wp_remote_get( $this->url, array( 'timeout' => self::TIMEOUT ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$this->response = $response;
			$this->cleanup();
			return true;
		}
	}

	/**
	 * Returns the sanitized URL
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Allows to override the HTTP response body
	 * @param string $new_body
	 * @return void
	 */
	protected function set_response_body( $new_body ) {
		if ( is_array( $this->response ) ) {
			$this->response['body'] = $new_body;
		}
	}

	/**
	 * Returns the HTTP response body
	 * @return string
	 */
	public function get_response_body() {
		return isset( $this->response['body'] ) ? $this->response['body'] : '';
	}

	/**
	 * Returns the HTTP response code
	 * @return string
	 */
	public function get_response_code() {
		return isset( $this->response['response']['code'] ) ? $this->response['response']['code'] : null;
	}

	/**
	 * Returns the content type
	 * @return string
	 */
	public function get_content_type() {
		return isset( $this->response['headers']['content-type'] ) ? $this->response['headers']['content-type'] : null;
	}

	/**
	 * Checks if content type is html
	 * @return bool
	 */
	public function is_html() {
		return stripos( $this->get_content_type(), 'html' ) !== false;
	}

	/**
	 * Removes WordPress-specific meta tags
	 * @return void
	 */
	protected function cleanup() {
		if ( $this->is_html() ) {
			$response_body = preg_replace( '/<link rel=["\' ](pingback|alternate|EditURI|wlwmanifest|index|profile|prev)["\' ](.*?)>/si', '', $this->get_response_body() );
			$response_body = preg_replace( '/<meta name=["\' ]generator["\' ](.*?)>/si', '', $response_body );
			$this->set_response_body( $response_body );
		}
	}

	/**
	 * Extracts the list of unique URLs
	 * @param string $origin_url Base URL of site. Used to extract URLs that relate only to the current site.
	 * @return array
	 */
	public function extract_all_urls( $origin_url ) {
		$all_urls = array();

		if ($this->is_html() && preg_match_all( '/' . str_replace( '/', '\/', $origin_url ) . '[^"\'#\? ]+/i', $this->response['body'], $matches ) ) {
			$all_urls = array_unique( $matches[0] );
		}

		return $all_urls;
	}

	/**
	 * Replaces base URL
	 * @param string $origin_url
	 * @param string $destination_url
	 * @return void
	 */
	public function replace_url($origin_url, $destination_url) {
		if ( $this->is_html() ) {
			$response_body = str_replace( $origin_url, $destination_url, $this->get_response_body() );
			$response_body = str_replace( '<head>', "<head>\n<base href=\"" . esc_attr( $destination_url ) . "\" />\n", $response_body );
			$this->set_response_body( $response_body );
		}
	}
}
