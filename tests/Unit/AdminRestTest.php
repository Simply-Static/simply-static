<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionMethod;
use Simply_Static\Admin_Rest;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class AdminRestTest extends UnitTestCase {

	/** @var Admin_Rest */
	private $rest;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/admin/inc/class-ss-admin-rest.php' );

		$this->rest = new Admin_Rest();
	}

	public function test_settings_export_cannot_be_tricked_into_including_secrets(): void {
		WpEnv::$options['simply-static'] = array(
			'delivery_method'                 => 'zip',
			'origin_url'                      => 'https://private-origin.example.test',
			'local_dir'                       => '/srv/private-export',
			'github_personal_access_token'     => 'explicit-secret',
			'github-personal-access-token'     => 'legacy-secret',
			'future_provider_auth_token'       => 'future-secret',
			'custom_provider_private_key_file' => 'future-private-key',
			'archive_deploy_id'                => 'runtime-deploy',
			'archive_task_list'                => array( 'setup', 'fetch_urls' ),
			'generate_type'                   => 'update',
			'zip_batch_offset'                => 2500,
			'zip_total_files'                 => 100000,
			'zip_files'                       => array( '/srv/private-export/index.html' ),
			'provider'                        => array(
				'label'       => 'Public provider label',
				'credentials' => array(
					'api_secret' => 'nested-secret',
					'region'     => 'eu-central-1',
				),
			),
		);

		add_filter(
			'ss_export_excluded_options',
			static function (): array {
				return array();
			}
		);

		$export = json_decode( (string) $this->rest->get_settings_export(), true );

		self::assertSame(
			array(
				'delivery_method' => 'zip',
				'provider'        => array( 'label' => 'Public provider label' ),
			),
			$export
		);
	}

	public function test_save_settings_normalizes_multiline_booleans_and_nested_unknown_arrays(): void {
		$this->saveSettings(
			array(
				'additional_urls'     => "  https://one.test/\r\nhttps://two.test/<b>path</b>  ",
				'critical_css_custom' => "<b>body</b> {\r\n color: red;\n}",
				'use_search'          => 'false',
				'server_cron'         => 'yes',
				'ss_use_builds'       => '0',
				'custom_provider'     => array(
					'label'   => " <strong>Primary</strong>\n provider ",
					'details' => array(
						'region' => '<em>Europe</em>',
						'tags'   => array( '<i>fast</i>', " static\nsite " ),
					),
				),
			)
		);

		$saved = WpEnv::$options['simply-static'];

		self::assertSame( "https://one.test/\nhttps://two.test/path", $saved['additional_urls'] );
		self::assertSame( "body {\n color: red;\n}", $saved['critical_css_custom'] );
		self::assertFalse( $saved['use_search'] );
		self::assertTrue( $saved['server_cron'] );
		self::assertFalse( $saved['ss_use_builds'] );
		self::assertSame(
			array(
				'label'   => 'Primary provider',
				'details' => array(
					'region' => 'Europe',
					'tags'   => array( 'fast', 'static site' ),
				),
			),
			$saved['custom_provider']
		);
	}

	/**
	 * @dataProvider unsafeWebhookProvider
	 */
	public function test_save_settings_rejects_private_and_invalid_webhook_urls( string $field, string $url ): void {
		$this->saveSettings( array( $field => $url ) );

		self::assertSame( '', WpEnv::$options['simply-static'][ $field ] );
	}

	/** @return array<string,array{string,string}> */
	public function unsafeWebhookProvider(): array {
		return array(
			'unified private IPv4' => array( 'ss_webhook_url', 'http://127.0.0.1/hook' ),
			'unified invalid'      => array( 'ss_webhook_url', 'javascript:alert(1)' ),
			'legacy private IPv4'  => array( 'ss_single_export_webhook_url', 'http://10.0.0.8/hook' ),
			'legacy invalid'       => array( 'ss_single_export_webhook_url', 'not a URL' ),
		);
	}

	public function test_save_settings_accepts_a_public_https_webhook_url(): void {
		$this->saveSettings( array( 'ss_webhook_url' => 'https://hooks.example.test/deploy' ) );

		self::assertSame(
			'https://hooks.example.test/deploy',
			WpEnv::$options['simply-static']['ss_webhook_url']
		);
	}

	public function test_save_settings_preserves_internal_runtime_values_even_if_the_form_attempts_to_replace_them(): void {
		$runtime = array(
			'encryption_key'                 => 'installation-key',
			'archive_status_messages'        => array( 'fetch' => array( 'message' => 'Running' ) ),
			'archive_deploy_id'               => 'deployment-123',
			'deploy_manifest_schema_version' => 2,
			'pages_status'                   => array( 'total' => 15, 'done' => 4 ),
			'archive_name'                   => 'simply-static-1.zip',
			'archive_start_time'             => '2026-07-12 10:00:00',
			'archive_end_time'               => null,
			'generate_type'                  => 'update',
			'archive_task_list'              => array( 'setup', 'fetch_urls', 'create_zip_archive', 'wrapup' ),
			'zip_batch_offset'               => 250,
			'zip_total_files'                => 900,
			'zip_files'                      => array( '/tmp/archive/a.html', '/tmp/archive/b.html' ),
			'version'                        => '3.4.5',
		);
		WpEnv::$options['simply-static'] = $runtime + array( 'delivery_method' => 'zip' );

		$this->saveSettings( array(
			'delivery_method'         => 'local',
			'encryption_key'          => 'attacker-controlled',
			'archive_status_messages' => array( 'injected' => 'message' ),
			'archive_name'            => '../../outside.zip',
			'generate_type'           => 'export',
			'archive_task_list'       => array( 'injected_task' ),
			'zip_batch_offset'        => 0,
			'zip_total_files'         => 0,
			'zip_files'               => array(),
			'version'                 => '0.0.0',
		) );

		foreach ( $runtime as $key => $value ) {
			self::assertSame( $value, WpEnv::$options['simply-static'][ $key ], $key );
		}
		self::assertSame( 'local', WpEnv::$options['simply-static']['delivery_method'] );
	}

	public function test_cross_site_site_admin_is_denied_without_network_authorization(): void {
		$this->configureMultisiteTarget();
		WpEnv::$capabilities = array(
			'publish_pages'  => true,
			'manage_options' => true,
		);

		$request = new \WP_REST_Request( array( 'blog_id' => 2 ) );

		self::assertFalse( $this->rest->can_generate_for_request( $request ) );
		self::assertFalse( $this->rest->can_view_activity_log_for_request( $request ) );
	}

	/**
	 * @dataProvider inactiveSiteProvider
	 */
	public function test_network_admin_is_denied_for_inactive_target_sites( string $status ): void {
		$this->configureMultisiteTarget( array( $status => 1 ) );
		WpEnv::$capabilities['manage_network_options'] = true;

		$request = new \WP_REST_Request( array( 'blog_id' => 2 ) );

		self::assertFalse( $this->rest->can_generate_for_request( $request ) );
		self::assertFalse( $this->rest->can_view_activity_log_for_request( $request ) );
	}

	/** @return array<string,array{string}> */
	public function inactiveSiteProvider(): array {
		return array(
			'deleted'  => array( 'deleted' ),
			'spam'     => array( 'spam' ),
			'archived' => array( 'archived' ),
		);
	}

	public function test_authorized_network_admin_can_access_an_active_target_site(): void {
		$this->configureMultisiteTarget();
		WpEnv::$capabilities['manage_network_options'] = true;

		$request = new \WP_REST_Request( array( 'blog_id' => 2 ) );

		self::assertTrue( $this->rest->can_generate_for_request( $request ) );
		self::assertTrue( $this->rest->can_view_activity_log_for_request( $request ) );
	}

	public function test_explicit_empty_integrations_remains_empty_after_save_and_read(): void {
		$this->saveSettings( array( 'integrations' => array() ) );
		WpEnv::$transients['simply_static_has_build_terms'] = 0;

		self::assertSame( array(), WpEnv::$options['simply-static']['integrations'] );
		self::assertSame( array(), $this->rest->get_settings()['integrations'] );
	}

	public function test_studio_migration_package_checksum_is_fail_closed(): void {
		$package = tempnam( sys_get_temp_dir(), 'ss-studio-package-' );
		self::assertNotFalse( $package );
		file_put_contents( $package, 'reviewed package bytes' );

		$method = new ReflectionMethod( Admin_Rest::class, 'downloaded_package_matches_checksum' );
		$method->setAccessible( true );
		try {
			self::assertTrue( $method->invoke( $this->rest, $package, hash_file( 'sha256', $package ) ) );
			self::assertFalse( $method->invoke( $this->rest, $package, str_repeat( '0', 64 ) ) );
			self::assertFalse( $method->invoke( $this->rest, $package, 'not-a-sha256' ) );
		} finally {
			unlink( $package );
		}
	}

	/** @param array<string,mixed> $params */
	private function saveSettings( array $params ): void {
		$response = json_decode(
			(string) $this->rest->save_settings( new \WP_REST_Request( $params ) ),
			true
		);

		self::assertSame( 200, $response['status'] ?? null );
	}

	/** @param array<string,mixed> $overrides */
	private function configureMultisiteTarget( array $overrides = array() ): void {
		WpEnv::$multisite = true;
		WpEnv::$sites[2]  = (object) array_merge(
			array(
				'blog_id'  => 2,
				'deleted'  => 0,
				'spam'     => 0,
				'archived' => 0,
			),
			$overrides
		);
		WpEnv::$site_capabilities[2] = array(
			'publish_pages'  => true,
			'manage_options' => true,
		);
	}
}
