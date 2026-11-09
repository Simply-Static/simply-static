<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use ReflectionProperty;
use Simply_Static\Background_Process;
use Simply_Static\Crawlers;
use Simply_Static\Discover_Urls_Task;
use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-query.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-task.php';
require_once dirname( __DIR__, 2 ) . '/src/tasks/class-ss-discover-urls-task.php';
require_once dirname( __DIR__, 2 ) . '/src/crawler/class-ss-crawlers.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-async-request.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-background-process.php';

final class DiscoveryBudgetWpdb {
	public $options = 'wp_options';

	public function get_blog_prefix(): string {
		return 'wp_';
	}

	public function prepare( string $query, ...$args ): string {
		return vsprintf( $query, array_map( static function ( $value ) {
			return is_int( $value ) ? $value : "'" . addslashes( (string) $value ) . "'";
		}, $args ) );
	}

	public function get_var( string $query ) {
		if ( preg_match( "/option_name = '([^']+)'/", $query, $match ) ) {
			return isset( WpEnv::$options[ $match[1] ] ) ? serialize( WpEnv::$options[ $match[1] ] ) : null;
		}
		return 0;
	}
}

final class DiscoveryBudgetCrawler {
	public $calls = 0;
	private $batches;

	public function __construct( int $batches ) {
		$this->batches = $batches;
	}

	public function is_active(): bool { return true; }
	public function js_object(): array { return array( 'name' => 'Checkpointed' ); }
	public function add_urls_to_queue(): int { ++$this->calls; return 100; }
	public function is_complete(): bool { return $this->calls >= $this->batches; }
	public function get_progress(): array { return array( 'added' => $this->calls * 100, 'scanned' => $this->calls * 100 ); }
}

/** Run the real background handle/should_continue loop with a deterministic clock. */
final class DiscoveryBudgetWorker extends Background_Process {
	protected $action = 'archive_creation_job';
	public $calls = 0;
	public $window_starts = 0;
	public $dispatches = 0;
	public $queued = true;
	public $seconds_per_batch = 7;
	public $status = 0;
	public $out_of_memory = false;
	public $after_batch;
	private $clock = 0;
	private $window_start = 0;

	public function run(): void { $this->handle(); }
	public function get_batches( $limit = 0, $for_site_id = null ) {
		return $this->queued ? array( (object) array( 'key' => 'discovery', 'data' => array( 'discover_urls' ) ) ) : array();
	}
	public function update( $key, $data ) { return $this; }
	public function delete( $key ) { $this->queued = false; return $this; }
	public function dispatch() { ++$this->dispatches; return true; }
	public function get_chain_id() { return 'discovery-budget-test'; }
	protected function clear_scheduled_event() {}
	protected function get_status() { return $this->status; }
	protected function memory_exceeded() { return $this->out_of_memory; }
	protected function time_exceeded() { return $this->clock - $this->window_start >= 20; }
	protected function unlock_process() { $this->process_lock_released = true; return $this; }
	public function lock_process( $reset_start_time = true ) {
		if ( $reset_start_time ) {
			++$this->window_starts;
			$this->window_start = $this->clock;
		}
		return true;
	}
	protected function task( $item ) {
		++$this->calls;
		$done = ( new Discover_Urls_Task() )->perform();
		$this->clock += $this->seconds_per_batch;
		if ( $this->after_batch ) {
			call_user_func( $this->after_batch, $this );
		}
		return $done ? false : $item;
	}
}

final class DiscoveryWorkerBudgetTest extends UnitTestCase {
	private $original_crawlers;
	private $original_plugin;
	private $original_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->original_crawlers = $this->singleton( Crawlers::class );
		$this->original_plugin = $this->singleton( Plugin::class );
		$this->original_wpdb = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = new DiscoveryBudgetWpdb();
		Options::instance()->set( 'archive_start_time', '2026-11-09 11:30:22' )->set( 'generate_type', 'export' )->save();
		add_filter( 'wp_archive_creation_job_wp_die', '__return_false' );
	}

	protected function tearDown(): void {
		$this->set_property( Crawlers::class, 'instance', null, $this->original_crawlers );
		$this->set_property( Plugin::class, 'instance', null, $this->original_plugin );
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	private function singleton( string $class ) {
		$property = new ReflectionProperty( $class, 'instance' );
		$property->setAccessible( true );
		return $property->getValue();
	}

	private function set_property( string $class, string $name, $instance, $value ): void {
		$property = new ReflectionProperty( $class, $name );
		$property->setAccessible( true );
		$property->setValue( $instance, $value );
	}

	private function worker( array $crawlers ): DiscoveryBudgetWorker {
		$manager = ( new ReflectionClass( Crawlers::class ) )->newInstanceWithoutConstructor();
		$this->set_property( Crawlers::class, 'crawlers', $manager, $crawlers );
		$this->set_property( Crawlers::class, 'instance', null, $manager );
		$worker = new DiscoveryBudgetWorker();
		$plugin = ( new ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();
		$this->set_property( Plugin::class, 'archive_creation_job', $plugin, $worker );
		$this->set_property( Plugin::class, 'instance', null, $plugin );
		return $worker;
	}

	public function test_checkpointed_batches_share_one_twenty_second_budget_without_resetting_it(): void {
		$crawler = new DiscoveryBudgetCrawler( 10 );
		$worker = $this->worker( array( $crawler ) );
		$worker->run();

		self::assertSame( 3, $crawler->calls );
		self::assertSame( 1, $worker->window_starts );
		self::assertSame( 1, $worker->dispatches );
		self::assertTrue( $worker->queued );
		self::assertSame( 300, Options::instance()->get( 'discover_urls_total_added' ) );
	}

	public function test_finished_crawler_can_advance_and_finish_discovery_within_the_same_window(): void {
		$first = new DiscoveryBudgetCrawler( 1 );
		$second = new DiscoveryBudgetCrawler( 3 );
		$worker = $this->worker( array( $first, $second ) );
		$worker->seconds_per_batch = 2;
		$worker->run();

		self::assertSame( 1, $first->calls );
		self::assertSame( 3, $second->calls );
		self::assertSame( 1, $worker->window_starts );
		self::assertSame( 0, $worker->dispatches );
		self::assertFalse( $worker->queued );
		self::assertNull( Options::instance()->get( 'discover_urls_total_added' ) );
	}

	/** @dataProvider stopConditions */
	public function test_pause_cancel_and_memory_limits_still_stop_between_discovery_batches( string $condition ): void {
		$crawler = new DiscoveryBudgetCrawler( 10 );
		$worker = $this->worker( array( $crawler ) );
		$worker->seconds_per_batch = 1;
		$worker->after_batch = static function ( $process ) use ( $condition ) {
			if ( 2 === $process->calls ) {
				if ( 'memory' === $condition ) {
					$process->out_of_memory = true;
				} else {
					$process->status = 'cancel' === $condition ? Background_Process::STATUS_CANCELLED : Background_Process::STATUS_PAUSED;
				}
			}
		};
		$worker->run();

		self::assertSame( 2, $crawler->calls );
		self::assertSame( 1, $worker->window_starts );
		self::assertSame( 'cancel' !== $condition, $worker->queued );
	}

	public function stopConditions(): array {
		return array( array( 'pause' ), array( 'cancel' ), array( 'memory' ) );
	}
}
