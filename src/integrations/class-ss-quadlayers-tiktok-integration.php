<?php

namespace Simply_Static;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Convert QuadLayers TikTok Feed widgets into self-contained static snapshots.
 *
 * QuadLayers renders its feed by POSTing the widget settings to a WordPress REST
 * endpoint in the browser. That endpoint cannot work once WordPress is offline.
 * This integration performs the POST while exporting, stores a last-known-good
 * manifest and its thumbnails in uploads, and replaces the runtime widget with
 * plain HTML whose local assets are included in the export queue.
 */
class Quadlayers_Tiktok_Integration extends Integration {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'quadlayers-tiktok';

	/**
	 * The integration is inexpensive when no matching widget exists and should
	 * keep working across QuadLayers constant/class name changes.
	 *
	 * @var bool
	 */
	protected $always_active = true;

	/**
	 * Automatic compatibility integration; there is no user-facing setting.
	 *
	 * @var bool
	 */
	protected $hidden = true;

	/**
	 * Snapshots already resolved during the current PHP process.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	protected $snapshots = array();

	/**
	 * QuadLayers frontend REST routes by feed source.
	 *
	 * @var array<string,string>
	 */
	protected $routes = array(
		'account'  => 'quadlayers/tiktok/frontend/user-video-list',
		'hashtag'  => 'quadlayers/tiktok/frontend/hashtag-video-list',
		'trending' => 'quadlayers/tiktok/frontend/trending-video-list',
		'username' => 'quadlayers/tiktok/frontend/external-user-video-list',
	);

	public function __construct() {
		$this->name        = __( 'QuadLayers TikTok Feed', 'simply-static' );
		$this->description = __( 'Replaces dynamic TikTok feeds with static, locally cached snapshots.', 'simply-static' );
	}

	/**
	 * Register the export-time DOM transformation.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_extract_and_replace_urls_in_html', array( $this, 'snapshot_feeds' ), 20, 2 );
	}

	/**
	 * Replace every QuadLayers feed container in a document.
	 *
	 * @param DOMDocument $dom       Parsed page document.
	 * @param Url_Extractor|object $extractor URL extractor for queuing local media.
	 * @return void
	 */
	public function snapshot_feeds( $dom, $extractor ) {
		if ( ! $dom instanceof DOMDocument ) {
			return;
		}

		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " tiktok-feed-feed ")]' );
		if ( ! $nodes || 0 === $nodes->length ) {
			return;
		}

		// DOMNodeList is live while nodes are replaced, so iterate over a copy.
		$feed_nodes = array();
		foreach ( $nodes as $node ) {
			if ( $node instanceof DOMElement ) {
				$feed_nodes[] = $node;
			}
		}

		$replaced = false;
		foreach ( $feed_nodes as $feed_node ) {
			$settings = $this->decode_feed_settings( $feed_node->getAttribute( 'data-feed' ) );
			$snapshot = is_array( $settings ) ? $this->get_snapshot( $settings ) : array();
			$static   = $this->build_static_feed( $dom, $feed_node, $settings, $snapshot, $extractor );

			if ( $feed_node->parentNode && $static instanceof DOMElement ) {
				$feed_node->parentNode->replaceChild( $static, $feed_node );
				$replaced = true;
			}
		}

		if ( $replaced ) {
			$this->add_snapshot_styles( $dom );
		}
	}

	/**
	 * Decode the widget's HTML-encoded JSON settings.
	 *
	 * @param string $value data-feed value.
	 * @return array<string,mixed>|null
	 */
	protected function decode_feed_settings( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		// Url_Extractor protects entities inside JSON attributes before building
		// the DOM and restores them after this integration hook runs. Restore the
		// same fixed tokens here so the intermediate DOM value is valid JSON.
		$value = strtr(
			$value,
			array(
				'QUOTE_PLACEHOLDER'     => '&quot;',
				'APOS_PLACEHOLDER'      => '&apos;',
				'LESSTHAN_PLACEHOLDER'  => '&lt;',
				'GREATTHAN_PLACEHOLDER' => '&gt;',
				'AMPERSAND_PLACEHOLDER' => '&amp;',
			)
		);
		$value   = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );
		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Resolve a current or last-known-good feed snapshot.
	 *
	 * @param array<string,mixed> $settings Feed settings.
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_snapshot( $settings ) {
		$feed_hash = $this->feed_hash( $settings );
		if ( isset( $this->snapshots[ $feed_hash ] ) ) {
			return $this->snapshots[ $feed_hash ];
		}

		$location = $this->snapshot_location( $feed_hash );
		$manifest = $this->read_manifest( $location );

		// Background exports can span several HTTP requests. Reuse a snapshot
		// captured for this exact export instead of repeatedly hitting TikTok.
		if ( $this->manifest_is_current( $manifest ) ) {
			$this->snapshots[ $feed_hash ] = $this->manifest_items( $manifest, $location );

			return $this->snapshots[ $feed_hash ];
		}

		$items = $this->request_feed_items( $settings );
		if ( is_wp_error( $items ) || empty( $items ) ) {
			if ( is_wp_error( $items ) ) {
				Util::debug_log( 'QuadLayers TikTok snapshot: ' . $items->get_error_message() );
			}

			$this->snapshots[ $feed_hash ] = $this->manifest_items( $manifest, $location );

			return $this->snapshots[ $feed_hash ];
		}

		$prior_items = $this->index_items_by_id( $this->manifest_items( $manifest, $location ) );
		$new_items   = array();
		foreach ( $items as $item ) {
			$cached_image = '';
			if ( isset( $prior_items[ $item['id'] ]['image'] ) ) {
				$cached_image = $prior_items[ $item['id'] ]['image'];
			}

			$item['image'] = $this->cache_thumbnail( $item, $location, $cached_image );
			unset( $item['cover_image_url'] );
			$new_items[] = $item;
		}

		$new_manifest = array(
			'version'      => 1,
			'captured_at'  => time(),
			'export_token' => $this->current_export_token(),
			'items'        => $new_items,
		);
		$this->write_manifest( $location, $new_manifest );

		$this->snapshots[ $feed_hash ] = $this->manifest_items( $new_manifest, $location );

		return $this->snapshots[ $feed_hash ];
	}

	/**
	 * Request feed data from the same local REST route used by the widget.
	 *
	 * @param array<string,mixed> $settings Feed settings.
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	protected function request_feed_items( $settings ) {
		$source = isset( $settings['source'] ) && is_scalar( $settings['source'] ) ? sanitize_key( $settings['source'] ) : 'account';
		if ( ! isset( $this->routes[ $source ] ) ) {
			return new \WP_Error( 'ss_tiktok_unknown_source', __( 'The TikTok feed source is not supported.', 'simply-static' ) );
		}

		$route = $this->routes[ $source ];
		$url   = function_exists( 'rest_url' )
			? rest_url( $route )
			: home_url( '/wp-json/' . $route );

		$max_bytes = max( 1024, (int) apply_filters( 'simply_static_tiktok_response_max_bytes', 2 * 1024 * 1024 ) );
		$payload = array(
			'feedSettings' => $settings,
			'createTime'   => '',
		);
		$body    = wp_json_encode( $payload );
		if ( false === $body ) {
			return new \WP_Error( 'ss_tiktok_encode_failed', __( 'The TikTok feed settings could not be encoded.', 'simply-static' ) );
		}

		// Prefer an in-process REST dispatch. Managed WordPress environments may
		// deliberately block container-to-public-host loopback requests even while
		// the same frontend endpoint is reachable from a visitor's browser.
		$decoded = $this->request_feed_items_from_local_rest( $route, $body, $max_bytes );
		if ( is_wp_error( $decoded ) ) {
			return $decoded;
		}
		if ( null === $decoded ) {
			$response = $this->auth_remote_post(
				$url,
				array(
					'timeout'             => max( 1, (int) apply_filters( 'simply_static_tiktok_request_timeout', 20 ) ),
					'headers'             => array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					'body'                => $body,
					'data_format'         => 'body',
					'limit_response_size' => $max_bytes,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return new \WP_Error( 'ss_tiktok_http_error', __( 'The TikTok feed endpoint returned an error.', 'simply-static' ) );
			}

			$response_body = wp_remote_retrieve_body( $response );
			if ( ! is_string( $response_body ) || '' === $response_body || strlen( $response_body ) > $max_bytes ) {
				return new \WP_Error( 'ss_tiktok_invalid_response', __( 'The TikTok feed endpoint returned an invalid response.', 'simply-static' ) );
			}

			$decoded = json_decode( $response_body, true );
		}
		if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
			$decoded = $decoded['data'];
		}
		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'ss_tiktok_invalid_json', __( 'The TikTok feed endpoint did not return valid JSON.', 'simply-static' ) );
		}

		return $this->normalize_feed_items( $decoded, $settings );
	}

	/**
	 * Dispatch the fixed QuadLayers route inside WordPress when the REST server
	 * is available. A null result means callers should use the HTTP fallback.
	 *
	 * @param string $route     REST route without a leading slash.
	 * @param string $body      JSON request body.
	 * @param int    $max_bytes Maximum serialized response size.
	 * @return mixed|\WP_Error|null
	 */
	protected function request_feed_items_from_local_rest( $route, $body, $max_bytes ) {
		if ( ! function_exists( 'rest_do_request' ) || ! class_exists( '\\WP_REST_Request' ) ) {
			return null;
		}

		$request = new \WP_REST_Request( 'POST', '/' . ltrim( $route, '/' ) );
		$request->set_header( 'Accept', 'application/json' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( $body );
		$response = rest_do_request( $request );

		// QuadLayers permits administrators or open IDs saved in its own feed
		// model. A widget can still contain a valid connected account even when
		// that feed-model record no longer exists, and background exports do not
		// run as the administrator used by HTTP Basic Auth. In that one case,
		// invoke only QuadLayers' registered frontend callback directly. The route
		// remains allowlisted and the request body still comes from the exported
		// page; no arbitrary REST permission callback is bypassed.
		if ( $this->is_rest_permission_failure( $response ) ) {
			$direct_response = $this->request_feed_items_from_registered_callback( $route, $request );
			if ( null !== $direct_response ) {
				$response = $direct_response;
			}
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( is_array( $response ) ) {
			$data   = $response;
			$status = 200;
		} elseif ( is_object( $response ) && method_exists( $response, 'get_status' ) && method_exists( $response, 'get_data' ) ) {
			$data   = $response->get_data();
			$status = (int) $response->get_status();
		} else {
			return new \WP_Error( 'ss_tiktok_invalid_response', __( 'The TikTok feed endpoint returned an invalid response.', 'simply-static' ) );
		}
		if ( 404 === $status && is_array( $data ) && 'rest_no_route' === ( $data['code'] ?? '' ) ) {
			return null;
		}
		if ( 200 !== $status ) {
			return new \WP_Error( 'ss_tiktok_http_error', __( 'The TikTok feed endpoint returned an error.', 'simply-static' ) );
		}

		$encoded = wp_json_encode( $data );
		if ( false === $encoded || strlen( $encoded ) > $max_bytes ) {
			return new \WP_Error( 'ss_tiktok_invalid_response', __( 'The TikTok feed endpoint returned an invalid response.', 'simply-static' ) );
		}

		return $data;
	}

	/**
	 * Whether an internal REST dispatch was rejected by its permission callback.
	 *
	 * @param mixed $response REST response.
	 * @return bool
	 */
	protected function is_rest_permission_failure( $response ) {
		if ( ! is_object( $response ) || ! method_exists( $response, 'get_status' ) || ! method_exists( $response, 'get_data' ) ) {
			return false;
		}

		$status = (int) $response->get_status();
		$data   = $response->get_data();

		return in_array( $status, array( 401, 403 ), true )
			&& is_array( $data )
			&& 'rest_forbidden' === ( $data['code'] ?? '' );
	}

	/**
	 * Invoke the callback registered for one of the fixed QuadLayers routes.
	 *
	 * @param string           $route   REST route without a leading slash.
	 * @param \WP_REST_Request $request Prepared request.
	 * @return mixed|null
	 */
	protected function request_feed_items_from_registered_callback( $route, $request ) {
		if ( ! in_array( $route, $this->routes, true ) || ! function_exists( 'rest_get_server' ) ) {
			return null;
		}

		$server = rest_get_server();
		if ( ! is_object( $server ) || ! method_exists( $server, 'get_routes' ) ) {
			return null;
		}

		$routes   = $server->get_routes();
		$handlers = $routes[ '/' . ltrim( $route, '/' ) ] ?? array();
		foreach ( $handlers as $handler ) {
			$callback = is_array( $handler ) ? ( $handler['callback'] ?? null ) : null;
			if ( ! is_array( $callback ) || ! isset( $callback[0], $callback[1] ) || ! is_object( $callback[0] ) || 'callback' !== $callback[1] || ! is_callable( $callback ) ) {
				continue;
			}

			$class = ltrim( get_class( $callback[0] ), '\\' );
			if ( 0 !== strpos( $class, 'QuadLayers\\TTF\\Api\\Rest\\Endpoints\\Frontend\\' ) ) {
				continue;
			}

			return call_user_func( $callback, $request );
		}

		return null;
	}

	/**
	 * Normalize and bound untrusted endpoint data before persisting or rendering.
	 *
	 * @param array<int,mixed>    $items Feed response.
	 * @param array<string,mixed> $settings Feed settings.
	 * @return array<int,array<string,mixed>>
	 */
	protected function normalize_feed_items( $items, $settings ) {
		$max_items = max( 1, min( 100, (int) apply_filters( 'simply_static_tiktok_max_items', 24 ) ) );
		if ( isset( $settings['limit'] ) && is_scalar( $settings['limit'] ) && (int) $settings['limit'] > 0 ) {
			$max_items = min( $max_items, (int) $settings['limit'] );
		}

		$normalized = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id        = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';
			$share_url = $this->sanitize_tiktok_url( isset( $item['share_url'] ) ? $item['share_url'] : '' );
			if ( '' === $id || '' === $share_url ) {
				continue;
			}

			$title = isset( $item['title'] ) && is_scalar( $item['title'] ) && '' !== trim( (string) $item['title'] )
				? $item['title']
				: ( isset( $item['video_description'] ) ? $item['video_description'] : '' );
			$normalized[] = array(
				'id'              => substr( $id, 0, 128 ),
				'share_url'       => $share_url,
				'title'           => $this->bounded_text( $title, 300 ),
				'likes_count'     => isset( $item['likes_count'] ) ? absint( $item['likes_count'] ) : 0,
				'comments_count'  => isset( $item['comments_count'] ) ? absint( $item['comments_count'] ) : 0,
				'views_count'     => isset( $item['views_count'] ) ? absint( $item['views_count'] ) : 0,
				'width'           => isset( $item['width'] ) ? absint( $item['width'] ) : 0,
				'height'          => isset( $item['height'] ) ? absint( $item['height'] ) : 0,
				'cover_image_url' => $this->sanitize_media_url( isset( $item['cover_image_url'] ) ? $item['cover_image_url'] : '' ),
			);

			if ( count( $normalized ) >= $max_items ) {
				break;
			}
		}

		return $normalized;
	}

	/**
	 * Cache one remote thumbnail and return its safe basename.
	 *
	 * @param array<string,mixed> $item         Normalized item.
	 * @param array<string,string> $location    Snapshot directory information.
	 * @param string               $prior_image Existing basename for this video.
	 * @return string
	 */
	protected function cache_thumbnail( $item, $location, $prior_image = '' ) {
		if ( empty( $location['directory'] ) || empty( $item['cover_image_url'] ) ) {
			return $this->existing_image( $location, $prior_image );
		}

		$existing = $this->existing_image( $location, $prior_image );
		if ( '' !== $existing ) {
			return $existing;
		}

		$url = $item['cover_image_url'];
		if ( ! function_exists( 'wp_safe_remote_get' ) ) {
			return '';
		}

		$max_bytes = max( 1024, (int) apply_filters( 'simply_static_tiktok_image_max_bytes', 2 * 1024 * 1024 ) );
		$response  = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => max( 1, (int) apply_filters( 'simply_static_tiktok_image_timeout', 20 ) ),
				'redirection'         => 3,
				'sslverify'           => true,
				'headers'             => array( 'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/gif' ),
				'limit_response_size' => $max_bytes,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$image = wp_remote_retrieve_body( $response );
		if ( ! is_string( $image ) || '' === $image || strlen( $image ) > $max_bytes ) {
			return '';
		}

		$extension = $this->image_extension( $image );
		if ( '' === $extension ) {
			return '';
		}

		$basename = sanitize_file_name( $item['id'] );
		if ( '' === $basename || strlen( $basename ) > 80 ) {
			$basename = substr( hash( 'sha256', $item['id'] ), 0, 24 );
		}
		$basename .= '.' . $extension;
		$target    = trailingslashit( $location['directory'] ) . $basename;
		$temp      = tempnam( $location['directory'], 'ss-tiktok-' );

		if ( false === $temp ) {
			return '';
		}

		$written = @file_put_contents( $temp, $image );
		if ( strlen( $image ) !== $written || ! @rename( $temp, $target ) ) {
			@unlink( $temp );

			return '';
		}

		return $basename;
	}

	/**
	 * Detect supported raster formats by signature, not remote headers.
	 *
	 * @param string $image Image bytes.
	 * @return string
	 */
	protected function image_extension( $image ) {
		if ( 0 === strncmp( $image, "\xFF\xD8\xFF", 3 ) ) {
			return 'jpg';
		}
		if ( 0 === strncmp( $image, "\x89PNG\r\n\x1A\n", 8 ) ) {
			return 'png';
		}
		if ( 0 === strncmp( $image, 'GIF87a', 6 ) || 0 === strncmp( $image, 'GIF89a', 6 ) ) {
			return 'gif';
		}
		if ( strlen( $image ) >= 12 && 'RIFF' === substr( $image, 0, 4 ) && 'WEBP' === substr( $image, 8, 4 ) ) {
			return 'webp';
		}
		if ( strlen( $image ) >= 12 && 'ftyp' === substr( $image, 4, 4 ) && in_array( substr( $image, 8, 4 ), array( 'avif', 'avis' ), true ) ) {
			return 'avif';
		}

		return '';
	}

	/**
	 * Build the static feed DOM.
	 *
	 * @param DOMDocument             $dom        Document.
	 * @param DOMElement              $original   Dynamic feed node.
	 * @param array<string,mixed>|null $settings   Feed settings.
	 * @param array<int,array<string,mixed>> $items Snapshot items.
	 * @param Url_Extractor|object    $extractor  URL extractor.
	 * @return DOMElement
	 */
	protected function build_static_feed( $dom, $original, $settings, $items, $extractor ) {
		$container = $dom->createElement( 'div' );
		$columns   = is_array( $settings ) && isset( $settings['columns'] ) && is_scalar( $settings['columns'] ) ? (int) $settings['columns'] : 3;
		$columns   = max( 1, min( 6, $columns ) );
		$layout    = is_array( $settings ) && isset( $settings['layout'] ) && is_scalar( $settings['layout'] ) ? sanitize_key( $settings['layout'] ) : 'gallery';
		$layout    = 'carousel' === $layout ? 'carousel' : 'gallery';

		$container->setAttribute( 'class', 'ss-tiktok-feed ss-tiktok-feed--layout-' . $layout . ' ss-tiktok-feed--columns-' . $columns );
		$container->setAttribute( 'data-ss-tiktok-snapshot', '1' );
		if ( $original->hasAttribute( 'id' ) ) {
			$container->setAttribute( 'id', $original->getAttribute( 'id' ) );
		}

		if ( empty( $items ) ) {
			$container->setAttribute( 'class', 'ss-tiktok-feed ss-tiktok-feed--unavailable' );
			$message = $dom->createElement( 'p' );
			$message->appendChild( $dom->createTextNode( __( 'TikTok posts are temporarily unavailable.', 'simply-static' ) ) );
			$container->appendChild( $message );

			return $container;
		}

		foreach ( $items as $item ) {
			$link = $dom->createElement( 'a' );
			$link->setAttribute( 'class', 'ss-tiktok-feed__item' );
			$link->setAttribute( 'href', $item['share_url'] );
			$link->setAttribute( 'target', '_blank' );
			$link->setAttribute( 'rel', 'noopener noreferrer' );
			$link->setAttribute( 'aria-label', '' !== $item['title'] ? $item['title'] : __( 'View TikTok video', 'simply-static' ) );

			$media = $dom->createElement( 'span' );
			$media->setAttribute( 'class', 'ss-tiktok-feed__media' );
			if ( ! empty( $item['image_url'] ) ) {
				$image_url = $item['image_url'];
				if ( is_object( $extractor ) && method_exists( $extractor, 'add_to_extracted_urls' ) ) {
					$image_url = $extractor->add_to_extracted_urls( $image_url );
				}

				$image = $dom->createElement( 'img' );
				$image->setAttribute( 'src', $image_url );
				$image->setAttribute( 'alt', '' !== $item['title'] ? $item['title'] : __( 'TikTok video', 'simply-static' ) );
				$image->setAttribute( 'loading', 'lazy' );
				$image->setAttribute( 'decoding', 'async' );
				if ( ! empty( $item['width'] ) && ! empty( $item['height'] ) ) {
					$image->setAttribute( 'width', (string) $item['width'] );
					$image->setAttribute( 'height', (string) $item['height'] );
				}
				$media->appendChild( $image );
			}

			$play = $dom->createElement( 'span' );
			$play->setAttribute( 'class', 'ss-tiktok-feed__play' );
			$play->setAttribute( 'aria-hidden', 'true' );
			$play->appendChild( $dom->createTextNode( "\u{25B6}" ) );
			$media->appendChild( $play );
			$link->appendChild( $media );

			$stats = $this->build_stats( $dom, $item, $settings );
			if ( $stats ) {
				$link->appendChild( $stats );
			}
			$container->appendChild( $link );
		}

		return $container;
	}

	/**
	 * Build a compact engagement overlay.
	 *
	 * @param DOMDocument             $dom  Document.
	 * @param array<string,mixed>      $item     Snapshot item.
	 * @param array<string,mixed>|null $settings Feed settings.
	 * @return DOMElement|null
	 */
	protected function build_stats( $dom, $item, $settings ) {
		$mask = is_array( $settings ) && isset( $settings['mask'] ) && is_array( $settings['mask'] ) ? $settings['mask'] : array();
		if ( isset( $mask['display'] ) && ! $this->setting_enabled( $mask['display'] ) ) {
			return null;
		}

		$values = array();
		if ( ! empty( $item['likes_count'] ) && ( ! isset( $mask['likes_count'] ) || $this->setting_enabled( $mask['likes_count'] ) ) ) {
			$values[] = "\u{2665} " . $this->compact_number( $item['likes_count'] );
		}
		if ( ! empty( $item['comments_count'] ) && ( ! isset( $mask['comments_count'] ) || $this->setting_enabled( $mask['comments_count'] ) ) ) {
			$values[] = "\u{1F4AC} " . $this->compact_number( $item['comments_count'] );
		}

		if ( empty( $values ) ) {
			return null;
		}

		$stats = $dom->createElement( 'span' );
		$stats->setAttribute( 'class', 'ss-tiktok-feed__stats' );
		$stats->appendChild( $dom->createTextNode( implode( '  ', $values ) ) );

		return $stats;
	}

	/**
	 * Normalize boolean-like values emitted by plugin settings.
	 *
	 * @param mixed $value Setting value.
	 * @return bool
	 */
	protected function setting_enabled( $value ) {
		return ! in_array( $value, array( false, 0, '0', 'false', 'off', 'no', '' ), true );
	}

	/**
	 * Add one small stylesheet for every page containing a snapshot.
	 *
	 * @param DOMDocument $dom Document.
	 * @return void
	 */
	protected function add_snapshot_styles( $dom ) {
		if ( $dom->getElementById( 'simply-static-tiktok-snapshot-css' ) ) {
			return;
		}

		$style = $dom->createElement( 'style' );
		$style->setAttribute( 'id', 'simply-static-tiktok-snapshot-css' );
		$style->appendChild(
			$dom->createTextNode(
				'.ss-tiktok-feed{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}'
				. '.ss-tiktok-feed--columns-1{grid-template-columns:repeat(1,minmax(0,1fr))}'
				. '.ss-tiktok-feed--columns-2{grid-template-columns:repeat(2,minmax(0,1fr))}'
				. '.ss-tiktok-feed--columns-4{grid-template-columns:repeat(4,minmax(0,1fr))}'
				. '.ss-tiktok-feed--columns-5{grid-template-columns:repeat(5,minmax(0,1fr))}'
				. '.ss-tiktok-feed--columns-6{grid-template-columns:repeat(6,minmax(0,1fr))}'
				. '.ss-tiktok-feed--layout-carousel{display:flex;overflow-x:auto;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch}'
				. '.ss-tiktok-feed--layout-carousel .ss-tiktok-feed__item{flex:0 0 calc((100% - 24px)/3);scroll-snap-align:start}'
				. '.ss-tiktok-feed--layout-carousel.ss-tiktok-feed--columns-1 .ss-tiktok-feed__item{flex-basis:100%}'
				. '.ss-tiktok-feed--layout-carousel.ss-tiktok-feed--columns-2 .ss-tiktok-feed__item{flex-basis:calc((100% - 12px)/2)}'
				. '.ss-tiktok-feed--layout-carousel.ss-tiktok-feed--columns-4 .ss-tiktok-feed__item{flex-basis:calc((100% - 36px)/4)}'
				. '.ss-tiktok-feed--layout-carousel.ss-tiktok-feed--columns-5 .ss-tiktok-feed__item{flex-basis:calc((100% - 48px)/5)}'
				. '.ss-tiktok-feed--layout-carousel.ss-tiktok-feed--columns-6 .ss-tiktok-feed__item{flex-basis:calc((100% - 60px)/6)}'
				. '.ss-tiktok-feed__item{position:relative;display:block;overflow:hidden;border-radius:4px;color:#fff;background:#111;text-decoration:none}'
				. '.ss-tiktok-feed__media{display:block;position:relative;aspect-ratio:9/16;overflow:hidden}'
				. '.ss-tiktok-feed__media img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .2s ease}'
				. '.ss-tiktok-feed__item:hover .ss-tiktok-feed__media img{transform:scale(1.025)}'
				. '.ss-tiktok-feed__play{position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%);font-size:30px;line-height:1;text-shadow:0 1px 8px rgba(0,0,0,.65)}'
				. '.ss-tiktok-feed__stats{position:absolute;right:8px;bottom:7px;left:8px;font-size:12px;font-weight:600;text-shadow:0 1px 4px #000}'
				. '.ss-tiktok-feed--unavailable{display:block;padding:1rem;text-align:center;background:#f5f5f5;color:#333}'
				. '.ss-tiktok-feed--unavailable p{margin:0}'
				. '@media(max-width:767px){.ss-tiktok-feed{grid-template-columns:repeat(2,minmax(0,1fr))}.ss-tiktok-feed--layout-carousel .ss-tiktok-feed__item{flex-basis:calc((100% - 12px)/2)}}'
				. '@media(max-width:479px){.ss-tiktok-feed{grid-template-columns:1fr}.ss-tiktok-feed--layout-carousel .ss-tiktok-feed__item{flex-basis:100%}}'
			)
		);

		$heads = $dom->getElementsByTagName( 'head' );
		if ( $heads->length > 0 ) {
			$heads->item( 0 )->appendChild( $style );
		} elseif ( $dom->documentElement ) {
			$dom->documentElement->insertBefore( $style, $dom->documentElement->firstChild );
		}
	}

	/**
	 * Return the persistent cache directory and URL for a feed.
	 *
	 * @param string $feed_hash Feed fingerprint.
	 * @return array<string,string>
	 */
	protected function snapshot_location( $feed_hash ) {
		$uploads = wp_upload_dir();
		if ( ! is_array( $uploads ) || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return array();
		}

		$relative  = 'simply-static/tiktok/' . $feed_hash;
		$directory = trailingslashit( $uploads['basedir'] ) . $relative;
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			return array();
		}

		return array(
			'directory' => $directory,
			'url'       => trailingslashit( $uploads['baseurl'] ) . $relative,
			'manifest'  => trailingslashit( $directory ) . 'snapshot.json',
		);
	}

	/**
	 * Read and validate a snapshot manifest.
	 *
	 * @param array<string,string> $location Snapshot location.
	 * @return array<string,mixed>
	 */
	protected function read_manifest( $location ) {
		if ( empty( $location['manifest'] ) || ! is_file( $location['manifest'] ) || is_link( $location['manifest'] ) ) {
			return array();
		}

		$max_bytes = max( 1024, (int) apply_filters( 'simply_static_tiktok_manifest_max_bytes', 512 * 1024 ) );
		$size      = filesize( $location['manifest'] );
		if ( false === $size || $size <= 0 || $size > $max_bytes ) {
			return array();
		}

		$contents = @file_get_contents( $location['manifest'] );
		$manifest = is_string( $contents ) ? json_decode( $contents, true ) : null;

		return is_array( $manifest ) && isset( $manifest['items'] ) && is_array( $manifest['items'] ) ? $manifest : array();
	}

	/**
	 * Atomically persist a last-known-good manifest.
	 *
	 * @param array<string,string> $location Snapshot location.
	 * @param array<string,mixed>  $manifest Manifest data.
	 * @return bool
	 */
	protected function write_manifest( $location, $manifest ) {
		if ( empty( $location['directory'] ) || empty( $location['manifest'] ) ) {
			return false;
		}

		$json = wp_json_encode( $manifest );
		$temp = tempnam( $location['directory'], 'ss-tiktok-' );
		if ( false === $json || false === $temp ) {
			return false;
		}

		$written = @file_put_contents( $temp, $json );
		if ( strlen( $json ) !== $written || ! @rename( $temp, $location['manifest'] ) ) {
			@unlink( $temp );

			return false;
		}

		return true;
	}

	/**
	 * Validate manifest items and attach current local upload URLs.
	 *
	 * @param array<string,mixed>  $manifest Manifest.
	 * @param array<string,string> $location Snapshot location.
	 * @return array<int,array<string,mixed>>
	 */
	protected function manifest_items( $manifest, $location ) {
		if ( empty( $manifest['items'] ) || ! is_array( $manifest['items'] ) ) {
			return array();
		}

		$items = array();
		foreach ( $manifest['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id        = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';
			$share_url = $this->sanitize_tiktok_url( isset( $item['share_url'] ) ? $item['share_url'] : '' );
			if ( '' === $id || '' === $share_url ) {
				continue;
			}

			$image     = isset( $item['image'] ) ? $this->existing_image( $location, $item['image'] ) : '';
			$image_url = '' !== $image && ! empty( $location['url'] ) ? trailingslashit( $location['url'] ) . rawurlencode( $image ) : '';
			$items[]   = array(
				'id'             => substr( $id, 0, 128 ),
				'share_url'      => $share_url,
				'title'          => $this->bounded_text( isset( $item['title'] ) ? $item['title'] : '', 300 ),
				'likes_count'    => isset( $item['likes_count'] ) ? absint( $item['likes_count'] ) : 0,
				'comments_count' => isset( $item['comments_count'] ) ? absint( $item['comments_count'] ) : 0,
				'views_count'    => isset( $item['views_count'] ) ? absint( $item['views_count'] ) : 0,
				'width'          => isset( $item['width'] ) ? absint( $item['width'] ) : 0,
				'height'         => isset( $item['height'] ) ? absint( $item['height'] ) : 0,
				'image'          => $image,
				'image_url'      => $image_url,
			);
		}

		return $items;
	}

	/**
	 * Check whether a manifest was captured by the current export process.
	 *
	 * @param array<string,mixed> $manifest Manifest.
	 * @return bool
	 */
	protected function manifest_is_current( $manifest ) {
		if ( empty( $manifest['items'] ) ) {
			return false;
		}

		$token = $this->current_export_token();
		if ( '' !== $token ) {
			return isset( $manifest['export_token'] ) && is_scalar( $manifest['export_token'] ) && hash_equals( $token, (string) $manifest['export_token'] );
		}

		$freshness = max( 0, (int) apply_filters( 'simply_static_tiktok_snapshot_freshness', 10 * MINUTE_IN_SECONDS ) );

		return $freshness > 0
			&& ! empty( $manifest['captured_at'] )
			&& is_scalar( $manifest['captured_at'] )
			&& (int) $manifest['captured_at'] >= time() - $freshness;
	}

	/**
	 * Create a token shared by all workers in one export.
	 *
	 * @return string
	 */
	protected function current_export_token() {
		$started = Options::instance()->get( 'archive_start_time' );
		if ( ! is_scalar( $started ) || '' === (string) $started ) {
			return '';
		}

		return hash( 'sha256', (string) $started );
	}

	/**
	 * Return an existing safe image basename.
	 *
	 * @param array<string,string> $location Snapshot location.
	 * @param string               $basename Candidate basename.
	 * @return string
	 */
	protected function existing_image( $location, $basename ) {
		$basename = is_string( $basename ) ? $basename : '';
		if (
			empty( $location['directory'] )
			|| '' === $basename
			|| basename( $basename ) !== $basename
			|| ! preg_match( '/^[A-Za-z0-9._-]+\.(?:jpe?g|png|gif|webp|avif)$/i', $basename )
		) {
			return '';
		}

		$file = trailingslashit( $location['directory'] ) . $basename;

		return is_file( $file ) && ! is_link( $file ) && filesize( $file ) > 0 ? $basename : '';
	}

	/**
	 * Index snapshot items by video ID.
	 *
	 * @param array<int,array<string,mixed>> $items Items.
	 * @return array<string,array<string,mixed>>
	 */
	protected function index_items_by_id( $items ) {
		$indexed = array();
		foreach ( $items as $item ) {
			if ( ! empty( $item['id'] ) ) {
				$indexed[ $item['id'] ] = $item;
			}
		}

		return $indexed;
	}

	/**
	 * Stable feed fingerprint without persisting access tokens.
	 *
	 * @param array<string,mixed> $settings Feed settings.
	 * @return string
	 */
	protected function feed_hash( $settings ) {
		$source   = isset( $settings['source'] ) && is_scalar( $settings['source'] ) ? sanitize_key( $settings['source'] ) : 'account';
		$identity = array( 'source' => $source );
		foreach ( array( 'id', 'open_id', 'username', 'hashtag' ) as $key ) {
			if ( isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ) {
				$identity[ $key ] = (string) $settings[ $key ];
			}
		}
		if ( isset( $settings['account'] ) && is_array( $settings['account'] ) ) {
			foreach ( array( 'id', 'open_id', 'username', 'unique_id' ) as $key ) {
				if ( isset( $settings['account'][ $key ] ) && is_scalar( $settings['account'][ $key ] ) ) {
					$identity[ 'account_' . $key ] = (string) $settings['account'][ $key ];
				}
			}
		}

		return substr( hash( 'sha256', wp_json_encode( $identity ) ), 0, 20 );
	}

	/**
	 * Allow only public HTTPS media URLs. wp_safe_remote_get performs the DNS/IP
	 * validation immediately before the request.
	 *
	 * @param mixed $url URL value.
	 * @return string
	 */
	protected function sanitize_media_url( $url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		$parts = wp_parse_url( trim( $url ) );
		$valid = is_array( $parts )
			&& isset( $parts['scheme'], $parts['host'] )
			&& 'https' === strtolower( $parts['scheme'] )
			&& empty( $parts['user'] )
			&& empty( $parts['pass'] )
			&& ( ! isset( $parts['port'] ) || 443 === (int) $parts['port'] );
		$valid = (bool) apply_filters( 'simply_static_tiktok_allowed_media_url', $valid, $url );

		return $valid ? esc_url_raw( trim( $url ) ) : '';
	}

	/**
	 * Restrict outbound links to TikTok HTTPS hosts.
	 *
	 * @param mixed $url URL value.
	 * @return string
	 */
	protected function sanitize_tiktok_url( $url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		$url   = trim( $url );
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || 'https' !== strtolower( $parts['scheme'] ) ) {
			return '';
		}
		if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) || ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) ) {
			return '';
		}

		$host = strtolower( rtrim( $parts['host'], '.' ) );
		if ( 'tiktok.com' !== $host && '.tiktok.com' !== substr( $host, - 11 ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	/**
	 * Sanitize and limit text without requiring mbstring.
	 *
	 * @param mixed $value  Text value.
	 * @param int   $length Maximum characters/bytes.
	 * @return string
	 */
	protected function bounded_text( $value, $length ) {
		$text = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );

		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $length ) : substr( $text, 0, $length );
	}

	/**
	 * Format an engagement count without depending on locale helpers.
	 *
	 * @param mixed $number Count.
	 * @return string
	 */
	protected function compact_number( $number ) {
		$number = absint( $number );
		if ( $number >= 1000000 ) {
			return rtrim( rtrim( number_format( $number / 1000000, 1, '.', '' ), '0' ), '.' ) . 'M';
		}
		if ( $number >= 1000 ) {
			return rtrim( rtrim( number_format( $number / 1000, 1, '.', '' ), '0' ), '.' ) . 'K';
		}

		return (string) $number;
	}
}
