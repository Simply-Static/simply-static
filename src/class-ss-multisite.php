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
		add_action( 'ss_before_perform_archive_action', [ $this, 'before_perform_action' ], 1, 3 );
		add_action( 'ss_after_perform_archive_action', [ $this, 'after_perform_action' ], 99, 3 );
        add_action( 'ss_before_perform_archive_running_check', [ $this, 'before_running_check' ], 1, 2 );
		add_action( 'ss_before_render_activity_log', [ $this, 'before_rendering_activity_log' ], 1 );;
		add_action( 'ss_before_render_export_log', [ $this, 'switch_to_blog' ] );
		add_action( 'ss_after_render_export_log', [ $this, 'restore_blog' ], 99, 2 );
		add_action( 'ss_before_sending_response_for_static_archive', [ $this, 'restore_blog' ] );
		add_action( 'ss_after_render_activity_log', [ $this, 'restore_blog' ], 99, 2 );
		add_action( 'ss_archive_creation_job_after_start_queue', [ $this, 'restore_blog' ], 99, 2 );
		add_action( 'ss_archive_creation_job_already_running', [ $this, 'restore_blog' ], 99, 2 );
		add_filter( 'ss_can_delete_file', [ $this, 'can_delete_file' ], 20, 3 );
		add_action( 'admin_footer', [ $this, 'hide_top_menu' ] );
		add_action( 'network_admin_menu', array( Admin_Settings::get_instance(), 'add_menu' ), 2 );
        add_action( 'ss_archive_creation_job_before_start', [ $this, 'check_for_export' ], 30 );
        add_action( 'ss_archive_creation_job_before_start', [ $this, 'add_export' ], 40 );
        add_action( 'ss_after_cleanup', [ $this, 'remove_export_check' ], 10, 1 );

    }

    /**
     * Add the current Blog ID to export check.
     *
     * @return void
     */
    public function add_export() {
        if ( ! $this->can_disable_export() ) {
            return;
        }

        $blog_id = get_current_blog_id();

        update_site_option( Plugin::SLUG . '_multisite_export_running', $blog_id );
    }

    /**
     * Delete the export check if the current blog is on it.
     *
     * @return void
     */
    public function remove_export_check() {
        if ( ! $this->can_disable_export() ) {
            return;
        }

        $export_site_id = $this->current_running_export_site_id();

        if ( ! $export_site_id ) {
            return;
        }

        $blog_id = get_current_blog_id();

        if ( absint( $blog_id ) !== absint( $export_site_id ) ) {
            return;
        }

        delete_site_option( Plugin::SLUG . '_multisite_export_running' );
    }

    /**
     * Return Blog ID of the current export check.
     *
     * @return false|mixed
     */
     public function current_running_export_site_id() {
        return get_site_option( Plugin::SLUG . '_multisite_export_running', false );;
     }

    /**
     * Check for export.
     *
     * @return void
     * @throws \Exception
     */
     public function check_for_export() {
        if ( ! $this->can_disable_export() ) {
            return;
        }

        $export_site_id = $this->current_running_export_site_id();

        if ( ! $export_site_id ) {
            return;
        }

        $blog_id = get_current_blog_id();

        if ( absint( $blog_id ) === absint( $export_site_id ) ) {
            return;
        }

        throw new \Exception( __( 'Export is already running on another site. Upgrade to Pro to queue site exports.', 'simply-static' ) );
     }

     public function can_disable_export() {
        $is_pro_installed = defined( 'SIMPLY_STATIC_PRO_VERSION' ) ? true : false;
        return apply_filters( 'ss_multisite_can_disable_export', ! $is_pro_installed );
     }

	public function after_perform_action( $blog_id, $action, $archive_creation_job ) {
		if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
			return;
		}

		$this->restore_blog( $blog_id, $archive_creation_job );
	}

    public function before_running_check( $blog_id, $archive_creation_job ) {
        if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
            return;
        }

        $this->switch_to_blog( absint( $_REQUEST['blog_id'] ) );

        $options = Options::reinstance();
        $archive_creation_job->set_options( $options );
    }

	/**
	 * @param integer $blog_id
	 * @param string $action
	 * @param Archive_Creation_Job $archive_creation_job
	 *
	 * @return void
	 */
	public function before_perform_action( $blog_id, $action, $archive_creation_job ) {
		if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
			return;
		}

		update_site_option( Plugin::SLUG . '_blog_exported', absint( $_REQUEST['blog_id'] ) );
		Util::debug_log( 'Last export: ' . absint( $_REQUEST['blog_id'] ) );

		$this->switch_to_blog( absint( $_REQUEST['blog_id'] ) );

        $options = Options::reinstance();
        $archive_creation_job->set_options( $options );
	}

    public function before_rendering_activity_log( $blog_id ) {
        if ( ! isset( $_REQUEST['blog_id'] ) || ! isset( $_REQUEST['is_network_admin'] ) ) {
            return;
        }

        $blog_id = absint( $_REQUEST['blog_id'] );
        $this->switch_to_blog( $blog_id );

        $options = Options::reinstance();
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
        $this->switched = $blog_id;
	}

	/**
	 * Restore the blog.
     *
     * @param Archive_Creation_Job $archive_job
	 *
	 * @return void
	 */
	public function restore_blog( $blog_id = 0,  $archive_job = null) {
        if ( ! $this->switched ) {
            return;
        }

		if ( get_current_blog_id() !== $this->switched ) {
			return;
		}

		restore_current_blog();
		Util::debug_log( "Restored to blog: " . get_current_blog_id() );
		$this->switched = get_current_blog_id();

        if ( $archive_job ) {
            $options = Options::reinstance();
            $archive_job->set_options( $options );
        }
	}


}