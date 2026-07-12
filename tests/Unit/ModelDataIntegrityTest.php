<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Model;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';

final class ModelDataIntegrityWpdb {

	/** @var array<int,array<string,mixed>> */
	public $updates = array();

	/** @var array<int,array<string,mixed>> */
	public $inserts = array();

	/** @var int */
	public $insert_id = 73;

	/** @var int */
	public $failed_updates_remaining = 0;

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/**
	 * @param array<string,mixed> $data
	 * @param array<string,mixed> $where
	 * @return int|false
	 */
	public function update( string $table, array $data, array $where ) {
		$this->updates[] = array(
			'table' => $table,
			'data'  => $data,
			'where' => $where,
		);

		if ( $this->failed_updates_remaining > 0 ) {
			--$this->failed_updates_remaining;
			return false;
		}

		return 1;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return int|false
	 */
	public function insert( string $table, array $data ) {
		$this->inserts[] = array(
			'table' => $table,
			'data'  => $data,
		);

		return 1;
	}
}

final class ModelDataIntegrityRecord extends Model {

	/** @var string */
	protected static $table_name = 'model_integrity';

	/** @var array<string,string> */
	protected static $columns = array(
		'id'         => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
		'title'      => 'VARCHAR(255) NOT NULL',
		'status'     => 'VARCHAR(30) NOT NULL',
		'created_at' => 'DATETIME NOT NULL',
		'updated_at' => 'DATETIME NOT NULL',
	);

	/** @var string */
	protected static $primary_key = 'id';
}

final class ModelDataIntegrityTest extends UnitTestCase {

	/** @var ModelDataIntegrityWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb       = new ModelDataIntegrityWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	public function test_hydrated_record_starts_clean(): void {
		$record = ModelDataIntegrityRecord::initialize( $this->persistedAttributes() );

		self::assertTrue( $record->save() );
		self::assertSame( array(), $this->wpdb->updates );
	}

	public function test_update_contains_only_changed_fields_and_timestamp(): void {
		$record        = ModelDataIntegrityRecord::initialize( $this->persistedAttributes() );
		$record->title = 'After';

		self::assertTrue( $record->save() );
		self::assertCount( 1, $this->wpdb->updates );
		self::assertSame(
			array(
				'title'      => 'After',
				'updated_at' => '2026-07-12 12:00:00',
			),
			$this->wpdb->updates[0]['data']
		);
		self::assertSame( array( 'id' => 9 ), $this->wpdb->updates[0]['where'] );
	}

	public function test_reverting_a_change_to_the_persisted_value_avoids_an_update(): void {
		$record        = ModelDataIntegrityRecord::initialize( $this->persistedAttributes() );
		$record->title = 'Temporary';
		$record->title = 'Before';

		self::assertTrue( $record->save() );
		self::assertSame( array(), $this->wpdb->updates );
	}

	public function test_failed_update_retains_dirty_fields_for_retry(): void {
		$this->wpdb->failed_updates_remaining = 1;
		$record                              = ModelDataIntegrityRecord::initialize( $this->persistedAttributes() );
		$record->status                      = 'published';

		self::assertFalse( $record->save() );
		self::assertTrue( $record->save() );
		self::assertCount( 2, $this->wpdb->updates );
		self::assertSame( $this->wpdb->updates[0]['data'], $this->wpdb->updates[1]['data'] );
		self::assertSame(
			array(
				'status'     => 'published',
				'updated_at' => '2026-07-12 12:00:00',
			),
			$this->wpdb->updates[1]['data']
		);

		// A successful retry clears the dirty state.
		self::assertTrue( $record->save() );
		self::assertCount( 2, $this->wpdb->updates );
	}

	public function test_clean_new_record_still_inserts_all_initialized_values(): void {
		$record = ModelDataIntegrityRecord::initialize(
			array(
				'title'  => 'New record',
				'status' => 'draft',
			)
		);

		self::assertTrue( $record->save() );
		self::assertCount( 1, $this->wpdb->inserts );
		self::assertSame(
			array(
				'title'      => 'New record',
				'status'     => 'draft',
				'created_at' => '2026-07-12 12:00:00',
				'updated_at' => '2026-07-12 12:00:00',
			),
			$this->wpdb->inserts[0]['data']
		);
		self::assertSame( 73, $record->id );

		self::assertTrue( $record->save() );
		self::assertSame( array(), $this->wpdb->updates );
	}

	/** @return array<string,mixed> */
	private function persistedAttributes(): array {
		return array(
			'id'         => 9,
			'title'      => 'Before',
			'status'     => 'draft',
			'created_at' => '2026-07-01 09:00:00',
			'updated_at' => '2026-07-01 09:00:00',
		);
	}
}
