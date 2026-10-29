<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Generate_404_Task;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-generate-404-task.php';

final class Generate404TestTask extends Generate_404_Task {

	/** @var Page[] */
	public $handled_pages = array();

	public function handle_response( $static_page ) {
		$this->handled_pages[] = $static_page;
	}
}

final class Generate404TaskTest extends UnitTestCase {

	/** @var string */
	private $archive_dir;

	/** @var mixed */
	private $previous_fetcher;

	/** @var mixed */
	private $previous_wpdb;

	protected function setUp(): void {
		parent::setUp();
		foreach ( array(
			'src/class-ss-plugin.php',
			'src/class-ss-options.php',
			'src/class-ss-util.php',
			'src/class-ss-query.php',
			'src/models/class-ss-model.php',
			'src/models/class-ss-page.php',
			'src/class-ss-url-fetcher.php',
		) as $source ) {
			$this->requireSource( $source );
		}

		$this->archive_dir = sys_get_temp_dir() . '/simply-static-404-' . uniqid( '', true ) . '/';
		wp_mkdir_p( $this->archive_dir );
		WpEnv::$options['simply-static'] = array(
			'temp_files_dir'          => dirname( untrailingslashit( $this->archive_dir ) ),
			'archive_name'            => basename( untrailingslashit( $this->archive_dir ) ),
			'archive_start_time'      => '2026-10-29 00:00:00',
			'archive_status_messages' => array(),
		);
		Options::reinstance();

		$property = new \ReflectionProperty( Url_Fetcher::class, 'instance' );
		$property->setAccessible( true );
		$this->previous_fetcher = $property->getValue();
		$this->previous_wpdb    = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = new class() {
			public $insert_id = 0;

			public function get_blog_prefix(): string {
				return 'wp_';
			}

			public function insert( $table, $data ): int {
				++$this->insert_id;
				return 1;
			}

			public function query( $query ): int {
				return 1;
			}
		};
	}

	protected function tearDown(): void {
		$property = new \ReflectionProperty( Url_Fetcher::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, $this->previous_fetcher );
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		@unlink( $this->archive_dir . '404.html' );
		@rmdir( $this->archive_dir );
		parent::tearDown();
	}

	public function test_failed_fetches_return_an_error_instead_of_completing_the_export(): void {
		$this->mockFetcher( 50, static function ( Page $page ): bool {
			return false;
		} );

		$result = ( new Generate404TestTask() )->perform();

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'generate_404_failed', $result->get_error_code() );
		self::assertStringContainsString( '404.html', $result->get_error_message() );
		self::assertSame( 'Generating 404 Page.', WpEnv::$options['simply-static']['archive_status_messages']['generate_404']['message'] );
	}

	/**
	 * Other tests install a namespaced get_permalink() seam. Keep this test on
	 * the WordPress stub so it exercises an actual configured custom page.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_failed_custom_page_falls_back_to_a_theme_404(): void {
		WpEnv::$options['simply-static']['custom_404_page'] = 17;
		Options::reinstance();
		$urls = array();
		$this->mockFetcher( 2, function ( Page $page ) use ( &$urls ): bool {
			$urls[] = $page->url;
			if ( 1 === count( $urls ) ) {
				return false;
			}

			$page->http_status_code = 404;
			file_put_contents( $this->archive_dir . $page->file_path, 'Theme 404' );
			return true;
		} );
		$task = new Generate404TestTask();

		self::assertTrue( $task->perform() );
		self::assertSame( 'https://example.test/post-17/', $urls[0] );
		self::assertCount( 1, $task->handled_pages );
		self::assertSame( 'Theme 404', file_get_contents( $this->archive_dir . '404.html' ) );
		self::assertSame( '404 Page generated', WpEnv::$options['simply-static']['archive_status_messages']['generate_404']['message'] );
	}

	public function test_a_successful_request_without_a_generated_file_is_an_error(): void {
		$this->mockFetcher( 50, static function ( Page $page ): bool {
			$page->http_status_code = 404;
			return true;
		} );

		self::assertInstanceOf( \WP_Error::class, ( new Generate404TestTask() )->perform() );
	}

	public function test_theme_probes_that_return_success_pages_are_cleaned_up_and_report_failure(): void {
		$this->mockFetcher( 50, function ( Page $page ): bool {
			$page->http_status_code = 200;
			file_put_contents( $this->archive_dir . $page->file_path, 'Redirected homepage' );
			return true;
		} );

		self::assertInstanceOf( \WP_Error::class, ( new Generate404TestTask() )->perform() );
		self::assertFileDoesNotExist( $this->archive_dir . '404.html' );
	}

	private function mockFetcher( int $attempts, callable $callback ): void {
		$fetcher = $this->getMockBuilder( Url_Fetcher::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'fetch' ) )
			->getMock();
		$fetcher->expects( self::exactly( $attempts ) )->method( 'fetch' )->willReturnCallback( $callback );
		$property = new \ReflectionProperty( Url_Fetcher::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, $fetcher );
	}
}
