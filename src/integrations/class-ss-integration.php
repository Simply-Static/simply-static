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

		// Backwards compatibility applies only when the option has never been
		// stored. An explicitly empty array means the user disabled everything.
		if ( null === $integrations && $this->active_by_default ) {
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
	 * On environments that require HTTP Basic Auth (e.g. Static Studio),
	 * plain wp_remote_get calls will fail with 401. This helper mirrors the
	 * authentication logic used by Url_Fetcher::remote_get().
	 *
	 * @param string $url  URL to fetch.
	 * @param array  $args Optional. Additional arguments for wp_remote_get.
	 *
	 * @return array|\WP_Error Response or WP_Error on failure.
	 */
	protected function auth_remote_get( $url, $args = [] ) {
		$allowed = Util::is_local_origin_url( $url );
		$allowed = (bool) apply_filters( 'ss_integration_allow_remote_get', $allowed, $url, $this->id );
		if ( ! $allowed ) {
			return new \WP_Error( 'ss_disallowed_remote_url', __( 'Integration requests must target the configured WordPress origin.', 'simply-static' ) );
		}

		// Verify TLS by default. Self-signed local environments can opt out for
		// their exact origin with the narrowly scoped filter.
		if ( ! isset( $args['sslverify'] ) ) {
			$args['sslverify'] = (bool) apply_filters( 'ss_remote_get_sslverify', true, $url );
		}
		// Redirects can cross origins while retaining request headers in some
		// transports. Callers can explicitly opt in when no credentials are used.
		if ( ! isset( $args['redirection'] ) ) {
			$args['redirection'] = 0;
		}

		$authorization = Util::get_basic_auth_header_for_url( $url );
		if ( null !== $authorization ) {
			$args['headers'] = isset( $args['headers'] ) ? $args['headers'] : [];
			$args['headers']['Authorization'] = $authorization;
		}

		return wp_remote_get( $url, apply_filters( 'ss_remote_get_args', $args ) );
	}

	/**
	 * Extract safe child sitemap URLs from an HTTP response.
	 *
	 * SEO plugins expose similar sitemap indexes. Keeping parsing here ensures
	 * they share body/URL bounds, network-disabled XML parsing, and exact-origin
	 * checks before a child URL reaches the export queue.
	 *
	 * @param array|\WP_Error $response HTTP response.
	 * @return string[]
	 */
	protected function extract_sitemap_index_urls( $response ) {
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body      = wp_remote_retrieve_body( $response );
		$max_bytes = max( 1024, (int) apply_filters( 'ss_integration_sitemap_max_bytes', 5 * 1024 * 1024 ) );
		if ( ! is_string( $body ) || '' === $body || strlen( $body ) > $max_bytes ) {
			return array();
		}

		if ( preg_match( '/<!\s*(?:DOCTYPE|ENTITY)\b/i', $body ) || ! function_exists( 'simplexml_load_string' ) ) {
			return array();
		}

		$previous_errors = libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body, 'SimpleXMLElement', defined( 'LIBXML_NONET' ) ? LIBXML_NONET : 0 );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( false === $xml ) {
			return array();
		}

		$nodes = $xml->xpath( '//*[local-name()="sitemap"]/*[local-name()="loc"]' );
		if ( ! is_array( $nodes ) ) {
			return array();
		}

		$limit = max( 1, (int) apply_filters( 'ss_integration_sitemap_max_urls', 1000 ) );
		$urls  = array();
		foreach ( $nodes as $node ) {
			$url = trim( (string) $node );
			if ( '' === $url || ! Util::is_local_origin_url( $url ) ) {
				continue;
			}
			$urls[ $url ] = true;
			if ( count( $urls ) >= $limit ) {
				break;
			}
		}

		return array_keys( $urls );
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
