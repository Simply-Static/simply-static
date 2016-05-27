<?php
/**
 * @package Simply_Static\Unit_tests
 */
class Simply_Static_Query_Test extends WP_UnitTestCase {

	/**
	 * Set the test URL
	 */
	const URL = 'http://example.org/blog/my-first-blog-post';

	public function create_pages( $count ) {
		$i = 0;
		while ( $i++ < $count ) {
			Simply_Static_Page_Factory::create();
		}
	}

	public function test_count() {
		$this->create_pages( 2 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$this->assertEquals( 2, $query->count() );
	}

	public function test_first() {
		$page = Simply_Static_Page_Factory::create();

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$page2 = $query->first();

		$this->assertEquals( $page->id, $page2->id );
	}

	public function test_find_by() {
		$page = Simply_Static_Page_Factory::create();

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$page2 = $query->find_by( 'url', $page->url );

		$this->assertEquals( $page->id, $page2->id );
	}

	public function test_find_or_initialize_by() {
		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$page = $query->find_or_initialize_by( 'url', self::URL );

		$this->assertEquals( self::URL, $page->url );
		$this->assertFalse( $page->exists() );
	}

	public function test_find_or_create_by() {
		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$page = $query->find_or_create_by( 'url', self::URL );

		$this->assertEquals( self::URL, $page->url );
		$this->assertTrue( $page->exists() );
	}

	public function test_update_all_with_string() {
		$this->create_pages( 2 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$query->update_all( 'http_status_code = 418' );
		$records_updated = $query->update_all( "error_message = 'qwerty'" );

		$page = $query->first();

		$this->assertEquals( 2, $records_updated );
		$this->assertEquals( 418, $page->http_status_code );
		$this->assertEquals( 'qwerty', $page->error_message );
	}

	public function test_update_all_with_assoc_array() {
		$this->create_pages( 2 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$query->update_all( array( 'http_status_code' => 418 ) );
		$records_updated = $query->update_all( array( 'error_message' => 'qwerty' ) );

		$page = $query->first();

		$this->assertEquals( $records_updated, 2 );
		$this->assertEquals( $page->http_status_code, 418 );
		$this->assertEquals( $page->error_message, 'qwerty' );
	}

	public function test_delete_all() {
		$this->create_pages( 5 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$count = $query->count();
		$this->assertEquals( 5, $count );

		$records_deleted = $query->delete_all();
		$this->assertEquals( 5, $records_deleted );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$count = $query->count();
		$this->assertEquals( 0, $count );
	}

	public function test_limit() {
		$this->create_pages( 3 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->limit(2)->find();
		$this->assertEquals( 2, count( $records ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->limit(4)->find();
		$this->assertEquals( 3, count( $records ) );
	}

	public function test_offset() {
		$this->create_pages( 10 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->limit(100)->offset(6)->find();
		$this->assertEquals( 4, count( $records ) );
	}

	public function test_order() {
		Simply_Static_Page_Factory::create( array( 'file_path' => 'b' ) );
		Simply_Static_Page_Factory::create( array( 'file_path' => 'c' ) );
		Simply_Static_Page_Factory::create( array( 'file_path' => 'a' ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$page = $query->order( 'file_path ASC' )->first();
		$this->assertEquals( 'a', $page->file_path );

		$page = $query->order( 'file_path DESC' )->first();
		$this->assertEquals( 'c', $page->file_path );
	}

	public function test_where_with_string() {
		Simply_Static_Page_Factory::create( array( 'url' => self::URL ) );
		Simply_Static_Page_Factory::create( array( 'http_status_code' => 404 ) );
		$this->create_pages( 1 );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->where( "url = '" . self::URL . "'" )->find();
		$this->assertEquals( 1, sizeof( $records ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->where( "http_status_code = 404" )->find();
		$this->assertEquals( 404, $records[0]->http_status_code );
	}

	public function test_where_with_assoc_array() {
		$conditions = array( 'url' => self::URL, 'http_status_code' => 404 );

		$this->create_pages( 4 );
		Simply_Static_Page_Factory::create( $conditions );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->where( $conditions )->find();
		$this->assertEquals( 1, sizeof( $records ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->where( array( 'http_status_code' => 200 ) )->find();
		$this->assertEquals( 4, sizeof( $records ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query->where( array( 'error_message' => null ) )->find();
		$this->assertEquals( 5, sizeof( $records ) );
	}

	public function test_where_with_string_plus_args() {
		$conditions = array( 'url' => self::URL, 'http_status_code' => 404 );

		$this->create_pages( 3 );
		Simply_Static_Page_Factory::create( $conditions );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query
			->where( "url = ?", self::URL)
			->where( "http_status_code = ? AND error_message IS NULL", 404 )
			->find();
		$this->assertEquals( 1, sizeof( $records ) );

		$query = new Simply_Static_Query( 'Simply_Static_Page' );
		$records = $query
			->where( "http_status_code = ?", 200 )
			->find();
		$this->assertEquals( 3, sizeof( $records ) );
	}
}
