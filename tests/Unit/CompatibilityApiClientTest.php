<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Compatibility_API_Client;
use Simply_Static\Plugin_Compatibility;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class CompatibilityApiClientTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-compatibility-api-client.php' );
		$this->requireSource( 'src/class-ss-plugin-compatibility.php' );
	}

	public function test_fetches_filters_and_caches_static_snapshot(): void {
		WpEnv::$remote_response['body'] = wp_json_encode( array(
			'schema_version' => '1.0',
			'plugins'        => array(
				array(
					'slug'                => 'working-plugin',
					'name'                => 'Working Plugin',
					'status'              => 'compatible_with_config',
					'compatible'          => true,
					'recommended_product' => 'pro',
					'icon'                => 'https://example.test/icon.svg',
				),
				array(
					'slug'       => 'dynamic-plugin',
					'name'       => 'Dynamic Plugin',
					'status'     => 'not_compatible',
					'compatible' => false,
				),
			),
		) );

		$plugins = Compatibility_API_Client::get_plugins();

		self::assertCount( 1, $plugins );
		self::assertSame( 'working-plugin', $plugins[0]['slug'] );
		self::assertSame( 'compatible_with_config', $plugins[0]['status'] );
		self::assertCount( 1, WpEnv::$remote_requests );
		self::assertSame( 'SAFE_GET', WpEnv::$remote_requests[0]['method'] );
		self::assertSame( Compatibility_API_Client::DEFAULT_ENDPOINT, WpEnv::$remote_requests[0]['url'] );

		// A second read comes from the transient and performs no network request.
		self::assertSame( $plugins, Compatibility_API_Client::get_plugins() );
		self::assertCount( 1, WpEnv::$remote_requests );
		self::assertSame( $plugins, WpEnv::$site_options[ Compatibility_API_Client::SNAPSHOT_OPTION ] );
	}

	public function test_uses_last_known_good_snapshot_when_remote_file_is_unavailable(): void {
		$stale = array( array( 'slug' => 'cached-plugin', 'name' => 'Cached Plugin' ) );
		WpEnv::$site_options[ Compatibility_API_Client::SNAPSHOT_OPTION ] = $stale;
		WpEnv::$remote_response['response']['code'] = 503;

		self::assertSame( $stale, Compatibility_API_Client::get_plugins() );
		self::assertStringContainsString( '503', WpEnv::$site_options[ Compatibility_API_Client::LAST_ERROR_OPTION ] );
		self::assertSame( $stale, WpEnv::$site_transients[ Compatibility_API_Client::CACHE_KEY ] );
	}

	public function test_plugin_compatibility_uses_central_data_and_keeps_filter_contract(): void {
		WpEnv::$site_transients[ Compatibility_API_Client::CACHE_KEY ] = array(
			array( 'slug' => 'central-plugin', 'name' => 'Central Plugin', 'status' => 'fully_compatible' ),
		);
		add_filter( 'ss_compatible_plugins', static function ( array $plugins ): array {
			$plugins[] = array( 'slug' => 'filtered-plugin', 'name' => 'Filtered Plugin' );
			return $plugins;
		} );

		$compatibility = new Plugin_Compatibility();
		$plugins       = $compatibility->get_plugins();

		self::assertSame( array( 'central-plugin', 'filtered-plugin' ), wp_list_pluck( $plugins, 'slug' ) );
		self::assertSame( array(), WpEnv::$remote_requests );
	}
}
