<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';
require_once dirname( __DIR__, 2 ) . '/src/crawler/class-ss-crawler.php';

final class PagePersistenceWpdb {
	public $insert_id = 37;
	public $last_error = '';
	public $write_result = 1;
	public $writes = array();
	public $claim_result = 1;
	public $url_column_length = array( 'type' => 'byte', 'length' => 65535 );
	public $length_lookups = array();

	public function get_blog_prefix(): string {
		return 'wp_' . get_current_blog_id() . '_';
	}

	public function insert( $table, $data ) {
		$this->writes[] = array( 'table' => $table, 'data' => $data );
		return $this->write_result;
	}

	public function update( $table, $data, $where ) {
		return $this->insert( $table, $data );
	}

	public function get_row( $query, $output = null ) {
		return null;
	}

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function query( $query ) {
		return $this->claim_result;
	}

	public function get_col_length( $table, $column ) {
		$this->length_lookups[] = array( $table, $column );
		return $this->url_column_length;
	}
}

final class PagePersistenceTest extends UnitTestCase {
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb = new PagePersistenceWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	public function test_long_urls_with_a_common_index_prefix_are_saved_in_full(): void {
		$prefix = 'https://example.test/' . str_repeat( 'category/', 60 );
		foreach ( array( $prefix . '?tag=first', $prefix . '?tag=second' ) as $url ) {
			$page = Page::initialize( array( 'url' => $url ) );
			self::assertTrue( $page->save() );
		}

		self::assertSame( $prefix . '?tag=first', $this->wpdb->writes[0]['data']['url'] );
		self::assertSame( $prefix . '?tag=second', $this->wpdb->writes[1]['data']['url'] );
		self::assertNull( Options::instance()->get( 'archive_status_messages' ) );
		self::assertSame( array(), $this->wpdb->length_lookups );
	}

	public function test_long_nested_url_preserves_its_generated_artifact_path_when_saved_after_fetching(): void {
		$relative_directory = str_repeat( 'nested-category/', 20 ) . 'long-article/';
		$url = 'https://example.test/' . $relative_directory;
		$page = Page::initialize( array( 'url' => $url, 'content_type' => 'text/html' ) );
		self::assertTrue( $page->save() );

		$fetcher = ( new \ReflectionClass( Url_Fetcher::class ) )->newInstanceWithoutConstructor();
		$page->file_path = $fetcher->get_expected_file_path_for_static_page( $page );
		$page->http_status_code = 200;

		self::assertGreaterThan( 255, strlen( $page->file_path ) );
		self::assertSame( $relative_directory . 'index.html', $page->file_path );
		self::assertTrue( $page->save() );
		self::assertSame( $url, $this->wpdb->writes[0]['data']['url'] );
		self::assertSame( $relative_directory . 'index.html', $this->wpdb->writes[1]['data']['file_path'] );
		self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );

		// A URL-only roundtrip misses the later write performed by the fetcher.
		// This unindexed field must also support the complete generated path.
		$columns = new \ReflectionProperty( Page::class, 'columns' );
		$columns->setAccessible( true );
		self::assertSame( 'TEXT NULL', $columns->getValue()['file_path'] );
	}

	public function test_text_byte_limit_accepts_the_boundary_and_rejects_oversize_without_a_write(): void {
		$url = 'https://example.test/' . str_repeat( 'a', 65535 - strlen( 'https://example.test/' ) );
		self::assertTrue( Page::initialize( array( 'url' => $url ) )->save() );
		self::assertFalse( Page::initialize( array( 'url' => $url . 'a' ) )->save() );
		self::assertCount( 1, $this->wpdb->writes );
		self::assertStringContainsString( '65,535 bytes', $this->lastMessage() );
	}

	public function test_limit_counts_bytes_rather_than_unicode_characters(): void {
		$url = 'https://example.test/' . str_repeat( 'é', 33000 );
		self::assertFalse( Page::initialize( array( 'url' => $url ) )->save() );
		self::assertSame( array(), $this->wpdb->writes );
		self::assertStringContainsString( '65,535 bytes', $this->lastMessage() );
	}

	/** @dataProvider widerTextCapacityProvider */
	public function test_existing_urls_in_custom_wider_text_columns_can_save_fetched_metadata( int $capacity ): void {
		$this->wpdb->url_column_length = array( 'type' => 'byte', 'length' => $capacity );
		$url = 'https://example.test/' . str_repeat( 'a', 70000 );
		$page = Page::initialize( array( 'id' => 9, 'url' => $url ) );
		$page->http_status_code = 200;
		$page->file_path = 'article/index.html';

		self::assertTrue( $page->save() );
		self::assertSame( $url, $page->url );
		self::assertSame( 'article/index.html', $this->wpdb->writes[0]['data']['file_path'] );
		self::assertArrayNotHasKey( 'url', $this->wpdb->writes[0]['data'] );
		self::assertSame( array( array( 'wp_1_simply_static_pages', 'url' ) ), $this->wpdb->length_lookups );
		self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );
	}

	public function widerTextCapacityProvider(): array {
		return array(
			'mediumtext' => array( 16777215 ),
			'longtext'   => array( 4294967295 ),
		);
	}

	public function test_unknown_column_capacity_keeps_the_safe_text_byte_limit(): void {
		$this->wpdb->url_column_length = false;
		$url = 'https://example.test/' . str_repeat( 'a', 70000 );

		self::assertFalse( Page::initialize( array( 'url' => $url ) )->save() );
		self::assertSame( array(), $this->wpdb->writes );
		self::assertStringContainsString( '65,535 bytes', $this->lastMessage() );
	}

	public function test_failed_insert_reports_url_table_and_database_error_without_assigning_an_id(): void {
		WpEnv::$current_blog_id = 12;
		$this->startExport();
		$this->wpdb->write_result = false;
		$this->wpdb->last_error = 'INSERT command denied';
		$url = 'https://example.test/' . str_repeat( 'long-path/', 40 );
		$page = Page::initialize( array( 'url' => $url ) );

		self::assertFalse( $page->save() );
		self::assertNull( $page->id );
		self::assertStringContainsString( $url, $this->lastMessage() );
		self::assertStringContainsString( 'wp_12_simply_static_pages', $this->lastMessage() );
		self::assertStringContainsString( 'INSERT command denied', $this->lastMessage() );
		self::assertStringContainsString( $url, Options::instance()->get( 'archive_page_write_error' ) );
	}

	public function test_failed_update_keeps_dirty_fields_for_retry_and_zero_rows_is_success(): void {
		$page = Page::initialize( array( 'id' => 9, 'url' => 'https://example.test/old' ) );
		$page->url = 'https://example.test/new';
		$this->wpdb->write_result = false;
		self::assertFalse( $page->save() );
		$this->wpdb->write_result = 0;
		self::assertTrue( $page->save() );
		self::assertSame( $this->wpdb->writes[0]['data'], $this->wpdb->writes[1]['data'] );
		self::assertTrue( $page->save() );
		self::assertCount( 2, $this->wpdb->writes );
	}

	public function test_distinct_failures_are_logged_without_overwriting_the_initial_export_error(): void {
		$this->startExport();
		$this->wpdb->write_result = false;
		self::assertFalse( Page::initialize( array( 'url' => 'https://example.test/first/' ) )->save() );
		self::assertFalse( Page::initialize( array( 'url' => 'https://example.test/second/' ) )->save() );
		self::assertCount( 2, Options::instance()->get( 'archive_status_messages' ) );
		self::assertStringContainsString( '/first/', Options::instance()->get( 'archive_page_write_error' ) );
	}

	public function test_failed_write_is_recorded_in_the_debug_log_when_enabled(): void {
		$log_file = tempnam( sys_get_temp_dir(), 'ss-page-write-' );
		add_filter( 'ss_debug_log_file', static function () use ( $log_file ) {
			return $log_file;
		} );
		Options::instance()->set( 'debugging_mode', true )->save();
		$this->wpdb->write_result = false;
		$this->wpdb->last_error = 'INSERT command denied';

		try {
			self::assertFalse( Page::initialize( array( 'url' => 'https://example.test/missing/' ) )->save() );
			$contents = file_get_contents( $log_file );
			self::assertStringContainsString( 'https://example.test/missing/', $contents );
			self::assertStringContainsString( 'INSERT command denied', $contents );
		} finally {
			unlink( $log_file );
		}
	}

	public function test_fallback_reason_is_actionable_and_activity_text_is_escaped_and_deduplicated(): void {
		$this->wpdb->write_result = false;
		$url = 'https://example.test/?tag=<script>alert(1)</script>';
		$page = Page::initialize( array( 'url' => $url ) );
		self::assertFalse( $page->save() );
		self::assertFalse( $page->save() );
		self::assertCount( 1, Options::instance()->get( 'archive_status_messages' ) );
		self::assertStringNotContainsString( '<script>', $this->lastMessage() );
		self::assertStringContainsString( '&lt;script&gt;', $this->lastMessage() );
		self::assertStringContainsString( 'schema and permissions', $this->lastMessage() );
		self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );
	}

	public function test_failed_claim_reports_database_error_but_a_lost_race_does_not(): void {
		$this->startExport();
		$page = Page::initialize( array( 'id' => 9, 'url' => 'https://example.test/' ) );
		$this->wpdb->claim_result = 0;
		self::assertFalse( $page->claim_for_processing( 'last_checked_at', '2026-11-06 08:00:00' ) );
		self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );
		$this->wpdb->claim_result = false;
		$this->wpdb->last_error = 'UPDATE command denied';
		self::assertFalse( $page->claim_for_processing( 'last_checked_at', '2026-11-06 08:00:00' ) );
		self::assertStringContainsString( 'UPDATE command denied', $this->lastMessage() );
	}

	public function test_crawler_does_not_count_a_failed_write_as_queued(): void {
		$this->wpdb->write_result = false;
		$crawler = new class extends \Simply_Static\Crawler\Crawler {
			public function detect(): array {
				return array( 'https://example.test/missing/' );
			}
		};

		self::assertSame( 0, $crawler->add_urls_to_queue() );
		self::assertStringContainsString( '/missing/', $this->lastMessage() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider cliLifecycleProvider
	 */
	public function test_cli_write_warns_and_throws_before_and_during_export_for_command_cleanup( bool $active ): void {
		define( 'WP_CLI', true );
		require_once __DIR__ . '/../Support/PagePersistenceCliStub.php';
		if ( $active ) {
			$this->startExport();
		}
		$this->wpdb->write_result = false;
		$this->wpdb->last_error = "Database failure\n\033[31m";
		$page = Page::initialize( array( 'url' => 'https://example.test/missing/' ) );

		try {
			$page->save();
			self::fail( 'A CLI command must stop on a failed page write.' );
		} catch ( \RuntimeException $error ) {
			self::assertSame( array( $error->getMessage() ), \WP_CLI::$warnings );
			self::assertStringContainsString( '/missing/', $error->getMessage() );
			self::assertStringNotContainsString( "\n", $error->getMessage() );
			self::assertStringNotContainsString( "\033", $error->getMessage() );
			if ( $active ) {
				self::assertNotNull( Options::instance()->get( 'archive_page_write_error' ) );
			} else {
				self::assertNull( Options::instance()->get( 'archive_page_write_error' ) );
			}
		}
	}

	public function cliLifecycleProvider(): array {
		return array( 'preparing a build' => array( false ), 'running export tasks' => array( true ) );
	}

	private function startExport(): void {
		Options::instance()->set( 'archive_start_time', '2026-11-06 08:00:00' )->set( 'archive_end_time', null )->save();
	}

	private function lastMessage(): string {
		$messages = Options::instance()->get( 'archive_status_messages' );
		return end( $messages )['message'];
	}
}
