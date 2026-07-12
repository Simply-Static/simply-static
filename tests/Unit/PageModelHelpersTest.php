<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Additional_File_Handler;
use Simply_Static\Page;
use Simply_Static\Page_Handler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-additional-file-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';

final class PageModelHelpersWpdb {

	/** @var array<int,array<string,mixed>> */
	public $summary_rows = array();

	/** @var array<string,mixed>|null */
	public $row;

	/** @var string[] */
	public $queries = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/**
	 * @param string $query
	 * @param mixed $output
	 * @return array<int,array<string,mixed>>
	 */
	public function get_results( $query, $output = null ): array {
		$this->queries[] = $query;
		return $this->summary_rows;
	}

	/**
	 * @param string $query
	 * @param mixed $output
	 * @return array<string,mixed>|null
	 */
	public function get_row( $query, $output = null ) {
		$this->queries[] = $query;
		return $this->row;
	}

	public function query( string $query ): int {
		$this->queries[] = $query;
		return 1;
	}
}

final class PageModelCustomHandler extends Page_Handler {
}

final class PageModelHelpersTest extends UnitTestCase {

	/** @var PageModelHelpersWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb       = new PageModelHelpersWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
		WpEnv::$current_blog_id = 3;
	}

	public function test_attributes_default_site_id_but_preserve_an_explicit_site(): void {
		$default = Page::initialize( array( 'url' => 'https://example.test/default' ) );
		$explicit = Page::initialize(
			array(
				'url'     => 'https://example.test/explicit',
				'site_id' => 11,
			)
		);

		self::assertSame( 3, $default->site_id );
		self::assertSame( 11, $explicit->site_id );
	}

	public function test_error_and_status_messages_append_distinct_values_without_duplicates(): void {
		$page = Page::initialize( array( 'url' => 'https://example.test/' ) );

		$page->set_error_message( 'Timed out' );
		$page->set_error_message( 'Timed out' );
		$page->set_error_message( 'Invalid response' );
		$page->set_status_message( 'Skipped query string' );
		$page->set_status_message( 'Skipped query string' );
		$page->set_status_message( 'Saved redirect' );

		self::assertSame( 'Timed out; Invalid response', $page->error_message );
		self::assertSame( 'Skipped query string; Saved redirect', $page->status_message );
	}

	/**
	 * @dataProvider binaryPageProvider
	 * @param array<string,mixed> $attributes
	 */
	public function test_binary_detection_uses_mime_extension_and_additional_file_handler( array $attributes ): void {
		self::assertTrue( Page::initialize( $attributes )->is_binary_file() );
	}

	/** @return array<string,array{array<string,mixed>}> */
	public function binaryPageProvider(): array {
		return array(
			'image mime with parameters' => array(
				array( 'content_type' => 'image/svg+xml; charset=UTF-8' ),
			),
			'uppercase extension' => array(
				array( 'content_type' => 'text/plain', 'file_path' => '/assets/manual.PDF' ),
			),
			'additional file without mime' => array(
				array( 'content_type' => null, 'handler' => Additional_File_Handler::class ),
			),
		);
	}

	public function test_text_pages_are_not_binary_and_type_checks_are_case_insensitive(): void {
		$page = Page::initialize(
			array(
				'content_type' => 'Text/HTML; Charset=UTF-8',
				'file_path'    => '/index.html',
			)
		);

		self::assertTrue( $page->is_type( 'text/html' ) );
		self::assertFalse( $page->is_type( 'image/' ) );
		self::assertFalse( $page->is_binary_file() );
		$page->content_type = null;
		self::assertFalse( $page->is_type( 'text' ) );
	}

	public function test_handler_resolution_accepts_absolute_and_short_names_and_falls_back_safely(): void {
		$absolute = Page::initialize( array( 'handler' => PageModelCustomHandler::class ) );
		$short    = Page::initialize( array( 'handler' => 'Additional_File_Handler' ) );
		$missing  = Page::initialize( array( 'handler' => 'No_Such_Handler' ) );

		self::assertSame( PageModelCustomHandler::class, $absolute->get_handler_class() );
		self::assertInstanceOf( PageModelCustomHandler::class, $absolute->get_handler() );
		self::assertSame( '\\' . Additional_File_Handler::class, $short->get_handler_class() );
		self::assertInstanceOf( Additional_File_Handler::class, $short->get_handler() );
		self::assertSame( Page_Handler::class, $missing->get_handler_class() );
		self::assertInstanceOf( Page_Handler::class, $missing->get_handler() );
	}

	public function test_json_helpers_round_trip_falsey_values_and_merge_keys(): void {
		$page = Page::initialize( array() );
		$page->set_json_data_by_key( 'zero', 0 );
		$page->set_json_data_by_key( 'disabled', false );
		$page->set_json_data_by_key( 'empty', '' );
		$page->set_json_data_by_key( 'items', array( 'one' ) );

		self::assertSame( 0, $page->get_json_data_by_key( 'zero' ) );
		self::assertFalse( $page->get_json_data_by_key( 'disabled' ) );
		self::assertSame( '', $page->get_json_data_by_key( 'empty' ) );
		self::assertSame( array( 'one' ), $page->get_json_data_by_key( 'items' ) );
		self::assertNull( $page->get_json_data_by_key( 'missing' ) );

		$page->json = '{invalid';
		self::assertNull( $page->get_json_data_by_key( 'zero' ) );
	}

	public function test_content_hash_setter_updates_the_timestamp(): void {
		$page = Page::initialize( array() );
		$hash = sha1( 'page body', true );

		$page->set_content_hash( $hash );

		self::assertSame( $hash, $page->content_hash );
		self::assertSame( '2026-07-12 12:00:00', $page->last_modified_at );
		self::assertTrue( $page->is_content_identical( $hash ) );
	}

	public function test_http_status_summary_fills_missing_buckets(): void {
		$this->wpdb->summary_rows = array(
			array( 'status' => '2', 'count' => '15' ),
			array( 'status' => '4', 'count' => '3' ),
		);

		self::assertSame(
			array( '1' => 0, '2' => '15', '3' => 0, '4' => '3', '5' => 0 ),
			Page::get_http_status_codes_summary()
		);
		self::assertStringContainsString( 'GROUP BY LEFT(http_status_code, 1)', $this->wpdb->queries[0] );
	}

	public function test_parent_lookup_and_delete_are_scoped_page_queries(): void {
		$this->wpdb->row = array(
			'id'      => 41,
			'site_id' => 3,
			'url'     => 'https://example.test/parent',
		);
		$page = Page::initialize(
			array(
				'id'          => 42,
				'found_on_id' => 41,
				'url'         => 'https://example.test/child',
			)
		);

		$parent = $page->parent_static_page();
		self::assertSame( 41, $parent->id );
		self::assertStringContainsString( "id = '41'", $this->wpdb->queries[0] );
		self::assertStringContainsString( 'site_id=3', $this->wpdb->queries[0] );
		self::assertStringEndsWith( ' LIMIT 1', $this->wpdb->queries[0] );

		self::assertSame( 1, $page->delete() );
		self::assertSame(
			"DELETE FROM  wp_simply_static_pages WHERE 1=1 AND id = '42' AND site_id=3",
			$this->wpdb->queries[1]
		);
	}
}
