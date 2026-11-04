<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\SEOPress_Integration;
use Simply_Static\Tests\Support\UnitTestCase;

final class SeopressSitemapExclusionTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/handlers/class-ss-page-handler.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-seopress-integration.php' );
	}

	public function test_internal_forms_are_removed_from_the_selection_used_by_index_and_child_routes(): void {
		( new SEOPress_Integration() )->run();
		$options = array(
			'seopress_xml_sitemap_post_types_list' => array(
				'page'     => array( 'include' => '1' ),
				'ssp-form' => array( 'include' => '1' ),
			),
		);

		$filtered = apply_filters( 'option_seopress_xml_sitemap_option_name', $options );

		self::assertSame(
			array( 'page' => array( 'include' => '1' ) ),
			$filtered['seopress_xml_sitemap_post_types_list']
		);
		self::assertArrayHasKey( 'ssp-form', $options['seopress_xml_sitemap_post_types_list'] );
	}

	public function test_other_post_type_selections_and_sitemap_settings_are_preserved(): void {
		( new SEOPress_Integration() )->run();
		$options = array(
			'seopress_xml_sitemap_general_enable'  => '1',
			'seopress_xml_sitemap_post_types_list' => array(
				'page'                => array( 'include' => '1' ),
				'post'                => array( 'include' => '0' ),
				'product'             => array(),
				'ssp-form-submission' => array( 'include' => '1' ),
				'ssp-form'            => array( 'include' => '1' ),
			),
			'seopress_xml_sitemap_taxonomies_list' => array(
				'category' => array( 'include' => '0' ),
			),
		);
		$expected = $options;
		unset( $expected['seopress_xml_sitemap_post_types_list']['ssp-form'] );

		self::assertSame( $expected, apply_filters( 'option_seopress_xml_sitemap_option_name', $options ) );
	}

	public function test_missing_or_malformed_options_are_left_unchanged(): void {
		( new SEOPress_Integration() )->run();

		foreach ( array( false, null, '', array(), array( 'seopress_xml_sitemap_post_types_list' => '' ), array( 'seopress_xml_sitemap_post_types_list' => null ) ) as $options ) {
			self::assertSame( $options, apply_filters( 'option_seopress_xml_sitemap_option_name', $options ) );
		}
	}
}
