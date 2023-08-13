<?php

namespace Simply_Static;

/**
 * Class to handle CDN updates.
 */
class Simply_CDN_Handler {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Contains data array for the site.
	 *
	 * @var object|bool
	 */
	public $data;

	/**
	 * Returns instance of Simply_CDN_Handler.
	 *
	 * @return object|null
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Simply_CDN_Handler.
	 *
	 * @return void
	 */
	public function __construct() {
		$options    = get_option( 'simply-static' );
		$this->data = Simply_CDN_Api::get_data( $options['ssh_security_token'] );
	}

	/**
	 * Upload files to CDN.
	 *
	 * @param string $access_key given access key for verification.
	 * @param string $pull_zone given pullzone name.
	 * @param string $to_path path to upload.
	 * @param string $file_path path in local filesystem.
	 *
	 * @return void
	 */
	public function upload_file( string $access_key, string $pull_zone, string $to_path, string $file_path ) {
		// Prepare WP Filesystem.
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		if ( is_null( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$content = $wp_filesystem->get_contents( $file_path );

		$response = wp_remote_request( 'https://storage.bunnycdn.com/' . $pull_zone . '/' . $to_path, array(
			'method'  => 'PUT',
			'headers' => array(
				'AccessKey' => $access_key,
			),
			'body'    => $content,
		) );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				Util::debug_log( 'Sucessfully uploaded ' . $file_path );
			} else {
				$error_message = wp_remote_retrieve_response_message( $response );
				Util::debug_log( $error_message );
			}
		} else {
			$error_message = $response->get_error_message();
			Util::debug_log( $error_message );
		}
	}
}
