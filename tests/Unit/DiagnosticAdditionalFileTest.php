<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use Simply_Static\Diagnostic;
use Simply_Static\Tests\Support\UnitTestCase;

final class DiagnosticAdditionalFileTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-diagnostic.php' );
	}

	public function test_valid_file_regex_is_accepted(): void {
		$reflection = new ReflectionClass( Diagnostic::class );
		$diagnostic = $reflection->newInstanceWithoutConstructor();

		$result = $diagnostic->is_additional_file_valid( '/\.pdf$/i' );

		self::assertTrue( $result['test'] );
		self::assertSame( 'Additional File/Dir /\.pdf$/i is valid', $result['description'] );
		self::assertNull( $result['error'] );
	}
}
