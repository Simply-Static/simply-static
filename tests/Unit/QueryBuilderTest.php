<?php

declare(strict_types=1);

namespace {
	if ( ! function_exists( 'esc_sql' ) ) {
		/**
		 * @param mixed $value
		 * @return mixed
		 */
		function esc_sql( $value ) {
			if ( is_array( $value ) ) {
				return array_map( 'esc_sql', $value );
			}

			return addslashes( (string) $value );
		}
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Model;
	use Simply_Static\Page;
	use Simply_Static\Query;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
	require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
	require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';

	final class QueryBuilderWpdb {

		/** @var array<int,array<string,mixed>>|null */
		public $results = array();

		/** @var array<string,mixed>|null */
		public $row;

		/** @var mixed */
		public $value = 0;

		/** @var string[] */
		public $queries = array();

		public function get_blog_prefix(): string {
			return 'wp_';
		}

		/**
		 * @param string $query
		 * @param mixed $output
		 * @return array<int,array<string,mixed>>|null
		 */
		public function get_results( $query, $output = null ) {
			$this->queries[] = $query;
			return $this->results;
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

		/** @return mixed */
		public function get_var( string $query ) {
			$this->queries[] = $query;
			return $this->value;
		}

		public function query( string $query ): int {
			$this->queries[] = $query;
			return 3;
		}
	}

	final class QueryBuilderRecord extends Model {

		/** @var string */
		protected static $table_name = 'query_records';

		/** @var array<string,string> */
		protected static $columns = array(
			'id'         => 'BIGINT',
			'name'       => 'VARCHAR(255)',
			'created_at' => 'DATETIME',
			'updated_at' => 'DATETIME',
		);

		/** @var string */
		protected static $primary_key = 'id';
	}

	final class QueryBuilderPage extends Page {
	}

	final class QueryBuilderTest extends UnitTestCase {

		/** @var QueryBuilderWpdb */
		private $wpdb;

		protected function setUp(): void {
			parent::setUp();
			$this->wpdb       = new QueryBuilderWpdb();
			$GLOBALS['wpdb'] = $this->wpdb;
		}

		public function test_page_queries_are_scoped_to_the_current_site_for_class_strings_and_subclasses(): void {
			WpEnv::$current_blog_id = 7;

			self::assertSame(
				'SELECT * FROM  wp_simply_static_pages WHERE 1=1 AND site_id=7',
				( new Query( Page::class ) )->get_raw_sql( '*' )
			);
			self::assertSame(
				'SELECT * FROM  wp_simply_static_pages WHERE 1=1 AND url = \'https://example.test/page\' AND site_id=7',
				( new Query( QueryBuilderPage::class ) )
					->where( array( 'url' => 'https://example.test/page' ) )
					->get_raw_sql( '*' )
			);
		}

	public function test_explicit_page_site_scope_is_not_combined_with_the_current_site(): void {
			WpEnv::$current_blog_id = 7;

			$array_scope = ( new Query( Page::class ) )
				->where( array( 'site_id' => 12 ) )
				->get_raw_sql( '*' );
			$raw_scope = ( new Query( Page::class ) )
				->where( 'site_id IS NULL' )
				->get_raw_sql( '*' );

			self::assertSame(
				"SELECT * FROM  wp_simply_static_pages WHERE 1=1 AND site_id = '12'",
				$array_scope
			);
			self::assertSame(
				'SELECT * FROM  wp_simply_static_pages WHERE 1=1 AND site_id IS NULL',
				$raw_scope
			);
		self::assertStringNotContainsString( 'site_id=7', $array_scope . $raw_scope );
	}

	public function test_site_id_text_inside_a_quoted_value_cannot_disable_page_scoping(): void {
		WpEnv::$current_blog_id = 7;

		$sql = ( new Query( Page::class ) )
			->where( array( 'url' => 'https://example.test/?site_id=99' ) )
			->get_raw_sql( '*' );

		self::assertStringContainsString( "url = 'https://example.test/?site_id=99'", $sql );
		self::assertStringEndsWith( ' AND site_id=7', $sql );
	}

		public function test_where_templates_nulls_order_and_pagination_compose_consistently(): void {
			$sql = ( new Query( QueryBuilderRecord::class ) )
				->where( array( 'name' => null ) )
				->where( 'id > ? AND name <> ?', 4, "O'Reilly" )
				->order( 'id DESC' )
				->limit( 20 )
				->offset( 40 )
				->get_raw_sql( 'id, name' );

			self::assertSame(
				"SELECT id, name FROM  wp_simply_static_query_records WHERE 1=1 AND name IS NULL AND id > '4' AND name <> 'O\\'Reilly' ORDER BY id DESC LIMIT 20 OFFSET 40",
				$sql
			);
		}

		public function test_invalid_where_shapes_fail_before_query_execution(): void {
			$query = new Query( QueryBuilderRecord::class );

			try {
				$query->where( 'id BETWEEN ? AND ?', 1 );
				self::fail( 'Mismatched placeholders should throw.' );
			} catch ( \Exception $exception ) {
				self::assertStringContainsString( 'placeholders', $exception->getMessage() );
			}

			try {
				$query->where( array( 'id' ), 1 );
				self::fail( 'A non-string template should throw.' );
			} catch ( \Exception $exception ) {
				self::assertStringContainsString( 'first arg was not a string', $exception->getMessage() );
			}

			try {
				$query->where( 42 );
				self::fail( 'A scalar condition should throw.' );
			} catch ( \Exception $exception ) {
				self::assertStringContainsString( 'not a string or array', $exception->getMessage() );
			}

			self::assertSame( array(), $this->wpdb->queries );
		}

		public function test_find_and_first_hydrate_clean_models_and_handle_database_failure(): void {
			$this->wpdb->results = array(
				array( 'id' => 1, 'name' => 'Alpha' ),
				array( 'id' => 2, 'name' => 'Beta' ),
			);
			$records = ( new Query( QueryBuilderRecord::class ) )->order( 'id ASC' )->find();

			self::assertCount( 2, $records );
			self::assertInstanceOf( QueryBuilderRecord::class, $records[0] );
			self::assertSame( 'Alpha', $records[0]->name );
			self::assertSame(
				'SELECT * FROM  wp_simply_static_query_records ORDER BY id ASC',
				$this->wpdb->queries[0]
			);

			$this->wpdb->row = array( 'id' => 2, 'name' => 'Beta' );
			$first = ( new Query( QueryBuilderRecord::class ) )->first();
			self::assertSame( 2, $first->id );
			self::assertStringEndsWith( ' LIMIT 1', $this->wpdb->queries[1] );

			$this->wpdb->results = null;
			$this->wpdb->row     = null;
			self::assertNull( ( new Query( QueryBuilderRecord::class ) )->find() );
			self::assertNull( ( new Query( QueryBuilderRecord::class ) )->first() );
		}

		public function test_find_by_limits_the_database_work_and_find_or_initialize_preserves_lookup_value(): void {
			$this->wpdb->row = array( 'id' => 8, 'name' => 'Found' );
			$found = ( new Query( QueryBuilderRecord::class ) )->find_by( 'name', 'Found' );

			self::assertSame( 8, $found->id );
			self::assertSame(
				"SELECT * FROM  wp_simply_static_query_records WHERE 1=1 AND name = 'Found' LIMIT 1",
				$this->wpdb->queries[0]
			);

			$this->wpdb->row = null;
			$initialized = ( new Query( QueryBuilderRecord::class ) )->find_or_initialize_by( 'name', 'Missing' );
			self::assertFalse( $initialized->exists() );
			self::assertSame( 'Missing', $initialized->name );
		}

		public function test_delete_and_count_execute_the_composed_query_and_return_database_results(): void {
			$query = ( new Query( QueryBuilderRecord::class ) )->where( array( 'name' => 'Old' ) );
			self::assertSame( 3, $query->delete_all() );
			self::assertSame(
				"DELETE FROM  wp_simply_static_query_records WHERE 1=1 AND name = 'Old'",
				$this->wpdb->queries[0]
			);

			$this->wpdb->value = '17';
			self::assertSame( '17', ( new Query( QueryBuilderRecord::class ) )->count() );
			self::assertSame( 'SELECT COUNT(*) FROM  wp_simply_static_query_records', $this->wpdb->queries[1] );

			self::assertSame( 3, ( new Query( QueryBuilderRecord::class ) )->delete_by_id( 9 ) );
			self::assertSame(
				"DELETE FROM  wp_simply_static_query_records WHERE 1=1 AND id = '9'",
				$this->wpdb->queries[2]
			);
		}
	}
}
