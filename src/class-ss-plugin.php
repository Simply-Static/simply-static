<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class
 */
class Plugin {

	/**
	 * The slug of the plugin; used in actions, filters, i18n, table names, etc.
	 *
	 * @var string
	 */
	const SLUG = 'simply-static';

	/**
	 * Singleton instance.
	 *
	 * @var Simply_Static
	 */
	protected static $instance = null;

	/**
	 * An instance of the options structure containing all options for this plugin
	 *
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * View object.
	 *
	 * @var \Simply_Static\View
	 */
	protected $view = null;

	/**
	 * Archive creation process
	 *
	 * @var \Simply_Static\Archive_Creation_Job
	 */
	protected $archive_creation_job = null;

	/**
	 * Current page name.
	 *
	 * @var string
	 */
	protected $current_page = '';

	/**
	 * @var null|\Simply_Static\Page_Handlers
	 */
	protected $page_handlers = null;


	/**
	 * Return an instance of the Simply Static plugin
	 * @return Simply_Static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->includes();

			// Enqueue admin scripts.
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_scripts' ) );
			// Add the options page and menu item.
			add_action( 'admin_menu', array( self::$instance, 'add_plugin_admin_menu' ), 2 );

			// Maybe clear local directory.
			add_action( 'ss_after_setup_task', array( self::$instance, 'maybe_clear_directory' ) );

			// Handle AJAX requests.
			add_action( 'wp_ajax_static_archive_action', array( self::$instance, 'static_archive_action' ) );
			add_action( 'wp_ajax_render_export_log', array( self::$instance, 'render_export_log' ) );
			add_action( 'wp_ajax_render_activity_log', array( self::$instance, 'render_activity_log' ) );

			// Instead of using ajax, activate export log file and run with cron.
			add_action( 'simply_static_site_export_cron', array( self::$instance, 'run_static_export' ) );

			// Filters.
			add_filter( 'simplystatic.archive_creation_job.task_list', array(
				self::$instance,
				'filter_task_list'
			), 10, 2 );

			add_filter( 'http_request_args', array( self::$instance, 'add_http_filters' ), 10, 2 );

			$integrations = new Integrations();
			$integrations->load();

			self::$instance->options              = Options::instance();
			self::$instance->view                 = new View();
			self::$instance->archive_creation_job = new Archive_Creation_Job();
			self::$instance->page_handlers        = new Page_Handlers();

			$page                         = isset( $_GET['page'] ) ? $_GET['page'] : '';
			self::$instance->current_page = $page;

			// Maybe run upgrade.
			Upgrade_Handler::run();

			// Boot up admin.
			Admin_Settings::get_instance();
		}

		return self::$instance;
	}

	/**
	 * Include required files
	 * @return void
	 */
	private function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) );
		require_once $path . 'src/class-ss-phpuri.php';
		require_once $path . 'src/class-ss-options.php';
		require_once $path . 'src/class-ss-view.php';
		require_once $path . 'src/class-ss-url-extractor.php';
		require_once $path . 'src/class-ss-url-fetcher.php';
		require_once $path . 'src/class-ss-archive-creation-job.php';
		require_once $path . 'src/tasks/class-ss-task.php';
		require_once $path . 'src/tasks/class-ss-setup-task.php';
		require_once $path . 'src/tasks/class-ss-fetch-urls-task.php';
		require_once $path . 'src/tasks/class-ss-transfer-files-locally-task.php';
		require_once $path . 'src/tasks/class-ss-simply-cdn-task.php';
		require_once $path . 'src/tasks/class-ss-create-zip-archive.php';
		require_once $path . 'src/tasks/class-ss-wrapup-task.php';
		require_once $path . 'src/tasks/class-ss-cancel-task.php';
		require_once $path . 'src/handlers/class-ss-page-handler.php';
		require_once $path . 'src/class-ss-query.php';
		require_once $path . 'src/models/class-ss-model.php';
		require_once $path . 'src/models/class-ss-page.php';
		require_once $path . 'src/class-ss-diagnostic.php';
		require_once $path . 'src/class-ss-sql-permissions.php';
		require_once $path . 'src/class-ss-upgrade-handler.php';
		require_once $path . 'src/class-ss-util.php';
		require_once $path . 'src/class-page-handlers.php';
		require_once $path . 'src/class-integrations.php';
		require_once $path . 'src/admin/inc/class-ss-admin-settings.php';
		require_once $path . 'src/admin/inc/class-ss-migrate-settings.php';
	}

	/**
	 * Enqueue admin-specific javascript files for this plugin's admin pages only
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		// Plugin admin JS. Tack on plugin version.
		if ( $this->current_page === 'simply-static' ) {
			wp_enqueue_script( self::SLUG . '-generate-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'js/admin-generate.js', array(), SIMPLY_STATIC_VERSION );
			wp_localize_script(
				self::SLUG . '-generate-styles',
				'ss_generate',
				[
					'is_network_admin' => is_network_admin() ? '1' : '0',
					'is_cron'          => Util::is_cron() ? '1' : '0'
				]
			);
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 * @return void
	 */
	public function add_plugin_admin_menu() {

		if ( apply_filters( 'ss_hide_admin_menu', false ) ) {
			return;
		}

		// Add main menu item.
		/*
	add_menu_page(
		__( 'Simply Static', 'simply-static' ),
		__( 'Simply Static', 'simply-static' ),
		apply_filters( 'ss_settings_capability', 'edit_posts' ),
		self::SLUG,
		array( self::$instance, 'display_generate_page' ),
		SIMPLY_STATIC_URL . '/assets/simply-static-icon.svg',
	);


	add_submenu_page(
		self::SLUG,
		__( 'Generate Static Site', 'simply-static' ),
		__( 'Generate', 'simply-static' ),
		apply_filters( 'ss_settings_capability', 'edit_posts' ),
		self::SLUG,
		array( self::$instance, 'display_generate_page' )
	);
	*/
	}

	/**
	 * Render the page for generating a static site
	 * @return void
	 */
	public function display_generate_page() {
		$done = $this->archive_creation_job->is_job_done();

		$this->view
			->set_layout( 'admin' )
			->set_template( 'generate' )
			->assign( 'archive_generation_done', $done );

		do_action( 'ss_before_render_generate_page', $this->view, $this->options );

		$this->view->render();
	}


	/**
	 * Handle archive job without ajax.
	 *
	 * @return void
	 */
	public function run_static_export( $blog_id = 0 ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}
		do_action( 'ss_before_static_export', $blog_id );
		$this->archive_creation_job->start( $blog_id );
	}

	/**
	 * Handle cancel archive job without ajax.
	 *
	 * @return void
	 */
	public function cancel_static_export() {
		$this->archive_creation_job->cancel();
	}

	/**
	 * Handle requests for creating a static archive and send a response via ajax
	 * @return void
	 */
	public function static_archive_action() {
		check_ajax_referer( 'simply-static_generate' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die( __( 'Not permitted', 'simply-static' ) );
		}

		$action  = $_POST['perform'];
		$blog_id = isset( $_POST['blog_id'] ) ? absint( $_POST['blog_id'] ) : get_current_blog_id();

		do_action( 'ss_before_perform_archive_action', $blog_id, $action, $this->archive_creation_job );

		if ( $action === 'start' ) {
			Util::delete_debug_log();
			Util::debug_log( "Received request to start generating a static archive" );

			do_action( 'ss_before_static_export', $blog_id );
			$this->archive_creation_job->start( $blog_id );
		} elseif ( $action === 'cancel' ) {
			Util::debug_log( "Received request to cancel static archive generation" );
			$this->archive_creation_job->cancel();
		}

		do_action( 'ss_after_perform_archive_action', $blog_id, $action, $this->archive_creation_job );


		$this->send_json_response_for_static_archive( $action );
	}

	/**
	 * Render json+html for response to static archive creation.
	 *
	 * @return void
	 */
	public function send_json_response_for_static_archive( $action ) {
		$done         = $this->archive_creation_job->is_job_done();
		$current_task = $this->archive_creation_job->get_current_task();

		$activity_log_html = $this->view
			->set_template( '_activity_log' )
			->assign( 'status_messages', $this->options->get( 'archive_status_messages' ) )
			->render_to_string();

		do_action( 'ss_before_sending_response_for_static_archive' );

		// send json response and die().
		wp_send_json(
			array(
				'action'            => $action,
				'activity_log_html' => $activity_log_html,
				'pages_status'      => $this->options->get( 'pages_status' ),
				'done'              => $done
			)
		);
	}

	public function get_activity_log( $blog_id = 0 ) {
		$blog_id = $blog_id ?: get_current_blog_id();

		do_action( 'ss_before_render_activity_log', $blog_id );

		$log = $this->options->get( 'archive_status_messages' );

		do_action( 'ss_after_render_activity_log', $blog_id );

		return $log;
	}

	/**
	 * Render the activity log and send it via ajax
	 * @return void|array
	 */
	public function render_activity_log() {
		check_ajax_referer( 'simply-static_generate' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( esc_html__( 'Not permitted', 'simply-static' ) );
		}

		$blog_id = isset( $_POST['blog_id'] ) ? absint( $_POST['blog_id'] ) : get_current_blog_id();

		do_action( 'ss_before_render_activity_log', $blog_id );

		// $archive_manager = new Archive_Manager();

		$content = $this->view
			->set_template( '_activity_log' )
			->assign( 'status_messages', $this->options->get( 'archive_status_messages' ) )
			->render_to_string();

		do_action( 'ss_after_render_activity_log', $blog_id );

		// send json response and die().
		wp_send_json( array( 'html' => $content ) );
	}

	public function get_export_log( $per_page, $current_page = 1, $blog_id = 0 ) {

		$blog_id = $blog_id ?: get_current_blog_id();

		do_action( 'ss_before_render_export_log', $blog_id );

		$offset = ( intval( $current_page ) - 1 ) * intval( $per_page );

		$static_pages = apply_filters(
			'ss_total_pages_log',
			Page::query()
			    ->limit( $per_page )
			    ->offset( $offset )
			    ->order( 'http_status_code DESC' )
			    ->find()
		);

		$http_status_codes  = Page::get_http_status_codes_summary();
		$total_static_pages = array_sum( array_values( $http_status_codes ) );
		$total_pages        = ceil( $total_static_pages / $per_page );

		do_action( 'ss_after_render_export_log', $blog_id );

		$static_pages_formatted = [];

		foreach ( $static_pages as $static_page ) {
			$msg                = '';
			$parent_static_page = $static_page->parent_static_page();
			if ( $parent_static_page ) {
				$display_url = Util::get_path_from_local_url( $parent_static_page->url );
				$msg         .= "<a href='" . $parent_static_page->url . "'>" . sprintf( __( 'Found on %s', 'simply-static' ), $display_url ) . "</a>";
			}
			if ( $msg !== '' && $static_page->status_message ) {
				$msg .= '; ';
			}
			$msg .= $static_page->status_message;

			$information = [
				'id'          => $static_page->id,
				'url'         => $static_page->url,
				'processable' => in_array( $static_page->http_status_code, Page::$processable_status_codes ),
				'code'        => $static_page->http_status_code,
				'notes'       => $msg,
				'error'       => $static_page->error_message,
			];

			$static_pages_formatted[] = $information;
		}

		return [
			'static_pages'       => $static_pages_formatted,
			'total_static_pages' => $total_static_pages,
			'total_pages'        => $total_pages,
			'status_codes'       => $http_status_codes,
		];
	}

	/**
	 * Render the export log and send it via ajax
	 *
	 * @return void
	 */
	public function render_export_log() {
		check_ajax_referer( 'simply-static_generate' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( __( 'Not permitted', 'simply-static' ) );
		}

		$blog_id      = isset( $_POST['blog_id'] ) ? absint( $_POST['blog_id'] ) : get_current_blog_id();
		$per_page     = $_POST['per_page'];
		$current_page = $_POST['page'];

		$log = $this->get_export_log( $per_page, $current_page, $blog_id );

		$content = $this->view
			->set_template( '_export_log' )
			->assign( 'static_pages', $log['static_pages'] )
			->assign( 'http_status_codes', $log['status_codes'] )
			->assign( 'current_page', $current_page )
			->assign( 'total_pages', $log['total_pages'] )
			->assign( 'total_static_pages', $log['total_static_pages'] )
			->render_to_string();

		// send json response and die().
		wp_send_json( array( 'html' => $content ) );
	}

	/**
	 * Starts the archive creation job.
	 *
	 * @return Archive_Creation_Job|null
	 */
	public function get_archive_creation_job() {
		return $this->archive_creation_job;
	}

	/**
	 * Set HTTP Basic Auth for wp-background-processing
	 *
	 * @param array $parsed_args given args.
	 * @param string $url given URL.
	 *
	 * @return array
	 */
	public function add_http_filters( $parsed_args, $url ) {
		if ( strpos( $url, get_bloginfo( 'url' ) ) !== false ) {
			$digest = self::$instance->options->get( 'http_basic_auth_digest' );

			if ( $digest ) {
				$parsed_args['headers']['Authorization'] = 'Basic ' . $digest;
			}
		}

		return $parsed_args;
	}

	/**
	 * Return the task list for the Archive Creation Job to process
	 *
	 * @param array $task_list The list of tasks to process.
	 * @param string $delivery_method The method of delivering static files.
	 *
	 * @return array                   The list of tasks to process
	 */
	public function filter_task_list( $task_list, $delivery_method ) {
		array_push( $task_list, 'setup', 'fetch_urls' );
		if ( $delivery_method === 'zip' ) {
			array_push( $task_list, 'create_zip_archive' );
		} else if ( $delivery_method === 'local' ) {
			array_push( $task_list, 'transfer_files_locally' );
		} else if ( $delivery_method === 'simply-cdn' ) {
			array_push( $task_list, 'simply_cdn' );
		}
		array_push( $task_list, 'wrapup' );

		return $task_list;
	}


	/**
	 * Maybe clear local directory before export.
	 *
	 * @return void
	 */
	public function maybe_clear_directory() {
		// Clear out the local directory before copying files.
		if ( 'on' === $this->options->get( 'clear_directory_before_export' ) && 'local' === $this->options->get( 'delivery_method' ) ) {
			$local_dir = apply_filters( 'ss_local_dir', $this->options->get( 'local_dir' ) );

			// Make sure the directory exists and is not empty.
			$iterator = new \FilesystemIterator( $local_dir );

			if ( is_dir( $local_dir ) && $iterator->valid() ) {
				Transfer_Files_Locally_Task::delete_local_directory_static_files( $local_dir, $this->options );
			}
		}

	}
}
