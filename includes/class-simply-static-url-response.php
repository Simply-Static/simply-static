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
	 * The body content of the response
	 * @var string
	 */
	public $body = '';

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
		$this->body = $response['body'];
		$this->code = $response['response']['code'];
		$this->message = $response['response']['message'];
	}

	/**
	 * Returns the content type
	 * @return string
	 */
	public function get_content_type() {
		return isset( $this->headers['content-type'] ) ? $this->headers['content-type'] : null;
	}

	/**
	 * Checks if content type is html
	 * @return bool
	 */
	public function is_html() {
		return stripos( $this->get_content_type(), 'html' ) !== false;
	}

	/**
	 * Checks if content type is html
	 * @return bool
	 */
	public function is_css() {
		return stripos( $this->get_content_type(), 'css' ) !== false;
	}

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
	 * @return array
	 */
	public function extract_urls() {
		$extractor = new Simply_Static_Url_Extractor( $this );
		return $extractor->extract_urls();
	}

	/**
	 * Replaces origin URL with desintation URL in response body
	 * @param string $destination_scheme The protocol for the destination URL
	 * @param string $destination_host   The host for the destination URL
	 * @return void
	 */
	public function replace_urls( $destination_scheme, $destination_host ) {
		/* TODO: Might want to eventually rope this into extract_urls_from_html/
		 	extract_urls_from_css so that we're only doing preg_replace/
			str_replace once. Only reason I'm not doing that now is because of
			the fix for wp_json_encode.
		*/
		if ( $this->is_html() || $this->is_css() ) {
			$destination_url = $destination_scheme . '://' . $destination_host;

			// replace any instance of the origin url, whether it starts with https://, http://, or //
			$response_body = preg_replace( '/(https?:)?\/\/' . addcslashes( sist_origin_host(), '/' ) . '/i', $destination_url, $this->body );
			// also replace wp_json_encode'd urls, as used by WP's `concatemoji`
			$response_body = str_replace( addcslashes( sist_origin_url(), '/' ), addcslashes( $destination_url, '/' ), $response_body );
			$this->body = $response_body;
		}
	}
}
