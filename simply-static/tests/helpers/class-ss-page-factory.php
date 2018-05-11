<?php
namespace Simply_Static;

/**
 * Returns a faked Page
 */
class Page_Factory extends \WP_UnitTestCase {

	const DOMAIN = 'http://example.org';

	public static function create( $attrs = array() ) {
		$faker = \Faker\Factory::create();

		$attributes = array(
			'url' => self::DOMAIN . '/' . $faker->slug,
			'file_path' => $faker->word . '.html',
			'http_status_code' => 200,
			'content_type' => '',
			'content_hash' => sha1( $faker->paragraph(), true ),
			'last_checked_at' => Util::formatted_datetime(),
			'last_modified_at' => Util::formatted_datetime(),
			'last_transferred_at' => Util::formatted_datetime(),
			'created_at' => Util::formatted_datetime(),
			'updated_at' => Util::formatted_datetime()
		);
		// merge in any provided attributes
		$attributes = array_merge( $attributes, $attrs );

		$page = Page::initialize( $attributes );
		$page->save();
		return $page;
	}
}
