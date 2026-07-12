<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WpTestEnvironment::reset();

		if ( class_exists( '\\Simply_Static\\Options', false ) ) {
			\Simply_Static\Options::reinstance();
		}
	}

	protected function tearDown(): void {
		if ( class_exists( '\\Simply_Static\\Options', false ) ) {
			\Simply_Static\Options::reinstance();
		}

		parent::tearDown();
	}

	protected function requireSource( string $relative_path ): void {
		require_once dirname( __DIR__, 2 ) . '/' . ltrim( $relative_path, '/' );
	}
}
