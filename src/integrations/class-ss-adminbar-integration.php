<?php

namespace Simply_Static;

class SS_Adminbar_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'ss-adminbar';

	public function __construct() {
		$this->name        = __( 'Simply Static (Admin Bar)', 'simply-static' );
		$this->description = __( 'Adds an admin bar integration for Simply Static to see the current status of static exports.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_item' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_bar_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_admin_bar_scripts' ) );
		add_action( 'wp_ajax_ss_admin_get_status', array( $this, 'get_export_status' ) );
	}

	public function add_admin_bar_item( $admin_bar ) {
		// Get settings page.
		$generate_settings = esc_url( get_admin_url() . 'admin.php?page=simply-static-generate' );

		$admin_bar->add_node( [
			'id'    => 'ss-admin-bar',
			'title' => __( 'Static Generation: Waiting..', 'simply-static' ),
			'href'  => $generate_settings,
			'meta'  => [
				'id'    => 'ss-admin-bar',
				'title' => __( 'Static Generation: Waiting..', 'simply-static' ),
			],
		] );
	}

	/**
	 * Add scripts for admin bar.
	 *
	 * @return void
	 */
	public function add_admin_bar_scripts() {
		// exit if user is not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script( 'ss-admin-bar-script', SIMPLY_STATIC_URL . '/assets/admin-bar.js', [ 'jquery' ], '1.0', true );
		wp_localize_script( 'ss-admin-bar-script', 'ss_admin_status_object', [
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'ss-admin-bar-nonce' ),
			'failed_tests' => intval( get_transient('simply_static_failed_tests') ),
			'translations' => [
				'label'   => __( 'Static Generation:', 'simply-static' ),
				'running' => __( 'Running..', 'simply-static' ),
				'idle'    => __( 'Idle', 'simply-static' ),
				'error'   => __( 'Error', 'simply-static' ),
			]
		] );
	}

	/**
	 * Get information if an export is running.
	 *
	 * @return void
	 */
	public function get_export_status() {
		// Validate nonce.
		if ( ! wp_verify_nonce( $_POST['security'], 'ss-admin-bar-nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check if Simply Static is running
		$status = 'error';

		if ( class_exists( 'Simply_Static\Archive_Creation_Job' ) ) {
			$job    = new Archive_Creation_Job();
			$status = ( $job->is_running() ) ? 'running' : 'idle';
			wp_send_json_success( [ 'status' => $status ] );
		} else {
			wp_send_json_error( [ 'status' => $status ] );
		}
		wp_send_json_success( [ 'status' => $status ] );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return class_exists( 'Simply_Static\Plugin' );
	}
}
