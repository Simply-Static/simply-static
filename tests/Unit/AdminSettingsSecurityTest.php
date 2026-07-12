<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Admin_Settings;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class AdminSettingsSecurityTest extends UnitTestCase {

	/** @var Admin_Settings */
	private $settings;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/admin/inc/class-ss-admin-settings.php' );

		$reflection = new ReflectionClass( Admin_Settings::class );
		$this->settings = $reflection->newInstanceWithoutConstructor();
	}

	public function test_generate_only_bootstrap_contains_no_privileged_settings_or_metadata(): void {
		$all_settings = array(
			'debugging_mode'              => true,
			'integrations'                => array( 'safe-integration', 'ss-uam', array( 'api_secret' => 'nested' ) ),
			'delivery_method'             => 'sftp',
			'local_dir'                   => '/srv/static/private',
			'http_basic_auth_username'    => 'crawler',
			'http_basic_auth_password'    => 'correct horse battery staple',
			'github_personal_access_token' => 'github-secret',
		);
		$args = array(
			'current_settings'             => $all_settings,
			'allowed_pages'                => array( '/', '/diagnostics', '/general', '/deployment', '/uam' ),
			'home_path'                    => '/srv/www/wordpress',
			'admin_email'                  => 'owner@example.test',
			'temp_files_dir'               => '/tmp/simply-static-private',
			'local_dir'                    => '/srv/filter-injected-export',
			'log_file'                     => 'https://example.test/uploads/installation-key-debug.txt',
			'debug_log_url'                => 'https://example.test/uploads/alternate-private-debug.txt',
			'form_connection_url'          => 'https://example.test/wp-admin/post-new.php?post_type=ssp-form',
			'studio_migrate_active'        => true,
			'studio_migrate_url'           => 'https://example.test/wp-admin/tools.php?page=studio-backup',
			'sites'                        => array( array( 'blog_id' => 2, 'name' => 'Private Site' ) ),
			'selectable_sites'             => array( array( 'blog_id' => 2, 'name' => 'Private Site' ) ),
			'github_personal_access_token' => 'filter-injected-secret',
			'integration_metadata'         => array(
				'label'      => 'Visible label',
				'api_secret' => 'metadata-secret',
			),
			'connect'                      => array(
				'is_connected' => true,
				'endpoint'     => 'https://api.vendor.test/private',
				'last_test'    => '2026-07-12T10:00:00Z',
				'message'      => 'Account 123 is connected',
				'auth_token'   => 'connect-secret',
			),
		);

		$restricted = $this->settings->apply_admin_bootstrap_capability_boundary(
			$args,
			$all_settings,
			false,
			true,
			false
		);

		self::assertSame(
			array(
				'debugging_mode' => false,
				'integrations'   => array( 'safe-integration', 'ss-uam' ),
			),
			$restricted['current_settings']
		);
		self::assertSame( array( '/' ), $restricted['allowed_pages'] );
		self::assertFalse( $restricted['can_manage_settings'] );
		self::assertTrue( $restricted['can_view_activity_log'] );
		self::assertFalse( $restricted['can_view_diagnostics'] );
		self::assertSame( '', $restricted['home_path'] );
		self::assertSame( '', $restricted['admin_email'] );
		self::assertSame( '', $restricted['temp_files_dir'] );
		self::assertSame( array( 'is_connected' => true ), $restricted['connect'] );
		self::assertSame( array( 'label' => 'Visible label' ), $restricted['integration_metadata'] );

		foreach ( array( 'log_file', 'debug_log_url', 'local_dir', 'form_connection_url', 'studio_migrate_active', 'studio_migrate_url', 'sites', 'selectable_sites', 'github_personal_access_token' ) as $key ) {
			self::assertArrayNotHasKey( $key, $restricted );
		}

		$serialized = wp_json_encode( $restricted );
		foreach ( array( 'correct horse battery staple', 'github-secret', 'filter-injected-secret', 'metadata-secret', 'connect-secret', '/srv/www/wordpress', '/srv/static/private', '/srv/filter-injected-export', 'owner@example.test', 'installation-key-debug.txt', 'alternate-private-debug.txt', 'Private Site', 'api.vendor.test', 'Account 123' ) as $secret ) {
			self::assertStringNotContainsString( $secret, $serialized );
		}
	}

	/**
	 * @dataProvider diagnosticsAccessProvider
	 * @param string[] $expected_pages
	 */
	public function test_generate_only_allowed_pages_require_the_diagnostics_capability( bool $can_view_diagnostics, array $expected_pages ): void {
		$restricted = $this->settings->apply_admin_bootstrap_capability_boundary(
			array( 'allowed_pages' => array( '/', '/diagnostics', '/general', '/debug', '/uam' ) ),
			array( 'integrations' => array() ),
			false,
			false,
			$can_view_diagnostics
		);

		self::assertSame( $expected_pages, $restricted['allowed_pages'] );
	}

	/** @return array<string,array{bool,string[]}> */
	public function diagnosticsAccessProvider(): array {
		return array(
			'without diagnostics capability' => array( false, array( '/' ) ),
			'with diagnostics capability'    => array( true, array( '/', '/diagnostics' ) ),
		);
	}

	public function test_settings_administrator_bootstrap_is_not_reduced(): void {
		$args = array(
			'current_settings' => array( 'delivery_method' => 'zip' ),
			'allowed_pages'    => array( '/', '/general', '/diagnostics' ),
			'home_path'       => '/srv/www/wordpress',
			'admin_email'     => 'owner@example.test',
		);

		$result = $this->settings->apply_admin_bootstrap_capability_boundary(
			$args,
			$args['current_settings'],
			true,
			true,
			true
		);

		self::assertSame( $args['current_settings'], $result['current_settings'] );
		self::assertSame( $args['allowed_pages'], $result['allowed_pages'] );
		self::assertSame( '/srv/www/wordpress', $result['home_path'] );
		self::assertSame( 'owner@example.test', $result['admin_email'] );
		self::assertTrue( $result['can_manage_settings'] );
	}

	/**
	 * @dataProvider invalidCancelNonceProvider
	 */
	public function test_get_cancel_fallback_rejects_absent_or_invalid_nonce_before_cancellation( ?string $nonce ): void {
		WpEnv::$is_admin = true;
		WpEnv::$capabilities['publish_pages'] = true;
		$_GET = array(
			'page'          => 'simply-static-generate',
			'cancel-export' => 'true',
		);
		if ( null !== $nonce ) {
			$_GET['_wpnonce'] = $nonce;
		}

		$this->settings->maybe_handle_cancel_export();

		self::assertSame( array(), WpEnv::$action_log );
		self::assertSame(
			array(
				array(
					'nonce'  => null === $nonce ? '' : $nonce,
					'action' => 'simply-static-cancel-export',
				),
			),
			WpEnv::$nonce_verifications
		);
	}

	/** @return array<string,array{?string}> */
	public function invalidCancelNonceProvider(): array {
		return array(
			'absent nonce'  => array( null ),
			'invalid nonce' => array( 'forged-nonce' ),
		);
	}

	public function test_get_cancel_authorization_accepts_a_valid_nonce_for_generate_user(): void {
		WpEnv::$capabilities['publish_pages'] = true;
		WpEnv::$valid_nonces['simply-static-cancel-export'] = array( 'valid-nonce' );

		self::assertTrue(
			$this->settings->is_authorized_get_cancel_request(
				array(
					'page'          => 'simply-static-generate',
					'cancel-export' => 'true',
					'_wpnonce'      => 'valid-nonce',
				)
			)
		);
	}
}
