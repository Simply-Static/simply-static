<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Deploy_Manifest_Service;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Setup_Task;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Upgrade_Handler;

final class PageSchemaWpdb {

	public $exists = true;
	public $column_type = 'varchar(255)';
	public $file_path_type = 'varchar(255)';
	public $url_collation = 'utf8mb4_unicode_520_ci';
	public $file_path_collation = 'utf8mb4_unicode_520_ci';
	public $prefix = 'wp_';
	public $lowercase_table_names = false;
	public $index_prefix = null;
	public $fail_index_change = false;
	public $fail_column_change = false;
	public $last_error = '';
	public $queries = array();
	public $definitions = array();

	public function get_blog_prefix(): string {
		return 1 === get_current_blog_id() ? $this->prefix : $this->prefix . get_current_blog_id() . '_';
	}

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
	}

	public function esc_like( string $value ): string {
		return addcslashes( $value, '_%\\' );
	}

	public function prepare( string $sql, ...$values ): string {
		return vsprintf( str_replace( '%s', "'%s'", $sql ), $values );
	}

	public function get_var( string $query ) {
		$this->queries[] = $query;
		$table = $this->get_blog_prefix() . 'simply_static_pages';
		return $this->exists ? ( $this->lowercase_table_names ? strtolower( $table ) : $table ) : null;
	}

	public function get_row( string $query, $output = null ) {
		$this->queries[] = $query;
		if ( ! $this->exists ) {
			return null;
		}
		$is_path = false !== strpos( $query, "'file_path'" );
		return array(
			'Type' => $is_path ? $this->file_path_type : $this->column_type,
			'Collation' => $is_path ? $this->file_path_collation : $this->url_collation,
		);
	}

	public function get_results( string $query, $output = null ): array {
		$this->queries[] = $query;
		return $this->exists ? array(
			array( 'Column_name' => 'url', 'Sub_part' => $this->index_prefix, 'Non_unique' => '1' ),
		) : array();
	}

	public function query( string $query ) {
		$this->queries[] = $query;
		if ( $this->fail_index_change || $this->fail_column_change ) {
			$this->last_error = 'ALTER command denied';
			return false;
		}
		if ( false !== strpos( $query, 'MODIFY `url` TEXT' ) ) {
			$this->column_type = 'text';
		}
		if ( false !== strpos( $query, 'MODIFY `file_path` TEXT' ) ) {
			$this->file_path_type = 'text';
		}
		if ( false !== strpos( $query, 'ADD KEY `url`' ) ) {
			$this->index_prefix = 191;
		}
		return 0;
	}

	public function applyDefinition( array $queries ): array {
		foreach ( $queries as $query ) {
			$this->queries[] = $query;
			$this->definitions[] = $query;
			if ( ! $this->exists ) {
				$this->exists = true;
				$this->index_prefix = 191;
				$this->column_type = 'text';
				$this->file_path_type = 'text';
			}
		}
		return $queries;
	}
}

final class PageSchemaUpgradeTest extends UnitTestCase {

	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		foreach ( array(
			'src/class-ss-plugin.php',
			'src/class-ss-options.php',
			'src/class-ss-util.php',
			'src/models/class-ss-model.php',
			'src/models/class-ss-page.php',
			'src/class-ss-deploy-manifest-service.php',
			'src/class-ss-upgrade-handler.php',
			'src/tasks/class-ss-task.php',
			'src/tasks/class-ss-setup-task.php',
		) as $source ) {
			$this->requireSource( $source );
		}

		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		wp_mkdir_p( dirname( $upgrade_file ) );
		if ( ! file_exists( $upgrade_file ) ) {
			file_put_contents( $upgrade_file, "<?php\n" );
		}

		$this->wpdb = new PageSchemaWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
		delete_option( Page::SCHEMA_VERSION_OPTION );
		add_filter( 'dbdelta_queries', array( $this->wpdb, 'applyDefinition' ) );
	}

	public function test_new_table_uses_text_storage_and_a_non_unique_prefix_index(): void {
		$this->wpdb->exists = false;

		self::assertTrue( Page::create_or_update_table() );

		self::assertCount( 1, $this->wpdb->definitions );
		self::assertStringContainsString( '`url` TEXT NOT NULL', $this->wpdb->definitions[0] );
		self::assertStringContainsString( '`file_path` TEXT NULL', $this->wpdb->definitions[0] );
		self::assertStringContainsString( 'KEY url (url(191))', $this->wpdb->definitions[0] );
		self::assertSame( Page::SCHEMA_VERSION, get_option( Page::SCHEMA_VERSION_OPTION ) );
	}

	/** @dataProvider legacyColumnProvider */
	public function test_existing_and_manually_widened_tables_convert_columns_and_index_in_one_alter( string $type ): void {
		$this->wpdb->column_type = $type;

		self::assertTrue( Page::maybe_upgrade_schema() );

		$alter = 'ALTER TABLE `wp_simply_static_pages` MODIFY `url` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL, MODIFY `file_path` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NULL, DROP INDEX `url`, ADD KEY `url` (`url`(191))';
		self::assertContains( $alter, $this->wpdb->queries );
		self::assertLessThan(
			array_search( $this->wpdb->definitions[0], $this->wpdb->queries, true ),
			array_search( $alter, $this->wpdb->queries, true )
		);
		self::assertSame( 'text', $this->wpdb->column_type );
		self::assertSame( 'text', $this->wpdb->file_path_type );
		self::assertSame( 191, $this->wpdb->index_prefix );
	}

	public function legacyColumnProvider(): array {
		return array( 'original schema' => array( 'varchar(255)' ), 'manual workaround' => array( 'varchar(512)' ) );
	}

	public function test_ready_schema_marker_avoids_queries_on_subsequent_requests(): void {
		self::assertTrue( Page::maybe_upgrade_schema() );
		$this->wpdb->queries = array();

		self::assertTrue( Page::maybe_upgrade_schema() );
		self::assertSame( array(), $this->wpdb->queries );
	}

	public function test_failed_index_migration_does_not_attempt_a_text_conversion_and_can_retry(): void {
		$this->wpdb->fail_index_change = true;

		self::assertFalse( Page::maybe_upgrade_schema() );
		self::assertFalse( get_option( Page::SCHEMA_VERSION_OPTION ) );
		self::assertSame( array(), $this->wpdb->definitions );
		self::assertSame( 'varchar(255)', $this->wpdb->column_type );

		$this->wpdb->fail_index_change = false;
		$this->wpdb->last_error = '';
		self::assertTrue( Page::maybe_upgrade_schema() );
	}

	public function test_failed_atomic_conversion_preserves_both_columns_and_the_original_index_for_retry(): void {
		$this->wpdb->fail_column_change = true;

		self::assertFalse( Page::maybe_upgrade_schema() );
		self::assertFalse( get_option( Page::SCHEMA_VERSION_OPTION ) );
		self::assertSame( 'varchar(255)', $this->wpdb->column_type );
		self::assertSame( 'varchar(255)', $this->wpdb->file_path_type );
		self::assertNull( $this->wpdb->index_prefix );
		self::assertSame( array(), $this->wpdb->definitions );

		$this->wpdb->fail_column_change = false;
		$this->wpdb->last_error = '';
		self::assertTrue( Page::maybe_upgrade_schema() );
		self::assertSame( 'text', $this->wpdb->column_type );
	}

	public function test_each_column_keeps_its_own_collation_instead_of_using_the_table_default(): void {
		$this->wpdb->url_collation = 'utf8mb4_bin';
		$this->wpdb->file_path_collation = 'utf8_unicode_ci';
		self::assertTrue( Page::maybe_upgrade_schema() );

		$queries = implode( "\n", $this->wpdb->queries );
		self::assertStringContainsString( 'MODIFY `url` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL', $queries );
		self::assertStringContainsString( 'MODIFY `file_path` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL', $queries );
	}

	public function test_uppercase_prefix_can_find_a_table_stored_in_lowercase_by_mysql(): void {
		$this->wpdb->prefix = 'WP_';
		$this->wpdb->lowercase_table_names = true;
		self::assertTrue( Page::maybe_upgrade_schema() );
		self::assertStringContainsString( 'ALTER TABLE `WP_simply_static_pages` MODIFY `url`', implode( "\n", $this->wpdb->queries ) );
	}

	public function test_manually_larger_text_columns_are_not_shrunk(): void {
		$this->wpdb->column_type = 'longtext';
		$this->wpdb->file_path_type = 'mediumtext';
		self::assertTrue( Page::maybe_upgrade_schema() );
		self::assertSame( 'longtext', $this->wpdb->column_type );
		self::assertSame( 'mediumtext', $this->wpdb->file_path_type );
		self::assertStringNotContainsString( 'MODIFY', implode( "\n", $this->wpdb->queries ) );
	}

	public function test_first_page_query_after_switching_a_blog_prepares_schema_before_wpdb_can_cache_old_lengths(): void {
		WpEnv::$multisite = true;
		switch_to_blog( 42 );
		Page::query();

		self::assertSame( 'text', $this->wpdb->column_type );
		self::assertSame( 'text', $this->wpdb->file_path_type );
		self::assertStringContainsString( 'wp_42_simply_static_pages', implode( "\n", $this->wpdb->queries ) );
		restore_current_blog();
	}

	public function test_direct_save_stops_before_a_write_when_target_blog_migration_fails(): void {
		$this->wpdb->fail_column_change = true;
		$page = Page::initialize( array( 'url' => 'https://example.test/page' ) );
		self::assertFalse( $page->save() );
		self::assertNull( $page->id );
		self::assertFalse( get_option( Page::SCHEMA_VERSION_OPTION ) );
	}

	public function test_boot_upgrades_schema_even_when_plugin_version_has_not_changed(): void {
		WpEnv::$options['simply-static'] = array(
			'version' => SIMPLY_STATIC_VERSION,
			'deploy_manifest_schema_version' => Deploy_Manifest_Service::SCHEMA_VERSION,
		);
		Options::reinstance();

		Upgrade_Handler::run();

		self::assertSame( 'text', $this->wpdb->column_type );
		self::assertSame( Page::SCHEMA_VERSION, get_option( Page::SCHEMA_VERSION_OPTION ) );
	}

	public function test_switched_multisite_blog_uses_its_own_table_and_blog_schema_marker(): void {
		WpEnv::$multisite = true;
		switch_to_blog( 42 );

		self::assertTrue( Page::maybe_upgrade_schema() );

		self::assertStringContainsString( 'CREATE TABLE wp_42_simply_static_pages', $this->wpdb->definitions[0] );
		self::assertSame( Page::SCHEMA_VERSION, get_option( Page::SCHEMA_VERSION_OPTION ) );
		self::assertFalse( get_site_option( Page::SCHEMA_VERSION_OPTION ) );
		restore_current_blog();
	}

	public function test_setup_stops_before_deleting_files_or_pages_when_schema_cannot_be_upgraded(): void {
		$this->wpdb->fail_index_change = true;

		$result = ( new Setup_Task() )->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'pages_schema_upgrade_failed', $result->get_error_code() );
		self::assertSame( array(), $this->wpdb->definitions );
		self::assertStringNotContainsString( 'DELETE', implode( "\n", $this->wpdb->queries ) );
	}
}
