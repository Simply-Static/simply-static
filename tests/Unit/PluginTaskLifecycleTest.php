<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';

final class PluginTaskLifecycleJob {

	/** @var string[] */
	public $events = array();

	/** @var array<int,array{string,string,bool}> */
	public $messages = array();

	public function pause(): void {
		$this->events[] = 'pause';
	}

	public function resume(): void {
		$this->events[] = 'resume';
	}

	public function cancel(): void {
		$this->events[] = 'cancel';
	}

	public function save_status_message( string $message, string $key, bool $unique = false ): void {
		$this->events[]   = 'message:' . $key;
		$this->messages[] = array( $message, $key, $unique );
	}
}

final class PluginTaskLifecycleTest extends UnitTestCase {

	/** @var Plugin */
	private $plugin;

	/** @var Options */
	private $options;

	/** @var PluginTaskLifecycleJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();
		WpEnv::$options['simply-static'] = array(
			'debugging_mode'          => false,
			'delivery_method'         => 'zip',
			'generate_404'            => false,
			'smart_crawl'             => false,
			'generate_type'           => 'export',
			'archive_status_messages' => array(),
		);
		$this->options = Options::reinstance();
		$this->job     = new PluginTaskLifecycleJob();
		$this->plugin  = ( new \ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();

		$options_property = new \ReflectionProperty( Plugin::class, 'options' );
		$options_property->setAccessible( true );
		$options_property->setValue( $this->plugin, $this->options );
		$job_property = new \ReflectionProperty( Plugin::class, 'archive_creation_job' );
		$job_property->setAccessible( true );
		$job_property->setValue( $this->plugin, $this->job );
	}

	/**
	 * @dataProvider fourOhFourDeliveryProvider
	 * @param string[] $expected
	 */
	public function test_404_only_task_list_short_circuits_crawl_and_selects_delivery_task( string $delivery, array $expected ): void {
		WpEnv::$options['simply-static-404-only'] = '1';
		$this->options
			->set( 'smart_crawl', true )
			->set( 'generate_404', false );

		self::assertSame( $expected, $this->plugin->filter_task_list( array( 'extension_task' ), $delivery ) );
	}

	/** @return array<string,array{string,array<int,string>}> */
	public function fourOhFourDeliveryProvider(): array {
		return array(
			'zip' => array(
				'zip',
				array( 'setup', 'generate_404', 'create_zip_archive', 'wrapup' ),
			),
			'local' => array(
				'local',
				array( 'setup', 'generate_404', 'transfer_files_locally', 'wrapup' ),
			),
			'extension delivery' => array(
				's3',
				array( 'setup', 'generate_404', 'wrapup' ),
			),
		);
	}

	/**
	 * @dataProvider regularDeliveryProvider
	 * @param string[] $delivery_tasks
	 */
	public function test_full_smart_crawl_export_preserves_extension_tasks_and_builds_complete_sequence( string $delivery, array $delivery_tasks ): void {
		$this->options
			->set( 'smart_crawl', true )
			->set( 'generate_404', true )
			->set( 'generate_type', 'export' );

		self::assertSame(
			array_merge(
				array( 'extension_task', 'setup', 'discover_urls', 'fetch_urls', 'generate_404' ),
				$delivery_tasks,
				array( 'wrapup' )
			),
			$this->plugin->filter_task_list( array( 'extension_task' ), $delivery )
		);
	}

	/** @return array<string,array{string,array<int,string>}> */
	public function regularDeliveryProvider(): array {
		return array(
			'zip'   => array( 'zip', array( 'create_zip_archive' ) ),
			'local' => array( 'local', array( 'transfer_files_locally' ) ),
			'pro extension' => array( 's3', array() ),
		);
	}

	/**
	 * @dataProvider incrementalScopeProvider
	 * @param array<string,mixed> $flags
	 */
	public function test_incremental_scopes_skip_url_discovery_but_still_fetch_and_deliver( array $flags ): void {
		$this->options->set( 'smart_crawl', true );
		foreach ( $flags as $key => $value ) {
			if ( 'generate_type' === $key ) {
				$this->options->set( $key, $value );
			} else {
				WpEnv::$options[ $key ] = $value;
			}
		}

		$tasks = $this->plugin->filter_task_list( array(), 'zip' );

		self::assertNotContains( 'discover_urls', $tasks );
		self::assertSame( array( 'setup', 'fetch_urls', 'create_zip_archive', 'wrapup' ), $tasks );
	}

	/** @return array<string,array{array<string,mixed>}> */
	public function incrementalScopeProvider(): array {
		return array(
			'update' => array( array( 'generate_type' => 'update' ) ),
			'single' => array( array( 'simply-static-use-single' => '12,13' ) ),
			'build'  => array( array( 'simply-static-use-build' => 'release-7' ) ),
		);
	}

	public function test_pause_resume_and_cancel_delegate_in_safe_status_message_order(): void {
		$this->plugin->pause_static_export();
		self::assertSame( array( 'pause', 'message:pause' ), $this->job->events );

		$this->job->events = array();
		$this->plugin->resume_static_export();
		self::assertSame( array( 'message:resume', 'resume' ), $this->job->events );

		$this->job->events = array();
		$this->plugin->cancel_static_export();
		self::assertSame( array( 'message:cancel', 'cancel' ), $this->job->events );
		self::assertSame(
			array(
				array( 'Export paused.', 'pause', true ),
				array( 'Export resumed.', 'resume', true ),
				array( 'Export cancelled.', 'cancel', true ),
			),
			$this->job->messages
		);
		self::assertSame( $this->job, $this->plugin->get_archive_creation_job() );
	}

	public function test_activity_log_drops_malformed_entries_and_sanitizes_user_visible_fields(): void {
		$this->options->set(
			'archive_status_messages',
			array(
				'bad'  => 'legacy scalar',
				'good' => array(
					'message'  => '<strong>Saved</strong><script>alert(1)</script>',
					'datetime' => "2026-07-12\n12:00:00",
				),
				'partial' => array(),
			)
		);

		$log = $this->plugin->get_activity_log( 4 );

		self::assertArrayNotHasKey( 'bad', $log );
		self::assertSame( '<strong>Saved</strong>', $log['good']['message'] );
		self::assertSame( '2026-07-12 12:00:00', $log['good']['datetime'] );
		self::assertSame( array( 'message' => '', 'datetime' => '' ), $log['partial'] );
		self::assertContains( 'ss_before_render_activity_log', WpEnv::$action_log );
		self::assertContains( 'ss_after_render_activity_log', WpEnv::$action_log );
	}
}
