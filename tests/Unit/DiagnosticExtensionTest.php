<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Diagnostic;
use Simply_Static\Tests\Support\UnitTestCase;

final class DiagnosticExtensionTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-diagnostic.php' );
	}

	public function test_extensions_can_add_diagnostic_checks(): void {
		add_filter( 'ss_diagnostic_checks', static function ( array $checks ): array {
			$checks['WordPress']['Extension check'] = array(
				'test'        => false,
				'description' => 'Extension check passed.',
				'error'       => 'Extension check failed.',
			);

			return $checks;
		} );

		$result = $this->filter_checks( array( 'WordPress' => array() ) );

		self::assertFalse( $result['WordPress']['Extension check']['test'] );
		self::assertSame( 'Extension check failed.', $result['WordPress']['Extension check']['error'] );
	}

	public function test_invalid_extension_result_preserves_existing_checks(): void {
		$checks = array(
			'WordPress' => array(
				'Existing check' => array(
					'test'        => true,
					'description' => 'Existing check passed.',
					'error'       => 'Existing check failed.',
				),
			),
		);

		add_filter( 'ss_diagnostic_checks', static function () {
			return null;
		} );

		self::assertSame( $checks, $this->filter_checks( $checks ) );
	}

	private function filter_checks( array $checks ): array {
		$reflection = new ReflectionClass( Diagnostic::class );
		$diagnostic = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'filter_checks' );
		$method->setAccessible( true );

		return $method->invoke( $diagnostic, $checks );
	}
}
