<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Background_Process;
use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-async-request.php';
require_once dirname( __DIR__, 2 ) . '/src/background/class-ss-background-process.php';

final class BackgroundStateWpdb {

	/** @var string */
	public $options = 'wp_options';

	/** @var string */
	public $dbname = 'wordpress_test';

	/** @var string */
	public $base_prefix = 'wp_';

	/** @var string|null */
	public $database_lock_result;

	/** @var mixed */
	public $database_lock_owner;

	/** @var string[] */
	public $queries = array();

	/**
	 * @param mixed ...$args
	 */
	public function prepare( string $query, ...$args ): string {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		foreach ( $args as $value ) {
			$replacement = is_int( $value ) ? (string) $value : "'" . addslashes( (string) $value ) . "'";
			$query = preg_replace( '/%[sd]/', $replacement, $query, 1 );
		}

		return $query;
	}

	/** @return mixed */
	public function get_var( string $query ) {
		$this->queries[] = $query;

		if ( false !== strpos( $query, 'GET_LOCK(' ) ) {
			return $this->database_lock_result;
		}

		if ( false !== strpos( $query, 'RELEASE_LOCK(' ) ) {
			return '1';
		}

		if ( false !== strpos( $query, 'IS_USED_LOCK(' ) ) {
			return $this->database_lock_owner;
		}

		if ( preg_match( "/option_name = '([^']+)'/", $query, $matches ) ) {
			return WpEnv::$options[ stripslashes( $matches[1] ) ] ?? null;
		}

		return null;
	}
}

class BackgroundStateProcess extends Background_Process {

	/** @var string */
	protected $action = 'state_process';

	/** @var array<int,object> */
	public $batches = array();

	/** @var int */
	public $dispatch_calls = 0;

	/** @var int */
	public $schedule_calls = 0;

	/** @var int */
	public $clear_calls = 0;

	/** @param mixed $item */
	protected function task( $item ) {
		return false;
	}

	/** @return array<int,object> */
	public function get_batches( $limit = 0, $for_site_id = null ) {
		return $this->batches;
	}

	protected function generate_key( $length = 64, $key = 'batch' ) {
		return substr( $this->identifier . '_' . $key . '_fixed', 0, $length );
	}

	public function dispatch() {
		++$this->dispatch_calls;
		return true;
	}

	/** @return array|\WP_Error|false|bool */
	public function dispatch_via_background() {
		return parent::dispatch();
	}

	protected function schedule_event() {
		++$this->schedule_calls;
	}

	protected function clear_scheduled_event() {
		++$this->clear_calls;
	}

	public function get_chain_id() {
		return '00000000-0000-4000-8000-000000000002';
	}

	public function status_key(): string {
		return $this->get_status_key();
	}

	public function queue_is_empty(): bool {
		return $this->is_queue_empty();
	}

	public function unlock_for_test(): void {
		$this->unlock_process();
	}

	public function owns_lock_for_test(): bool {
		return $this->owns_process_lock();
	}

	public function lock_is_stale_for_test( string $value ): bool {
		return $this->is_process_lock_stale( $value );
	}

	/** @return float|null */
	public function lock_timestamp_for_test( string $value ) {
		return $this->get_process_lock_timestamp( $value );
	}

	public function generated_key_from_parent( int $length = 64 ): string {
		return parent::generate_key( $length );
	}

	/** @return mixed */
	public static function unserialize_for_test( $value, $allowed_classes ) {
		return parent::maybe_unserialize( $value, $allowed_classes );
	}

	/** @return int|null */
	public static function site_id_from_key( string $key ) {
		return parent::extract_site_id_from_column_name( $key );
	}

	/** @return bool|array */
	public function allowed_classes_for_test() {
		return $this->allowed_batch_data_classes;
	}

	public function set_start_time_for_test( int $start_time ): void {
		$this->start_time = $start_time;
	}

	public function time_exceeded_for_test(): bool {
		return $this->time_exceeded();
	}

	public function memory_exceeded_for_test(): bool {
		return $this->memory_exceeded();
	}

	public function complete_for_test(): void {
		$this->complete();
	}

	/** @return array<string,mixed> */
	public function post_args_for_test(): array {
		return $this->get_post_args();
	}

	/** @return array<string,mixed> */
	public function query_args_for_test(): array {
		return $this->get_query_args();
	}
}

final class BackgroundDispatchProcess extends BackgroundStateProcess {

	/** @var bool */
	public $processing = false;

	/** @var bool */
	public $loopback = true;

	/** @var int */
	public $inline_calls = 0;

	public function is_processing() {
		return $this->processing;
	}

	protected function is_loopback_available() {
		return $this->loopback;
	}

	protected function dispatch_inline() {
		++$this->inline_calls;
		return true;
	}

}

final class BackgroundLoopbackProcess extends BackgroundStateProcess {

	/** @var bool|null */
	public $restricted;

	protected function is_restricted_hosting() {
		if ( null !== $this->restricted ) {
			return $this->restricted;
		}

		return parent::is_restricted_hosting();
	}

	public function loopback_available_for_test(): bool {
		return parent::is_loopback_available();
	}

	public function restricted_hosting_for_test(): bool {
		return parent::is_restricted_hosting();
	}
}

final class BackgroundCustomPostProcess extends BackgroundStateProcess {

	/** @var array<string,mixed> */
	protected $post_args = array(
		'timeout'     => 9,
		'redirection' => 4,
		'body'        => array( 'custom' => 'value' ),
	);
}

final class BackgroundMaybeHandleProcess extends BackgroundStateProcess {

	/** @var bool */
	public $processing = false;

	/** @var bool */
	public $queue_empty = false;

	/** @var int */
	public $delete_all_calls = 0;

	/** @var int */
	public $handle_calls = 0;

	public function is_processing() {
		return $this->processing;
	}

	protected function is_queue_empty() {
		return $this->queue_empty;
	}

	public function delete_all() {
		++$this->delete_all_calls;
	}

	protected function handle() {
		++$this->handle_calls;
	}

	protected function maybe_wp_die( $return = null ) {
		return 'handled';
	}
}

final class BackgroundHealthcheckProcess extends BackgroundStateProcess {

	/** @var bool */
	public $processing = false;

	/** @var bool */
	public $queue_empty = false;

	public function is_processing() {
		return $this->processing;
	}

	protected function is_queue_empty() {
		return $this->queue_empty;
	}
}

final class BackgroundHandleProcess extends BackgroundStateProcess {

	/** @var object */
	public $batch;

	/** @var bool */
	public $queue_empty = false;

	/** @var mixed */
	public $task_result = false;

	/** @var bool */
	public $continue = true;

	/** @var bool */
	public $cancel_during_task = false;

	/** @var bool */
	public $use_real_lock = false;

	/** @var int */
	public $task_calls = 0;

	/** @var int */
	public $lock_calls = 0;

	/** @var int */
	public $unlock_calls = 0;

	/** @var int */
	public $update_calls = 0;

	/** @var int */
	public $delete_calls = 0;

	/** @var int */
	public $delete_all_calls = 0;

	/** @var int */
	public $complete_calls = 0;

	public function __construct() {
		parent::__construct();
		$this->batch = (object) array(
			'key'  => 'wp_state_process_batch_one',
			'data' => array( 'first' ),
		);
	}

	/** @param mixed $item */
	protected function task( $item ) {
		++$this->task_calls;
		if ( $this->cancel_during_task ) {
			update_option( $this->status_key(), self::STATUS_CANCELLED );
		}

		return $this->task_result;
	}

	protected function get_batch( $for_site_id = null ) {
		return $this->batch;
	}

	protected function is_queue_empty() {
		return $this->queue_empty;
	}

	public function lock_process( $reset_start_time = true ) {
		if ( $this->use_real_lock ) {
			return parent::lock_process( $reset_start_time );
		}

		++$this->lock_calls;
		return true;
	}

	protected function unlock_process() {
		if ( $this->use_real_lock ) {
			return parent::unlock_process();
		}

		++$this->unlock_calls;
		return $this;
	}

	/** @param mixed[] $data */
	public function update( $key, $data ) {
		++$this->update_calls;
		$this->batch->data = $data;
		return $this;
	}

	public function delete( $key ) {
		++$this->delete_calls;
		$this->queue_empty = true;
		return $this;
	}

	public function delete_all() {
		++$this->delete_all_calls;
		$this->queue_empty = true;
	}

	public function should_continue() {
		return $this->continue;
	}

	protected function complete() {
		++$this->complete_calls;
	}

	protected function maybe_wp_die( $return = null ) {
		return 'handled';
	}

	public function run_handle() {
		return parent::handle();
	}
}

final class BackgroundCronProcess extends Background_Process {

	/** @var string */
	protected $action = 'cron_process';

	/** @var int */
	protected $cron_interval = 2;

	/** @param mixed $item */
	protected function task( $item ) {
		return false;
	}

	public function get_chain_id() {
		return '00000000-0000-4000-8000-000000000003';
	}

	public function schedule_for_test(): void {
		$this->schedule_event();
	}

	public function clear_for_test(): void {
		$this->clear_scheduled_event();
	}
}

final class BackgroundProcessStateTest extends UnitTestCase {

	/** @var BackgroundStateWpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		WpEnv::$options['simply-static'] = array( 'debugging_mode' => false );
		Options::reinstance();
		$this->wpdb       = new BackgroundStateWpdb();
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	public function test_constructor_normalizes_allowed_unserialize_classes_and_registers_hooks(): void {
		$restricted = new BackgroundStateProcess( false );
		$invalid    = new BackgroundStateProcess( 'not-an-array' );

		self::assertFalse( $restricted->allowed_classes_for_test() );
		self::assertTrue( $invalid->allowed_classes_for_test() );
		self::assertSame( 'wp_state_process', $restricted->get_identifier() );
		self::assertSame( 1, $restricted->get_current_site_id() );
		self::assertArrayHasKey( 'wp_state_process_cron', WpEnv::$filters );
		self::assertArrayHasKey( 'wp_state_process_query_args', WpEnv::$filters );
	}

	public function test_queue_save_update_delete_and_delete_all_manage_persisted_batches(): void {
		$process = new BackgroundStateProcess();
		$process->push_to_queue( 'one' )->push_to_queue( array( 'two' ) )->save();

		self::assertSame( array( 'one', array( 'two' ) ), WpEnv::$options['wp_state_process_batch_fixed'] );
		$process->save();
		self::assertSame( array( 'one', array( 'two' ) ), WpEnv::$options['wp_state_process_batch_fixed'] );

		$process->update( 'manual_batch', array( 'updated' ) );
		$process->update( 'ignored_empty_batch', array() );
		self::assertSame( array( 'updated' ), WpEnv::$options['manual_batch'] );
		self::assertArrayNotHasKey( 'ignored_empty_batch', WpEnv::$options );
		$process->delete( 'manual_batch' );
		self::assertArrayNotHasKey( 'manual_batch', WpEnv::$options );

		WpEnv::$options['batch_a'] = array( 'a' );
		WpEnv::$options['batch_b'] = array( 'b' );
		WpEnv::$options[ $process->status_key() ] = Background_Process::STATUS_PAUSED;
		$process->batches = array(
			(object) array( 'key' => 'batch_a', 'data' => array( 'a' ) ),
			(object) array( 'key' => 'batch_b', 'data' => array( 'b' ) ),
		);
		$process->delete_all();
		self::assertArrayNotHasKey( 'batch_a', WpEnv::$options );
		self::assertArrayNotHasKey( 'batch_b', WpEnv::$options );
		self::assertArrayNotHasKey( $process->status_key(), WpEnv::$options );
		self::assertContains( 'wp_state_process_cancelled', WpEnv::$action_log );
	}

	public function test_async_dispatch_keeps_nonce_out_of_url_and_forwards_only_auth_cookie(): void {
		$_COOKIE[ LOGGED_IN_COOKIE ] = 'logged-in-cookie';
		$_COOKIE['CF_Authorization'] = 'access-cookie';
		$_COOKIE['analytics']        = 'tracking-cookie';
		$process                     = new BackgroundDispatchProcess();

		$response = $process->dispatch_via_background();

		self::assertSame( WpEnv::$remote_response, $response );
		self::assertCount( 1, WpEnv::$remote_requests );
		$request = WpEnv::$remote_requests[0];
		self::assertStringContainsString( 'action=wp_state_process', $request['url'] );
		self::assertStringNotContainsString( 'nonce', $request['url'] );
		self::assertSame( 'nonce-wp_state_process', $request['args']['body']['nonce'] );
		self::assertSame(
			array(
				'CF_Authorization' => 'access-cookie',
				LOGGED_IN_COOKIE   => 'logged-in-cookie',
			),
			$request['args']['cookies']
		);
		self::assertSame( 0, $request['args']['redirection'] );
		self::assertTrue( $request['args']['sslverify'] );
	}

	public function test_async_dispatch_rejects_a_filtered_cross_origin_url_before_sending_credentials(): void {
		add_filter(
			'wp_state_process_query_url',
			static function (): string {
				return 'https://attacker.example/collect';
			}
		);
		$process = new BackgroundDispatchProcess();

		$response = $process->dispatch_via_background();

		self::assertTrue( $response );
		self::assertSame( array(), WpEnv::$remote_requests );
		self::assertSame( 1, $process->inline_calls );
	}

	public function test_custom_post_arguments_and_filters_cannot_drop_nonce_or_enable_redirects(): void {
		$custom = new BackgroundCustomPostProcess();
		$args   = $custom->post_args_for_test();
		self::assertSame( 9, $args['timeout'] );
		self::assertSame( 'value', $args['body']['custom'] );
		self::assertSame( 'nonce-wp_state_process', $args['body']['nonce'] );
		self::assertSame( 0, $args['redirection'] );

		add_filter(
			'wp_state_process_post_args',
			static function (): array {
				return array( 'body' => array( 'filtered' => true ), 'redirection' => 5 );
			}
		);
		$args = ( new BackgroundStateProcess() )->post_args_for_test();
		self::assertTrue( $args['body']['filtered'] );
		self::assertSame( 'nonce-wp_state_process', $args['body']['nonce'] );
		self::assertSame( 0, $args['redirection'] );
	}

	public function test_internal_loopback_alias_requires_an_explicit_allow_filter(): void {
		add_filter(
			'wp_state_process_query_url',
			static function (): string {
				return 'http://127.0.0.1/wp-admin/admin-ajax.php';
			}
		);
		add_filter( 'wp_state_process_dispatch_url_allowed', '__return_true' );
		$process = new BackgroundDispatchProcess();

		self::assertSame( WpEnv::$remote_response, $process->dispatch_via_background() );
		self::assertCount( 1, WpEnv::$remote_requests );
		self::assertStringStartsWith( 'http://127.0.0.1/', WpEnv::$remote_requests[0]['url'] );
	}

	public function test_custom_access_proxy_cookie_can_be_allowlisted_without_forwarding_all_cookies(): void {
		$_COOKIE['custom_access'] = 'required';
		$_COOKIE['analytics']     = 'private';
		add_filter(
			'wp_state_process_forwarded_cookie_names',
			static function ( array $names ): array {
				$names[] = 'custom_access';
				return $names;
			}
		);

		$args = ( new BackgroundStateProcess() )->post_args_for_test();
		self::assertSame( array( 'custom_access' => 'required' ), $args['cookies'] );
	}

	public function test_pause_cancel_resume_and_active_status_transitions(): void {
		$process = new BackgroundStateProcess();
		self::assertFalse( $process->is_active() );

		$process->pause();
		self::assertTrue( $process->is_paused() );
		self::assertTrue( $process->is_active() );

		$process->resume();
		self::assertFalse( $process->is_paused() );
		self::assertSame( 1, $process->schedule_calls );
		self::assertSame( 1, $process->dispatch_calls );
		self::assertContains( 'wp_state_process_resumed', WpEnv::$action_log );

		$process->cancel();
		self::assertTrue( $process->is_cancelled() );
		self::assertTrue( $process->is_active() );
		self::assertSame( 2, $process->dispatch_calls );
	}

	public function test_queue_and_processing_activity_use_site_specific_locks(): void {
		$process = new BackgroundStateProcess();
		$process->batches = array( (object) array( 'key' => 'batch', 'data' => array( 'task' ) ) );
		self::assertTrue( $process->is_queued() );
		self::assertFalse( $process->queue_is_empty() );

		$process->batches = array();
		WpEnv::$multisite = true;
		$process->set_current_site_id( 8 );
		WpEnv::$site_transients['wp_state_process_process_lock_site_8'] = 'lock';
		self::assertTrue( $process->is_processing() );
		self::assertTrue( $process->is_active() );
		self::assertSame( 8, $process->get_current_site_id() );

		unset( WpEnv::$site_transients['wp_state_process_process_lock_site_8'] );
		self::assertFalse( $process->is_processing() );
	}

	public function test_process_locks_are_owned_released_and_protected_from_other_workers(): void {
		WpEnv::$multisite = true;
		$owner = new BackgroundStateProcess();
		$other = new BackgroundStateProcess();
		$owner->set_current_site_id( 4 );
		$other->set_current_site_id( 4 );

		$owner->lock_process();
		$key = 'wp_state_process_process_lock_site_4';
		self::assertArrayHasKey( $key, WpEnv::$site_transients );
		self::assertTrue( $owner->owns_lock_for_test() );
		self::assertTrue( $owner->has_active_process_lock() );

		$other->unlock_for_test();
		self::assertArrayHasKey( $key, WpEnv::$site_transients );
		$owner->unlock_for_test();
		self::assertArrayNotHasKey( $key, WpEnv::$site_transients );
		self::assertFalse( $owner->owns_lock_for_test() );
		self::assertContains( 'wp_state_process_process_locked', WpEnv::$action_log );
		self::assertContains( 'wp_state_process_process_unlocked', WpEnv::$action_log );
	}

	public function test_stale_and_malformed_lock_values_are_classified_without_releasing_active_foreign_locks(): void {
		$process = new BackgroundStateProcess();
		$stale   = '0.00000000 ' . ( time() - 120 ) . ':token';
		$active  = microtime() . ':token';

		self::assertTrue( $process->lock_is_stale_for_test( $stale ) );
		self::assertFalse( $process->lock_is_stale_for_test( $active ) );
		self::assertFalse( $process->lock_is_stale_for_test( 'legacy-lock' ) );
		self::assertNull( $process->lock_timestamp_for_test( 'legacy-lock' ) );
		self::assertIsFloat( $process->lock_timestamp_for_test( $active ) );

		WpEnv::$site_transients['wp_state_process_process_lock'] = $stale;
		$process->unlock_for_test();
		self::assertArrayNotHasKey( 'wp_state_process_process_lock', WpEnv::$site_transients );
	}

	public function test_database_lock_owner_keeps_admin_reset_guard_active_after_transient_expiry(): void {
		$process = new BackgroundStateProcess();
		$this->wpdb->database_lock_owner = '81';

		self::assertTrue( $process->has_active_process_lock() );
		self::assertStringContainsString( 'SELECT IS_USED_LOCK(', $this->wpdb->queries[0] );
		self::assertSame( array(), WpEnv::$site_transients );

		$this->wpdb->database_lock_owner = null;
		self::assertFalse( $process->has_active_process_lock() );
	}

	public function test_database_lock_names_are_hmac_scoped_per_install_and_site(): void {
		$this->wpdb->database_lock_result = '0';
		$site_one = new BackgroundStateProcess();
		$site_one->set_current_site_id( 1 );
		self::assertFalse( $site_one->lock_process() );
		$site_one_query = $this->wpdb->queries[0];

		$this->wpdb->queries = array();
		$site_two = new BackgroundStateProcess();
		$site_two->set_current_site_id( 2 );
		self::assertFalse( $site_two->lock_process() );
		$site_two_query = $this->wpdb->queries[0];

		self::assertNotSame( $site_one_query, $site_two_query );
		self::assertMatchesRegularExpression( "/GET_LOCK\\('simply_static_[a-f0-9]{48}', 0\\)/", $site_one_query );
		self::assertStringNotContainsString( $this->wpdb->dbname, $site_one_query );
		self::assertStringNotContainsString( WpEnv::$home_url, $site_one_query );
	}

	public function test_dispatch_schedules_recovery_and_honors_running_filter_and_inline_paths(): void {
		$process = new BackgroundDispatchProcess();
		$process->processing = true;
		self::assertFalse( $process->dispatch_via_background() );
		self::assertSame( 1, $process->schedule_calls );

		$process->processing = false;
		add_filter( 'wp_state_process_pre_dispatch', '__return_true' );
		self::assertFalse( $process->dispatch_via_background() );
		self::assertSame( 2, $process->schedule_calls );

		remove_filter( 'wp_state_process_pre_dispatch', '__return_true' );
		$process->loopback = false;
		self::assertTrue( $process->dispatch_via_background() );
		self::assertSame( 1, $process->inline_calls );
	}

	public function test_dispatch_remote_success_and_failure_fallback_preserve_chain_arguments(): void {
		$process = new BackgroundDispatchProcess();
		WpEnv::$remote_response = array( 'response' => array( 'code' => 200 ), 'body' => '' );

		self::assertSame( WpEnv::$remote_response, $process->dispatch_via_background() );
		self::assertCount( 1, WpEnv::$remote_requests );
		self::assertStringContainsString( 'action=wp_state_process', WpEnv::$remote_requests[0]['url'] );
		self::assertStringContainsString( 'chain_id=', WpEnv::$remote_requests[0]['url'] );
		self::assertTrue( WpEnv::$remote_requests[0]['args']['sslverify'] );

		WpEnv::$remote_response = new \WP_Error( 'loopback', 'Connection refused' );
		self::assertTrue( $process->dispatch_via_background() );
		self::assertSame( 'no', WpEnv::$site_transients['wp_state_process_loopback_available'] );
		self::assertSame( 1, $process->inline_calls );
	}

	public function test_loopback_detection_uses_override_cache_status_and_failure_cache(): void {
		$process = new BackgroundLoopbackProcess();
		add_filter( 'wp_state_process_loopback_available', '__return_true' );
		self::assertTrue( $process->loopback_available_for_test() );
		remove_filter( 'wp_state_process_loopback_available', '__return_true' );

		$process->restricted = true;
		self::assertFalse( $process->loopback_available_for_test() );
		$process->restricted = false;
		WpEnv::$site_transients['wp_state_process_loopback_available'] = 'yes';
		self::assertTrue( $process->loopback_available_for_test() );
		WpEnv::$site_transients['wp_state_process_loopback_available'] = 'no';
		self::assertFalse( $process->loopback_available_for_test() );

		unset( WpEnv::$site_transients['wp_state_process_loopback_available'] );
		WpEnv::$remote_response = array( 'response' => array( 'code' => 403 ) );
		self::assertFalse( $process->loopback_available_for_test() );
		self::assertSame( 'no', WpEnv::$site_transients['wp_state_process_loopback_available'] );
		self::assertTrue( WpEnv::$remote_requests[0]['args']['sslverify'] );
		self::assertStringContainsString( 'action=wp_state_process', WpEnv::$remote_requests[0]['url'] );
		self::assertSame( 'nonce-wp_state_process', WpEnv::$remote_requests[0]['args']['body']['nonce'] );
		self::assertSame( '1', WpEnv::$remote_requests[0]['args']['body']['simply_static_probe'] );
		self::assertTrue( WpEnv::$remote_requests[0]['args']['blocking'] );
		self::assertSame( 0, WpEnv::$remote_requests[0]['args']['redirection'] );

		unset( WpEnv::$site_transients['wp_state_process_loopback_available'] );
		WpEnv::$remote_response = array( 'response' => array( 'code' => 204 ) );
		self::assertTrue( $process->loopback_available_for_test() );
		self::assertSame( 'yes', WpEnv::$site_transients['wp_state_process_loopback_available'] );
	}

	public function test_loopback_cache_is_isolated_between_multisite_domains(): void {
		WpEnv::$multisite = true;
		$process = new BackgroundLoopbackProcess();
		$process->restricted = false;
		$process->set_current_site_id( 2 );
		WpEnv::$remote_response = array( 'response' => array( 'code' => 403 ) );
		self::assertFalse( $process->loopback_available_for_test() );
		self::assertSame( 'no', WpEnv::$site_transients['wp_state_process_loopback_available_site_2'] );

		$process->set_current_site_id( 3 );
		WpEnv::$remote_response = array( 'response' => array( 'code' => 204 ) );
		self::assertTrue( $process->loopback_available_for_test() );
		self::assertSame( 'yes', WpEnv::$site_transients['wp_state_process_loopback_available_site_3'] );
		self::assertSame( 'no', WpEnv::$site_transients['wp_state_process_loopback_available_site_2'] );
	}

	public function test_restricted_hosting_detection_can_be_explicitly_overridden(): void {
		$process = new BackgroundLoopbackProcess();
		$process->restricted = null;
		add_filter( 'wp_state_process_is_restricted_hosting', '__return_true' );
		self::assertTrue( $process->restricted_hosting_for_test() );
		remove_filter( 'wp_state_process_is_restricted_hosting', '__return_true' );
		add_filter( 'wp_state_process_is_restricted_hosting', '__return_false' );
		self::assertFalse( $process->restricted_hosting_for_test() );
	}

	public function test_maybe_handle_routes_processing_cancel_pause_empty_and_ready_states(): void {
		$process = new BackgroundMaybeHandleProcess();
		$_REQUEST['nonce'] = 'background-nonce';
		$_REQUEST['simply_static_probe'] = '1';
		self::assertSame( 'handled', $process->maybe_handle() );
		self::assertSame( 0, $process->handle_calls );
		unset( $_REQUEST['simply_static_probe'] );
		$process->processing = true;
		self::assertSame( 'handled', $process->maybe_handle() );
		self::assertSame( 0, $process->handle_calls );

		$process->processing = false;
		update_option( $process->status_key(), Background_Process::STATUS_CANCELLED );
		self::assertSame( 'handled', $process->maybe_handle() );
		self::assertSame( 1, $process->delete_all_calls );
		self::assertSame( 1, $process->clear_calls );

		delete_option( $process->status_key() );
		$process->pause();
		self::assertSame( 'handled', $process->maybe_handle() );
		self::assertContains( 'wp_state_process_paused', WpEnv::$action_log );

		delete_option( $process->status_key() );
		$process->queue_empty = true;
		self::assertSame( 'handled', $process->maybe_handle() );

		$process->queue_empty = false;
		self::assertSame( 'handled', $process->maybe_handle() );
		self::assertSame( 1, $process->handle_calls );
		self::assertSame( 1, $process->get_current_site_id() );
		self::assertSame(
			array( 'nonce' => 'background-nonce', 'action' => 'wp_state_process' ),
			WpEnv::$nonce_verifications[0]
		);
	}

	public function test_cron_healthcheck_returns_without_aborting_and_only_dispatches_ready_queues(): void {
		$process = new BackgroundHealthcheckProcess();
		$process->processing = true;
		self::assertNull( $process->handle_cron_healthcheck() );
		self::assertSame( 0, $process->dispatch_calls );
		self::assertSame( 0, $process->clear_calls );

		$process->processing = false;
		$process->queue_empty = true;
		self::assertNull( $process->handle_cron_healthcheck() );
		self::assertSame( 1, $process->clear_calls );
		self::assertSame( 0, $process->dispatch_calls );

		$process->queue_empty = false;
		self::assertNull( $process->handle_cron_healthcheck() );
		self::assertSame( 1, $process->dispatch_calls );
	}

	public function test_handle_removes_completed_items_and_completes_an_empty_queue(): void {
		$process = new BackgroundHandleProcess();
		$process->task_result = false;

		self::assertSame( 'handled', $process->run_handle() );
		self::assertSame( 1, $process->task_calls );
		self::assertSame( 2, $process->lock_calls );
		self::assertSame( 1, $process->delete_calls );
		self::assertSame( 1, $process->unlock_calls );
		self::assertSame( 1, $process->complete_calls );
		self::assertSame( 0, $process->dispatch_calls );
	}

	public function test_database_lock_contention_prevents_any_task_from_running(): void {
		$this->wpdb->database_lock_result = '0';
		$process = new BackgroundHandleProcess();
		$process->use_real_lock = true;

		self::assertSame( 'handled', $process->run_handle() );
		self::assertSame( 0, $process->task_calls );
		self::assertSame( 0, $process->delete_calls );
		self::assertSame( 0, $process->complete_calls );
		self::assertCount( 1, $this->wpdb->queries );
		self::assertStringContainsString( 'SELECT GET_LOCK(', $this->wpdb->queries[0] );
		self::assertArrayNotHasKey( 'wp_state_process_process_lock', WpEnv::$site_transients );
	}

	public function test_acquired_database_lock_is_released_after_the_worker_finishes(): void {
		$this->wpdb->database_lock_result = '1';
		$process = new BackgroundHandleProcess();
		$process->use_real_lock = true;

		self::assertSame( 'handled', $process->run_handle() );
		self::assertSame( 1, $process->task_calls );
		self::assertSame( 1, $process->delete_calls );
		self::assertSame( 1, $process->complete_calls );
		$lock_queries = array_values(
			array_filter(
				$this->wpdb->queries,
				static function ( string $query ): bool {
					return false !== strpos( $query, '_LOCK(' );
				}
			)
		);
		self::assertCount( 2, $lock_queries );
		self::assertStringContainsString( 'SELECT GET_LOCK(', $lock_queries[0] );
		self::assertStringContainsString( 'SELECT RELEASE_LOCK(', $lock_queries[1] );
		self::assertArrayNotHasKey( 'wp_state_process_process_lock', WpEnv::$site_transients );
	}

	public function test_handle_persists_incomplete_items_and_dispatches_the_next_worker(): void {
		$process = new BackgroundHandleProcess();
		$process->task_result = 'retry';
		$process->continue    = false;

		self::assertSame( 'handled', $process->run_handle() );
		self::assertSame( array( 'retry' ), $process->batch->data );
		self::assertSame( 1, $process->update_calls );
		self::assertSame( 0, $process->delete_calls );
		self::assertSame( 1, $process->dispatch_calls );
		self::assertSame( 0, $process->complete_calls );
	}

	public function test_handle_cancellation_before_and_after_task_clears_the_queue(): void {
		$before = new BackgroundHandleProcess();
		update_option( $before->status_key(), Background_Process::STATUS_CANCELLED );
		self::assertSame( 'handled', $before->run_handle() );
		self::assertSame( 0, $before->task_calls );
		self::assertSame( 1, $before->delete_all_calls );
		self::assertSame( 1, $before->clear_calls );

		delete_option( $before->status_key() );
		$after = new BackgroundHandleProcess();
		$after->cancel_during_task = true;
		self::assertSame( 'handled', $after->run_handle() );
		self::assertSame( 1, $after->task_calls );
		self::assertSame( 1, $after->delete_all_calls );
		self::assertSame( 1, $after->clear_calls );
		self::assertSame( 0, $after->complete_calls );
	}

	public function test_cron_interval_schedule_clear_and_complete_lifecycle(): void {
		$process = new BackgroundCronProcess();
		self::assertSame( 2, $process->get_cron_interval() );
		$schedules = $process->schedule_cron_healthcheck( array() );
		self::assertSame( 120, $schedules['wp_cron_process_cron_interval']['interval'] );
		self::assertSame( 'Every 2 Minutes', $schedules['wp_cron_process_cron_interval']['display'] );

		$process->schedule_for_test();
		$process->schedule_for_test();
		self::assertCount( 1, $GLOBALS['simply_static_test_scheduled_hooks']['wp_cron_process_cron'] );
		$process->clear_for_test();
		self::assertArrayNotHasKey( 'wp_cron_process_cron', $GLOBALS['simply_static_test_scheduled_hooks'] );

		add_filter(
			'wp_cron_process_cron_interval',
			static function (): int {
				return 0;
			}
		);
		self::assertSame( 5, $process->get_cron_interval() );
	}

	public function test_serialization_key_generation_and_site_id_parsing_cover_multisite_boundaries(): void {
		$serialized_array = serialize( array( 'task', 2 ) );
		self::assertSame(
			array( 'task', 2 ),
			BackgroundStateProcess::unserialize_for_test( $serialized_array, false )
		);
		$object = BackgroundStateProcess::unserialize_for_test( serialize( new \stdClass() ), false );
		self::assertInstanceOf( '__PHP_Incomplete_Class', $object );
		self::assertSame( 'plain', BackgroundStateProcess::unserialize_for_test( 'plain', false ) );
		self::assertSame( 12, BackgroundStateProcess::site_id_from_key( 'wp_state_process_batch_12_hash' ) );
		self::assertNull( BackgroundStateProcess::site_id_from_key( 'wp_state_process_batch_hash' ) );

		WpEnv::$multisite       = true;
		WpEnv::$current_blog_id = 12;
		$key = ( new BackgroundStateProcess() )->generated_key_from_parent( 48 );
		self::assertStringStartsWith( 'wp_state_process_batch_12_', $key );
		self::assertLessThanOrEqual( 48, strlen( $key ) );
	}

	public function test_resource_limits_and_continue_state_respect_filters_and_status(): void {
		$process = new BackgroundStateProcess();
		$process->set_start_time_for_test( time() + 60 );
		self::assertFalse( $process->time_exceeded_for_test() );
		self::assertFalse( $process->memory_exceeded_for_test() );
		self::assertTrue( $process->should_continue() );

		add_filter( 'wp_state_process_time_exceeded', '__return_true' );
		self::assertTrue( $process->time_exceeded_for_test() );
		self::assertFalse( $process->should_continue() );
	}

	public function test_base_complete_clears_status_schedule_and_emits_chain_action(): void {
		$process = new BackgroundStateProcess();
		update_option( $process->status_key(), Background_Process::STATUS_PAUSED );

		$process->complete_for_test();

		self::assertArrayNotHasKey( $process->status_key(), WpEnv::$options );
		self::assertSame( 1, $process->clear_calls );
		self::assertContains( 'wp_state_process_completed', WpEnv::$action_log );
	}
}
