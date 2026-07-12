<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Page_Handler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;
use Simply_Static\Util;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-phpuri.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';

final class LocalAssetContainmentPageHandler extends Page_Handler {
}

final class LocalAssetContainmentPage extends Page {

	public function save() {
		return true;
	}

	public function get_handler() {
		return new LocalAssetContainmentPageHandler( $this );
	}
}

final class LocalAssetContainmentTest extends UnitTestCase {

	/** @var string */
	private $asset_dir;

	/** @var string */
	private $asset_url_path;

	/** @var string */
	private $archive_dir;

	/** @var string */
	private $upload_asset_dir;

	/** @var string */
	private $outside_file;

	/** @var Url_Fetcher */
	private $fetcher;

	protected function setUp(): void {
		parent::setUp();

		$token                = str_replace( '.', '', uniqid( 'ss-', true ) );
		$this->asset_dir        = WP_CONTENT_DIR . '/local-asset-tests-' . $token;
		$this->asset_url_path   = '/wp-content/' . basename( $this->asset_dir );
		$this->archive_dir      = sys_get_temp_dir() . '/simply-static-archive-' . $token;
		$this->upload_asset_dir = WpEnv::$upload_dir['basedir'] . '/local-asset-tests-' . $token;
		$this->outside_file     = dirname( untrailingslashit( ABSPATH ) ) . '/outside-' . $token . '.json';

		wp_mkdir_p( $this->asset_dir );
		wp_mkdir_p( $this->archive_dir );
		wp_mkdir_p( $this->upload_asset_dir );

		WpEnv::$options['simply-static'] = array(
			'archive_name'              => 'archive',
			'debugging_mode'            => false,
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => 'secret',
		);
		Options::reinstance();

		$this->resetFetcher();
		$this->fetcher = Url_Fetcher::instance();

		$archive_property = new \ReflectionProperty( Url_Fetcher::class, 'archive_dir' );
		$archive_property->setAccessible( true );
		$archive_property->setValue( $this->fetcher, trailingslashit( $this->archive_dir ) );
	}

	protected function tearDown(): void {
		$this->resetFetcher();
		$this->removeTree( $this->asset_dir );
		$this->removeTree( $this->archive_dir );
		$this->removeTree( $this->upload_asset_dir );
		if ( is_link( $this->outside_file ) || is_file( $this->outside_file ) ) {
			unlink( $this->outside_file );
		}

		parent::tearDown();
	}

	public function test_safe_asset_is_copied_from_its_canonical_wordpress_path(): void {
		$source = $this->asset_dir . '/safe.json';
		file_put_contents( $source, '{"safe":true}' );
		$url = 'https://example.test' . $this->asset_url_path . '/safe.json?ver=1';

		self::assertSame( realpath( $source ), Util::resolve_local_asset_path( $url ) );
		self::assertTrue( $this->fetcher->fetch( $this->pageFor( $url ) ) );
		self::assertSame(
			'{"safe":true}',
			file_get_contents( $this->archive_dir . $this->asset_url_path . '/safe.json' )
		);
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	public function test_asset_in_an_explicit_custom_upload_root_is_allowed(): void {
		$source = $this->upload_asset_dir . '/upload.json';
		file_put_contents( $source, '{"upload":true}' );
		$url = WpEnv::$upload_dir['baseurl'] . '/' . basename( $this->upload_asset_dir ) . '/upload.json';

		self::assertSame( realpath( $source ), Util::resolve_local_asset_path( $url ) );
		self::assertTrue( $this->fetcher->fetch( $this->pageFor( $url ) ) );
		self::assertSame(
			'{"upload":true}',
			file_get_contents( $this->archive_dir . '/wp-content/uploads/' . basename( $this->upload_asset_dir ) . '/upload.json' )
		);
		self::assertSame( array(), WpEnv::$remote_requests );
	}

	/**
	 * @dataProvider unsafePathProvider
	 */
	public function test_unsafe_asset_paths_are_rejected_without_disk_or_http_fallback( string $path ): void {
		$url      = 'https://example.test' . $path;
		$resolved = Util::resolve_local_asset_path( $url );

		self::assertInstanceOf( \WP_Error::class, $resolved );
		self::assertSame( 'simply_static_unsafe_local_asset_path', $resolved->get_error_code() );
		self::assertFalse( $this->fetcher->fetch( $this->pageFor( $url ) ) );
		self::assertSame( array(), WpEnv::$remote_requests );
		self::assertSame( array( '.', '..' ), scandir( $this->archive_dir ) );
	}

	/** @return array<string,array{string}> */
	public function unsafePathProvider(): array {
		return array(
			'literal traversal'       => array( '/wp-content/../../outside.json' ),
			'encoded traversal'       => array( '/wp-content/%2e%2e/%2e%2e/outside.json' ),
			'double encoded traversal' => array( '/wp-content/%252e%252e/%252e%252e/outside.json' ),
			'encoded separator'       => array( '/wp-content/safe%2f..%2foutside.json' ),
			'encoded null byte'       => array( '/wp-content/safe%00.json' ),
			'backslashes'             => array( '/wp-content\\..\\outside.json' ),
		);
	}

	public function test_symlink_that_escapes_a_wordpress_root_is_rejected(): void {
		file_put_contents( $this->outside_file, '{"secret":true}' );
		$link = $this->asset_dir . '/escape.json';

		if ( ! function_exists( 'symlink' ) || ! @symlink( $this->outside_file, $link ) ) {
			self::markTestSkipped( 'The filesystem does not permit symlink creation.' );
		}

		$url      = 'https://example.test' . $this->asset_url_path . '/escape.json';
		$resolved = Util::resolve_local_asset_path( $url );

		self::assertInstanceOf( \WP_Error::class, $resolved );
		self::assertSame( 'simply_static_unsafe_local_asset_path', $resolved->get_error_code() );
		self::assertFalse( $this->fetcher->fetch( $this->pageFor( $url ) ) );
		self::assertSame( '{"secret":true}', file_get_contents( $this->outside_file ) );
		self::assertSame( array(), WpEnv::$remote_requests );
		self::assertSame( array( '.', '..' ), scandir( $this->archive_dir ) );
	}

	private function pageFor( string $url ): LocalAssetContainmentPage {
		return LocalAssetContainmentPage::initialize(
			array(
				'id'  => 1,
				'url' => $url,
			)
		);
	}

	private function resetFetcher(): void {
		$instance_property = new \ReflectionProperty( Url_Fetcher::class, 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	private function removeTree( string $path ): void {
		if ( is_link( $path ) || is_file( $path ) ) {
			unlink( $path );
			return;
		}
		if ( ! is_dir( $path ) ) {
			return;
		}

		$items = scandir( $path );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}
				$this->removeTree( $path . '/' . $item );
			}
		}
		rmdir( $path );
	}
}
