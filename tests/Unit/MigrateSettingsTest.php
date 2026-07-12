<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Migrate_Settings;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class MigrationWpdb {
	public function get_charset_collate(): string {
		return '';
	}

	public function get_blog_prefix(): string {
		return 'wp_';
	}
}

final class MigrateSettingsTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/admin/inc/class-ss-migrate-settings.php' );
		$GLOBALS['wpdb'] = new MigrationWpdb();

		$upgrade_dir = ABSPATH . 'wp-admin/includes';
		wp_mkdir_p( $upgrade_dir );
		if ( ! file_exists( $upgrade_dir . '/upgrade.php' ) ) {
			file_put_contents( $upgrade_dir . '/upgrade.php', "<?php\n" );
		}
	}

	public function test_migrates_basic_auth_without_truncating_colons_and_removes_digest(): void {
		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_digest' => base64_encode( 'crawler:p:a:ss' ),
			'urls_to_exclude'         => "/private/\n/preview/",
			'search-excludable'       => "/hidden/\n",
		);

		Migrate_Settings::migrate();
		$options = WpEnv::$options['simply-static'];

		self::assertArrayNotHasKey( 'http_basic_auth_digest', $options );
		self::assertSame( 'crawler', $options['http_basic_auth_username'] );
		self::assertSame( 'p:a:ss', $options['http_basic_auth_password'] );
		self::assertSame( "/private/\n/preview/", $options['urls_to_exclude'] );
		self::assertSame( "/hidden/", $options['search_excludable'] );
	}

	public function test_invalid_digest_does_not_overwrite_existing_credentials(): void {
		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_digest'   => '%%%not-base64%%%',
			'http_basic_auth_username' => 'existing-user',
			'http_basic_auth_password' => 'existing-pass',
		);

		Migrate_Settings::migrate();
		$options = WpEnv::$options['simply-static'];

		self::assertArrayNotHasKey( 'http_basic_auth_digest', $options );
		self::assertSame( 'existing-user', $options['http_basic_auth_username'] );
		self::assertSame( 'existing-pass', $options['http_basic_auth_password'] );
	}

	public function test_legacy_lists_and_comment_requirements_are_migrated_safely(): void {
		WpEnv::$options['comment_registration'] = 1;
		WpEnv::$options['require_name_email'] = 0;
		WpEnv::$options['simply-static'] = array(
			'urls_to_exclude'   => array( '/one/' => array(), '/two/' => array() ),
			'search-excludable' => array( '/search-one/' => true, '/search-two/' => true ),
			'use-comments'      => 'yes',
			'tiiny-password'    => 'deployment-secret',
		);

		Migrate_Settings::migrate();
		$options = WpEnv::$options['simply-static'];

		self::assertSame( "/one/\n/two/", $options['urls_to_exclude'] );
		self::assertSame( "/search-one/\n/search-two/", $options['search_excludable'] );
		self::assertTrue( $options['use_comments'] );
		self::assertSame( 0, WpEnv::$options['comment_registration'] );
		self::assertSame( 1, WpEnv::$options['require_name_email'] );
		self::assertSame( 'deployment-secret', $options['tiiny_password'] );
		self::assertArrayNotHasKey( 'tiiny-password', $options );
	}

	public function test_missing_settings_option_migrates_to_a_valid_array(): void {
		Migrate_Settings::migrate();

		self::assertIsArray( WpEnv::$options['simply-static'] );
		self::assertSame( SIMPLY_STATIC_VERSION, WpEnv::$options['simply-static']['version'] );
		self::assertFalse( WpEnv::$options['simply-static']['server_cron'] );
	}
}
