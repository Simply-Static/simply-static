<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\AIO_SEO_Integration;
use Simply_Static\Tests\Support\UnitTestCase;

final class AioSeoSitemapExclusionTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-aio-seo-integration.php' );
	}

	public function test_forms_are_removed_from_the_names_used_for_indexes_and_child_sitemaps(): void {
		( new AIO_SEO_Integration() )->run();

		self::assertSame(
			array( 'page', 'post', 'product' ),
			apply_filters( 'aioseo_public_post_types', array( 'page', 'ssp-form', 'post', 'product' ), true )
		);
	}

	public function test_forms_are_removed_from_post_type_metadata_without_changing_public_content(): void {
		( new AIO_SEO_Integration() )->run();
		$page    = array( 'name' => 'page', 'label' => 'Pages', 'hierarchical' => true );
		$form    = array( 'name' => 'ssp-form', 'label' => 'Forms', 'hierarchical' => false );
		$product = array( 'name' => 'product', 'label' => 'Products', 'hasArchive' => true );

		self::assertSame(
			array( $page, $product ),
			apply_filters( 'aioseo_public_post_types', array( $page, $form, $product ), false )
		);
	}

	public function test_existing_exclusions_are_preserved(): void {
		add_filter(
			'aioseo_public_post_types',
			static function ( $post_types ) {
				return array_values( array_diff( $post_types, array( 'product' ) ) );
			},
			5
		);
		( new AIO_SEO_Integration() )->run();

		self::assertSame(
			array( 'page', 'post' ),
			apply_filters( 'aioseo_public_post_types', array( 'page', 'ssp-form', 'post', 'product' ), true )
		);
		self::assertSame( array(), apply_filters( 'aioseo_public_post_types', array(), true ) );
	}

	public function test_filtering_is_idempotent_and_only_removes_the_exact_form_type(): void {
		( new AIO_SEO_Integration() )->run();
		$names    = apply_filters( 'aioseo_public_post_types', array( 'page', 'ssp-form', 'ssp-form-preview' ), true );
		$metadata = apply_filters(
			'aioseo_public_post_types',
			array( array( 'name' => 'ssp-form' ), array( 'name' => 'page', 'extra' => 'preserve' ) ),
			false
		);

		self::assertSame( array( 'page', 'ssp-form-preview' ), $names );
		self::assertSame( $names, apply_filters( 'aioseo_public_post_types', $names, true ) );
		self::assertSame( array( array( 'name' => 'page', 'extra' => 'preserve' ) ), $metadata );
		self::assertSame( $metadata, apply_filters( 'aioseo_public_post_types', $metadata, false ) );
	}

	public function test_an_excluded_form_only_type_list_stays_empty(): void {
		( new AIO_SEO_Integration() )->run();

		self::assertSame( array(), apply_filters( 'aioseo_public_post_types', array( 'ssp-form' ), true ) );
		self::assertSame( array(), apply_filters( 'aioseo_public_post_types', array( array( 'name' => 'ssp-form' ) ), false ) );
	}
}
