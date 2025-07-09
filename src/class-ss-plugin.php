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
	 * @var null|\Simply_Static\Integrations
	 */
	protected $integrations = null;


	/**
	 * Return an instance of the Simply Static plugin
	 * @return Simply_Static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->includes();

			// Apply hooks after init to avoid loading issues.
			add_action( 'init', function () {
				// Run export via WP-Cron.
				add_action( 'simply_static_site_export_cron', array( self::$instance, 'run_static_export' ) );

				// Filters.
				add_filter( 'simplystatic.archive_creation_job.task_list', array(
					self::$instance,
					'filter_task_list'
				), 10, 2 );

				// Maybe clear local directory.
				add_action( 'ss_after_setup_task', array( self::$instance, 'maybe_clear_directory' ) );

				// Add quick link to the plugin page.
				add_filter( 'plugin_action_links_simply-static/simply-static.php', array(
					self::$instance,
					'add_quick_links'
				) );

				// Handle Basic Auth.
				add_filter( 'http_request_args', array( self::$instance, 'add_http_filters' ), 10, 2 );

				// Set up integrations.
				self::$instance->integrations = new Integrations();
				self::$instance->integrations->load();

				// Set up defaults.
				self::$instance->options              = Options::instance();
				self::$instance->view                 = new View();
				self::$instance->archive_creation_job = new Archive_Creation_Job();
				self::$instance->page_handlers        = new Page_Handlers();

				// Set up pagination.
				$page                         = isset( $_GET['page'] ) ? $_GET['page'] : '';
				self::$instance->current_page = $page;

				// Maybe run upgrade.
				Upgrade_Handler::run();

				// Multisite.
				if ( is_multisite() ) {
					Multisite::get_instance();
				}

				// Plugin compatibility.
				Plugin_Compatibility::get_instance();

				// Boot up admin.
				Admin_Settings::get_instance();
			} );
		}

		return self::$instance;
	}

	public function get_integrations() {
		return $this->integrations->get_integrations();
	}

	public function get_integration( $integration ) {
		$integrations = $this->integrations->get_integrations();
		if ( empty( $integrations[ $integration ] ) ) {
			return null;
		}

		$class = $integrations[ $integration ];

		return new $class();
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	private function includes() {
		$path = plugin_dir_path( dirname( __FILE__ ) );
		require_once $path . 'src/class-ss-phpuri.php';
		require_once $path . 'src/class-ss-options.php';
		require_once $path . 'src/class-ss-view.php';
		require_once $path . 'src/class-ss-url-extractor.php';
		require_once $path . 'src/class-ss-url-fetcher.php';
		require_once $path . 'src/background/class-ss-async-request.php';
		require_once $path . 'src/background/class-ss-background-process.php';
		require_once $path . 'src/tasks/exceptions/class-ss-pause-exception.php';
		require_once $path . 'src/class-ss-archive-creation-job.php';
		require_once $path . 'src/tasks/traits/class-skip-further-processing-exception.php';
		require_once $path . 'src/tasks/traits/trait-can-process-pages.php';
		require_once $path . 'src/tasks/traits/trait-can-transfer.php';
		require_once $path . 'src/tasks/class-ss-task.php';
		require_once $path . 'src/tasks/class-ss-setup-task.php';
		require_once $path . 'src/tasks/class-ss-fetch-urls-task.php';
		require_once $path . 'src/tasks/class-ss-transfer-files-locally-task.php';
		require_once $path . 'src/tasks/class-ss-create-zip-archive.php';
		require_once $path . 'src/tasks/class-ss-wrapup-task.php';
		require_once $path . 'src/tasks/class-ss-cancel-task.php';
		require_once $path . 'src/tasks/class-ss-generate-404-task.php';
		require_once $path . 'src/tasks/class-ss-scan-all-task.php';
		require_once $path . 'src/handlers/class-ss-page-handler.php';
		require_once $path . 'src/class-ss-query.php';
		require_once $path . 'src/models/class-ss-model.php';
		require_once $path . 'src/models/class-ss-page.php';
		require_once $path . 'src/class-ss-diagnostic.php';
		require_once $path . 'src/class-ss-sql-permissions.php';
		require_once $path . 'src/class-ss-upgrade-handler.php';
		require_once $path . 'src/class-ss-util.php';
		require_once $path . 'src/class-ss-page-handlers.php';
		require_once $path . 'src/class-ss-integrations.php';
		require_once $path . 'src/admin/inc/class-ss-admin-settings.php';
		require_once $path . 'src/admin/inc/class-ss-admin-meta.php';
		require_once $path . 'src/admin/inc/class-ss-migrate-settings.php';
		require_once $path . 'src/class-ss-multisite.php';
		require_once $path . 'src/class-ss-plugin-compatibility.php';
	}

	/**
	 * Old method to include admin menu.
	 *
	 * @return void
	 * @deprecated
	 */
	public function add_plugin_admin_menu() {
		// Deprecated, only for upgrade support.
	}

	/**
	 * Handle static export.
	 *
	 * @param int $blog_id given blog id.
	 *
	 * @return void
	 */
	public function run_static_export( $blog_id = 0, $type = 'export' ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}
		do_action( 'ss_before_static_export', $blog_id, $type );

		// Clear transients.
		Util::clear_transients();

		// Start export.
		$this->archive_creation_job->start( $blog_id, $type );

		// Determine server type for basic auth check.
		$server_type   = esc_html( $_SERVER['SERVER_SOFTWARE'] );
		$basic_auth_on = false;

		switch ( $server_type ) {
			case ( strpos( $server_type, 'Apache' ) !== false ) :
				if ( isset( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_USER'] ) ) {
					$basic_auth_on = true;
				}
				break;
			case ( strpos( $server_type, 'nginx' ) !== false ) :
				if ( isset( $_SERVER['REMOTE_USER'] ) && ! empty( $_SERVER['REMOTE_USER'] ) ) {
					$basic_auth_on = true;
				}
				break;
			case ( strpos( $server_type, 'IIS' ) !== false ) :
				if ( isset( $_SERVER['AUTH_USER'] ) && ! empty( $_SERVER['AUTH_USER'] ) ) {
					$basic_auth_on = true;
				}
				break;
		}

		// Exit if Basic Auth but no credentials were provided.
		if ( $basic_auth_on ) {
			$options         = get_option( 'simply-static' );
			$basic_auth_user = $options['http_basic_auth_username'];
			$basic_auth_pass = $options['http_basic_auth_password'];

			if ( empty( $basic_auth_user ) && empty( $basic_auth_pass ) ) {
				// Cancel export.
				$message = __( 'Missing Basic Auth credentials - you need to configure the Basic Auth credentials in Simply Static -> Settings -> Misc -> Basic Auth to continue the export.', 'simply-static' );
				$this->archive_creation_job->cancel();
				$this->archive_creation_job->save_status_message( $message, 'error' );

				// Reset logs.
				$options['archive_name']       = null;
				$options['archive_start_time'] = null;
				$options['archive_end_time']   = null;

				update_option( 'simply-static', $options );
			}
		}
	}

	/**
	 * Handle pause archive job.
	 *
	 * @return void
	 */
	public function pause_static_export() {
		// Clear WP object cache.
		wp_cache_flush();

		// Cancel export.
		$this->archive_creation_job->pause();

		$this->get_archive_creation_job()->save_status_message( "Export paused.", 'pause', true );
	}

	/**
	 * Handle resume archive job.
	 *
	 * @return void
	 */
	public function resume_static_export() {
		// Clear WP object cache.
		wp_cache_flush();

		$this->get_archive_creation_job()->save_status_message( "Export resumed.", 'resume', true );

		// Cancel export.
		$this->archive_creation_job->resume();
	}

	/**
	 * Handle cancel archive job.
	 *
	 * @return void
	 */
	public function cancel_static_export() {
		// Clear WP object cache.
		wp_cache_flush();

		$this->get_archive_creation_job()->save_status_message( "Export cancelled.", 'cancel', true );

		// Cancel export.
		$this->archive_creation_job->cancel();
	}

	/**
	 * Get activity log data.
	 *
	 * @param int $blog_id given blog id.
	 *
	 * @return mixed
	 */
	public function get_activity_log( $blog_id = 0 ) {
		$blog_id = $blog_id ?: get_current_blog_id();

		do_action( 'ss_before_render_activity_log', $blog_id );

		$log = $this->options->get( 'archive_status_messages' );

		do_action( 'ss_after_render_activity_log', $blog_id );

		return $log;
	}

	/**
	 * Get export log data.
	 *
	 * @param int $per_page given per page.
	 * @param int $current_page given current page.
	 * @param int $blog_id given blog id.
	 *
	 * @return array
	 */
	public function get_export_log( $per_page, $current_page = 1, $blog_id = 0 ) {

		$blog_id = $blog_id ?: get_current_blog_id();

		do_action( 'ss_before_render_export_log', $blog_id );

		$per_page = $per_page ?: 25;
		$offset   = ( intval( $current_page ) - 1 ) * intval( $per_page );

		$static_pages = apply_filters(
			'ss_total_pages_log',
			Page::query()
			    ->limit( $per_page )
			    ->offset( $offset )
			    ->order( 'http_status_code DESC' )
			    ->find()
		);

		$http_status_codes  = Page::get_http_status_codes_summary();
		$total_static_pages = apply_filters( 'ss_total_pages', array_sum( array_values( $http_status_codes ) ) );
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

			// Combine status messages.
			if ( $msg !== '' && $static_page->status_message ) {
				$msg .= ' ';
			}

			// Avoid duplicate status messages.
			if ( ! empty ( $static_page->status_message ) ) {
				if ( strpos( $static_page->status_message, ';' ) !== false ) {
					$cleaned = implode( '', array_unique( explode( '; ', $static_page->status_message ) ) );
					$msg     .= $cleaned;
				} else {
					$msg .= $static_page->status_message;
				}
			} else {
				$msg .= $static_page->status_message;
			}

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
	 * Starts the archive creation job.
	 *
	 * @return Archive_Creation_Job|null
	 */
	public function get_archive_creation_job() {
		return $this->archive_creation_job;
	}

	/**
	 * Return the task list for the Archive Creation Job to process
	 *
	 * @param array $task_list The list of tasks to process.
	 * @param string $delivery_method The method of delivering static files.
	 *
	 * @return array The list of tasks to process.
	 */
	public function filter_task_list( $task_list, $delivery_method ): array {

		$generate_404            = $this->options->get( 'generate_404' );
		$scan_themes_plugins_dir = $this->options->get( 'scan_themes_plugins_dir' );

		$task_list[] = 'setup';

		if ( $scan_themes_plugins_dir ) {
			$task_list[] = 'scan_themes_plugins_dir';
		}

		$task_list[] = 'fetch_urls';

		// Add 404 task
		if ( $generate_404 ) {
			$task_list[] = 'generate_404';
		}

		if ( 'zip' === $delivery_method ) {
			$task_list[] = 'create_zip_archive';
		} elseif ( 'local' === $delivery_method ) {
			$task_list[] = 'transfer_files_locally';
		}

		$task_list[] = 'wrapup';

		return $task_list;
	}


	/**
	 * Maybe clear local directory before export.
	 *
	 * @return void
	 */
	public function maybe_clear_directory() {
		// Check the export type.
		$use_single            = get_option( 'simply-static-use-single' );
		$use_build             = get_option( 'simply-static-use-build' );
		$clear_local_directory = apply_filters( 'ss_clear_local_directory', empty( $use_build ) && empty( $use_single ) && $this->options->get( 'clear_directory_before_export' ) && 'local' === $this->options->get( 'delivery_method' ) );

		// Clear out the local directory before copying files.
		if ( $clear_local_directory ) {
			$local_dir = apply_filters( 'ss_local_dir', $this->options->get( 'local_dir' ) );

			// Make sure the directory exists and is not empty.
			$iterator = new \FilesystemIterator( $local_dir );

			if ( is_dir( $local_dir ) && $iterator->valid() ) {
				Transfer_Files_Locally_Task::delete_local_directory_static_files( $local_dir, $this->options );
			}
		}
	}

	/**
	 * Register quick links in plugins settings page.
	 *
	 * @param array $links given list of links.
	 *
	 * @return array
	 */
	public function add_quick_links( $links ) {
		$settings_url = esc_url( get_admin_url() . 'admin.php?page=simply-static-settings' );
		$docs_url     = esc_url( 'https://docs.simplystatic.com' );

		$links[] = '<a href="' . $settings_url . '">' . esc_html__( 'Settings', 'simply-static' ) . '</a>';
		$links[] = '<a target="_blank" href="' . $docs_url . '">' . esc_html__( 'Docs', 'simply-static' ) . '</a>';

		return $links;
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

		if ( ! Util::is_local_url( $url ) ) {
			return $parsed_args;
		}

		// Basic Auth?
		$basic_auth_user = self::$instance->options->get( 'http_basic_auth_username' );
		$basic_auth_pass = self::$instance->options->get( 'http_basic_auth_password' );

		if ( ! empty( $basic_auth_user ) && ! empty( $basic_auth_pass ) ) {
			$parsed_args['headers']['Authorization'] = 'Basic ' . base64_encode( $basic_auth_user . ':' . $basic_auth_pass );
		}

		return $parsed_args;
	}
}
