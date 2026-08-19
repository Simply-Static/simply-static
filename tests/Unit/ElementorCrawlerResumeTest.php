<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Crawler\Elementor_Crawler;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;

final class ElementorCrawlerResumeWpdb {

	/** @var string */
	public $postmeta = 'wp_postmeta';

	/** @var int */
	public $insert_id = 1;

	/** @var array<int,array<string,mixed>> */
	public $inserts = array();

	/** @var array<int,string> */
	public $queries = array();

	/** @var array<int,array<string,mixed>> */
	private $meta_rows;

	/** @param array<int,array<string,mixed>> $meta_rows */
	public function __construct( array $meta_rows ) {
		$this->meta_rows = $meta_rows;
	}

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	/** @param mixed ...$arguments */
	public function prepare( string $query, ...$arguments ): string {
		return vsprintf( $query, $arguments );
	}

	/** @return null */
	public function get_row( string $query, $output = null ) {
		return null;
	}

	/** @return array<int,array<string,mixed>> */
	public function get_results( string $query, string $output ): array {
		$this->queries[] = $query;
		preg_match( '/meta_id > (\d+).*OCTET_LENGTH\(meta_value\) <= (\d+).*LIMIT (\d+)/', $query, $matches );
		$last_meta_id = isset( $matches[1] ) ? (int) $matches[1] : 0;
		$max_bytes    = isset( $matches[2] ) ? (int) $matches[2] : PHP_INT_MAX;
		$limit        = isset( $matches[3] ) ? (int) $matches[3] : 10;
		$rows         = array_values( array_filter( $this->meta_rows, static function ( array $row ) use ( $last_meta_id, $max_bytes ): bool {
			return (int) $row['meta_id'] > $last_meta_id && strlen( (string) $row['meta_value'] ) <= $max_bytes;
		} ) );
		usort( $rows, static function ( array $left, array $right ): int {
			return (int) $left['meta_id'] <=> (int) $right['meta_id'];
		} );

		return array_slice( $rows, 0, $limit );
	}

	/** @param array<string,mixed> $data */
	public function insert( string $table, array $data ): int {
		$this->inserts[] = array( 'table' => $table, 'data' => $data );
		++$this->insert_id;

		return 1;
	}

	public function flush(): void {
	}
}

final class ElementorCrawlerResumeTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
		$this->requireSource( 'src/class-ss-query.php' );
		$this->requireSource( 'src/models/class-ss-model.php' );
		$this->requireSource( 'src/models/class-ss-page.php' );
		$this->requireSource( 'src/crawler/class-ss-crawler.php' );
		$this->requireSource( 'src/crawler/class-ss-elementor-crawler.php' );
	}

	public function test_files_and_lottie_rows_resume_from_small_checkpoints(): void {
		$root = sys_get_temp_dir() . '/ss-elementor-resume-' . bin2hex( random_bytes( 8 ) );
		wp_mkdir_p( $root . '/assets/deep' );
		file_put_contents( $root . '/assets/one.css', 'one' );
		file_put_contents( $root . '/assets/two.js', 'two' );
		file_put_contents( $root . '/assets/deep/three.svg', 'three' );
		file_put_contents( $root . '/assets/skip.php', '<?php' );

		$database = new ElementorCrawlerResumeWpdb( array(
			array( 'meta_id' => 3, 'meta_value' => '{invalid' ),
			array( 'meta_id' => 9, 'meta_value' => $this->lottieJson( 'https://example.test/first.json' ) ),
			array( 'meta_id' => 15, 'meta_value' => $this->lottieJson( 'https://example.test/second.json' ) ),
		) );
		$GLOBALS['wpdb'] = $database;
		Options::instance()->set( 'archive_start_time', '2026-08-19 12:00:00' )->save();
		add_filter( 'simply_static_elementor_crawler_max_entries_per_batch', static function () {
			return 2;
		} );
		add_filter( 'simply_static_elementor_meta_batch_size', static function () {
			return 1;
		} );
		add_filter( 'simply_static_elementor_meta_max_entries_per_batch', static function () {
			return 1;
		} );

		$total_added = 0;
		$complete    = false;
		$progress    = array();

		try {
			for ( $attempt = 0; $attempt < 20; $attempt++ ) {
				$crawler      = new ResumableElementorCrawler( $root );
				$total_added += $crawler->add_urls_to_queue();
				$complete     = $crawler->is_complete();
				$progress     = $crawler->get_progress();
				if ( $complete ) {
					break;
				}
				self::assertTrue(
					is_array( Options::instance()->get( 'elementor_directory_crawler_state' ) )
					|| is_array( Options::instance()->get( 'elementor_crawler_state' ) )
				);
			}

			self::assertTrue( $complete );
			self::assertSame( 6, $total_added );
			self::assertSame( 6, $progress['added'] );
			self::assertGreaterThanOrEqual( 7, $progress['scanned'] );
			self::assertNull( Options::instance()->get( 'elementor_directory_crawler_state' ) );
			self::assertNull( Options::instance()->get( 'elementor_crawler_state' ) );
			self::assertNotEmpty( $database->queries );
			self::assertStringContainsString( 'OCTET_LENGTH(meta_value) <= 5242880', implode( ' ', $database->queries ) );

			$urls = array_column( array_column( $database->inserts, 'data' ), 'url' );
			sort( $urls );
			self::assertSame(
				array(
					'https://example.test/first.json',
					'https://example.test/second.json',
					'https://example.test/wp-content/elementor/deep/three.svg',
					'https://example.test/wp-content/elementor/one.css',
					'https://example.test/wp-content/elementor/two.js',
					'https://example.test/wp-includes/js/imagesloaded.min.js',
				),
				$urls
			);
		} finally {
			$this->removeTree( $root );
		}
	}

	private function lottieJson( string $url ): string {
		return (string) json_encode( array(
			array(
				'widgetType' => 'lottie',
				'settings'   => array(
					'source_json' => array( 'source' => 'library', 'url' => $url ),
				),
			),
		) );
	}

	private function removeTree( string $root ): void {
		if ( ! is_dir( $root ) ) {
			return;
		}
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			$item->isDir() && ! $item->isLink() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() );
		}
		@rmdir( $root );
	}
}

final class ResumableElementorCrawler extends Elementor_Crawler {

	/** @var string */
	private $root;

	public function __construct( string $root ) {
		parent::__construct();
		$this->root = $root;
	}

	public function is_elementor_pro_active() {
		return true;
	}

	/** @return array<int,array{basedir:string,baseurl:string}> */
	protected function get_elementor_scan_directories() : array {
		return array(
			array(
				'basedir' => $this->root . '/assets',
				'baseurl' => 'https://example.test/wp-content/elementor',
			),
		);
	}
}
