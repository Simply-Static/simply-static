<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Setup_Task;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Extractor;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-setup-task.php';

final class RegexSnapshotWpdb {
	public $insert_id = 0;
	public $inserted_urls = array();

	public function get_blog_prefix(): string { return 'wp_'; }
	public function get_row( $query, $output = null ) { return null; }
	public function query( $query ): int { return 1; }
	public function insert( $table, $data ): int {
		++$this->insert_id;
		$this->inserted_urls[] = $data['url'];
		return 1;
	}
	public function prepare( $query, ...$args ): string {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		return vsprintf( $query, array_map( static function ( $value ) {
			return is_int( $value ) ? $value : "'" . addslashes( (string) $value ) . "'";
		}, $args ) );
	}
}

/** Keep real setup/provider/queue handling, without unrelated filesystem scans. */
final class RegexSnapshotSetupTask extends Setup_Task {
	public function delete_temp_static_files() { return true; }
	public function add_additional_files_to_db( $additional_files ) {}
}

final class AdditionalUrlRegexSnapshotTest extends UnitTestCase {
	private $original_wpdb;
	private $base;
	private $files = array();
	private $directories = array();
	private $setup_calls = 0;
	private $additional_calls = 0;
	private $setup_pattern = '/\/setup-route\/.*$/i';
	private $additional_pattern = '/\/list-route\/.*$/i';

	protected function setUp(): void {
		parent::setUp();
		foreach ( array(
			'src/class-ss-phpuri.php', 'src/class-ss-html-encoding-helper.php',
			'src/handlers/class-ss-page-handler.php', 'src/class-ss-url-fetcher.php',
			'src/class-ss-url-extractor.php',
		) as $source ) { $this->requireSource( $source ); }
		$this->original_wpdb = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = new RegexSnapshotWpdb();
		$this->base = sys_get_temp_dir() . '/ss-regex-snapshot-' . uniqid();
		WpEnv::$options[ Page::SCHEMA_VERSION_OPTION ] = Page::SCHEMA_VERSION;
		WpEnv::$options['simply-static'] = array(
			'archive_deploy_id'    => 'export-one',
			'archive_name'         => 'archive-one',
			'archive_start_time'   => '2026-11-09 20:13:26',
			'archive_end_time'     => null,
			'temp_files_dir'       => $this->base,
			'generate_type'        => 'export',
			'destination_url_type' => 'absolute',
			'destination_scheme'   => 'https://',
			'destination_host'     => 'static.example.test',
			'smart_crawl'          => true,
			'additional_urls'      => '/\/configured\/.*$/i',
		);
		Options::reinstance();
		// Virtual routes are deliberately absent from WordPress's candidate set.
		add_filter( 'ss_regex_candidate_url_limit', static function () { return 0; } );
		add_filter( 'ss_setup_task_additional_urls', function ( $urls ) {
			++$this->setup_calls; // Represents a provider's expensive generation.
			return $urls . "\nhttps://example.test/generated-api.json\n" . $this->setup_pattern;
		} );
		add_filter( 'ss_additional_urls', function ( $urls ) {
			++$this->additional_calls;
			if ( '' !== $this->additional_pattern ) { $urls[] = $this->additional_pattern; }
			return $urls;
		} );
	}

	protected function tearDown(): void {
		foreach ( $this->files as $file ) { unlink( $file ); }
		$directories = array_unique( $this->directories );
		usort( $directories, static function ( $a, $b ) { return strlen( $b ) <=> strlen( $a ); } );
		foreach ( $directories as $directory ) { rmdir( $directory ); }
		if ( is_dir( $this->base ) ) { rmdir( $this->base ); }
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	public function test_setup_resolves_both_providers_once_and_later_workers_keep_their_virtual_route_regexes(): void {
		$this->run_setup();
		self::assertContains( 'https://example.test/generated-api.json', $GLOBALS['wpdb']->inserted_urls );
		for ( $worker = 0; $worker < 3; ++$worker ) {
			Options::reinstance(); // A fresh worker must use persisted results.
			$urls = $this->extract_links();
			foreach ( array( 'configured/one', 'setup-route/one', 'list-route/one', 'wp-content/site.css' ) as $path ) {
				self::assertContains( 'https://example.test/' . $path, $urls );
			}
			self::assertNotContains( 'https://example.test/unselected/one', $urls );
		}
		self::assertSame( 1, $this->setup_calls );
		self::assertSame( 1, $this->additional_calls );
	}

	public function test_an_empty_resolved_snapshot_stays_empty_even_if_configuration_or_providers_change(): void {
		$this->setup_pattern = '';
		$this->additional_pattern = '';
		Options::instance()->set( 'additional_urls', '' )->save();
		$this->run_setup();
		self::assertSame( array(), Options::instance()->get( 'archive_additional_url_regexes' )['regexes'] );
		$this->setup_pattern = '/\/setup-route\/.*$/i';
		$this->additional_pattern = '/\/list-route\/.*$/i';
		Options::instance()->set( 'additional_urls', '/\/configured\/.*$/i' )->save();
		for ( $worker = 0; $worker < 2; ++$worker ) {
			Options::reinstance();
			self::assertSame( array( 'https://example.test/wp-content/site.css' ), $this->extract_links() );
		}
		self::assertSame( 1, $this->setup_calls );
		self::assertSame( 1, $this->additional_calls );
	}

	public function test_a_new_export_replaces_the_previous_provider_regexes(): void {
		$this->run_setup();
		Options::instance()->set( 'archive_deploy_id', 'export-two' )->save();
		// Even if name/start share the same second, the new deploy is distinct.
		self::assertSame( $this->configured_links(), $this->extract_links() );
		$this->setup_pattern = '/\/new-route\/.*$/i';
		$this->additional_pattern = '';
		$this->run_setup();
		Options::reinstance();
		$urls = $this->extract_links( '<a href="/new-route/one">New virtual route</a>' );
		self::assertContains( 'https://example.test/new-route/one', $urls );
		self::assertNotContains( 'https://example.test/setup-route/one', $urls );
		self::assertNotContains( 'https://example.test/list-route/one', $urls );
		self::assertSame( 2, $this->setup_calls );
		self::assertSame( 2, $this->additional_calls );
	}

	/** @dataProvider unusableSnapshots */
	public function test_untrusted_or_finished_archive_snapshots_fall_back_without_replaying_either_provider( string $case ): void {
		$this->run_setup();
		$options = Options::instance();
		if ( 'foreign-blog' === $case ) {
			WpEnv::$current_blog_id = 2;
		} elseif ( 'missing' === $case ) {
			$options->destroy( 'archive_additional_url_regexes' );
		} elseif ( 'malformed-regexes' === $case ) {
			$snapshot = $options->get( 'archive_additional_url_regexes' );
			$snapshot['regexes'][] = array( 'not a regex string' );
			$options->set( 'archive_additional_url_regexes', $snapshot );
		} elseif ( 'missing-identity' === $case ) {
			$options->set( 'archive_additional_url_regexes', array( 'regexes' => array( '/.*/' ) ) );
		} else {
			$changes = array(
				'deploy' => array( 'archive_deploy_id', 'another-export' ),
				'name' => array( 'archive_name', 'another-archive' ),
				'start' => array( 'archive_start_time', '2026-11-09 20:13:27' ),
				'finished' => array( 'archive_end_time', '2026-11-09 20:20:00' ),
				'empty-deploy' => array( 'archive_deploy_id', '' ),
				'blank-deploy' => array( 'archive_deploy_id', '   ' ),
				'nonstring-deploy' => array( 'archive_deploy_id', array( 'export-one' ) ),
				'empty-name' => array( 'archive_name', '' ),
				'empty-start' => array( 'archive_start_time', '' ),
			);
			$options->set( $changes[ $case ][0], $changes[ $case ][1] );
		}
		$options->save();
		Options::reinstance();
		self::assertSame( $this->configured_links(), $this->extract_links() );
		self::assertSame( 1, $this->setup_calls );
		self::assertSame( 1, $this->additional_calls );
	}

	public function unusableSnapshots(): array {
		return array_map( static function ( $case ) { return array( $case ); }, array(
			'deploy', 'name', 'start', 'foreign-blog', 'finished', 'missing',
			'malformed-regexes', 'missing-identity', 'empty-deploy', 'blank-deploy',
			'nonstring-deploy', 'empty-name', 'empty-start',
		) );
	}

	public function test_legacy_or_standalone_extraction_uses_only_current_configured_patterns(): void {
		Options::instance()->set( 'archive_deploy_id', null )->save();
		self::assertSame( $this->configured_links(), $this->extract_links() );
		self::assertSame( 0, $this->setup_calls );
		self::assertSame( 0, $this->additional_calls );
	}

	public function test_setup_without_an_archive_identity_discards_an_old_snapshot(): void {
		$this->run_setup();
		Options::instance()->set( 'archive_deploy_id', '' )->save();
		$this->run_setup();
		Options::reinstance();
		self::assertNull( Options::instance()->get( 'archive_additional_url_regexes' ) );
		self::assertSame( $this->configured_links(), $this->extract_links() );
		self::assertSame( 2, $this->setup_calls );
		self::assertSame( 2, $this->additional_calls );
	}

	private function run_setup(): void {
		$directory = Options::instance()->get_archive_dir();
		$this->directories[] = rtrim( $directory, '/' );
		self::assertTrue( ( new RegexSnapshotSetupTask() )->perform() );
	}

	private function configured_links(): array {
		return array( 'https://example.test/configured/one', 'https://example.test/wp-content/site.css' );
	}

	private function extract_links( string $extra = '' ): array {
		$directory = Options::instance()->get_archive_dir();
		if ( ! is_dir( $directory ) ) { wp_mkdir_p( $directory ); }
		$this->directories[] = rtrim( $directory, '/' );
		$name = uniqid( 'page-', true ) . '.html';
		$file = $directory . $name;
		$this->files[] = $file;
		file_put_contents( $file,
			'<a href="/configured/one">Configured</a><a href="/setup-route/one">Setup provider</a>'
			. '<a href="/list-route/one">List provider</a><a href="/unselected/one">Unselected</a>'
			. '<link rel="stylesheet" href="/wp-content/site.css">' . $extra
		);
		$page = Page::initialize( array(
			'url' => 'https://example.test/page', 'file_path' => $name,
			'http_status_code' => 200, 'content_type' => 'text/html; charset=UTF-8',
		) );
		return array_values( ( new Url_Extractor( $page ) )->extract_and_update_urls() );
	}
}
