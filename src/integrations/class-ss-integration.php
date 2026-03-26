<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Integration {

	/**
	 * Integration Name.
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var bool
	 */
	protected $always_active = false;

	/**
	 * Integration Description.
	 * @var string
	 */
	protected $description = '';

	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Active by default.
	 *
	 * @var bool
	 */
	protected $active_by_default = true;

	/**
	 * Hidden from UI.
	 *
	 * @var bool
	 */
 protected $hidden = false;

 /**
  * Whether enabling/disabling this integration requires a full admin UI reload
  * to reflect changes immediately (e.g., UI components appear/disappear).
  *
  * @var bool
  */
 protected $requires_ui_reload = false;

	/**
	 * Load the integration.
	 *
	 * @return void
	 */
	public function load() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->run();
	}


	/**
	 * Check if such Integration is enabled
	 *
	 * @return mixed|null
	 */
	public function is_enabled() {
		return apply_filters( 'simply_static_integration_' . $this->id . '_enabled', $this->can_run() );
	}

	/**
	 * Check if the integration is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$options      = Options::instance();
		$integrations = $options->get( 'integrations' );

		// Mainly for backwards compatibility. If there is no such option, it means it's all active.
		if ( empty( $integrations ) && $this->active_by_default ) {
			return true;
		}

		if ( ! is_array( $integrations ) ) {
			$integrations = [];
		}

		return in_array( $this->id, $integrations, true );
	}

	/**
	 * A Check if this integration can run.
	 * Example: Check if a plugin is activated in DB or a class exists.
	 *
	 * @return boolean
	 */
	public function can_run() {
		if ( $this->always_active ) {
			return true;
		}

		if ( ! $this->dependency_active() ) {
			return false;
		}

		return $this->is_active();
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return true;
	}

	/**
	 * Set if the integration is pro or not.
	 *
	 * @return boolean
	 */
	public function is_pro() {
		return false;
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
	}

	/**
	 * Perform a wp_remote_get request with Basic Auth headers when configured.
	 *
	 * On environments that require HTTP Basic Auth (e.g. Simply Static Studio),
	 * plain wp_remote_get calls will fail with 401. This helper mirrors the
	 * authentication logic used by Url_Fetcher::remote_get().
	 *
	 * @param string $url  URL to fetch.
	 * @param array  $args Optional. Additional arguments for wp_remote_get.
	 *
	 * @return array|\WP_Error Response or WP_Error on failure.
	 */
	protected function auth_remote_get( $url, $args = [] ) {
		$options  = Options::instance();
		$username = $options->get( 'http_basic_auth_username' );
		$password = $options->get( 'http_basic_auth_password' );

		// Disable SSL verification to match Url_Fetcher::remote_get() behaviour.
		// Studio and other self-hosted environments may use certificates that
		// cannot be verified by the server's CA bundle, causing silent failures.
		if ( ! isset( $args['sslverify'] ) ) {
			$args['sslverify'] = false;
		}

		if ( ! empty( $username ) && ! empty( $password ) ) {
			$args['headers'] = isset( $args['headers'] ) ? $args['headers'] : [];
			$args['headers']['Authorization'] = 'Basic ' . base64_encode( $username . ':' . $password );
		}

		return wp_remote_get( $url, apply_filters( 'ss_remote_get_args', $args ) );
	}

	/**
	 * Include File.
	 *
	 * @param string $path given path.
	 *
	 * @return void
	 */
	public function include_file( $path ) {
		require_once trailingslashit( SIMPLY_STATIC_PATH ) . 'src/' . $path;
	}

	/**
	 * Object used for JS part.
	 *
	 * @return array
	 */
    public function js_object() {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'active'        => $this->is_active(),
            'pro'           => $this->is_pro(),
            'can_run'       => $this->dependency_active(),
            'always_active' => $this->always_active,
            'hidden'        => $this->hidden,
            'requires_ui_reload' => (bool) $this->requires_ui_reload,
        ];
    }
}
