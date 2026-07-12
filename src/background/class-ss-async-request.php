<?php

namespace Simply_Static;

/**
 * WP Async Request
 *
 * Copied from: https://github.com/deliciousbrains/wp-background-processing/tree/master
 * Copied version: 1.4.0
 *
 * @package WP-Background-Processing
 */

/**
 * Abstract WP_Async_Request class.
 *
 * @abstract
 */
abstract class Async_Request {

	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'wp';

	/**
	 * Action
	 *
	 * (default value: 'async_request')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'async_request';

	/**
	 * Identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifier;

	/**
	 * Data
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $data = array();

	/**
	 * Initiate new async request.
	 */
	public function __construct() {
		$this->identifier = $this->prefix . '_' . $this->action;

		add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
		add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
	}

	/**
	 * Set data used during the request.
	 *
	 * @param array $data Data.
	 *
	 * @return $this
	 */
	public function data( $data ) {
		$this->data = $data;

		return $this;
	}

	/**
	 * Dispatch the async request.
	 *
	 * @return array|WP_Error|false HTTP Response array, WP_Error on failure, or false if not attempted.
	 */
	public function dispatch() {
		$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
		$args = $this->get_post_args();

		// Async credentials must never be forwarded to a filtered cross-origin
		// endpoint. Refuse the dispatch rather than relying on redirect behavior.
		if ( ! $this->is_dispatch_url_allowed( $url ) ) {
			return new \WP_Error( 'invalid_async_origin', __( 'The background request URL must use the WordPress admin origin.', 'simply-static' ) );
		}

		if ( class_exists( __NAMESPACE__ . '\\Util' ) ) {
			$authorization = Util::get_basic_auth_header_for_url( $url );
			if ( $authorization ) {
				$args['headers']                  = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
				$args['headers']['Authorization'] = $authorization;
			}
		}

		return wp_remote_post( esc_url_raw( $url ), $args );
	}

	/**
	 * Get query args.
	 *
	 * @return array
	 */
	protected function get_query_args() {
		if ( property_exists( $this, 'query_args' ) ) {
			return $this->query_args;
		}

		$args = array(
			'action' => $this->identifier,
		);

		/**
		 * Filters the query arguments used during an async request.
		 *
		 * @param array $args
		 */
		return apply_filters( $this->identifier . '_query_args', $args );
	}

	/**
	 * Get query URL.
	 *
	 * @return string
	 */
	protected function get_query_url() {
		if ( property_exists( $this, 'query_url' ) ) {
			return $this->query_url;
		}

		$url = admin_url( 'admin-ajax.php' );

		/**
		 * Filters the query URL used during an async request.
		 *
		 * @param string $url
		 */
		return apply_filters( $this->identifier . '_query_url', $url );
	}

	/**
	 * Get post args.
	 *
	 * @return array
	 */
	protected function get_post_args() {
		if ( property_exists( $this, 'post_args' ) ) {
			$args = is_array( $this->post_args ) ? $this->post_args : array();
		} else {
			$args = array(
				'timeout'     => 5,
				'blocking'    => false,
				'redirection' => 0,
				'body'        => is_array( $this->data ) ? $this->data : array(),
				'cookies'     => $this->get_auth_cookies(),
				'sslverify'   => (bool) apply_filters( 'https_local_ssl_verify', true ),
			);
		}

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param array $args
		 */
		$args = apply_filters( $this->identifier . '_post_args', $args );
		$args = is_array( $args ) ? $args : array();

		// The nonce is a transport invariant, not optional integration data. Add
		// it after custom properties and filters so supported overrides cannot
		// accidentally turn every nonblocking request into an AJAX 403.
		$body = isset( $args['body'] ) && is_array( $args['body'] ) ? $args['body'] : array();
		$body['nonce']      = wp_create_nonce( $this->identifier );
		$args['body']       = $body;
		$args['redirection'] = 0;

		return $args;
	}

	/**
	 * Return only WordPress authentication cookies required to validate a nonce.
	 *
	 * @return array
	 */
	protected function get_auth_cookies() {
		$cookies     = array();
		$cookie_names = array( 'CF_Authorization', 'cf_clearance', 'PHPSESSID' );
		foreach ( array( 'LOGGED_IN_COOKIE', 'AUTH_COOKIE', 'SECURE_AUTH_COOKIE' ) as $cookie_constant ) {
			if ( ! defined( $cookie_constant ) ) {
				continue;
			}

			$cookie_names[] = constant( $cookie_constant );
		}

		/**
		 * Filter the exact cookie names required by a same-origin access proxy.
		 *
		 * @param string[] $cookie_names Default WordPress and common SSO cookies.
		 * @param string[] $available    Cookie names available on this request.
		 */
		$cookie_names = apply_filters( $this->identifier . '_forwarded_cookie_names', $cookie_names, array_keys( $_COOKIE ) );
		$cookie_names = is_array( $cookie_names ) ? array_unique( array_filter( array_map( 'strval', $cookie_names ) ) ) : array();
		foreach ( $cookie_names as $cookie_name ) {
			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				$cookies[ $cookie_name ] = $_COOKIE[ $cookie_name ];
			}
		}

		return $cookies;
	}

	/**
	 * Validate the filtered loopback target before forwarding bearer context.
	 *
	 * Internal aliases can be explicitly allowed with the action-specific
	 * `_dispatch_url_allowed` filter while exact public origin remains the safe
	 * default.
	 *
	 * @param string $url Candidate dispatch URL.
	 * @return bool
	 */
	protected function is_dispatch_url_allowed( $url ) {
		$admin_url = admin_url( 'admin-ajax.php' );
		$allowed   = ! class_exists( __NAMESPACE__ . '\\Util' ) || Util::is_same_origin_url( $url, $admin_url );

		return (bool) apply_filters( $this->identifier . '_dispatch_url_allowed', $allowed, $url, $admin_url );
	}

	/**
	 * Maybe handle a dispatched request.
	 *
	 * Check for correct nonce and pass to handler.
	 *
	 * @return void|mixed
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing.
		if ( function_exists( 'session_write_close' ) ) {
			session_write_close();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		// A blocking capability probe validates this exact action, authentication,
		// nonce, and WAF route without consuming queued work.
		if ( ! empty( $_REQUEST['simply_static_probe'] ) ) {
			return $this->maybe_wp_die();
		}

		$this->handle();

		return $this->maybe_wp_die();
	}

	/**
	 * Should the process exit with wp_die?
	 *
	 * @param mixed $return What to return if filter says don't die, default is null.
	 *
	 * @return void|mixed
	 */
	protected function maybe_wp_die( $return = null ) {
		/**
		 * Should wp_die be used?
		 *
		 * @return bool
		 */
		if ( apply_filters( $this->identifier . '_wp_die', true ) ) {
			wp_die();
		}

		return $return;
	}

	/**
	 * Handle a dispatched request.
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	abstract protected function handle();
}
