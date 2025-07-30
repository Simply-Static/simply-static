<?php
namespace Simply_Static;

class Delay_Integration extends Integration {

	protected $id = 'delay';

	protected $always_active = true;

	public function __construct() {
		$this->name = __( 'Delay', 'simply-static' );
		$this->description = __( 'Adds a delay to the static site generation.', 'simply-static' );
	}

	/**
	 * Disabled by default.
	 *
	 * Set add_filter( 'simply_static_integration_delay_enabled', true ); to enable it.
	 *
	 * @return false
	 */
	public function can_run() {
		return false;
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_cleanup', [ $this, 'set_delayed' ] );
		add_action( 'ss_before_perform_archive_action', [ $this, 'check_delayed' ], 10, 2 );
		add_filter( 'ss_is_running_statuses', [ $this, 'add_delayed_status' ] );
	}

	public function add_delayed_status( $statuses ) {
		$statuses['delayed'] = $this->is_delayed();
		$statuses['delayed_until'] = absint( $this->get_delayed_until_seconds() );
		return $statuses;
	}

	public function get_delayed_until_seconds() {
		$delayed = $this->get_delayed_time();

		if ( ! $delayed ) {
			return 0;
		}
		return $delayed - time();
	}

	public function check_delayed( $blog_id, $action ) {
		if ( 'start' !== $action ) {
			return;
		}

		if ( ! $this->is_delayed() ) {
			return;
		}

		$delay_time = $this->get_delayed_time();
		$left       = $delay_time - time();

		throw new \Exception( sprintf( __( 'You can export again in about %s second(s)', 'simply-static'), $left ) );
	}


	/**
	 * Set until the next export is delayed.
	 *
	 * @param integer $delay Delay in seconds.
	 *
	 * @return void
	 */
	public function set_delayed( $delay = 60 ) {
		update_option( 'simply_static_delayed_until', time() + intval($delay) );
	}

	/**
	 * Check if the next export is delayed.
	 *
	 * @return bool
	 */
	public function is_delayed() {
		$delayed = get_option( 'simply_static_delayed_until', null );

		return $delayed && $delayed > time();
	}

	public function get_delayed_time() {
		return get_option( 'simply_static_delayed_until', null );
	}
}
