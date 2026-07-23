<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Page_Handler;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-phpuri.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/class-ss-skip-further-processing-exception.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-fetch-urls-task.php';

final class FetchUrlsAssetPathMigrationWpdb {

	/** @var array<string,mixed> */
	public $row = array();

	/** @var array<int,array<string,mixed>> */
	public $updates = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/**
	 * @param string $query
	 * @param mixed  $output
	 *
	 * @return array<string,mixed>
	 */
	public function get_row( $query, $output = null ): array {
		return $this->row;
	}

	/**
	 * @param array<string,mixed> $data
	 * @param array<string,mixed> $where
	 */
	public function update( string $table, array $data, array $where ): int {
		$this->updates[] = array(
			'table' => $table,
			'data'  => $data,
			'where' => $where,
		);
		$this->row = array_merge( $this->row, $data );

		return 1;
	}
}

final class FetchUrlsAssetPathMigrationTest extends UnitTestCase {

	private const ASSET_URL = 'https://example.test/wordpress/wp-content/uploads/2022/05/top-menu-04@2x.png';
	private const HASHED_PATH = 'wordpress/wp-content/uploads/2022/05/933db1a4d7081b14748e63e95353a2a4.png';
	private const LEGACY_PATH = 'wordpress/wp-content/uploads/2022/05/top-menu-04@2x.png';

	/** @var FetchUrlsAssetPathMigrationWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		$this->wpdb       = new FetchUrlsAssetPathMigrationWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;

		WpEnv::$home_url = 'https://example.test';
		WpEnv::$site_url = 'https://example.test/wordpress';
		WpEnv::$options['simply-static'] = array(
			'archive_start_time' => '2026-07-12 11:00:00',
			'generate_type'      => 'update',
			'origin_url'         => '',
		);
		Options::reinstance();

		// Match WP Multibyte Patch's behavior for filenames that require URL encoding.
		add_filter(
			'sanitize_file_name',
			static function ( string $sanitized, string $raw ): string {
				return $raw === rawurlencode( $raw ) ? $sanitized : md5( $raw );
			},
			10,
			2
		);
	}

	public function test_update_requeues_existing_asset_when_sanitized_path_changed(): void {
		$this->wpdb->row = $this->childRow( self::LEGACY_PATH );

		( new Fetch_Urls_Task() )->set_url_found_on( $this->parentPage(), self::ASSET_URL );

		self::assertCount( 1, $this->wpdb->updates );
		self::assertSame(
			array(
				'last_modified_at' => '2026-07-12 12:00:00',
			),
			$this->wpdb->updates[0]['data']
		);
		self::assertSame( array( 'id' => 42 ), $this->wpdb->updates[0]['where'] );
	}

	public function test_update_does_not_requeue_asset_when_stored_path_is_current(): void {
		$this->wpdb->row = $this->childRow( self::HASHED_PATH );

		( new Fetch_Urls_Task() )->set_url_found_on( $this->parentPage(), self::ASSET_URL );

		self::assertSame( array(), $this->wpdb->updates );
	}

	/** @return array<string,mixed> */
	private function childRow( string $file_path ): array {
		return array(
			'id'                  => 42,
			'site_id'             => 1,
			'url'                 => self::ASSET_URL,
			'file_path'           => $file_path,
			'http_status_code'    => 200,
			'content_type'        => 'image/png',
			'handler'             => Page_Handler::class,
			'found_on_id'         => 7,
			'fetch_attempts'      => 0,
			'last_checked_at'     => '2026-01-10 08:00:00',
			'last_modified_at'    => '2026-01-10 08:00:00',
			'last_transferred_at' => '2026-01-10 08:00:00',
			'created_at'          => '2026-01-10 08:00:00',
			'updated_at'          => '2026-07-12 12:00:00',
		);
	}

	private function parentPage(): Page {
		return Page::initialize(
			array(
				'id'      => 7,
				'site_id' => 1,
				'url'     => 'https://example.test/',
			)
		);
	}
}
