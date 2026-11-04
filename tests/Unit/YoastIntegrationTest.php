<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Yoast_Integration;

final class YoastIntegrationTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-yoast-integration.php' );
	}

	public function test_internal_forms_are_excluded_without_starting_an_export(): void {
		( new Yoast_Integration() )->run();

		self::assertTrue( apply_filters( 'wpseo_sitemap_exclude_post_type', false, 'ssp-form' ) );
	}

	public function test_public_content_types_remain_available_to_yoast_sitemaps(): void {
		( new Yoast_Integration() )->run();

		foreach ( array( 'page', 'post', 'product' ) as $post_type ) {
			self::assertFalse( apply_filters( 'wpseo_sitemap_exclude_post_type', false, $post_type ) );
		}
	}

	public function test_existing_post_type_exclusions_are_preserved(): void {
		add_filter(
			'wpseo_sitemap_exclude_post_type',
			static function ( $excluded, $post_type ) {
				return 'product' === $post_type ? true : $excluded;
			},
			5,
			2
		);
		( new Yoast_Integration() )->run();

		self::assertTrue( apply_filters( 'wpseo_sitemap_exclude_post_type', false, 'product' ) );
		self::assertTrue( apply_filters( 'wpseo_sitemap_exclude_post_type', true, 'page' ) );
		self::assertTrue( apply_filters( 'wpseo_sitemap_exclude_post_type', true, 'post' ) );
		self::assertTrue( apply_filters( 'wpseo_sitemap_exclude_post_type', true, 'ssp-form' ) );
		self::assertFalse( apply_filters( 'wpseo_sitemap_exclude_post_type', false, 'page' ) );
	}
}
