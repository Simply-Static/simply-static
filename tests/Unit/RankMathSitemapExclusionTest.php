<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Rank_Math_Integration;
use Simply_Static\Tests\Support\UnitTestCase;

final class RankMathSitemapExclusionTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-rank-math-integration.php' );
	}

	public function test_internal_forms_are_excluded_without_starting_an_export(): void {
		$this->runIntegration();

		self::assertTrue( apply_filters( 'rank_math/sitemap/exclude_post_type', false, 'ssp-form' ) );
	}

	public function test_public_content_types_remain_available_to_rank_math_sitemaps(): void {
		$this->runIntegration();

		foreach ( array( 'page', 'post', 'product', 'ssp-form-submission' ) as $post_type ) {
			self::assertFalse( apply_filters( 'rank_math/sitemap/exclude_post_type', false, $post_type ) );
		}
	}

	public function test_existing_post_type_exclusions_are_preserved(): void {
		add_filter(
			'rank_math/sitemap/exclude_post_type',
			static function ( $excluded, $post_type ) {
				return 'product' === $post_type ? true : $excluded;
			},
			5,
			2
		);
		$this->runIntegration();

		self::assertTrue( apply_filters( 'rank_math/sitemap/exclude_post_type', false, 'product' ) );
		self::assertTrue( apply_filters( 'rank_math/sitemap/exclude_post_type', true, 'page' ) );
		self::assertTrue( apply_filters( 'rank_math/sitemap/exclude_post_type', true, 'ssp-form' ) );
		self::assertFalse( apply_filters( 'rank_math/sitemap/exclude_post_type', false, 'page' ) );
	}

	private function runIntegration(): void {
		// Sitemap handlers depend on Rank Math's runtime, outside this hook test.
		$integration = $this->getMockBuilder( Rank_Math_Integration::class )
			->onlyMethods( array( 'include_file' ) )
			->getMock();
		$integration->run();
	}
}
