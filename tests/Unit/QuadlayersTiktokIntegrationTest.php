<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use DOMDocument;
use Simply_Static\Integrations;
use Simply_Static\Options;
use Simply_Static\Quadlayers_Tiktok_Integration;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use QuadLayers\TTF\Api\Rest\Endpoints\Frontend\User_Video_List\Test_Callback as QuadlayersTestCallback;

$simply_static_root = dirname( __DIR__, 2 );
require_once $simply_static_root . '/src/class-ss-plugin.php';
require_once $simply_static_root . '/src/class-ss-options.php';
require_once $simply_static_root . '/src/class-ss-phpuri.php';
require_once $simply_static_root . '/src/class-ss-util.php';
require_once $simply_static_root . '/src/integrations/class-ss-integration.php';
require_once $simply_static_root . '/src/integrations/class-ss-quadlayers-tiktok-integration.php';
require_once $simply_static_root . '/src/class-ss-integrations.php';
require_once $simply_static_root . '/tests/Support/QuadlayersRestCallback.php';

final class TiktokSnapshotExtractor {
	/** @var string[] */
	public $urls = array();

	public function add_to_extracted_urls( string $url ): string {
		$this->urls[] = $url;

		return str_replace( 'https://example.test', 'https://static.example.test', $url );
	}
}

class TestableTiktokSnapshotIntegration extends Quadlayers_Tiktok_Integration {
	/** @var array<int,array<string,mixed>>|\WP_Error */
	public $feed_result;

	/** @var int */
	public $feed_requests = 0;

	/** @param array<int,array<string,mixed>>|\WP_Error $feed_result */
	public function __construct( $feed_result ) {
		parent::__construct();
		$this->feed_result = $feed_result;
	}

	protected function request_feed_items( $settings ) {
		$this->feed_requests++;

		return $this->feed_result;
	}

	/** @return array<int,array<string,mixed>> */
	public function snapshot( array $settings ): array {
		return $this->get_snapshot( $settings );
	}
}

final class ExposedTiktokIntegration extends Quadlayers_Tiktok_Integration {
	/** @return array<int,array<string,mixed>>|\WP_Error */
	public function request( array $settings ) {
		return $this->request_feed_items( $settings );
	}

	public function imageExtension( string $image ): string {
		return $this->image_extension( $image );
	}

	/** @return array<string,mixed>|null */
	public function decodeSettings( string $value ): ?array {
		return $this->decode_feed_settings( $value );
	}
}

final class InternalRestTiktokIntegration extends Quadlayers_Tiktok_Integration {
	/** @var mixed */
	public $local_response;

	/** @var array<int,mixed> */
	public $local_requests = array();

	/** @param mixed $local_response */
	public function __construct( $local_response ) {
		parent::__construct();
		$this->local_response = $local_response;
	}

	/** @return array<int,array<string,mixed>>|\WP_Error */
	public function request( array $settings ) {
		return $this->request_feed_items( $settings );
	}

	protected function request_feed_items_from_local_rest( $route, $body, $max_bytes ) {
		$this->local_requests[] = array( $route, $body, $max_bytes );

		return $this->local_response;
	}
}

final class QuadlayersTiktokIntegrationTest extends UnitTestCase {

	/** @var string */
	private $upload_root;

	protected function setUp(): void {
		parent::setUp();
		$this->upload_root = sys_get_temp_dir() . '/ss-tiktok-' . bin2hex( random_bytes( 8 ) );
		WpEnv::$upload_dir = array(
			'basedir' => $this->upload_root,
			'baseurl' => 'https://example.test/wp-content/uploads',
		);
		WpEnv::$options['simply-static'] = array(
			'archive_start_time' => '2026-09-07 18:00:00',
		);
		Options::reinstance();
	}

	protected function tearDown(): void {
		$this->removeDirectory( $this->upload_root );
		parent::tearDown();
	}

	public function test_dynamic_widgets_become_static_markup_with_local_queued_thumbnails(): void {
		$settings = array(
			'id'      => 'feed-1',
			'source'  => 'account',
			'layout'  => 'carousel',
			'columns' => 4,
			'limit'   => 8,
			'account' => array( 'open_id' => 'account-123' ),
		);
		$item = array(
			'id'              => 'video-123',
			'share_url'       => 'https://www.tiktok.com/@example/video/123',
			'title'           => 'A static TikTok post',
			'likes_count'     => 1200,
			'comments_count'  => 30,
			'views_count'     => 45600,
			'width'           => 720,
			'height'          => 1280,
			'cover_image_url' => 'https://cdn.example.net/video-123.jpg',
		);
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => "\xFF\xD8\xFF" . str_repeat( 'image', 20 ),
		);

		$integration = new TestableTiktokSnapshotIntegration( array( $item ) );
		$extractor   = new TiktokSnapshotExtractor();
		$document    = $this->feedDocument( $settings, 2 );

		$integration->run();
		do_action( 'ss_after_extract_and_replace_urls_in_html', $document, $extractor );
		$html = (string) $document->saveHTML();

		self::assertSame( 1, $integration->feed_requests, 'Duplicate widgets should reuse one in-process snapshot.' );
		self::assertCount( 1, WpEnv::$remote_requests, 'The thumbnail should only be downloaded once.' );
		self::assertSame( 'SAFE_GET', WpEnv::$remote_requests[0]['method'] );
		self::assertStringNotContainsString( 'tiktok-feed-feed', $html );
		self::assertStringNotContainsString( 'data-feed=', $html );
		self::assertSame( 2, substr_count( $html, 'data-ss-tiktok-snapshot="1"' ) );
		self::assertSame( 1, substr_count( $html, 'id="simply-static-tiktok-snapshot-css"' ) );
		self::assertStringContainsString( 'ss-tiktok-feed--columns-4', $html );
		self::assertStringContainsString( 'ss-tiktok-feed--layout-carousel', $html );
		self::assertStringContainsString( 'https://www.tiktok.com/@example/video/123', $html );
		self::assertStringContainsString( 'https://static.example.test/wp-content/uploads/simply-static/tiktok/', $html );
		self::assertCount( 2, $extractor->urls, 'Each rendered image reference is queued for export.' );
		self::assertSame( $extractor->urls[0], $extractor->urls[1] );
		self::assertFileExists( str_replace( 'https://example.test/wp-content/uploads', $this->upload_root, $extractor->urls[0] ) );
	}

	public function test_integration_is_registered_automatically(): void {
		$integrations = ( new Integrations() )->get_integrations();

		self::assertArrayHasKey( 'quadlayers-tiktok', $integrations );
		self::assertSame( Quadlayers_Tiktok_Integration::class, $integrations['quadlayers-tiktok'] );
		self::assertTrue( ( new Quadlayers_Tiktok_Integration() )->is_enabled() );
	}

	public function test_failed_refresh_uses_the_last_known_good_snapshot(): void {
		$settings = array(
			'id'      => 'feed-1',
			'source'  => 'account',
			'account' => array( 'open_id' => 'account-123' ),
		);
		$item = array(
			'id'              => 'video-123',
			'share_url'       => 'https://www.tiktok.com/@example/video/123',
			'title'           => 'Cached post',
			'likes_count'     => 10,
			'comments_count'  => 2,
			'views_count'     => 100,
			'width'           => 720,
			'height'          => 1280,
			'cover_image_url' => 'https://cdn.example.net/video-123.jpg',
		);
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => "\x89PNG\r\n\x1A\n" . str_repeat( 'image', 20 ),
		);

		$first    = new TestableTiktokSnapshotIntegration( array( $item ) );
		$snapshot = $first->snapshot( $settings );
		self::assertCount( 1, $snapshot );
		self::assertNotSame( '', $snapshot[0]['image_url'] );

		WpEnv::$options['simply-static']['archive_start_time'] = '2026-09-07 19:00:00';
		Options::reinstance();
		$second   = new TestableTiktokSnapshotIntegration( new \WP_Error( 'offline', 'TikTok is unavailable.' ) );
		$fallback = $second->snapshot( $settings );

		self::assertSame( 1, $second->feed_requests );
		self::assertCount( 1, $fallback );
		self::assertSame( 'Cached post', $fallback[0]['title'] );
		self::assertSame( $snapshot[0]['image_url'], $fallback[0]['image_url'] );
		self::assertFileExists( dirname( str_replace( 'https://example.test/wp-content/uploads', $this->upload_root, $fallback[0]['image_url'] ) ) . '/snapshot.json' );
	}

	public function test_local_rest_request_matches_quadlayers_frontend_contract(): void {
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					array(
						'id'              => 'video-1',
						'share_url'       => 'https://www.tiktok.com/@example/video/1',
						'title'           => 'Video',
						'cover_image_url' => 'https://cdn.example.net/video.jpg',
					),
				)
			),
		);
		$settings = array(
			'id'           => 'feed-1',
			'source'       => 'account',
			'access_token' => 'local-endpoint-only',
		);

		$result = ( new ExposedTiktokIntegration() )->request( $settings );

		self::assertIsArray( $result );
		self::assertCount( 1, $result );
		self::assertCount( 1, WpEnv::$remote_requests );
		$request = WpEnv::$remote_requests[0];
		self::assertSame( 'POST', $request['method'] );
		self::assertSame( 'https://example.test/wp-json/quadlayers/tiktok/frontend/user-video-list', $request['url'] );
		self::assertSame( 0, $request['args']['redirection'] );
		self::assertFalse( $request['args']['sslverify'] );
		self::assertSame( 'application/json', $request['args']['headers']['Content-Type'] );
		$payload = json_decode( $request['args']['body'], true );
		self::assertSame( $settings, $payload['feedSettings'] );
		self::assertSame( '', $payload['createTime'] );
	}

	public function test_in_process_rest_response_is_preferred_over_http_loopback(): void {
		$settings = array(
			'id'     => 'feed-1',
			'source' => 'account',
			'limit'  => 9,
		);
		$item = array(
			'id'              => 'video-1',
			'share_url'       => 'https://www.tiktok.com/@example/video/1',
			'title'           => 'Video',
			'cover_image_url' => 'https://cdn.example.net/video.jpg',
		);
		$integration = new InternalRestTiktokIntegration( array( $item ) );

		$result = $integration->request( $settings );

		self::assertCount( 1, $result );
		self::assertSame( 'video-1', $result[0]['id'] );
		self::assertCount( 1, $integration->local_requests );
		self::assertSame( 'quadlayers/tiktok/frontend/user-video-list', $integration->local_requests[0][0] );
		self::assertSame(
			array( 'feedSettings' => $settings, 'createTime' => '' ),
			json_decode( $integration->local_requests[0][1], true )
		);
		self::assertSame( array(), WpEnv::$remote_requests, 'A successful internal REST dispatch must not perform an HTTP loopback.' );
	}

	public function test_feed_settings_decode_while_json_attribute_entities_are_preserved(): void {
		$settings = array(
			'id'      => 'feed-1',
			'source'  => 'account',
			'caption' => 'A & B < C > D\'s "double" quotes',
		);
		$attribute = htmlspecialchars( (string) wp_json_encode( $settings ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$preserved = strtr(
			$attribute,
			array(
				'&quot;' => 'QUOTE_PLACEHOLDER',
				'&apos;' => 'APOS_PLACEHOLDER',
				'&lt;'   => 'LESSTHAN_PLACEHOLDER',
				'&gt;'   => 'GREATTHAN_PLACEHOLDER',
				'&amp;'  => 'AMPERSAND_PLACEHOLDER',
			)
		);

		self::assertStringContainsString( 'QUOTE_PLACEHOLDER', $preserved );
		self::assertSame( $settings, ( new ExposedTiktokIntegration() )->decodeSettings( $preserved ) );
	}

	public function test_background_export_can_use_the_registered_quadlayers_callback_after_permission_failure(): void {
		$settings = array(
			'id'      => 'feed-1',
			'source'  => 'account',
			'open_id' => 'connected-account',
		);
		$item = array(
			'id'              => 'video-1',
			'share_url'       => 'https://www.tiktok.com/@example/video/1',
			'title'           => 'Video',
			'cover_image_url' => 'https://cdn.example.net/video.jpg',
		);
		$callback = new QuadlayersTestCallback( array( $item ) );
		WpEnv::$rest_dispatch_response = new \WP_REST_Response(
			array( 'code' => 'rest_forbidden', 'message' => 'Sorry, you are not allowed to do that.' ),
			401
		);
		WpEnv::$rest_server_routes = array(
			'/quadlayers/tiktok/frontend/user-video-list' => array(
				array( 'callback' => array( $callback, 'callback' ) ),
			),
		);

		$result = ( new ExposedTiktokIntegration() )->request( $settings );

		self::assertCount( 1, $result );
		self::assertSame( 'video-1', $result[0]['id'] );
		self::assertSame( 1, $callback->requests );
		self::assertCount( 1, WpEnv::$rest_requests );
		self::assertSame( array(), WpEnv::$remote_requests, 'The registered callback must avoid an HTTP loopback.' );
	}

	public function test_only_supported_raster_signatures_are_accepted(): void {
		$integration = new ExposedTiktokIntegration();

		self::assertSame( 'jpg', $integration->imageExtension( "\xFF\xD8\xFFpayload" ) );
		self::assertSame( 'png', $integration->imageExtension( "\x89PNG\r\n\x1A\npayload" ) );
		self::assertSame( 'webp', $integration->imageExtension( 'RIFFxxxxWEBPpayload' ) );
		self::assertSame( 'avif', $integration->imageExtension( 'xxxxftypavifpayload' ) );
		self::assertSame( '', $integration->imageExtension( '<html>not an image</html>' ) );
		self::assertSame( '', $integration->imageExtension( '<svg onload="alert(1)"></svg>' ) );
	}

	private function feedDocument( array $settings, int $count ): DOMDocument {
		$document = new DOMDocument();
		$feeds    = '';
		$encoded  = htmlspecialchars( (string) wp_json_encode( $settings ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		for ( $index = 0; $index < $count; $index++ ) {
			$feeds .= '<div id="tiktok-feed-' . $index . '" class="widget tiktok-feed-feed" data-feed="' . $encoded . '"></div>';
		}
		$document->loadHTML( '<!doctype html><html><head></head><body>' . $feeds . '</body></html>' );

		return $document;
	}

	private function removeDirectory( string $root ): void {
		if ( ! is_dir( $root ) ) {
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			$item->isDir() && ! $item->isLink() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() );
		}
		@rmdir( $root );
	}
}
