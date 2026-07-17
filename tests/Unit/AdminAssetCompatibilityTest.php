<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Admin_Settings;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class AdminAssetCompatibilityTest extends UnitTestCase {

	/** @var Admin_Settings */
	private $settings;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/admin/inc/class-ss-admin-settings.php' );

		$reflection = new ReflectionClass( Admin_Settings::class );
		$this->settings = $reflection->newInstanceWithoutConstructor();
	}

	public function test_registers_jsx_runtime_fallback_when_core_does_not_provide_it(): void {
		$this->settings->register_react_jsx_runtime_polyfill();

		self::assertSame(
			array(
				'src'       => SIMPLY_STATIC_URL . '/assets/react-jsx-runtime.js',
				'deps'      => array( 'react' ),
				'ver'       => SIMPLY_STATIC_VERSION,
				'in_footer' => true,
			),
			WpEnv::$registered_scripts['react-jsx-runtime'] ?? null
		);
	}

	public function test_preserves_a_jsx_runtime_registered_by_core(): void {
		$core_registration = array(
			'src'       => '/wp-includes/js/dist/vendor/react-jsx-runtime.min.js',
			'deps'      => array( 'react' ),
			'ver'       => '18.3.1',
			'in_footer' => true,
		);
		WpEnv::$registered_scripts['react-jsx-runtime'] = $core_registration;

		$this->settings->register_react_jsx_runtime_polyfill();

		self::assertSame( $core_registration, WpEnv::$registered_scripts['react-jsx-runtime'] );
	}

	public function test_jsx_runtime_fallback_is_included_in_the_plugin(): void {
		$asset = dirname( __DIR__, 2 ) . '/assets/react-jsx-runtime.js';

		self::assertFileExists( $asset );
		self::assertFileExists( $asset . '.LICENSE.txt' );
		self::assertStringContainsString( 'ReactJSXRuntime', (string) file_get_contents( $asset ) );
	}

	public function test_settings_bundle_declares_the_jsx_runtime_dependency(): void {
		$manifest = require dirname( __DIR__, 2 ) . '/src/admin/build/index.asset.php';

		self::assertContains( 'react-jsx-runtime', $manifest['dependencies'] ?? array() );
	}
}
