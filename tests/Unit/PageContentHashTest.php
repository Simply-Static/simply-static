<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;

final class PageContentHashTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
	}

	public function test_compares_full_binary_sha1_values(): void {
		$hash = sha1( 'content', true );
		$page = Page::initialize( array( 'content_hash' => $hash ) );

		self::assertTrue( $page->is_content_identical( $hash ) );
		self::assertFalse( $page->is_content_identical( sha1( 'different', true ) ) );
	}

	public function test_rejects_truncated_legacy_and_hex_encoded_hashes(): void {
		$hex = sha1( 'content' );
		$page = Page::initialize( array( 'content_hash' => substr( $hex, 0, 20 ) ) );

		self::assertFalse( $page->is_content_identical( sha1( 'content', true ) ) );

		$page->content_hash = $hex;
		self::assertFalse( $page->is_content_identical( $hex ) );
	}
}
