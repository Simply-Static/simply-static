<?php

declare(strict_types=1);

use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

defined( 'ABSPATH' ) || define( 'ABSPATH', sys_get_temp_dir() . '/simply-static-tests/wordpress/' );
defined( 'WP_CONTENT_DIR' ) || define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
defined( 'WP_CONTENT_URL' ) || define( 'WP_CONTENT_URL', 'https://example.test/wp-content' );
defined( 'WP_PLUGIN_DIR' ) || define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
defined( 'WP_PLUGIN_URL' ) || define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'SIMPLY_STATIC_PATH' ) || define( 'SIMPLY_STATIC_PATH', dirname( __DIR__, 2 ) . '/' );
defined( 'SIMPLY_STATIC_URL' ) || define( 'SIMPLY_STATIC_URL', 'https://example.test/wp-content/plugins/simply-static' );
defined( 'SIMPLY_STATIC_VERSION' ) || define( 'SIMPLY_STATIC_VERSION', 'test' );
defined( 'LOGGED_IN_COOKIE' ) || define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_simply_static_test' );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/** @var string */
		private $code;
		/** @var string */
		private $message;
		/** @var mixed */
		private $data;

		/** @param mixed $data */
		public function __construct( string $code = '', string $message = '', $data = null ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		/** @return mixed */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		/** @var mixed */
		private $data;
		/** @var int */
		private $status;

		/** @param mixed $data */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/** @return mixed */
		public function get_data() {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		/** @var array<string,mixed> */
		private $params;

		/** @param array<string,mixed> $params */
		public function __construct( array $params = array() ) {
			$this->params = $params;
		}

		/** @return array<string,mixed> */
		public function get_params(): array {
			return $this->params;
		}

		/** @return mixed */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}

function apply_filters( $hook, $value, ...$args ) {
	return WpEnv::applyFilters( (string) $hook, $value, ...$args );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	WpEnv::addFilter( (string) $hook, $callback, (int) $priority, (int) $accepted_args );
	return true;
}

function remove_filter( $hook, $callback, $priority = 10 ) {
	if ( empty( WpEnv::$filters[ $hook ][ $priority ] ) ) {
		return false;
	}
	foreach ( WpEnv::$filters[ $hook ][ $priority ] as $index => $registered ) {
		if ( $registered['callback'] === $callback ) {
			unset( WpEnv::$filters[ $hook ][ $priority ][ $index ] );
			return true;
		}
	}
	return false;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook, $callback, $priority, $accepted_args );
}

function remove_action( $hook, $callback, $priority = 10 ) {
	return remove_filter( $hook, $callback, $priority );
}

function do_action( $hook, ...$args ) {
	WpEnv::doAction( (string) $hook, ...$args );
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, WpEnv::$options ) ? WpEnv::$options[ $key ] : $default;
}

function add_option( $key, $value, $deprecated = '', $autoload = 'yes' ) {
	if ( array_key_exists( $key, WpEnv::$options ) ) {
		return false;
	}

	WpEnv::$options[ $key ] = $value;
	return true;
}

function update_option( $key, $value, $autoload = null ) {
	WpEnv::$options[ $key ] = $value;
	return true;
}

function delete_option( $key ) {
	unset( WpEnv::$options[ $key ] );
	return true;
}

function get_site_option( $key, $default = false ) {
	return array_key_exists( $key, WpEnv::$site_options ) ? WpEnv::$site_options[ $key ] : $default;
}

function update_site_option( $key, $value ) {
	WpEnv::$site_options[ $key ] = $value;
	return true;
}

function add_network_option( $network_id, $key, $value ) {
	if ( array_key_exists( $key, WpEnv::$site_options ) ) {
		return false;
	}

	WpEnv::$site_options[ $key ] = $value;
	return true;
}

function delete_site_option( $key ) {
	unset( WpEnv::$site_options[ $key ] );
	return true;
}

function get_transient( $key ) {
	return array_key_exists( $key, WpEnv::$transients ) ? WpEnv::$transients[ $key ] : false;
}

function set_transient( $key, $value, $expiration = 0 ) {
	WpEnv::$transients[ $key ] = $value;
	return true;
}

function delete_transient( $key ) {
	unset( WpEnv::$transients[ $key ] );
	return true;
}

function get_site_transient( $key ) {
	return array_key_exists( $key, WpEnv::$site_transients ) ? WpEnv::$site_transients[ $key ] : false;
}

function set_site_transient( $key, $value, $expiration = 0 ) {
	WpEnv::$site_transients[ $key ] = $value;
	return true;
}

function delete_site_transient( $key ) {
	unset( WpEnv::$site_transients[ $key ] );
	return true;
}

function home_url( $path = '' ) {
	return rtrim( WpEnv::$home_url, '/' ) . ( '' === $path ? '' : '/' . ltrim( (string) $path, '/' ) );
}

function site_url( $path = '' ) {
	return rtrim( WpEnv::$site_url, '/' ) . ( '' === $path ? '' : '/' . ltrim( (string) $path, '/' ) );
}

function wp_get_environment_type() {
	return WpEnv::$environment_type;
}

function includes_url( $path = '' ) {
	return rtrim( WpEnv::$site_url, '/' ) . '/wp-includes/' . ltrim( (string) $path, '/' );
}

function plugins_url( $path = '', $plugin = '' ) {
	return rtrim( WP_PLUGIN_URL, '/' ) . '/' . ltrim( (string) $path, '/' );
}

function admin_url( $path = '' ) {
	return site_url( 'wp-admin/' . ltrim( (string) $path, '/' ) );
}

/**
 * @param string|array<string,mixed> $key
 * @param mixed $value
 * @param string|null $url
 * @return string
 */
function add_query_arg( $key, $value = null, $url = null ) {
	if ( is_array( $key ) ) {
		$args = $key;
		$url  = (string) $value;
	} else {
		$args = array( (string) $key => $value );
		$url  = (string) $url;
	}

	$fragment = '';
	if ( false !== strpos( $url, '#' ) ) {
		list( $url, $fragment ) = explode( '#', $url, 2 );
		$fragment = '#' . $fragment;
	}

	$separator = false === strpos( $url, '?' ) ? '?' : '&';
	return $url . $separator . http_build_query( $args ) . $fragment;
}

function wp_create_nonce( $action = -1 ) {
	return 'nonce-' . sanitize_key( (string) $action );
}

function check_ajax_referer( $action = -1, $query_arg = false, $stop = true ) {
	$key = $query_arg ? (string) $query_arg : '_ajax_nonce';
	WpEnv::$nonce_verifications[] = array(
		'nonce'  => isset( $_REQUEST[ $key ] ) ? (string) $_REQUEST[ $key ] : '',
		'action' => (string) $action,
	);
	return 1;
}

function wp_doing_ajax() {
	return false;
}

function wp_generate_uuid4() {
	return '00000000-0000-4000-8000-000000000001';
}

function wp_is_uuid( $uuid, $version = null ) {
	return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $uuid );
}

function wp_rand( $min = 0, $max = 0 ) {
	if ( 0 === $max ) {
		$max = PHP_INT_MAX;
	}

	return mt_rand( (int) $min, (int) $max );
}

function get_admin_url( $blog_id = null, $path = '' ) {
	return admin_url( $path );
}

function plugin_dir_path( $file ) {
	return trailingslashit( dirname( (string) $file ) );
}

function plugin_dir_url( $file ) {
	return SIMPLY_STATIC_URL . '/';
}

function plugin_basename( $file ) {
	return basename( dirname( (string) $file ) ) . '/' . basename( (string) $file );
}

function trailingslashit( $value ) {
	return untrailingslashit( $value ) . '/';
}

function untrailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component );
}

function esc_url_raw( $url ) {
	return filter_var( (string) $url, FILTER_SANITIZE_URL );
}

function esc_url( $url ) {
	$url = esc_url_raw( $url );
	$scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
	return in_array( $scheme, array( '', 'http', 'https' ), true ) ? $url : '';
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_attr( $text ) {
	return esc_html( $text );
}

/**
 * @param mixed $value
 * @return mixed
 */
function esc_sql( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'esc_sql', $value );
	}

	return addslashes( (string) $value );
}

function __( $text, $domain = null ) {
	return (string) $text;
}

function _n( $single, $plural, $number, $domain = null ) {
	return 1 === (int) $number ? (string) $single : (string) $plural;
}

function _x( $text, $context, $domain = null ) {
	return (string) $text;
}

function esc_html_x( $text, $context, $domain = null ) {
	return esc_html( $text );
}

function esc_html__( $text, $domain = null ) {
	return esc_html( $text );
}

function sanitize_text_field( $value ) {
	if ( ! is_scalar( $value ) && null !== $value ) {
		return '';
	}
	$value = wp_strip_all_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t ]+/', ' ', $value );
	return trim( (string) $value );
}

function sanitize_textarea_field( $value ) {
	if ( ! is_scalar( $value ) && null !== $value ) {
		return '';
	}
	$value = str_replace( "\r", '', (string) $value );
	return trim( wp_strip_all_tags( $value ) );
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_title( $title ) {
	$title = strtolower( remove_accents( sanitize_text_field( $title ) ) );
	$title = preg_replace( '/[^a-z0-9_\-]+/', '-', $title );
	return trim( (string) $title, '-' );
}

function sanitize_file_name( $filename ) {
	$filename = basename( str_replace( '\\', '/', (string) $filename ) );
	$filename = preg_replace( '/[^A-Za-z0-9._-]+/u', '-', $filename );
	$filename = preg_replace( '/\.{2,}/', '.', (string) $filename );
	return trim( (string) $filename, '.-' );
}

function remove_accents( $text ) {
	if ( function_exists( 'iconv' ) ) {
		$converted = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $text );
		if ( false !== $converted ) {
			return $converted;
		}
	}
	return (string) $text;
}

function sanitize_option( $option, $value ) {
	return $value;
}

function map_deep( $value, $callback ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = map_deep( $item, $callback );
		}
	} elseif ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $property => $item ) {
			$value->{$property} = map_deep( $item, $callback );
		}
	} else {
		$value = call_user_func( $callback, $value );
	}

	return $value;
}

function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
	return json_encode( $value, $flags, $depth );
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function rest_ensure_response( $value ) {
	return $value instanceof WP_REST_Response ? $value : new WP_REST_Response( $value );
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = strip_tags( (string) $text );
	return $remove_breaks ? preg_replace( '/[\r\n\t ]+/', ' ', $text ) : $text;
}

function wp_kses_post( $html ) {
	$html = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', (string) $html );
	$html = preg_replace( '/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $html );
	$html = preg_replace_callback(
		'/\s+(href|src)\s*=\s*(["\'])(.*?)\2/i',
		static function ( array $matches ): string {
			return ' ' . $matches[1] . '=' . $matches[2] . esc_url( $matches[3] ) . $matches[2];
		},
		$html
	);
	return strip_tags( (string) $html, '<a><b><strong><em><i><code><span><p><br><ul><ol><li>' );
}

function wp_normalize_path( $path ) {
	$path = str_replace( '\\', '/', (string) $path );
	return preg_replace( '|(?<=.)/+|', '/', $path );
}

function wp_mkdir_p( $path ) {
	return is_dir( $path ) || @mkdir( $path, 0777, true );
}

function dbDelta( $queries = '', $execute = true ) {
	return array();
}

function wp_cache_flush() {
	return true;
}

function wp_next_scheduled( $hook, $args = array() ) {
	$events = $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] ?? array();
	return $events ? reset( $events ) : false;
}

function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array(), $wp_error = false ) {
	$GLOBALS['simply_static_test_scheduled_hooks'][ $hook ][] = (int) $timestamp;
	return true;
}

function wp_unschedule_event( $timestamp, $hook, $args = array(), $wp_error = false ) {
	if ( empty( $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] ) ) {
		return false;
	}

	$GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] = array_values(
		array_filter(
			$GLOBALS['simply_static_test_scheduled_hooks'][ $hook ],
			static function ( $event_timestamp ) use ( $timestamp ) {
				return (int) $event_timestamp !== (int) $timestamp;
			}
		)
	);

	if ( empty( $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] ) ) {
		unset( $GLOBALS['simply_static_test_scheduled_hooks'][ $hook ] );
	}

	return true;
}

function wp_convert_hr_to_bytes( $value ) {
	$value = trim( (string) $value );
	$bytes = (int) $value;
	$unit  = strtolower( substr( $value, -1 ) );

	if ( 'g' === $unit ) {
		$bytes *= 1024;
	}
	if ( 'g' === $unit || 'm' === $unit ) {
		$bytes *= 1024;
	}
	if ( in_array( $unit, array( 'g', 'm', 'k' ), true ) ) {
		$bytes *= 1024;
	}

	return $bytes;
}

function is_serialized( $data, $strict = true ) {
	if ( ! is_string( $data ) ) {
		return false;
	}

	$data = trim( $data );
	if ( 'N;' === $data ) {
		return true;
	}
	if ( strlen( $data ) < 4 || ':' !== $data[1] ) {
		return false;
	}

	$last = substr( $data, -1 );
	if ( $strict && ';' !== $last && '}' !== $last ) {
		return false;
	}

	return in_array( $data[0], array( 'a', 'b', 'd', 'i', 'O', 's', 'C' ), true );
}

function maybe_unserialize( $data ) {
	if ( is_serialized( $data ) ) {
		return unserialize( trim( $data ), array( 'allowed_classes' => false ) );
	}

	return $data;
}

function wp_upload_dir() {
	wp_mkdir_p( WpEnv::$upload_dir['basedir'] );
	return WpEnv::$upload_dir;
}

function wp_is_stream( $path ) {
	return (bool) preg_match( '#^[a-z][a-z0-9+.-]*://#i', (string) $path );
}

function current_time( $format ) {
	return '2026-07-12 12:00:00';
}

function get_bloginfo( $show = '' ) {
	$values = array(
		'charset'     => 'UTF-8',
		'version'     => '6.8',
		'admin_email' => 'admin@example.test',
	);
	return $values[ $show ] ?? '';
}

function remove_query_arg( $key, $url ) {
	$keys  = (array) $key;
	$parts = parse_url( (string) $url );
	if ( false === $parts ) {
		return $url;
	}
	parse_str( $parts['query'] ?? '', $query );
	foreach ( $keys as $item ) {
		unset( $query[ $item ] );
	}
	$rebuilt = '';
	if ( isset( $parts['scheme'] ) ) {
		$rebuilt .= $parts['scheme'] . '://';
	}
	if ( isset( $parts['user'] ) ) {
		$rebuilt .= $parts['user'] . ( isset( $parts['pass'] ) ? ':' . $parts['pass'] : '' ) . '@';
	}
	$rebuilt .= $parts['host'] ?? '';
	$rebuilt .= isset( $parts['port'] ) ? ':' . $parts['port'] : '';
	$rebuilt .= $parts['path'] ?? '';
	$rebuilt .= $query ? '?' . http_build_query( $query ) : '';
	$rebuilt .= isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
	return $rebuilt;
}

function get_current_blog_id() {
	return WpEnv::$current_blog_id;
}

function get_blog_details( $fields = null, $get_all = true ) {
	if ( is_array( $fields ) ) {
		$blog_id = (int) ( $fields['blog_id'] ?? $fields['site_id'] ?? 0 );
	} elseif ( is_object( $fields ) ) {
		$blog_id = (int) ( $fields->blog_id ?? $fields->site_id ?? 0 );
	} else {
		$blog_id = (int) $fields;
	}

	if ( ! isset( WpEnv::$sites[ $blog_id ] ) ) {
		return false;
	}

	$site = WpEnv::$sites[ $blog_id ];
	return is_array( $site ) ? (object) $site : $site;
}

function is_multisite() {
	return WpEnv::$multisite;
}

function is_admin() {
	return WpEnv::$is_admin;
}

function is_network_admin() {
	return false;
}

function switch_to_blog( $blog_id ) {
	WpEnv::$blog_stack[]       = WpEnv::$current_blog_id;
	WpEnv::$current_blog_id    = (int) $blog_id;
	return true;
}

function restore_current_blog() {
	if ( WpEnv::$blog_stack ) {
		WpEnv::$current_blog_id = (int) array_pop( WpEnv::$blog_stack );
	}
	return true;
}

function current_user_can( $capability, ...$args ) {
	return ! empty( WpEnv::$capabilities[ $capability ] );
}

function current_user_can_for_blog( $blog_id, $capability, ...$args ) {
	return ! empty( WpEnv::$site_capabilities[ (int) $blog_id ][ $capability ] );
}

function current_user_can_for_site( $site_id, $capability, ...$args ) {
	return ! empty( WpEnv::$site_capabilities[ (int) $site_id ][ $capability ] );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	$nonce  = (string) $nonce;
	$action = (string) $action;
	WpEnv::$nonce_verifications[] = array(
		'nonce'  => $nonce,
		'action' => $action,
	);

	return in_array( $nonce, WpEnv::$valid_nonces[ $action ] ?? array(), true ) ? 1 : false;
}

function register_rest_route( $namespace, $route, $args, $override = false ) {
	WpEnv::$routes[] = array(
		'namespace' => $namespace,
		'route'     => $route,
		'args'      => $args,
	);
	return true;
}

function wp_script_is( $handle, $status = 'enqueued' ) {
	if ( 'registered' !== $status ) {
		return false;
	}

	return array_key_exists( (string) $handle, WpEnv::$registered_scripts );
}

function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
	WpEnv::$registered_scripts[ (string) $handle ] = array(
		'src'       => $src,
		'deps'      => $deps,
		'ver'       => $ver,
		'in_footer' => $in_footer,
	);

	return true;
}

function wp_remote_get( $url, $args = array() ) {
	WpEnv::$remote_requests[] = array( 'method' => 'GET', 'url' => $url, 'args' => $args );
	return WpEnv::$remote_response;
}

function wp_remote_post( $url, $args = array() ) {
	WpEnv::$remote_requests[] = array( 'method' => 'POST', 'url' => $url, 'args' => $args );
	return WpEnv::$remote_response;
}

function wp_remote_retrieve_body( $response ) {
	return is_array( $response ) ? ( $response['body'] ?? '' ) : '';
}

function wp_remote_retrieve_response_code( $response ) {
	return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
}

function wp_tempnam( $filename = '' ) {
	return tempnam( sys_get_temp_dir(), 'ss-test-' );
}

function get_post_types( $args = array(), $output = 'names' ) {
	if ( null !== WpEnv::$post_types ) {
		return WpEnv::$post_types;
	}

	return array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' );
}

function get_taxonomies( $args = array(), $output = 'names' ) {
	if ( null !== WpEnv::$taxonomies ) {
		return WpEnv::$taxonomies;
	}

	return array();
}

function wp_list_pluck( $input_list, $field, $index_key = null ) {
	$result = array();
	foreach ( (array) $input_list as $item ) {
		$value = is_array( $item ) ? ( $item[ $field ] ?? null ) : ( $item->{$field} ?? null );
		$result[] = $value;
	}
	return $result;
}

function wp_http_validate_url( $url ) {
	$url = esc_url_raw( $url );
	$parts = parse_url( $url );
	if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || empty( $parts['host'] ) ) {
		return false;
	}
	$host = strtolower( $parts['host'] );
	if ( 'localhost' === $host || ( strlen( $host ) > 10 && '.localhost' === substr( $host, -10 ) ) ) {
		return false;
	}
	if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
		return filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ? $url : false;
	}
	return $url;
}

function __return_false() {
	return false;
}

function __return_true() {
	return true;
}

function __return_null() {
	return null;
}
