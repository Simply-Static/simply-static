<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight, dependency-free S3 client implementing AWS Signature Version 4.
 *
 * Built specifically so Simply Static can talk to any S3-compatible object
 * storage (Hetzner Object Storage, AWS S3, MinIO, DigitalOcean Spaces, …)
 * without bundling the full AWS SDK. It relies solely on the WordPress HTTP
 * API (`wp_remote_request`) and PHP's `hash_hmac()` so it works on any
 * supported WordPress install.
 *
 * Reference: https://docs.aws.amazon.com/general/latest/gr/sigv4_signing.html
 *
 * @package Simply_Static
 */
class S3_Client {

	/**
	 * Access key ID.
	 *
	 * @var string
	 */
	protected $access_key = '';

	/**
	 * Secret access key.
	 *
	 * @var string
	 */
	protected $secret_key = '';

	/**
	 * Region used for the signing scope (e.g. "fsn1" for Hetzner Falkenstein,
	 * "us-east-1" for AWS).
	 *
	 * @var string
	 */
	protected $region = 'us-east-1';

	/**
	 * Service name used for the signing scope. Always "s3" for object storage.
	 *
	 * @var string
	 */
	protected $service = 's3';

	/**
	 * Endpoint scheme (http|https).
	 *
	 * @var string
	 */
	protected $scheme = 'https';

	/**
	 * Endpoint host without the bucket part
	 * (e.g. "fsn1.your-objectstorage.com").
	 *
	 * @var string
	 */
	protected $endpoint_host = '';

	/**
	 * Bucket name.
	 *
	 * @var string
	 */
	protected $bucket = '';

	/**
	 * Whether to use path-style addressing (https://host/bucket/key) instead of
	 * virtual-hosted style (https://bucket.host/key).
	 *
	 * @var bool
	 */
	protected $path_style = false;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	protected $timeout = 60;

	/**
	 * Constructor.
	 *
	 * @param array $config {
	 *     Connection configuration.
	 *
	 *     @type string $access_key Access key ID.
	 *     @type string $secret_key Secret access key.
	 *     @type string $region     Signing region/location.
	 *     @type string $endpoint   Full endpoint URL, e.g. "https://fsn1.your-objectstorage.com".
	 *     @type string $bucket     Bucket name.
	 *     @type bool   $path_style Use path-style addressing. Defaults to virtual-hosted.
	 *     @type int    $timeout    Request timeout in seconds.
	 * }
	 */
	public function __construct( array $config ) {
		$this->access_key = isset( $config['access_key'] ) ? (string) $config['access_key'] : '';
		$this->secret_key = isset( $config['secret_key'] ) ? (string) $config['secret_key'] : '';
		$this->bucket     = isset( $config['bucket'] ) ? (string) $config['bucket'] : '';

		if ( ! empty( $config['region'] ) ) {
			$this->region = (string) $config['region'];
		}

		if ( isset( $config['path_style'] ) ) {
			$this->path_style = (bool) $config['path_style'];
		}

		if ( ! empty( $config['timeout'] ) ) {
			$this->timeout = (int) $config['timeout'];
		}

		$endpoint = isset( $config['endpoint'] ) ? trim( (string) $config['endpoint'] ) : '';

		if ( '' !== $endpoint ) {
			// Make sure we have a scheme so wp_parse_url can do its job.
			if ( false === strpos( $endpoint, '://' ) ) {
				$endpoint = 'https://' . $endpoint;
			}

			$parsed = wp_parse_url( $endpoint );

			if ( ! empty( $parsed['scheme'] ) ) {
				$this->scheme = $parsed['scheme'];
			}

			if ( ! empty( $parsed['host'] ) ) {
				$this->endpoint_host = $parsed['host'];

				if ( ! empty( $parsed['port'] ) ) {
					$this->endpoint_host .= ':' . $parsed['port'];
				}
			}
		}
	}

	/**
	 * Upload (or overwrite) an object.
	 *
	 * @param string $key          Object key (path within the bucket, no leading slash).
	 * @param string $body         Raw object contents.
	 * @param string $content_type Optional MIME type. Defaults to application/octet-stream.
	 * @param bool   $public       Whether to send a public-read ACL header.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public function put_object( $key, $body, $content_type = '', $public = false ) {
		$headers = array(
			'Content-Type' => $content_type ? $content_type : 'application/octet-stream',
		);

		if ( $public ) {
			$headers['x-amz-acl'] = 'public-read';
		}

		$response = $this->request( 'PUT', $key, array(), $headers, $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Delete a single object.
	 *
	 * @param string $key Object key.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public function delete_object( $key ) {
		$response = $this->request( 'DELETE', $key, array(), array(), '' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List object keys in the bucket, optionally filtered by prefix.
	 *
	 * Transparently follows pagination via the continuation token.
	 *
	 * @param string $prefix    Optional key prefix.
	 * @param int    $max_keys  Maximum number of keys to return (0 = no limit).
	 *
	 * @return array|\WP_Error Array of object keys, or WP_Error on failure.
	 */
	public function list_objects( $prefix = '', $max_keys = 0 ) {
		$keys               = array();
		$continuation_token = '';

		do {
			$query = array(
				'list-type' => '2',
			);

			if ( '' !== $prefix ) {
				$query['prefix'] = $prefix;
			}

			if ( '' !== $continuation_token ) {
				$query['continuation-token'] = $continuation_token;
			}

			$response = $this->request( 'GET', '', $query, array(), '' );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$xml = $this->parse_xml( wp_remote_retrieve_body( $response ) );

			if ( false === $xml ) {
				return new \WP_Error( 's3_list_parse_error', __( 'Unable to parse the bucket listing response.', 'simply-static' ) );
			}

			if ( isset( $xml->Contents ) ) {
				foreach ( $xml->Contents as $object ) {
					$keys[] = (string) $object->Key;

					if ( $max_keys && count( $keys ) >= $max_keys ) {
						return $keys;
					}
				}
			}

			$is_truncated       = isset( $xml->IsTruncated ) && 'true' === (string) $xml->IsTruncated;
			$continuation_token = isset( $xml->NextContinuationToken ) ? (string) $xml->NextContinuationToken : '';
		} while ( $is_truncated && '' !== $continuation_token );

		return $keys;
	}

	/**
	 * Delete every object in the bucket (optionally under a prefix).
	 *
	 * Uses the S3 multi-object delete API in chunks of 1000 keys.
	 *
	 * @param string $prefix Optional key prefix to restrict the deletion.
	 *
	 * @return int|\WP_Error Number of objects deleted, or WP_Error on failure.
	 */
	public function delete_all( $prefix = '' ) {
		$keys = $this->list_objects( $prefix );

		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		if ( empty( $keys ) ) {
			return 0;
		}

		$deleted = 0;

		foreach ( array_chunk( $keys, 1000 ) as $chunk ) {
			$result = $this->delete_objects( $chunk );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$deleted += count( $chunk );
		}

		return $deleted;
	}

	/**
	 * Delete a batch of objects (up to 1000) using the multi-object delete API.
	 *
	 * @param array $keys Object keys to delete.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public function delete_objects( array $keys ) {
		if ( empty( $keys ) ) {
			return true;
		}

		$body  = '<?xml version="1.0" encoding="UTF-8"?>';
		$body .= '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Quiet>true</Quiet>';

		foreach ( $keys as $key ) {
			$body .= '<Object><Key>' . $this->xml_escape( $key ) . '</Key></Object>';
		}

		$body .= '</Delete>';

		// The multi-object delete request requires a Content-MD5 header.
		$headers = array(
			'Content-Type' => 'application/xml',
			'Content-MD5'  => base64_encode( md5( $body, true ) ),
		);

		$response = $this->request( 'POST', '', array( 'delete' => '' ), $headers, $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Lightweight connection/permissions check.
	 *
	 * Performs a single (max 1 key) bucket listing, which validates the
	 * endpoint, credentials, signing region and bucket name in one round-trip.
	 *
	 * @return true|\WP_Error True if the bucket is reachable, WP_Error otherwise.
	 */
	public function test_connection() {
		$result = $this->list_objects( '', 1 );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Build and execute a signed S3 request.
	 *
	 * @param string $method  HTTP method.
	 * @param string $key     Object key (empty for bucket-level operations).
	 * @param array  $query   Query string parameters (unencoded).
	 * @param array  $headers Additional request headers.
	 * @param string $payload Request body.
	 *
	 * @return array|\WP_Error Response array on 2xx, WP_Error otherwise.
	 */
	protected function request( $method, $key, $query, $headers, $payload ) {
		if ( '' === $this->endpoint_host ) {
			return new \WP_Error( 's3_no_endpoint', __( 'No S3 endpoint configured.', 'simply-static' ) );
		}

		if ( '' === $this->bucket ) {
			return new \WP_Error( 's3_no_bucket', __( 'No S3 bucket configured.', 'simply-static' ) );
		}

		$amz_date   = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );

		$host           = $this->get_host();
		$canonical_uri  = $this->get_canonical_uri( $key );
		$payload_hash   = hash( 'sha256', $payload );

		// Base headers that are always signed.
		$headers['Host']                 = $host;
		$headers['x-amz-content-sha256'] = $payload_hash;
		$headers['x-amz-date']           = $amz_date;

		// Canonical query string.
		$canonical_query = $this->get_canonical_query( $query );

		// Canonical + signed headers (sorted by lowercased name).
		$canonical_header_map = array();
		foreach ( $headers as $name => $value ) {
			$canonical_header_map[ strtolower( $name ) ] = $this->trim_header_value( $value );
		}
		ksort( $canonical_header_map );

		$canonical_headers = '';
		foreach ( $canonical_header_map as $name => $value ) {
			$canonical_headers .= $name . ':' . $value . "\n";
		}

		$signed_headers = implode( ';', array_keys( $canonical_header_map ) );

		$canonical_request = implode( "\n", array(
			$method,
			$canonical_uri,
			$canonical_query,
			$canonical_headers,
			$signed_headers,
			$payload_hash,
		) );

		$credential_scope = $date_stamp . '/' . $this->region . '/' . $this->service . '/aws4_request';

		$string_to_sign = implode( "\n", array(
			'AWS4-HMAC-SHA256',
			$amz_date,
			$credential_scope,
			hash( 'sha256', $canonical_request ),
		) );

		$signing_key = $this->get_signing_key( $date_stamp );
		$signature   = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		$authorization = sprintf(
			'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
			$this->access_key,
			$credential_scope,
			$signed_headers,
			$signature
		);

		// Build the final request headers. WordPress sets the Host header from
		// the URL automatically, so we don't pass it along here.
		$request_headers               = $headers;
		$request_headers['Authorization'] = $authorization;
		unset( $request_headers['Host'] );

		$url = $this->scheme . '://' . $host . $canonical_uri;

		if ( '' !== $canonical_query ) {
			$url .= '?' . $canonical_query;
		}

		$args = array(
			'method'      => $method,
			'headers'     => $request_headers,
			'body'        => $payload,
			'timeout'     => $this->timeout,
			'redirection' => 0,
			'sslverify'   => apply_filters( 'simply_static_s3_sslverify', true ),
		);

		$response = wp_remote_request( $url, apply_filters( 'simply_static_s3_request_args', $args, $method, $key ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error(
				's3_request_failed',
				$this->format_error_message( $method, $key, $code, $response )
			);
		}

		return $response;
	}

	/**
	 * Resolve the request host based on the addressing style.
	 *
	 * @return string
	 */
	protected function get_host() {
		if ( $this->path_style ) {
			return $this->endpoint_host;
		}

		return $this->bucket . '.' . $this->endpoint_host;
	}

	/**
	 * Build the canonical URI for the request.
	 *
	 * @param string $key Object key (may be empty for bucket operations).
	 *
	 * @return string
	 */
	protected function get_canonical_uri( $key ) {
		$path = '';

		if ( $this->path_style ) {
			$path = '/' . $this->bucket;
		}

		if ( '' !== $key ) {
			$path .= '/' . $this->encode_key( $key );
		}

		return '' === $path ? '/' : $path;
	}

	/**
	 * Build the canonical (sorted, URI-encoded) query string.
	 *
	 * @param array $query Query parameters.
	 *
	 * @return string
	 */
	protected function get_canonical_query( array $query ) {
		if ( empty( $query ) ) {
			return '';
		}

		ksort( $query );

		$pairs = array();
		foreach ( $query as $name => $value ) {
			$pairs[] = $this->uri_encode( (string) $name ) . '=' . $this->uri_encode( (string) $value );
		}

		return implode( '&', $pairs );
	}

	/**
	 * Derive the SigV4 signing key for the given date.
	 *
	 * @param string $date_stamp YYYYMMDD date stamp.
	 *
	 * @return string Raw (binary) signing key.
	 */
	protected function get_signing_key( $date_stamp ) {
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $this->secret_key, true );
		$k_region  = hash_hmac( 'sha256', $this->region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $this->service, $k_region, true );

		return hash_hmac( 'sha256', 'aws4_request', $k_service, true );
	}

	/**
	 * URI-encode an object key while preserving the path separators.
	 *
	 * @param string $key Object key.
	 *
	 * @return string
	 */
	protected function encode_key( $key ) {
		$segments = explode( '/', $key );
		$segments = array_map( array( $this, 'uri_encode' ), $segments );

		return implode( '/', $segments );
	}

	/**
	 * RFC 3986 URI-encoding as required by AWS SigV4.
	 *
	 * `rawurlencode()` already follows RFC 3986, but PHP versions before 5.3
	 * left the tilde encoded. We normalise it here to be safe.
	 *
	 * @param string $string String to encode.
	 *
	 * @return string
	 */
	protected function uri_encode( $string ) {
		return str_replace( '%7E', '~', rawurlencode( $string ) );
	}

	/**
	 * Collapse internal whitespace in a header value as required for signing.
	 *
	 * @param string $value Header value.
	 *
	 * @return string
	 */
	protected function trim_header_value( $value ) {
		return preg_replace( '/\s+/', ' ', trim( (string) $value ) );
	}

	/**
	 * Escape a string for safe inclusion in an XML document.
	 *
	 * @param string $string String to escape.
	 *
	 * @return string
	 */
	protected function xml_escape( $string ) {
		return htmlspecialchars( $string, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Safely parse an XML string without exposing the parser to XXE.
	 *
	 * @param string $body XML payload.
	 *
	 * @return \SimpleXMLElement|false
	 */
	protected function parse_xml( $body ) {
		if ( '' === trim( (string) $body ) ) {
			return false;
		}

		$previous = libxml_use_internal_errors( true );

		if ( function_exists( 'libxml_disable_entity_loader' ) && PHP_VERSION_ID < 80000 ) {
			// Guard against XXE on PHP < 8 (the loader is disabled by default on PHP >= 8).
			$loader = libxml_disable_entity_loader( true );
		}

		$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET );

		if ( isset( $loader ) ) {
			libxml_disable_entity_loader( $loader );
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $xml;
	}

	/**
	 * Build a human-readable error message from a failed response.
	 *
	 * @param string $method   HTTP method.
	 * @param string $key      Object key.
	 * @param int    $code     HTTP status code.
	 * @param array  $response Raw HTTP response.
	 *
	 * @return string
	 */
	protected function format_error_message( $method, $key, $code, $response ) {
		$detail = '';
		$xml    = $this->parse_xml( wp_remote_retrieve_body( $response ) );

		if ( false !== $xml && isset( $xml->Message ) ) {
			$detail = (string) $xml->Message;

			if ( isset( $xml->Code ) ) {
				$detail = (string) $xml->Code . ': ' . $detail;
			}
		}

		$target = '' === $key ? $this->bucket : $this->bucket . '/' . $key;

		return sprintf(
			/* translators: 1: HTTP method, 2: object path, 3: HTTP status code, 4: error detail */
			__( 'S3 request failed (%1$s %2$s) with status %3$d. %4$s', 'simply-static' ),
			$method,
			$target,
			$code,
			$detail
		);
	}
}
