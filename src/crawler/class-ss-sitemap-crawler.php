<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Sitemap Crawler class
 *
 * This crawler detects URLs from XML sitemaps.
 */
class Sitemap_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 *
	 * @var string
	 */
	protected $id = 'sitemap';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Sitemap URLs', 'simply-static' );
		$this->description = __( 'Detects URLs from XML sitemaps.', 'simply-static' );
	}

	/**
	 * Detect sitemap URLs.
	 *
	 * @return array List of local URLs from sitemaps.
	 */
	public function detect() : array {
		$max_depth       = $this->get_non_negative_limit( 'simply_static_sitemap_max_depth', 5 );
		$max_documents   = $this->get_positive_limit( 'simply_static_sitemap_max_documents', 1000 );
		$max_urls        = $this->get_positive_limit( 'simply_static_sitemap_max_urls', 250000 );
		$max_bytes       = $this->get_positive_limit( 'simply_static_sitemap_max_response_size', 5 * 1024 * 1024 );
		$request_timeout = $this->get_request_timeout();
		$max_scan_time   = $this->get_max_scan_time();
		$deadline        = $this->now() + $max_scan_time;
		$post_types      = $this->get_post_type_selection();

		// An explicitly saved empty post type selection means that no sitemap URLs
		// should be discovered. Avoid making network requests in that case.
		if ( $post_types['configured'] && empty( $post_types['selected'] ) ) {
			return array();
		}

		$queue           = new \SplQueue();
		$known_documents = array();
		$visited         = array();
		$urls            = array();

		foreach ( $this->find_sitemap_urls( $request_timeout, $deadline ) as $sitemap_url ) {
			$this->enqueue_sitemap_document(
				$queue,
				$known_documents,
				$sitemap_url,
				0,
				$max_documents
			);
		}

		while (
			! $queue->isEmpty()
			&& count( $visited ) < $max_documents
			&& count( $urls ) < $max_urls
			&& $this->now() < $deadline
		) {
			$document = $queue->dequeue();
			$url      = $document['url'];
			$depth    = $document['depth'];
			$key      = $this->canonicalize_url( $url );

			if ( '' === $key || isset( $visited[ $key ] ) || $depth > $max_depth || ! $this->is_allowed_local_url( $url ) ) {
				continue;
			}

			// Mark the document before requesting it so a cyclic index can never
			// schedule another request for the same canonical URL.
			$visited[ $key ] = true;
			$remaining_time  = max( 0.1, $deadline - $this->now() );
			$xml             = $this->fetch_sitemap_document( $url, min( $request_timeout, $remaining_time ), $max_bytes );

			if ( false === $xml ) {
				continue;
			}

			foreach ( $this->extract_stylesheet_urls( $xml, $url ) as $stylesheet_url ) {
				if ( count( $urls ) >= $max_urls ) {
					break;
				}

				// Stylesheet processing instructions are untrusted sitemap input too.
				// Only same-origin HTTP(S) resources may reach the fetch queue.
				$this->add_unique_local_url( $urls, $stylesheet_url );
			}

			$root_name = strtolower( $xml->getName() );

			if ( 'sitemapindex' === $root_name ) {
				if ( $depth >= $max_depth ) {
					continue;
				}

				foreach ( $this->extract_locations( $xml, 'sitemap' ) as $child_url ) {
					$this->enqueue_sitemap_document(
						$queue,
						$known_documents,
						$child_url,
						$depth + 1,
						$max_documents
					);
				}

				continue;
			}

			if ( 'urlset' !== $root_name ) {
				continue;
			}

			foreach ( $this->extract_locations( $xml, 'url' ) as $page_url ) {
				if ( count( $urls ) >= $max_urls ) {
					break;
				}

				// Sitemap contents are untrusted input. Only local URLs may leave this
				// crawler and reach the database-backed fetch queue.
				if ( ! $this->is_allowed_local_url( $page_url ) || ! $this->should_include_page_url( $page_url, $post_types ) ) {
					continue;
				}

				$page_key = $this->canonicalize_url( $page_url );
				if ( '' === $page_key || isset( $urls[ $page_key ] ) ) {
					continue;
				}

				$urls[ $page_key ] = $this->strip_fragment( $page_url );
			}
		}

		return array_values( $urls );
	}

	/**
	 * Find known local sitemap URLs.
	 *
	 * @param float $request_timeout HTTP request timeout in seconds.
	 * @param float $deadline        Absolute traversal deadline.
	 *
	 * @return array List of local sitemap URLs.
	 */
	private function find_sitemap_urls( $request_timeout, $deadline ) : array {
		$sitemap_urls = array();

		$common_sitemaps = (array) apply_filters(
			'simply_static_sitemap_common_urls',
			array(
				home_url( '/sitemap.xml' ),
				home_url( '/sitemap_index.xml' ),
				home_url( '/wp-sitemap.xml' ),
			)
		);

		foreach ( $common_sitemaps as $sitemap_url ) {
			if ( $this->now() >= $deadline ) {
				break;
			}

			if ( ! $this->is_allowed_local_url( $sitemap_url ) ) {
				continue;
			}

			$response = wp_remote_head(
				$sitemap_url,
				array(
					'timeout'     => min( $request_timeout, max( 0.1, $deadline - $this->now() ) ),
					'redirection' => 0,
					'sslverify'   => true,
				)
			);

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$this->add_unique_local_url( $sitemap_urls, $sitemap_url );
			}
		}

		// Plugin-specific URLs do not need another HEAD request. Their dependency
		// is sufficient evidence that the endpoint may exist; the bounded GET below
		// still handles missing or invalid responses safely.
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
			$this->add_unique_local_url( $sitemap_urls, home_url( '/sitemap_index.xml' ) );
		}

		if ( class_exists( 'AIOSEO' ) ) {
			$this->add_unique_local_url( $sitemap_urls, home_url( '/sitemap.xml' ) );
		}

		if ( defined( 'SEOPRESS_VERSION' ) ) {
			$this->add_unique_local_url( $sitemap_urls, home_url( '/sitemaps.xml' ) );
		}

		if ( function_exists( 'wp_sitemaps_get_server' ) ) {
			$this->add_unique_local_url( $sitemap_urls, home_url( '/wp-sitemap.xml' ) );
		}

		return array_values( $sitemap_urls );
	}

	/**
	 * Request and parse one local sitemap document.
	 *
	 * @param string $sitemap_url    Sitemap URL.
	 * @param float  $request_timeout HTTP request timeout in seconds.
	 * @param int    $max_bytes       Maximum response size in bytes.
	 *
	 * @return \SimpleXMLElement|false Parsed XML or false on failure.
	 */
	private function fetch_sitemap_document( $sitemap_url, $request_timeout, $max_bytes ) {
		if ( ! $this->is_allowed_local_url( $sitemap_url ) ) {
			return false;
		}

		// Ask WordPress for one extra byte so a response that exceeds the cap can
		// be distinguished from one whose size is exactly the configured maximum.
		$response_limit = $max_bytes < PHP_INT_MAX ? $max_bytes + 1 : $max_bytes;
		$response       = wp_remote_get(
			$sitemap_url,
			array(
				'timeout'             => $request_timeout,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => $response_limit,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$xml_content = wp_remote_retrieve_body( $response );
		if ( ! is_string( $xml_content ) || '' === $xml_content || strlen( $xml_content ) > $max_bytes ) {
			return false;
		}

		return $this->parse_xml( $xml_content );
	}

	/**
	 * Parse sitemap XML without allowing network access and without leaking libxml
	 * error handling state into the rest of WordPress.
	 *
	 * @param string $xml_content XML content.
	 *
	 * @return \SimpleXMLElement|false
	 */
	private function parse_xml( $xml_content ) {
		// Sitemaps never require a DTD. Reject declarations before parsing to avoid
		// local-file entities and entity-amplification payloads as well as network
		// entities blocked by LIBXML_NONET below.
		if ( false !== stripos( $xml_content, '<!DOCTYPE' ) || false !== stripos( $xml_content, '<!ENTITY' ) ) {
			return false;
		}

		$previous_error_state = libxml_use_internal_errors( true );
		$flags                = defined( 'LIBXML_NONET' ) ? LIBXML_NONET : 0;

		try {
			$xml = simplexml_load_string( $xml_content, 'SimpleXMLElement', $flags );
		} catch ( \Throwable $exception ) {
			$xml = false;
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_error_state );
		}

		return $xml;
	}

	/**
	 * Extract loc values from either sitemap or URL entries, independently of the
	 * namespace prefix used by the document.
	 *
	 * @param \SimpleXMLElement $xml        Sitemap XML.
	 * @param string            $entry_name Entry element name.
	 *
	 * @return array
	 */
	private function extract_locations( $xml, $entry_name ) : array {
		$locations = $xml->xpath(
			'/*[local-name()="' . $entry_name . 'set" or local-name()="sitemapindex"]'
			. '/*[local-name()="' . $entry_name . '"]/*[local-name()="loc"]'
		);

		// The expression above intentionally supports urlset/url and
		// sitemapindex/sitemap. Reject any unexpected root/entry combination.
		if ( false === $locations ) {
			return array();
		}

		$urls = array();
		foreach ( $locations as $location ) {
			$url = trim( (string) $location );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Extract stylesheet URLs from xml-stylesheet processing instructions.
	 *
	 * WordPress's native sitemap index and child sitemap documents reference
	 * different XSL files. These resources are not represented by <loc> nodes,
	 * so they need to be discovered from the processing instruction itself.
	 *
	 * @param \SimpleXMLElement $xml          Parsed sitemap XML.
	 * @param string            $document_url URL of the sitemap document.
	 *
	 * @return array
	 */
	private function extract_stylesheet_urls( $xml, $document_url ) : array {
		$instructions = $xml->xpath( '/processing-instruction("xml-stylesheet")' );
		if ( false === $instructions ) {
			return array();
		}

		$urls = array();
		foreach ( $instructions as $instruction ) {
			if ( 1 !== preg_match( '/(?:^|\s)href\s*=\s*(["\'])(.*?)\1/i', (string) $instruction, $matches ) ) {
				continue;
			}

			$href = html_entity_decode( trim( $matches[2] ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
			$url  = \Simply_Static\Util::relative_to_absolute_url( $href, $document_url );

			if ( is_string( $url ) && '' !== $url ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Add a sitemap document to the traversal queue once.
	 *
	 * @param \SplQueue $queue           Traversal queue.
	 * @param array     $known_documents Canonical document set.
	 * @param string    $url             Sitemap URL.
	 * @param int       $depth           Traversal depth.
	 * @param int       $max_documents   Maximum unique documents.
	 *
	 * @return void
	 */
	private function enqueue_sitemap_document( $queue, &$known_documents, $url, $depth, $max_documents ) {
		if ( count( $known_documents ) >= $max_documents || ! $this->is_allowed_local_url( $url ) ) {
			return;
		}

		$key = $this->canonicalize_url( $url );
		if ( '' === $key || isset( $known_documents[ $key ] ) ) {
			return;
		}

		$known_documents[ $key ] = true;
		$queue->enqueue(
			array(
				'url'   => $this->strip_fragment( trim( (string) $url ) ),
				'depth' => (int) $depth,
			)
		);
	}

	/**
	 * Add a unique local URL to an associative URL set.
	 *
	 * @param array  $urls URL set.
	 * @param string $url  URL to add.
	 *
	 * @return void
	 */
	private function add_unique_local_url( &$urls, $url ) {
		if ( ! $this->is_allowed_local_url( $url ) ) {
			return;
		}

		$key = $this->canonicalize_url( $url );
		if ( '' !== $key ) {
			$urls[ $key ] = $this->strip_fragment( trim( (string) $url ) );
		}
	}

	/**
	 * Require an HTTP(S) URL matching one of WordPress's configured origins,
	 * including scheme and effective port.
	 *
	 * @param mixed $url URL to inspect.
	 *
	 * @return bool
	 */
	private function is_allowed_local_url( $url ) {
		if ( ! is_string( $url ) || '' === trim( $url ) || strlen( $url ) > 8192 ) {
			return false;
		}

		$url = trim( $url );
		if ( ! \Simply_Static\Util::is_local_url( $url ) ) {
			return false;
		}

		$same_origin = false;
		foreach ( \Simply_Static\Util::local_url_bases() as $base ) {
			if ( \Simply_Static\Util::is_same_origin_url( $url, $base ) ) {
				$same_origin = true;
				break;
			}
		}

		// Integrations may veto a local sitemap URL, but cannot use this filter to
		// promote a remote URL past the same-origin security check above.
		return $same_origin && (bool) apply_filters( 'simply_static_sitemap_allow_local_url', true, $url );
	}

	/**
	 * Canonical key used for visited/document/page de-duplication.
	 *
	 * @param string $url URL to canonicalize.
	 *
	 * @return string
	 */
	private function canonicalize_url( $url ) {
		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}

		$host  = strtolower( rtrim( (string) $parts['host'], '.' ) );
		$port  = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		$path  = isset( $parts['path'] ) && '' !== $parts['path'] ? (string) $parts['path'] : '/';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return $scheme . '://' . $host . ':' . $port . $path . $query;
	}

	/**
	 * Remove a URL fragment before a URL is requested or returned for queueing.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function strip_fragment( $url ) {
		$fragment_position = strpos( $url, '#' );

		return false === $fragment_position ? $url : substr( $url, 0, $fragment_position );
	}

	/**
	 * Read post type filtering once for the entire traversal.
	 *
	 * @return array{configured:bool,selected:array}
	 */
	private function get_post_type_selection() {
		$options = get_option( 'simply-static' );
		$options = is_array( $options ) ? $options : array();

		$configured = isset( $options['post_types'] )
			&& is_array( $options['post_types'] )
			&& ( ! empty( $options['post_types_configured'] ) || ! empty( $options['post_types'] ) );

		return array(
			'configured' => $configured,
			'selected'   => $configured ? $options['post_types'] : array(),
		);
	}

	/**
	 * Apply post type selection to a local page URL.
	 *
	 * @param string $page_url  Local page URL.
	 * @param array  $post_types Post type selection data.
	 *
	 * @return bool
	 */
	private function should_include_page_url( $page_url, $post_types ) {
		if ( empty( $post_types['configured'] ) ) {
			return true;
		}

		$post_id = url_to_postid( $page_url );
		if ( ! $post_id ) {
			// Preserve existing behavior for archives and other URLs that cannot be
			// resolved to an individual post.
			return true;
		}

		return in_array( get_post_type( $post_id ), $post_types['selected'], true );
	}

	/**
	 * Return a positive, filterable integer limit.
	 *
	 * @param string $filter  Filter name.
	 * @param int    $default Default value.
	 *
	 * @return int
	 */
	private function get_positive_limit( $filter, $default ) {
		$value = (int) apply_filters( $filter, $default );

		return $value > 0 ? $value : $default;
	}

	/**
	 * Return a non-negative, filterable integer limit.
	 *
	 * @param string $filter  Filter name.
	 * @param int    $default Default value.
	 *
	 * @return int
	 */
	private function get_non_negative_limit( $filter, $default ) {
		$value = (int) apply_filters( $filter, $default );

		return $value >= 0 ? $value : $default;
	}

	/**
	 * Get the bounded HTTP timeout.
	 *
	 * @return float
	 */
	private function get_request_timeout() {
		$timeout = (float) apply_filters( 'simply_static_sitemap_request_timeout', 10 );

		return $timeout > 0 ? $timeout : 10;
	}

	/**
	 * Get the bounded total traversal time budget.
	 *
	 * @return float
	 */
	private function get_max_scan_time() {
		$seconds = (float) apply_filters( 'simply_static_sitemap_max_scan_seconds', 30 );

		return max( 1, min( 300, $seconds ) );
	}

	/**
	 * Clock seam for deterministic traversal-budget tests.
	 *
	 * @return float
	 */
	protected function now() {
		return microtime( true );
	}
}
