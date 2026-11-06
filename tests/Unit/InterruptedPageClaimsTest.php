<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Task;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';

final class InterruptedClaimsWpdb {
	public $rows = array();
	public $queries = array();

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function prepare( string $query, string $start_time ): string {
		return str_replace( '%s', "'" . addslashes( $start_time ) . "'", $query );
	}

	public function query( string $query ): int {
		$this->queries[] = $query;
		if ( ! preg_match( "/^UPDATE wp_simply_static_pages SET (last_checked_at|last_transferred_at) = NULL(, file_path = NULL)? WHERE \\1 >= '([^']+)' AND fetch_attempts > 0$/", $query, $matches ) ) {
			throw new \RuntimeException( 'Unexpected claim recovery query: ' . $query );
		}

		$count = 0;
		foreach ( $this->rows as &$row ) {
			if ( null !== $row[ $matches[1] ] && $row[ $matches[1] ] >= $matches[3] && $row['fetch_attempts'] > 0 ) {
				$row[ $matches[1] ] = null;
				if ( ! empty( $matches[2] ) ) {
					$row['file_path'] = null;
				}
				++$count;
			}
		}

		return $count;
	}
}

final class InterruptedClaimsTask extends Task {
	use \Simply_Static\canProcessPages;

	protected static $task_name = 'fetch_urls';

	public function __construct( string $column = 'last_checked_at' ) {
		parent::__construct();
		$this->processing_column = $column;
	}

	public function perform() {
		return false;
	}
}

final class InterruptedPageClaimsTest extends UnitTestCase {
	private $database;
	private $previous_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->database = new InterruptedClaimsWpdb();
		$GLOBALS['wpdb'] = $this->database;
		Options::instance()->set( 'archive_start_time', '2026-07-12 11:00:00' );
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	public function test_recovery_keeps_completed_pages_and_restores_only_current_export_claims(): void {
		$this->database->rows = array(
			array( 'last_checked_at' => '2026-07-12 11:00:00', 'fetch_attempts' => 1, 'file_path' => 'page/index.html' ),
			array( 'last_checked_at' => '2026-07-12 12:00:00', 'fetch_attempts' => 3, 'file_path' => 'page/index.html' ),
			array( 'last_checked_at' => '2026-07-12 12:00:00', 'fetch_attempts' => 0, 'file_path' => 'page/index.html' ),
			array( 'last_checked_at' => '2026-07-11 12:00:00', 'fetch_attempts' => 1, 'file_path' => 'page/index.html' ),
			array( 'last_checked_at' => null, 'fetch_attempts' => 1, 'file_path' => 'page/index.html' ),
		);
		$task = new InterruptedClaimsTask();

		self::assertSame( 2, $task->recover_interrupted_pages() );
		self::assertSame(
			array( null, null, '2026-07-12 12:00:00', '2026-07-11 12:00:00', null ),
			array_column( $this->database->rows, 'last_checked_at' )
		);
		self::assertSame( array( 1, 3, 0, 1, 1 ), array_column( $this->database->rows, 'fetch_attempts' ) );
		self::assertSame(
			array( null, null, 'page/index.html', 'page/index.html', 'page/index.html' ),
			array_column( $this->database->rows, 'file_path' )
		);
		self::assertSame( 0, $task->recover_interrupted_pages() );
	}

	public function test_transfer_recovery_preserves_the_fetch_timestamp(): void {
		$this->database->rows = array(
			array( 'last_checked_at' => '2026-07-12 11:00:00', 'last_transferred_at' => '2026-07-12 12:00:00', 'fetch_attempts' => 2, 'file_path' => 'page/index.html' ),
		);

		self::assertSame( 1, ( new InterruptedClaimsTask( 'last_transferred_at' ) )->recover_interrupted_pages() );
		self::assertNull( $this->database->rows[0]['last_transferred_at'] );
		self::assertSame( '2026-07-12 11:00:00', $this->database->rows[0]['last_checked_at'] );
		self::assertSame( 2, $this->database->rows[0]['fetch_attempts'] );
		self::assertSame( 'page/index.html', $this->database->rows[0]['file_path'] );
	}

	public function test_no_active_export_does_not_reset_any_claims(): void {
		Options::instance()->set( 'archive_start_time', null );

		self::assertSame( 0, ( new InterruptedClaimsTask() )->recover_interrupted_pages() );
		self::assertSame( array(), $this->database->queries );
	}

	public function test_recovery_rejects_an_unrecognized_processing_column(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new InterruptedClaimsTask( 'url' ) )->recover_interrupted_pages();
	}
}
