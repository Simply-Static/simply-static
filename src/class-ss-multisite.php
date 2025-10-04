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
        add_action( 'ss_after_cleanup', [ $this, 'remove_from_queue' ], 10, 1 );
        add_action( 'ss_before_static_export', [ $this, 'add_to_queue' ], 10, 1 );
        add_action( 'ss_archive_creation_job_before_start_queue', [ $this, 'add_site_to_queue' ], 1 );
        add_filter( 'simplystatic.archive_creation_job.task_list', [ $this, 'filter_task_list' ], PHP_INT_MAX );
	}

    public function filter_task_list( $task_list ) {
        $multisite_task = [ 'multisite_queue' ];

        return array_merge( $multisite_task, $task_list );
    }

    public function add_site_to_queue( $blog_id ) {
        if ( ! self::is_queue_enabled() ) {
            return;
        }

        self::queue_export( $blog_id );
    }

    public function add_to_queue() {
        if ( ! self::is_queue_enabled() ) {
            return;
        }
        
        self::queue_export( get_current_blog_id() );
    }

    public function remove_from_queue() {
        self::dequeue_export( get_current_blog_id() );
    }

	public function after_perform_action( $blog_id, $action, $archive_creation_job ) {
		if ( 'start' !== $action ) {
			return;
		}

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
		if ( 'start' !== $action ) {
			return;
		}

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

    /**
     * Check if the queue is enabled.
     *
     * @return bool
     */
    public static function is_queue_enabled() {
        return is_multisite() && apply_filters( 'ss_multisite_queue_enabled', true );
    }

    /**
     * Get the export queue to find out which sites are queued for export.
     * Format:
     *  [
     *      blog_id => [
     *          'site_id' => blog_id,
     *          'time' => time(),
     *          'status' => 'queued' || 'running'
     *      ]
     *  ]
     * @return false|mixed
     */
    public static function get_export_queue() {
        return get_site_option( Plugin::SLUG . 'multisite_export_queue', [] );
    }

    /**
     * Save the export queue.
     *
     * @param array $queue Queue of exports.
     *
     * @return void
     */
    public static function update_export_queue( $queue ) {
        update_site_option( Plugin::SLUG . 'multisite_export_queue', $queue );
    }

    /**
     * Set a site to the export queue as running.
     * If the site is not in the queue at all, it is added and updated.
     *
     * @param integer $blog_id Site ID.
     *
     * @return void
     */
    public static function set_queued_export_as_running( $blog_id ) {
        $queue = self::get_export_queue();

        if ( ! isset( $queue[ $blog_id ] ) ) {
            $export_data = [
                    'site_id' => $blog_id,
                    'time' => time(),
                    'status' => 'running'
            ];
        } else {
            $export_data = $queue[ $blog_id ];
        }

        $export_data['status'] = 'running';
        $queue[ $blog_id ] = $export_data;

        self::update_export_queue( $queue );
    }

    /**
     * Queue a site for export.
     * If the site is already in the queue, it is overwritten..
     * If the site is not in the queue at all, it is added and updated.
     *
     * @param integer $blog_id Site ID.
     *
     * @return void
     */
    public static function queue_export( $blog_id ) {
        $queue = self::get_export_queue();

        $export_data = [
            'site_id' => $blog_id,
            'time' => time(),
            'status' => 'queued'
        ];

        $queue[ $blog_id ] = $export_data;

        self::update_export_queue( $queue );
    }

    /**
     * Remove a site from the export queue.
     *
     * @param integer $blog_id Site ID.
     *
     * @return void
     */
    public static function dequeue_export( $blog_id ) {
        $queue = self::get_export_queue();
        unset( $queue[ $blog_id ] );
        self::update_export_queue( $queue );
    }

    /**
     * Check if the site can run the export.
     * If the next export is the current site, it can run.
     * If the next export is 0, it can run (no site queued).
     *
     * @param integer $blog_id Site ID.
     *
     * @return bool
     */
    public static function can_run_export( $blog_id ) {
        return in_array( self::get_next_export(), [ 0, $blog_id ], true );
    }

    /**
     * Return if the queue is empty.
     *
     * @return bool
     */
    public static function is_queue_empty() {
        $queue = self::get_export_queue();
        return empty( $queue );
    }

    /**
     * Get the next site to export.
     *
     * @return int Site ID or 0 if the queue is empty.
     */
    public static function get_next_export() {
        if ( self::is_queue_empty() ) {
            return 0;
        }
        $queue = self::get_export_queue();

        uasort( $queue, function ( $a, $b ) {
            return $a['time'] - $b['time'];
        } );

        $statuses = wp_list_pluck( $queue, 'status' );
        $running_site_id = array_search( 'running', $statuses );

        if ( $running_site_id ) {
            return absint( $running_site_id );
        }

        $next_site = current( $queue );

        return absint( $next_site['site_id'] );
    }
}