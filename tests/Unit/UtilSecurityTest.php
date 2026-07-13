<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Util;

final class UtilSecurityTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-phpuri.php' );
		$this->requireSource( 'src/class-ss-util.php' );

		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => 'correct horse',
			'origin_url'               => '',
		);
		Options::reinstance();
	}

	/**
	 * @dataProvider sameOriginProvider
	 */
	public function test_same_origin_comparison_is_scheme_and_port_aware( string $url, string $base, bool $expected ): void {
		self::assertSame( $expected, Util::is_same_origin_url( $url, $base ) );
	}

	/** @return array<string,array{string,string,bool}> */
	public function sameOriginProvider(): array {
		return array(
			'exact'                 => array( 'https://example.test/a', 'https://example.test/b', true ),
			'case and default port' => array( 'https://EXAMPLE.test:443/a', 'https://example.test/b', true ),
			'alternate port'        => array( 'https://example.test:8443/a', 'https://example.test/b', false ),
			'downgrade'             => array( 'http://example.test/a', 'https://example.test/b', false ),
			'subdomain'             => array( 'https://cdn.example.test/a', 'https://example.test/b', false ),
			'userinfo'              => array( 'https://attacker@example.test/a', 'https://example.test/b', false ),
			'zero userinfo'         => array( 'https://0@example.test/a', 'https://example.test/b', false ),
			'non-http'              => array( 'file://example.test/a', 'https://example.test/b', false ),
		);
	}

	public function test_basic_auth_is_only_returned_for_an_exact_local_origin(): void {
		$expected = 'Basic ' . base64_encode( 'crawler:correct horse' );

		self::assertSame( $expected, Util::get_basic_auth_header_for_url( 'https://example.test/page' ) );
		self::assertNull( Util::get_basic_auth_header_for_url( 'https://example.test:8443/page' ) );
		self::assertNull( Util::get_basic_auth_header_for_url( 'http://example.test/page' ) );
		self::assertNull( Util::get_basic_auth_header_for_url( 'https://external.test/page' ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_runtime_basic_auth_constants_survive_an_unrelated_option_save(): void {
		define( 'SIMPLY_STATIC_HTTP_BASIC_AUTH_USERNAME', 'runtime-crawler' );
		define( 'SIMPLY_STATIC_HTTP_BASIC_AUTH_PASSWORD', 'runtime-password' );

		WpEnv::$options['simply-static'] = array(
			'delivery_method' => 'zip',
			'origin_url'      => '',
		);
		$options = Options::reinstance();

		self::assertTrue( $options->set( 'archive_start_time', '2026-07-13 18:00:00' )->save() );
		self::assertSame(
			'Basic ' . base64_encode( 'runtime-crawler:runtime-password' ),
			Util::get_basic_auth_header_for_url( 'https://example.test/page' )
		);
		self::assertArrayNotHasKey( 'http_basic_auth_username', WpEnv::$options['simply-static'] );
		self::assertArrayNotHasKey( 'http_basic_auth_password', WpEnv::$options['simply-static'] );
	}

	public function test_empty_credentials_never_create_an_authorization_header(): void {
		WpEnv::$options['simply-static']['http_basic_auth_username'] = '';
		WpEnv::$options['simply-static']['http_basic_auth_password'] = '';
		Options::reinstance();

		self::assertNull( Util::get_basic_auth_header_for_url( 'https://example.test/page' ) );
	}

	public function test_crawl_local_filters_do_not_implicitly_expand_basic_auth_trust(): void {
		add_filter( 'ss_is_local_origin_url', '__return_true' );
		add_filter(
			'ss_local_url_bases',
			static function ( array $bases ): array {
				$bases[] = 'https://integration.example.test';
				return $bases;
			}
		);

		self::assertTrue( Util::is_local_origin_url( 'https://integration.example.test/resource' ) );
		self::assertNull( Util::get_basic_auth_header_for_url( 'https://integration.example.test/resource' ) );

		add_filter( 'ss_send_basic_auth_to_url', '__return_true' );
		self::assertSame(
			'Basic ' . base64_encode( 'crawler:correct horse' ),
			Util::get_basic_auth_header_for_url( 'https://integration.example.test/resource' )
		);
	}

	public function test_absolute_paths_are_converted_only_inside_wordpress_roots(): void {
		self::assertSame(
			WP_CONTENT_URL . '/simply-static/export.zip',
			Util::abs_path_to_url( WP_CONTENT_DIR . '/simply-static/export.zip' )
		);
		self::assertSame(
			'https://example.test/wp-content-backup/export.zip',
			Util::abs_path_to_url( WP_CONTENT_DIR . '-backup/export.zip' )
		);
		self::assertSame( '', Util::abs_path_to_url( sys_get_temp_dir() . '/outside/export.zip' ) );
	}

	public function test_private_backup_artifacts_are_excluded_from_discovery_and_stale_queues(): void {
		$key = str_repeat( 'a', 32 );
		$url = 'https://example.test/wp-content/uploads/simply-static/backup-' . $key . '/studio-backup-2026-04-10.zip';

		self::assertTrue( Util::is_private_backup_path( $url ) );
		self::assertTrue( Util::is_private_backup_path( '/simply-static/backup-' . strtoupper( $key ) . '/config.json' ) );
		self::assertTrue( Util::is_url_excluded( $url ) );
	}

	/**
	 * @dataProvider publicSimplyStaticPathProvider
	 */
	public function test_public_simply_static_uploads_are_not_mistaken_for_private_backups( string $path ): void {
		self::assertFalse( Util::is_private_backup_path( $path ) );
	}

	/** @return array<string,array{string}> */
	public function publicSimplyStaticPathProvider(): array {
		return array(
			'generated config'       => array( '/wp-content/uploads/simply-static/configs/forms.json' ),
			'legacy archive name'    => array( '/wp-content/uploads/simply-static/studio-backup-2026-04-10.zip' ),
			'short backup key'       => array( '/wp-content/uploads/simply-static/backup-abc/archive.zip' ),
			'backup prefix collision' => array( '/wp-content/uploads/simply-static/backup-' . str_repeat( 'a', 32 ) . '-copy/archive.zip' ),
		);
	}

	public function test_sensitive_options_are_removed_with_legacy_and_future_key_fallbacks(): void {
		$options = array(
			'delivery_method'                 => 'zip',
			'github_personal_access_token'     => 'secret',
			'github-personal-access-token'     => 'legacy-secret',
			'custom_provider_private_key_file' => 'private',
			'future_service_license_key'        => 'license-secret',
			'future_service_webhook_url'        => 'https://hooks.test/future',
			'future_service_token'              => 'future-token',
			'ss_webhook_url'                   => 'https://hooks.test/?token=secret',
			'provider'                         => array(
				'label'      => 'kept nested',
				'api_secret' => 'nested secret',
			),
			'credentials'                      => array(
				'client_id' => 'also secret',
				'region'    => 'must not survive with a sensitive parent',
			),
			'public_label'                     => 'kept',
		);

		self::assertSame(
			array(
				'delivery_method' => 'zip',
				'provider'        => array( 'label' => 'kept nested' ),
				'public_label'    => 'kept',
			),
			Util::remove_sensitive_options( $options )
		);
	}

	public function test_recursive_deletion_rejects_protected_roots_but_allows_a_dedicated_child(): void {
		$uploads = WpEnv::$upload_dir['basedir'];
		$managed = $uploads . '/simply-static/temp-files';
		wp_mkdir_p( $managed . '/nested' );
		file_put_contents( $managed . '/nested/file.txt', 'content' );

		self::assertFalse( Util::is_safe_directory_to_delete( $uploads ) );
		self::assertTrue( Util::is_safe_directory_to_delete( $managed ) );

		Util::delete_dir_contents( $uploads );
		self::assertFileExists( $managed . '/nested/file.txt' );

		Util::delete_dir_contents( $managed );
		self::assertDirectoryExists( $managed );
		self::assertSame( array( '.', '..' ), scandir( $managed ) );
	}

	public function test_portable_import_preserves_top_level_and_nested_destination_secrets(): void {
		$current = array(
			'delivery_method'             => 'sftp',
			'sftp_pass'                   => 'destination-password',
			'provider'                    => array(
				'label'      => 'Old label',
				'api_secret' => 'nested-secret',
			),
			'credentials'                 => array(
				'client_id' => 'destination-client',
				'region'    => 'eu-central-1',
			),
			'unrelated_omitted_container' => array( 'label' => 'do not recreate me' ),
		);
		$incoming = array(
			'delivery_method' => 'zip',
			'provider'        => array( 'label' => 'Imported label' ),
		);

		self::assertSame(
			array(
				'delivery_method' => 'zip',
				'provider'        => array(
					'label'      => 'Imported label',
					'api_secret' => 'nested-secret',
				),
				'sftp_pass'       => 'destination-password',
				'credentials'     => array(
					'client_id' => 'destination-client',
					'region'    => 'eu-central-1',
				),
			),
			Util::preserve_sensitive_options( $incoming, $current )
		);
	}

	public function test_recursive_deletion_rejects_a_symlink_root(): void {
		$target = WpEnv::$upload_dir['basedir'] . '/target';
		$link   = WpEnv::$upload_dir['basedir'] . '/link';
		wp_mkdir_p( $target );
		file_put_contents( $target . '/keep.txt', 'content' );

		if ( ! function_exists( 'symlink' ) || ! @symlink( $target, $link ) ) {
			self::markTestSkipped( 'The filesystem does not permit symlink creation.' );
		}

		self::assertFalse( Util::is_safe_directory_to_delete( $link ) );
		Util::delete_dir_contents( $link );
		self::assertFileExists( $target . '/keep.txt' );
		unlink( $link );
	}
}
