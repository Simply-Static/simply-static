<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Upgrade_Handler;

final class UpgradeDefaultsTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-upgrade-handler.php' );
	}

	public function test_defaults_cover_every_settings_group_with_stable_types(): void {
		$defaults = Upgrade_Handler::get_default_options();

		$expected_types = array(
			'debugging_mode'                  => 'boolean',
			'server_cron'                     => 'boolean',
			'crawlers'                        => 'NULL',
			'post_types'                      => 'array',
			'post_types_configured'           => 'boolean',
			'plugins_to_include'              => 'array',
			'themes_to_include'               => 'array',
			'http_basic_auth_on'              => 'boolean',
			'github_batch_size'               => 'integer',
			'aws_disable_acl'                 => 'boolean',
			'save_form_entries'               => 'boolean',
			'captcha_service'                 => 'string',
			'cloudflare_turnstile_site_key'   => 'string',
			'cloudflare_turnstile_secret_key' => 'string',
			'recaptcha_site_key'              => 'string',
			'recaptcha_secret_key'            => 'string',
			'search_show_submit'              => 'boolean',
			'search_show_excerpt'             => 'boolean',
			'minify_css_exclude'              => 'string',
			'minify_js_exclude'               => 'string',
			'version_css'                     => 'boolean',
			'version_js'                      => 'boolean',
			'sftp_private_key'                => 'string',
			'shortpixel_enabled'              => 'boolean',
			'shortpixel_api_key'              => 'string',
			'ss_use_single_exports'           => 'boolean',
			'ss_use_builds'                   => 'boolean',
			'ss_single_pages'                 => 'array',
			'ss_single_taxonomy_archives'     => 'array',
			'ss_single_auto_export_types'     => 'array',
			'ss_webhook_enabled_types'        => 'array',
			'ss_uam_access'                   => 'array',
		);

		foreach ( $expected_types as $key => $type ) {
			self::assertArrayHasKey( $key, $defaults );
			self::assertSame( $type, gettype( $defaults[ $key ] ), $key );
		}
	}

	public function test_security_sensitive_defaults_are_disabled_or_empty(): void {
		$defaults = Upgrade_Handler::get_default_options();

		self::assertFalse( $defaults['debugging_mode'] );
		self::assertFalse( $defaults['http_basic_auth_on'] );
		self::assertSame( '', $defaults['http_basic_auth_username'] );
		self::assertSame( '', $defaults['http_basic_auth_password'] );
		self::assertSame( '', $defaults['github_personal_access_token'] );
		self::assertSame( '', $defaults['aws_access_key'] );
		self::assertSame( '', $defaults['aws_access_secret'] );
		self::assertSame( '', $defaults['sftp_private_key'] );
		self::assertSame( '', $defaults['ss_webhook_url'] );
	}

	public function test_default_filter_can_extend_the_single_canonical_provider(): void {
		add_filter( 'ss_default_options', static function ( array $defaults ): array {
			$defaults['extension_setting'] = 'enabled';
			return $defaults;
		} );

		self::assertSame( 'enabled', Upgrade_Handler::get_default_options()['extension_setting'] );
	}
}
