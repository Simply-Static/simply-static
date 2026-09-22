<?php

namespace Simply_Static;

/**
 * Loads the centrally published compatibility snapshot with a local cache.
 *
 * The upstream is a plain JSON file so it remains available when the database
 * site is deployed statically. The persistent snapshot is the outage fallback;
 * an unavailable upstream never erases the last known good data.
 */
class Compatibility_API_Client {

	const DEFAULT_ENDPOINT = 'https://simplystatic.com/wp-content/uploads/sscd/plugins.json';
	const CACHE_KEY = 'simply_static_compatibility_api_v1';
	const SNAPSHOT_OPTION = 'simply_static_compatibility_api_snapshot_v1';
	const LAST_ERROR_OPTION = 'simply_static_compatibility_api_last_error';
	const LAST_SYNC_OPTION = 'simply_static_compatibility_api_last_sync';
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;
	const ERROR_CACHE_TTL = 15 * MINUTE_IN_SECONDS;
	const MAX_RESPONSE_BYTES = 5 * 1024 * 1024;

	/**
	 * Return compatible plugin records from cache, upstream, or stale snapshot.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_plugins() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = self::fetch();
		if ( is_wp_error( $result ) ) {
			update_site_option( self::LAST_ERROR_OPTION, $result->get_error_message() );
			do_action( 'simply_static_compatibility_api_error', $result );

			$snapshot = get_site_option( self::SNAPSHOT_OPTION, array() );
			$snapshot = is_array( $snapshot ) ? $snapshot : array();
			set_site_transient( self::CACHE_KEY, $snapshot, self::ERROR_CACHE_TTL );

			return $snapshot;
		}

		$ttl = max( HOUR_IN_SECONDS, (int) apply_filters( 'simply_static_compatibility_api_cache_ttl', self::CACHE_TTL ) );
		set_site_transient( self::CACHE_KEY, $result, $ttl );
		update_site_option( self::SNAPSHOT_OPTION, $result );
		update_site_option( self::LAST_SYNC_OPTION, time() );
		delete_site_option( self::LAST_ERROR_OPTION );

		return $result;
	}

	/** Clear only the expiring cache; the outage-safe snapshot is retained. */
	public static function clear_cache() {
		delete_site_transient( self::CACHE_KEY );
	}

	/** Return the configured static JSON URL. */
	public static function get_endpoint() {
		$endpoint = defined( 'SIMPLY_STATIC_COMPATIBILITY_API_URL' )
			? SIMPLY_STATIC_COMPATIBILITY_API_URL
			: self::DEFAULT_ENDPOINT;

		return esc_url_raw( apply_filters( 'simply_static_compatibility_api_url', $endpoint ) );
	}

	/**
	 * Fetch and validate a version 1 snapshot.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	private static function fetch() {
		$endpoint = self::get_endpoint();
		if ( ! $endpoint ) {
			return new \WP_Error( 'invalid_compatibility_api_url', 'The compatibility API URL is invalid.' );
		}

		$args = array(
			'timeout'             => 8,
			'redirection'         => 3,
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
			'headers'             => array( 'Accept' => 'application/json' ),
			'user-agent'          => 'Simply Static/' . SIMPLY_STATIC_VERSION,
		);
		$response = wp_safe_remote_get(
			$endpoint,
			apply_filters( 'simply_static_compatibility_api_request_args', $args, $endpoint )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error( 'compatibility_api_http_error', 'Compatibility API returned HTTP ' . $code . '.' );
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $payload ) || ! isset( $payload['plugins'] ) || ! is_array( $payload['plugins'] ) ) {
			return new \WP_Error( 'invalid_compatibility_api_payload', 'Compatibility API returned invalid JSON.' );
		}

		$schema_version = isset( $payload['schema_version'] ) ? (string) $payload['schema_version'] : '1.0';
		if ( '1' !== strtok( $schema_version, '.' ) ) {
			return new \WP_Error( 'unsupported_compatibility_api_schema', 'Unsupported compatibility API schema ' . $schema_version . '.' );
		}

		$plugins = array();
		$seen    = array();
		foreach ( $payload['plugins'] as $plugin ) {
			$plugin = self::normalise_plugin( $plugin );
			if ( ! $plugin || isset( $seen[ $plugin['slug'] ] ) ) {
				continue;
			}

			$seen[ $plugin['slug'] ] = true;
			$plugins[]                = $plugin;
		}

		return $plugins;
	}

	/**
	 * Validate and map one public API record to the existing compatibility shape.
	 *
	 * @param mixed $plugin Remote plugin record.
	 *
	 * @return array<string,mixed>|null
	 */
	private static function normalise_plugin( $plugin ) {
		if ( ! is_array( $plugin ) ) {
			return null;
		}

		$slug   = sanitize_title( isset( $plugin['slug'] ) ? $plugin['slug'] : '' );
		$status = sanitize_key( isset( $plugin['status'] ) ? $plugin['status'] : 'fully_compatible' );
		if ( ! $slug || ! in_array( $status, array( 'fully_compatible', 'compatible_with_config' ), true ) ) {
			return null;
		}
		if ( isset( $plugin['compatible'] ) && ! $plugin['compatible'] ) {
			return null;
		}

		$icon = esc_url_raw( isset( $plugin['icon'] ) ? $plugin['icon'] : '' );

		return array(
			'slug'                => $slug,
			'name'                => sanitize_text_field( isset( $plugin['name'] ) ? $plugin['name'] : $slug ),
			'version'             => sanitize_text_field( isset( $plugin['version'] ) ? $plugin['version'] : '' ),
			'status'              => $status,
			'config_notes'        => sanitize_textarea_field( isset( $plugin['config_notes'] ) ? $plugin['config_notes'] : '' ),
			'recommended_product' => sanitize_key( isset( $plugin['recommended_product'] ) ? $plugin['recommended_product'] : '' ),
			'active_installs'     => absint( isset( $plugin['active_installs'] ) ? $plugin['active_installs'] : 0 ),
			'homepage'            => esc_url_raw( isset( $plugin['report_url'] ) ? $plugin['report_url'] : '' ),
			'icons'               => array( '1x' => $icon, '2x' => $icon, 'svg' => $icon ),
			'tags'                => isset( $plugin['tags'] ) && is_array( $plugin['tags'] ) ? array_map( 'sanitize_text_field', $plugin['tags'] ) : array(),
			'wporg'               => true,
		);
	}
}
