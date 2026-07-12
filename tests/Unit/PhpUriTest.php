<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\PhpUri;
use Simply_Static\Tests\Support\UnitTestCase;

final class PhpUriTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-phpuri.php' );
	}

	/**
	 * @dataProvider relativeReferenceProvider
	 */
	public function test_it_resolves_relative_references( string $relative, string $expected ): void {
		$base = PhpUri::parse( 'https://example.test/a/b/c?old=1#fragment' );

		self::assertSame( $expected, $base->join( $relative ) );
	}

	/** @return array<string,array{string,string}> */
	public function relativeReferenceProvider(): array {
		return array(
			'absolute'         => array( 'https://cdn.example/file.css', 'https://cdn.example/file.css' ),
			'protocol relative'=> array( '//cdn.example/file.css', 'https://cdn.example/file.css' ),
			'root relative'    => array( '/asset.css', 'https://example.test/asset.css' ),
			'sibling'          => array( 'image.png', 'https://example.test/a/b/image.png' ),
			'parent'           => array( '../image.png', 'https://example.test/a/image.png' ),
			'query only'       => array( '?new=1', 'https://example.test/a/b/c?new=1' ),
			'fragment only'    => array( '#new', 'https://example.test/a/b/c?old=1#new' ),
			'empty'            => array( '', 'https://example.test/a/b/c?old=1' ),
		);
	}
}
