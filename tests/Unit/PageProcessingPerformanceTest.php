<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Task;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/class-ss-skip-further-processing-exception.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/traits/trait-ss-can-process-pages.php';

final class BatchClaimWpdb {
	public $rows = array();
	public $queries = array();
	public $updates = array();

	public function get_blog_prefix() : string {
		return 'wp_';
	}

	public function prepare( string $query, ...$args ) : string {
		return vsprintf( $query, array_map( static function ( $value ) {
			return is_int( $value ) ? $value : "'" . addslashes( (string) $value ) . "'";
		}, $args ) );
	}

	public function query( string $query ) : int {
		$this->queries[] = $query;
		if ( preg_match( "/SET ([a-z_]+) = '([^']+)', fetch_attempts = ([0-9]+), updated_at = '([^']+)' WHERE id = ([0-9]+).*< '([^']+)' \) AND fetch_attempts = ([0-9]+)/", $query, $parts ) ) {
			$id = (int) $parts[5];
			$row = $this->rows[ $id ];
			if ( ( null !== $row[ $parts[1] ] && $row[ $parts[1] ] >= $parts[6] ) || (int) $row['fetch_attempts'] !== (int) $parts[7] || (int) $row['fetch_attempts'] >= 3 ) {
				return 0;
			}
			$this->rows[ $id ][ $parts[1] ] = $parts[2];
			$this->rows[ $id ]['fetch_attempts'] = (int) $parts[3];
			$this->rows[ $id ]['updated_at'] = $parts[4];
			return 1;
		}
		if ( preg_match( '/SET ([a-z_]+) = NULL WHERE id = ([0-9]+)/', $query, $parts ) ) {
			$this->rows[ (int) $parts[2] ][ $parts[1] ] = null;
			return 1;
		}

		throw new \RuntimeException( 'Unexpected query in page-claim fixture: ' . $query );
	}

	public function update( string $table, array $fields, array $where ) : int {
		$this->updates[] = $fields;
		$this->rows[ $where['id'] ] = array_merge( $this->rows[ $where['id'] ], $fields );
		return 1;
	}
}

final class BatchClaimTask extends Task {
	use \Simply_Static\canProcessPages;

	public $total_reads = 0;
	public $attempts_observed = array();
	public $failures_remaining = 0;
	private $database;

	public function __construct( BatchClaimWpdb $database ) {
		parent::__construct();
		$this->database = $database;
	}

	public function perform() {
		return $this->process_pages();
	}

	public function get_pages_to_process() {
		$pages = array();
		foreach ( $this->database->rows as $row ) {
			if ( ( null === $row['last_transferred_at'] || $row['last_transferred_at'] < $this->get_start_time() ) && $row['fetch_attempts'] < 3 ) {
				$pages[] = Page::initialize( $row );
			}
		}
		return $pages;
	}

	public function get_total_pages( $cached = true ) {
		$this->total_reads++;
		return count( $this->database->rows );
	}

	public function get_processed_pages() {
		return count( array_filter( $this->database->rows, function ( $row ) {
			return null !== $row['last_transferred_at'] && $row['last_transferred_at'] >= $this->get_start_time();
		} ) );
	}

	protected function get_exhausted_pages_count() {
		return 0;
	}

	protected function process_page( $page ) {
		$this->attempts_observed[] = $this->database->rows[ $page->id ]['fetch_attempts'];
		if ( $this->failures_remaining > 0 ) {
			$this->failures_remaining--;
			throw new \RuntimeException( 'Transient upload failure' );
		}
	}

	protected function check_if_running() {}
	protected function maybe_refresh_process_lock() {}
}

final class PageProcessingPerformanceTest extends UnitTestCase {
	private $database;
	private $previous_wpdb;

	protected function setUp() : void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->database = new BatchClaimWpdb();
		$GLOBALS['wpdb'] = $this->database;
		Options::instance()->set( 'archive_start_time', '2026-07-12 11:00:00' )->save();
	}

	protected function tearDown() : void {
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	private function add_page( int $id, int $attempts = 0 ) : Page {
		$this->database->rows[ $id ] = array(
			'id' => $id,
			'url' => 'https://example.test/page-' . $id . '/',
			'fetch_attempts' => $attempts,
			'last_transferred_at' => '2026-07-11 12:00:00',
			'last_checked_at' => '2026-07-11 12:00:00',
			'updated_at' => '2026-07-11 12:00:00',
		);
		return Page::initialize( $this->database->rows[ $id ] );
	}

	public function test_fifty_pages_need_one_claim_and_one_completion_write_each() : void {
		for ( $id = 1; $id <= 50; $id++ ) {
			$this->add_page( $id );
		}
		$task = new BatchClaimTask( $this->database );

		self::assertTrue( $task->perform() );
		self::assertCount( 50, $this->database->queries );
		self::assertCount( 50, $this->database->updates );
		self::assertSame( 1, $task->total_reads );
		self::assertSame( array_fill( 0, 50, 1 ), $task->attempts_observed );
		self::assertSame( array_fill( 0, 50, 0 ), array_values( array_column( $this->database->rows, 'fetch_attempts' ) ) );
	}

	public function test_failure_retains_the_persisted_attempt_and_success_can_reset_it() : void {
		$this->add_page( 1 );
		$task = new BatchClaimTask( $this->database );
		$task->failures_remaining = 1;

		self::assertFalse( $task->perform() );
		self::assertSame( 1, $this->database->rows[1]['fetch_attempts'] );
		self::assertNull( $this->database->rows[1]['last_transferred_at'] );
		self::assertTrue( $task->perform() );
		self::assertSame( array( 1, 2 ), $task->attempts_observed );
		self::assertSame( 0, $this->database->rows[1]['fetch_attempts'] );
	}

	public function test_stale_worker_cannot_overwrite_a_newer_retry_attempt() : void {
		$stale_page = $this->add_page( 1 );
		$this->database->rows[1]['fetch_attempts'] = 1;

		self::assertFalse( $stale_page->claim_for_processing( 'last_transferred_at', '2026-07-12 11:00:00' ) );
		self::assertSame( 1, $this->database->rows[1]['fetch_attempts'] );
		self::assertSame( 0, $stale_page->fetch_attempts );
		self::assertSame( array(), $this->database->updates );
	}

	public function test_claim_preserves_unrelated_dirty_fields_and_can_reset_to_original_zero() : void {
		$page = $this->add_page( 1 );
		$page->status_message = 'New processing message';

		self::assertTrue( $page->claim_for_processing( 'last_checked_at', '2026-07-12 11:00:00' ) );
		self::assertTrue( $page->save() );
		self::assertSame( 'New processing message', $this->database->rows[1]['status_message'] );
		$page->fetch_attempts = 0;
		self::assertTrue( $page->save() );
		self::assertSame( 0, $this->database->rows[1]['fetch_attempts'] );
	}
}
