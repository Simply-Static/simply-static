<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Diagnostic;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class DiagnosticBasicAuthTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-diagnostic.php' );
	}

	public function test_missing_server_software_is_handled_as_no_basic_auth(): void {
		$result = $this->diagnostic()->check_basic_auth_status();

		self::assertTrue( $result['test'] );
		self::assertSame( 'Basic Auth is not enabled.', $result['description'] );
	}

	public function test_detected_basic_auth_requires_both_credentials(): void {
		$_SERVER['SERVER_SOFTWARE'] = 'apache/2.4';
		$_SERVER['PHP_AUTH_USER']    = '0';
		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => 'crawler',
			'http_basic_auth_password' => '',
		);

		$result = $this->diagnostic()->check_basic_auth_status();

		self::assertFalse( $result['test'] );
		self::assertStringContainsString( 'no username or password is set', $result['error'] );
	}

	public function test_detected_basic_auth_accepts_complete_credentials(): void {
		$_SERVER['SERVER_SOFTWARE'] = 'NGINX';
		$_SERVER['REMOTE_USER']     = 'crawler';
		WpEnv::$options['simply-static'] = array(
			'http_basic_auth_username' => '0',
			'http_basic_auth_password' => '0',
		);

		$result = $this->diagnostic()->check_basic_auth_status();

		self::assertTrue( $result['test'] );
		self::assertStringContainsString( 'username and password are set', $result['description'] );
	}

	private function diagnostic(): Diagnostic {
		$reflection = new ReflectionClass( Diagnostic::class );
		$diagnostic = $reflection->newInstanceWithoutConstructor();
		$options    = $reflection->getProperty( 'options' );
		$options->setAccessible( true );
		$options->setValue( $diagnostic, Options::reinstance() );

		return $diagnostic;
	}
}
