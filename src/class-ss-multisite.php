<?php

namespace Simply_Static;

class Multisite {

	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'multisite';

	protected $switched = 0;

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of SS_Admin_Settings.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'ss_archive_creation_job_before_start', [ $this, 'switch_to_blog' ] );
		add_action( 'ss_before_perform_archive_action', [ $this, 'before_perform_action' ], 20, 3 );
		add_action( 'ss_after_perform_archive_action', [ $this, 'after_perform_action' ], 20, 2 );
		add_action( 'ss_before_render_activity_log', [ $this, 'switch_to_blog' ] );
		add_action( 'ss_before_render_export_log', [ $this, 'switch_to_blog' ] );
		add_action( 'ss_after_render_export_log', [ $this, 'restore_blog' ] );
		add_action( 'ss_before_sending_response_for_static_archive', [ $this, 'restore_blog' ] );
		add_action( 'ss_after_render_activity_log', [ $this, 'restore_blog' ] );
		add_action( 'ss_archive_creation_job_after_start_queue', [ $this, 'restore_blog' ] );
		add_action( 'ss_archive_creation_job_already_running', [ $this, 'restore_blog' ] );
		add_filter( 'ss_can_delete_file', [ $this, 'can_delete_file' ], 20, 3 );
		add_action( 'admin_footer', [ $this, 'hide_top_menu' ] );
		add_action( 'network_admin_menu', array( Admin_Settings::get_instance(), 'add_menu' ), 2 );
	}

	public function after_perform_action( $blog_id, $action ) {
		if ( 'start' !== $action ) {
			return;
		}

		if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
			return;
		}

		$this->restore_blog();
	}

	/**
	 * @param integer $blog_id
	 * @param string $action
	 * @param Archive_Creation_Job $archive_creation_job
	 *
	 * @return void
	 */
	public function before_perform_action( $blog_id, $action, $archive_creation_job ) {
		if ( 'start' !== $action ) {
			return;
		}

		if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
			return;
		}

		update_site_option( Plugin::SLUG . '_blog_exported', absint( $_REQUEST['blog_id'] ) );
		Util::debug_log( 'Last export: ' . absint( $_REQUEST['blog_id'] ) );

		$this->switch_to_blog( absint( $_REQUEST['blog_id'] ) );
	}

	/**
	 * Hide the "Simply Static Pro" top level menu on Multisite through CSS.
	 * For some reason, it's still showing even though it outputs nothing.
	 *
	 * @return void
	 */
	public function hide_top_menu() {
		if ( ! is_network_admin() ) {
			return;
		}
		?>
        <style>
            .toplevel_page_simply-static-pro {
                display: none;
            }
        </style>
		<?php
	}

	/**
	 * Can delete a file?
	 *
	 * @param boolean $bool False by default.
	 * @param \SplFileInfo $file File object.
	 * @param string $temp_dir Temporary directory.
	 *
	 * @return bool
	 */
	public function can_delete_file( $bool, $file, $temp_dir ) {
		$lookup = untrailingslashit( $temp_dir ) . DIRECTORY_SEPARATOR . Plugin::SLUG . '-' . get_current_blog_id() . '-';

		if ( 0 === strpos( $file->getRealPath(), $lookup ) ) {
			return true;
		}

		// Can delete only current blog ID files.
		return 0 === strpos( $file->getFilename(), Plugin::SLUG . '-' . get_current_blog_id() . '-' );
	}

	/**
	 * Switch to blog.
	 *
	 * @param integer $blog_id Blog ID.
	 *
	 * @return void
	 */
	public function switch_to_blog( $blog_id ) {
		if ( $blog_id === $this->switched ) {
			return;
		}

		switch_to_blog( $blog_id );
		Util::debug_log( "Switched to blog: " . get_current_blog_id() );
	}

	/**
	 * Restore the blog.
	 *
	 * @return void
	 */
	public function restore_blog() {
		if ( get_current_blog_id() !== $this->switched ) {
			return;
		}

		restore_current_blog();
		Util::debug_log( "Restored to blog: " . get_current_blog_id() );
		$this->switched = get_current_blog_id();
	}
}