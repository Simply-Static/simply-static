<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL response class
 *
 * @package Simply_Static
 */

class Simply_Static_Url_Response {

	/**
	 * The URI resource
	 *
	 * @var string
	 */
	public $url;

	/**
	 * The headers from the response
	 *
	 * @var string
	 */
	public $headers = array();

	/**
	 * The body content of the response
	 *
	 * @var string
	 */
	public $body = '';

	/**
	 * The code from the response, e.g. 200
	 *
	 * @var integer
	 */
	public $code = null;

	/**
	 * The message from the response, e.g. 'OK'
	 *
	 * @var string
	 */
	public $message = '';

	/**
	 * Constructor
	 *
	 * @param string $url URI resource
	 */
	public function __construct( $url, $response ) {
		$this->url = $url;
		$this->headers = $response['headers'];
		$this->body = $response['body'];
		$this->code = $response['response']['code'];
		$this->message = $response['response']['message'];
	}

	/**
	 * Returns the content type
	 *
	 * @return string
	 */
	public function get_content_type() {
		return isset( $this->headers['content-type'] ) ? $this->headers['content-type'] : null;
	}

	/**
	 * Checks if content type is html
	 *
	 * @return bool
	 */
	public function is_html() {
		return stripos( $this->get_content_type(), 'html' ) !== false;
	}

	/**
	 * Checks if content type is html
	 *
	 * @return bool
	 */
	public function is_css() {
		return stripos( $this->get_content_type(), 'css' ) !== false;
	}

	/**
	 * Checks for a location header (redirection URL). Returns an empty string if
	 * it doesn't find one.
	 *
	 * @return string
	 */
	public function get_redirect_url() {
		return array_key_exists( 'location', $this->headers ) ? $this->headers['location'] : '';
	}

	// /**
	//  * Removes WordPress-specific meta tags
	//  *
	//  * @return void
	//  */
	// protected function cleanup() {
	// 	if ( $this->is_html() ) {
	// 		$response_body = preg_replace( '/<link rel=["\' ](pingback|alternate|EditURI|wlwmanifest|index|profile|prev)["\' ](.*?)>/si', '', $this->get_response_body() );
	// 		$response_body = preg_replace( '/<meta name=["\' ]generator["\' ](.*?)>/si', '', $response_body );
	// 		$this->set_response_body( $response_body );
	// 	}
	// }

	/**
	 * Extracts the list of unique URLs
	 *
	 * @param string $origin_url Base URL of site. Used to extract URLs that relate only to the current site.
	 * @return array
	 */
	public function extract_urls( $origin_url ) {
		$extractor = new Simply_Static_Url_Extractor( $this );
		return $extractor->extract_urls();
	}

	/**
	 * Replaces base URL
	 *
	 * @param string $origin_url
	 * @param string $destination_url
	 * @return void
	 */
	public function replace_url( $origin_url, $destination_url ) {
		if ( $this->is_html() ) {
			$response_body = str_replace( $origin_url, $destination_url, $this->body );
			$this->body = $response_body;
		}
	}
}
