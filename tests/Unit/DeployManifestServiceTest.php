<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use RuntimeException;
use Simply_Static\Deploy_Manifest_Service;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;

final class DeployManifestServiceTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/models/class-ss-deploy-manifest.php' );
		$this->requireSource( 'src/models/class-ss-deploy-manifest-url.php' );
		$this->requireSource( 'src/class-ss-deploy-manifest-service.php' );
	}

	public function test_parent_urls_are_reused_without_a_query_per_record(): void {
		$service = $this->service();
		$parent  = Page::initialize(
			array(
				'id'               => 10,
				'url'              => 'https://example.test/parent/',
				'content_type'     => 'text/html',
				'http_status_code' => 200,
			)
		);
		$child   = Page::initialize(
			array(
				'id'               => 11,
				'found_on_id'      => 10,
				'url'              => 'https://example.test/child/',
				'content_type'     => 'text/html',
				'http_status_code' => 200,
			)
		);

		$parent_urls = $service->parentUrlMap( array( $parent, $child ) );
		$record      = $service->pageRecord( $child, $parent_urls[10] );

		self::assertSame( 'https://example.test/parent/', $parent_urls[10] );
		self::assertSame( array( 'https://example.test/parent/' ), $record['found_on'] );
		self::assertSame( 'page', $record['type'] );
	}

	public function test_manifest_query_excludes_pages_without_a_static_path(): void {
		global $wpdb;

		$wpdb                = new ManifestWpdb();
		$wpdb->selectResults = array(
			array(
				'id'               => 10,
				'url'              => 'https://example.test/',
				'file_path'        => 'index.html',
				'content_type'     => 'text/html',
				'http_status_code' => 200,
			),
		);

		$records = $this->service()->buildRecords();

		self::assertCount( 1, $records );
		self::assertSame( 'index.html', $records[0]['static_path'] );
		self::assertStringContainsString( 'file_path IS NOT NULL', $wpdb->selectQueries[0] );
		self::assertStringContainsString( "file_path != ''", $wpdb->selectQueries[0] );
	}

	public function test_manifest_and_urls_are_persisted_in_one_transaction(): void {
		global $wpdb;

		$wpdb    = new ManifestWpdb();
		$service = $this->service();
		$service->persist( $this->manifestFixture() );

		self::assertSame( 'START TRANSACTION', $wpdb->queries[0] );
		self::assertStringStartsWith( 'INSERT INTO `wp_simply_static_deploy_manifest_urls`', $wpdb->queries[1] );
		self::assertSame( 'COMMIT', $wpdb->queries[2] );
		self::assertCount( 2, $wpdb->deletes );
		self::assertCount( 1, $wpdb->inserts );
		self::assertCount( 1, $wpdb->prepareCalls );
		self::assertSame( 'deploy-test', $wpdb->inserts[0]['data']['deploy_id'] );
		self::assertContains( 'https://example.test/', $wpdb->prepareCalls[0]['args'] );
		self::assertContains( 'https://example.test/about/', $wpdb->prepareCalls[0]['args'] );
	}

	public function test_failed_url_insert_rolls_back_the_manifest(): void {
		global $wpdb;

		$wpdb                     = new ManifestWpdb();
		$wpdb->failQueryContaining = 'INSERT INTO';
		$service                  = $this->service();

		try {
			$service->persist( $this->manifestFixture() );
			self::fail( 'Expected persistence to fail.' );
		} catch ( RuntimeException $e ) {
			self::assertSame( 'Unable to store deploy manifest URLs.', $e->getMessage() );
		}

		self::assertSame( 'START TRANSACTION', $wpdb->queries[0] );
		self::assertStringStartsWith( 'INSERT INTO', $wpdb->queries[1] );
		self::assertSame( 'ROLLBACK', $wpdb->queries[2] );
	}

	/** @return array<string,mixed> */
	private function manifestFixture(): array {
		return array(
			'manifest_version' => '1.0',
			'deploy_id'        => 'deploy-test',
			'status'           => 'success',
			'domain'           => 'https://example.test',
			'mount_path'       => '',
			'started_at'       => '2026-07-12 10:00:00',
			'finished_at'      => '2026-07-12 10:00:01',
			'duration_seconds' => 1,
			'plugin_version'   => 'test',
			'wp_version'       => '6.8',
			'php_version'      => PHP_VERSION,
			'generate_type'    => 'export',
			'url_counts'       => array( 'total' => 2 ),
			'root_files'       => array(),
			'warnings'         => array(),
			'errors'           => array(),
			'urls'             => array(
				array(
					'url'                => 'https://example.test/',
					'source_url'         => null,
					'static_path'        => 'index.html',
					'type'               => 'page',
					'status_code'        => 200,
					'content_hash'       => 'sha1:abc',
					'file_size'          => 12,
					'redirect_target'    => null,
					'found_on'           => array(),
					'in_sitemap'         => null,
					'markdown_generated' => null,
					'warnings'           => array(),
					'errors'             => array(),
				),
				array(
					'url'                => 'https://example.test/about/',
					'source_url'         => null,
					'static_path'        => 'about/index.html',
					'type'               => 'page',
					'status_code'        => 200,
					'content_hash'       => null,
					'file_size'          => 24,
					'redirect_target'    => null,
					'found_on'           => array( 'https://example.test/' ),
					'in_sitemap'         => true,
					'markdown_generated' => false,
					'warnings'           => array(),
					'errors'             => array(),
				),
			),
		);
	}

	private function service(): Deploy_Manifest_Service {
		return new class extends Deploy_Manifest_Service {
			public function __construct() {
			}

			/**
			 * @param Page[] $pages
			 * @return array<int,string>
			 */
			public function parentUrlMap( array $pages ): array {
				return $this->get_parent_url_map( $pages );
			}

			/** @return array<string,mixed> */
			public function pageRecord( Page $page, ?string $parent_url ): array {
				return $this->format_page_record( $page, $parent_url );
			}

			/** @return array<int,array<string,mixed>> */
			public function buildRecords(): array {
				return $this->build_url_records();
			}

			/** @param array<string,mixed> $manifest */
			public function persist( array $manifest ): void {
				$this->persist_manifest( $manifest );
			}

			/** @return array<string,mixed> */
			protected function get_export_scope() {
				return array();
			}

			/** @return int|null */
			protected function get_file_size( $file_path ) {
				return null;
			}
		};
	}
}

final class ManifestWpdb {

	/** @var string[] */
	public $queries = array();

	/** @var array<int,array<string,mixed>> */
	public $deletes = array();

	/** @var array<int,array{table:string,data:array<string,mixed>}> */
	public $inserts = array();

	/** @var array<int,array{sql:string,args:array<int,mixed>}> */
	public $prepareCalls = array();

	/** @var array<int,array<string,mixed>> */
	public $selectResults = array();

	/** @var string[] */
	public $selectQueries = array();

	/** @var string|null */
	public $failQueryContaining = null;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @return array<int,array<string,mixed>> */
	public function get_results( string $sql, $output = null ): array {
		$this->selectQueries[] = $sql;
		return $this->selectResults;
	}

	/** @return int|false */
	public function query( string $sql ) {
		$this->queries[] = $sql;

		if ( null !== $this->failQueryContaining && false !== strpos( $sql, $this->failQueryContaining ) ) {
			return false;
		}

		return 0;
	}

	/** @param array<int,mixed> $args */
	public function prepare( string $sql, array $args ): string {
		$this->prepareCalls[] = array( 'sql' => $sql, 'args' => $args );
		return $sql . ' /* prepared */';
	}

	/** @param array<string,mixed> $where
	 *  @return int|false
	 */
	public function delete( string $table, array $where ) {
		$this->deletes[] = array( 'table' => $table, 'where' => $where );
		return 1;
	}

	/** @param array<string,mixed> $data
	 *  @return int|false
	 */
	public function insert( string $table, array $data ) {
		$this->inserts[] = array( 'table' => $table, 'data' => $data );
		return 1;
	}
}
