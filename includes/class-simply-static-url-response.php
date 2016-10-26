<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static URL response class
 * @package Simply_Static
 */

class Simply_Static_Url_Response {

	/**
	 * The URI resource
	 * @var string
	 */
	public $url = null;

	/**
	 * The headers from the response
	 * @var string
	 */
	public $headers = array();

	/**
	 * The file where the body content is stored
	 * @var string
	 */
	public $filename = '';

	/**
	 * The code from the response, e.g. 200
	 * @var integer
	 */
	public $code = null;

	/**
	 * The message from the response, e.g. 'OK'
	 * @var string
	 */
	public $message = '';

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
	public function __construct( $url, $response ) {
		$this->url = $url;
		$this->headers = $response['headers'];
		$this->filename = $response['filename'];
		$this->code = $response['response']['code'];
		$this->message = $response['response']['message'];
	}
	//
	// /**
	//  * Returns the content type
	//  * @return string
	//  */
	// public function get_content_type() {
	// 	return isset( $this->headers['content-type'] ) ? $this->headers['content-type'] : null;
	// }
	//
	// /**
	//  * Checks if content type is html
	//  * @return bool
	//  */
	// public function is_html() {
	// 	return stripos( $this->get_content_type(), 'html' ) !== false;
	// }
	//
	// /**
	//  * Checks if content type is html
	//  * @return bool
	//  */
	// public function is_css() {
	// 	return stripos( $this->get_content_type(), 'css' ) !== false;
	// }
	//
	// /**
	//  * Checks if content type is xml
	//  * @return bool
	//  */
	// public function is_xml() {
	// 	return stripos( $this->get_content_type(), 'xml' ) !== false;
	// }

	/**
	 * Checks for a location header (redirection URL)
	 *
	 * Returns an empty string if it doesn't find one.
	 *
	 * @return string
	 */
	public function get_redirect_url() {
		return isset( $this->headers['location'] ) ? $this->headers['location'] : '';
	}

	// public function get_body() {
	// 	return file_get_contents( $this->filename );
	// }
	//
	// public function save_body( $content ) {
	// 	return file_put_contents( $this->filename, $content );
	// }

}
