<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class
 */
class Plugin {
	/**
	 * Plugin version
	 * @var string
	 */
	const VERSION = '2.2.8';

	/**
	 * The slug of the plugin; used in actions, filters, i18n, table names, etc.
	 * @var string
	 */
	const SLUG = 'simply-static'; // keep it short; stick to alphas & dashes

	/**
	 * Singleton instance
	 * @var Simply_Static
	 */
	protected static $instance = null;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	/**
	 * View object
	 * @var \Simply_Static\View
	 */
	protected $view = null;

	/**
	 * Archive creation process
	 * @var Simply_Static\Archive_Creation_Job
	 */
	protected $archive_creation_job = null;

	/**
	 * Current page name
	 * @var string
	 */
	protected $current_page = '';

	/**
	 * @var null|\Simply_Static\Page_Handlers
	 */
	protected $page_handlers = null;

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Return an instance of the Simply Static plugin
	 * @return Simply_Static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->includes();

			// Load the text domain for i18n
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			// Enqueue admin styles
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_styles' ) );
			// Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_scripts' ) );
			// Add the options page and menu item.
			add_action( 'admin_menu', array( self::$instance, 'add_plugin_admin_menu' ), 2 );
			// Add admin info links.
			add_action( 'simply_static_admin_info_links', array( self::$instance, 'add_info_links' ) );

			// Maybe clear local directory.
			add_action( 'ss_after_setup_task', array( self::$instance, 'maybe_clear_directory' ) );

			// Handle AJAX requests
			add_action( 'wp_ajax_static_archive_action', array( self::$instance, 'static_archive_action' ) );
			add_action( 'wp_ajax_render_export_log', array( self::$instance, 'render_export_log' ) );
			add_action( 'wp_ajax_render_activity_log', array( self::$instance, 'render_activity_log' ) );

			// Instead of using ajax, activate export log file and run with cron.
			add_action( 'simply_static_site_export_cron', array( self::$instance, 'run_static_export' ) );

			// Filters
			add_filter( 'update_footer', array( self::$instance, 'filter_update_footer' ), 15 );
			add_filter( 'simplystatic.archive_creation_job.task_list', array(
				self::$instance,
				'filter_task_list'
			), 10, 2 );

			add_filter( 'http_request_args', array( self::$instance, 'add_http_filters' ), 10, 2 );

			// Disable WP lazy loading for more fail-safe crawling.
			add_filter( 'wp_lazy_loading_enabled', '__return_false' );

			$integrations = new Integrations();
			$integrations->load();

			self::$instance->options              = Options::instance();
			self::$instance->view                 = new View();
			self::$instance->archive_creation_job = new Archive_Creation_Job();
			self::$instance->page_handlers        = new Page_Handlers();

			$page                         = isset( $_GET['page'] ) ? $_GET['page'] : '';
			self::$instance->current_page = $page;

			Upgrade_Handler::run();

			// Exclude pages if not set.
			$urls_to_exclude = self::$instance->options->get( 'urls_to_exclude' );

			if ( ! isset( $urls_to_exclude ) || empty( $urls_to_exclude ) ) {
				$urls_to_exclude = array(
					site_url() . DIRECTORY_SEPARATOR . 'wp-json'      => array(
						'url'           => site_url() . DIRECTORY_SEPARATOR . 'wp-json',
						'do_not_save'   => '1',
						'do_not_follow' => '1',
					),
					site_url() . DIRECTORY_SEPARATOR . 'wp-login.php' => array(
						'url'           => site_url() . DIRECTORY_SEPARATOR . 'wp-login.php',
						'do_not_save'   => '1',
						'do_not_follow' => '1',
					),
				);
				self::$instance->options->set( 'urls_to_exclude', $urls_to_exclude );
			}
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
	}

	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only
	 * @return void
	 */
	public function enqueue_admin_styles() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_style( self::SLUG . '-admin-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css', array(), self::VERSION );
	}

	/**
	 * Enqueue admin-specific javascript files for this plugin's admin pages only
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		// Plugin admin JS. Tack on plugin version.
		if ( $this->current_page === 'simply-static' ) {
			wp_enqueue_script( self::SLUG . '-generate-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'js/admin-generate.js', array(), self::VERSION );
			wp_localize_script(
				self::SLUG . '-generate-styles',
				'ss_generate',
				[
					'is_network_admin' => is_network_admin() ? '1' : '0'
				]
			);
		}

		if ( $this->current_page === 'simply-static_settings' ) {
			wp_enqueue_script( self::SLUG . '-settings-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'js/admin-settings.js?1', array(), self::VERSION );
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

		// Add main menu item
		add_menu_page(
			__( 'Simply Static', 'simply-static' ),
			__( 'Simply Static', 'simply-static' ),
			apply_filters( 'ss_settings_capability', 'edit_posts' ),
			self::SLUG,
			array( self::$instance, 'display_generate_page' ),
			'dashicons-text-page'
		);

		add_submenu_page(
			self::SLUG,
			__( 'Generate Static Site', 'simply-static' ),
			__( 'Generate', 'simply-static' ),
			apply_filters( 'ss_settings_capability', 'edit_posts' ),
			self::SLUG,
			array( self::$instance, 'display_generate_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Settings', 'simply-static' ),
			__( 'Settings', 'simply-static' ),
			apply_filters( 'ss_settings_capability', 'manage_options' ),
			self::SLUG . '_settings',
			array( self::$instance, 'display_settings_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Diagnostics', 'simply-static' ),
			__( 'Diagnostics', 'simply-static' ),
			apply_filters( 'ss_settings_capability', 'manage_options' ),
			self::SLUG . '_diagnostics',
			array( self::$instance, 'display_diagnostics_page' )
		);
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

			if ( ! defined( 'DISABLE_WP_CRON' ) || defined( 'SS_CRON' ) ) {
				if ( ! wp_next_scheduled( 'simply_static_site_export_cron' ) ) {
					wp_schedule_single_event( time(), 'simply_static_site_export_cron', [ 'blog_id' => $blog_id ] );
				}
			} else {
				// Cron is unavaiable.
				do_action( 'ss_before_static_export', $blog_id );
				$this->archive_creation_job->start( $blog_id );
			}
		} elseif ( $action === 'cancel' ) {
			Util::debug_log( "Received request to cancel static archive generation" );
			$this->archive_creation_job->cancel();
		}

		do_action( 'ss_after_perform_archive_action', $blog_id, $action, $this->archive_creation_job );


		$this->send_json_response_for_static_archive( $action );
	}

	/**
	 * Render json+html for response to static archive creation
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

		// send json response and die()
		wp_send_json( array(
			'action'            => $action,
			'activity_log_html' => $activity_log_html,
			'pages_status'      => $this->options->get( 'pages_status' ),
			'done'              => $done // $done
		) );
	}

	/**
	 * Render the activity log and send it via ajax
	 * @return void
	 */
	public function render_activity_log() {
		check_ajax_referer( 'simply-static_generate' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( __( 'Not permitted', 'simply-static' ) );
		}

		$blog_id = isset( $_POST['blog_id'] ) ? absint( $_POST['blog_id'] ) : get_current_blog_id();

		do_action( 'ss_before_render_activity_log', $blog_id );

		// $archive_manager = new Archive_Manager();

		$content = $this->view
			->set_template( '_activity_log' )
			->assign( 'status_messages', $this->options->get( 'archive_status_messages' ) )
			->render_to_string();

		do_action( 'ss_after_render_activity_log', $blog_id );


		// send json response and die()
		wp_send_json( array(
			'html' => $content
		) );
	}

	/**
	 * Render the export log and send it via ajax
	 * @return void
	 */
	public function render_export_log() {
		check_ajax_referer( 'simply-static_generate' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			die( __( 'Not permitted', 'simply-static' ) );
		}

		$blog_id = isset( $_POST['blog_id'] ) ? absint( $_POST['blog_id'] ) : get_current_blog_id();

		do_action( 'ss_before_render_export_log', $blog_id );

		$per_page     = $_POST['per_page'];
		$current_page = $_POST['page'];
		$offset       = ( intval( $current_page ) - 1 ) * intval( $per_page );

		$static_pages = apply_filters(
			'ss_total_pages_log',
			Page::query()
			    ->limit( $per_page )
			    ->offset( $offset )
			    ->order( 'http_status_code' )
			    ->find()
		);

		$http_status_codes  = Page::get_http_status_codes_summary();
		$total_static_pages = array_sum( array_values( $http_status_codes ) );
		$total_pages        = ceil( $total_static_pages / $per_page );

		$content = $this->view
			->set_template( '_export_log' )
			->assign( 'static_pages', $static_pages )
			->assign( 'http_status_codes', $http_status_codes )
			->assign( 'current_page', $current_page )
			->assign( 'total_pages', $total_pages )
			->assign( 'total_static_pages', $total_static_pages )
			->render_to_string();

		do_action( 'ss_after_render_export_log' );


		// send json response and die()
		wp_send_json( array(
			'html' => $content
		) );
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
	 * Render the options page
	 * @return void
	 */
	public function display_settings_page() {
		if ( isset( $_POST['_settings'] ) ) {
			$this->save_options();
		} else if ( isset( $_POST['_reset'] ) ) {
			$this->reset_plugin();
		}

		$this->view
			->set_layout( 'admin' )
			->set_template( 'settings' )
			->assign( 'origin_scheme', Util::origin_scheme() )
			->assign( 'origin_host', Util::origin_host() )
			->assign( 'destination_scheme', $this->options->get( 'destination_scheme' ) )
			->assign( 'destination_host', $this->options->get( 'destination_host' ) )
			->assign( 'temp_files_dir', $this->options->get( 'temp_files_dir' ) )
			->assign( 'additional_urls', $this->options->get( 'additional_urls' ) )
			->assign( 'additional_files', $this->options->get( 'additional_files' ) )
			->assign( 'urls_to_exclude', $this->options->get( 'urls_to_exclude' ) )
			->assign( 'delivery_method', $this->options->get( 'delivery_method' ) )
			->assign( 'local_dir', $this->options->get( 'local_dir' ) )
			->assign( 'destination_url_type', $this->options->get( 'destination_url_type' ) )
			->assign( 'force_replace_url', $this->options->get( 'force_replace_url' ) )
			->assign( 'clear_directory_before_export', $this->options->get( 'clear_directory_before_export' ) )
			->assign( 'relative_path', $this->options->get( 'relative_path' ) )
			->assign( 'http_basic_auth_digest', $this->options->get( 'http_basic_auth_digest' ) )
			->assign( 'allow_subsites', $this->options->get( 'allow_subsites' ) )
			->render();
	}

	/**
	 * Save the options from the options page
	 * @return void
	 */
	public function save_options() {
		check_admin_referer( 'simply-static_settings' );

		// Set destination url type / scheme / host
		$destination_url_type = $this->fetch_post_value( 'destination_url_type' );

		$destination_scheme = $this->fetch_post_value( 'destination_scheme' );
		$destination_host   = untrailingslashit( $this->fetch_post_value( 'destination_host' ) );

		// Set URLs to exclude
		$urls_to_exclude = array();
		$excludables     = $this->fetch_post_array_value( 'excludable' );

		foreach ( $excludables as $excludable ) {
			$url = trim( $excludable['url'] );
			// excluding the template row (always has a blank url) and any rows
			// that the user didn't fill in
			if ( $url !== '' ) {
				$urls_to_exclude[ $url ] = array(
					'url'           => $url,
					'do_not_save'   => $excludable['do_not_save'],
					'do_not_follow' => $excludable['do_not_follow'],
				);
			}
		}

		$system_excludables = array(
			site_url() . DIRECTORY_SEPARATOR . 'wp-json'      => array(
				'url'           => site_url() . DIRECTORY_SEPARATOR . 'wp-json',
				'do_not_save'   => '1',
				'do_not_follow' => '1',
			),
			site_url() . DIRECTORY_SEPARATOR . 'wp-login.php' => array(
				'url'           => site_url() . DIRECTORY_SEPARATOR . 'wp-login.php',
				'do_not_save'   => '1',
				'do_not_follow' => '1',
			),
		);

		if ( isset( $urls_to_exclude ) && ! empty( $urls_to_exclude ) ) {
			$urls_to_exclude = array_merge( $system_excludables, $urls_to_exclude );
		} else {
			$urls_to_exclude = $system_excludables;
		}

		// Set relative path
		$relative_path = $this->fetch_post_value( 'relative_path' );
		$relative_path = untrailingslashit( Util::add_leading_slash( $relative_path ) );

		// Set basic auth
		// Checking $_POST array to see if fields exist. The fields are disabled
		// if the digest is set, but fetch_post_value() would still return them
		// as empty strings.
		if ( isset( $_POST['basic_auth_username'] ) && isset( $_POST['basic_auth_password'] ) ) {
			$basic_auth_user = trim( $this->fetch_post_value( 'basic_auth_username' ) );
			$basic_auth_pass = trim( $this->fetch_post_value( 'basic_auth_password' ) );

			if ( $basic_auth_user != '' && $basic_auth_pass != '' ) {
				$http_basic_auth_digest = base64_encode( $basic_auth_user . ':' . $basic_auth_pass );
			} else {
				$http_basic_auth_digest = null;
			}
			$this->options->set( 'http_basic_auth_digest', $http_basic_auth_digest );
		}

		// Save settings.
		$options = apply_filters(
			'simply_static_options',
			array(
				'destination_scheme'            => $destination_scheme,
				'destination_host'              => $destination_host,
				'temp_files_dir'                => Util::trailingslashit_unless_blank( $this->fetch_post_value( 'temp_files_dir' ) ),
				'additional_urls'               => $this->fetch_post_value( 'additional_urls' ),
				'additional_files'              => $this->fetch_post_value( 'additional_files' ),
				'urls_to_exclude'               => $urls_to_exclude,
				'delivery_method'               => $this->fetch_post_value( 'delivery_method' ),
				'local_dir'                     => Util::trailingslashit_unless_blank( $this->fetch_post_value( 'local_dir' ) ),
				'destination_url_type'          => $destination_url_type,
				'relative_path'                 => $relative_path,
				'force_replace_url'             => $this->fetch_post_value( 'force_replace_url' ),
				'clear_directory_before_export' => $this->fetch_post_value( 'clear_directory_before_export' ),
			)
		);

		if ( is_network_admin() ) {
			$options['allow_subsites'] = $this->fetch_post_value( 'allow_subsites' );
		}

		foreach ( $options as $key => $value ) {
			$value = $this->sanitize_option( $key, $value );
			$this->options->set( $key, $value );
		}

		$this->options->save();

		$message = __( 'Your changes have been saved.', 'simply-static' );
		$this->view->add_flash( 'updated', $message );
	}

	/**
	 * Sanitize Option
	 *
	 * @param string $key Option key.
	 * @param mixed $value Option value.
	 *
	 * @return mixed
	 */
	public function sanitize_option( $key, $value ) {
		switch ( $key ) {
			case 'additional_urls':
			case 'additional_files':
				$value = str_replace( '"', '', $value );
				$value = str_replace( "'", '', $value );
				break;
		}

		return $value;
	}

	/**
	 * Render the diagnostics page
	 * @return void
	 */
	public function display_diagnostics_page() {
		if ( isset( $_POST['_diagnostics'] ) ) {
			$this->save_diagnostics_options();
		} else if ( isset( $_POST['_email_debug_log'] ) ) {
			$this->email_debug_log();
		}

		$debug_file        = Util::get_debug_log_filename();
		$debug_file_exists = file_exists( $debug_file );
		$debug_file_url    = plugin_dir_url( dirname( __FILE__ ) ) . basename( $debug_file );

		$diagnostic = new Diagnostic();
		$results    = $diagnostic->results;

		$themes             = wp_get_themes();
		$current_theme      = wp_get_theme();
		$current_theme_name = $current_theme->name;

		$plugins = get_plugins();

		$this->view
			->set_layout( 'admin' )
			->set_template( 'diagnostics' )
			->assign( 'debugging_mode', $this->options->get( 'debugging_mode' ) )
			->assign( 'debug_file_exists', $debug_file_exists )
			->assign( 'debug_file_url', $debug_file_url )
			->assign( 'results', $results )
			->assign( 'themes', $themes )
			->assign( 'current_theme_name', $current_theme_name )
			->assign( 'plugins', $plugins )
			->render();
	}

	/**
	 * Save the options from the diagnostics page
	 * @return void
	 */
	public function save_diagnostics_options() {
		check_admin_referer( 'simply-static_diagnostics' );

		// Save settings
		$this->options
			->set( 'debugging_mode', $this->fetch_post_value( 'debugging_mode' ) )
			->save();

		$message = __( 'Your changes have been saved.', 'simply-static' );
		$this->view->add_flash( 'updated', $message );
	}

	/**
	 * Send the debug log to the desired email address
	 * @return void
	 */
	public function email_debug_log() {
		check_admin_referer( 'simply-static_email_debug_log' );

		$debug_file = Util::get_debug_log_filename();
		if ( file_exists( $debug_file ) ) {
			$email = $this->fetch_post_value( 'email_address' );

			$zip_filename = $debug_file . '.zip';
			$zip_archive  = new \PclZip( $zip_filename );

			if ( $zip_archive->create( $debug_file, PCLZIP_OPT_REMOVE_ALL_PATH ) === 0 ) {
				$message = __( 'Unable to create a ZIP of the debug log.', 'simply-static' );
				$this->view->add_flash( 'error', $message );
			} else {
				$content = $this->get_content_for_debug_email();

				// file_put_contents( $debug_file . '.html', $content );

				if ( wp_mail( $email, 'Simply Static Debug Log', $content, '', $zip_filename ) === true ) {
					$message = sprintf( __( 'Debug log successfully sent to: %s', 'simply-static' ), $email );
					$this->view->add_flash( 'updated', $message );
				} else {
					$message = __( 'We encountered an error when attempting to send out the debug log.', 'simply-static' );
					$this->view->add_flash( 'error', $message );
				}

				unlink( $zip_filename );
			}
		}
	}

	/**
	 * Generate the HTML content needed for the debug email
	 * @return string HTML content for the debug email
	 */
	protected function get_content_for_debug_email() {
		$content = "<div class='center'>";

		$content .= "<table width='600'>"
		            . "<tr><td><b>URL:</b></td><td>" . get_bloginfo( 'url' ) . "</td></tr>"
		            . "<tr><td><b>WP URL:</b></td><td>" . get_bloginfo( 'wpurl' ) . "</td></tr>"
		            . "<tr><td><b>Plugin Version:</b></td><td>" . Plugin::VERSION . "</td></tr>"
		            . "<tr><td><b>WP Version:</b></td><td>" . get_bloginfo( 'version' ) . "</td></tr>"
		            . "<tr><td><b>Multisite:</b></td><td>" . ( is_multisite() ? 'yes' : 'no' ) . "</td></tr>"
		            . "<tr><td><b>Admin Email:</b></td><td>" . get_bloginfo( 'admin_email' ) . "</td></tr>"
		            . "</table><br />";

		$content .= "<table width='600'><thead><tr><th>Settings</th></tr></thead><tbody><tr><td><pre>";
		$options = get_option( Plugin::SLUG );
		$content .= print_r( $options, true );
		$content .= "</pre></td></tr></tbody></table><br />";

		$diagnostic = new Diagnostic();
		$results    = $diagnostic->results;

		foreach ( $results as $title => $tests ) {
			$content .= "<table width='600'><thead><tr><th colspan='2'>" . $title . "</th></tr></thead><tbody>";
			foreach ( $tests as $result ) {
				$content .= "<tr><td>" . $result['label'] . "</td>";
				if ( $result['test'] ) {
					$content .= "<td style='color: #008000; font-weight: bold;'>" . $result['message'] . "</td>";
				} else {
					$content .= "<td style='color: #dc143c; font-weight: bold;'>" . $result['message'] . "</td>";
				}
				$content .= "</tr>";
			}
			$content .= "</tbody></table><br />";
		}

		$themes             = wp_get_themes();
		$current_theme      = wp_get_theme();
		$current_theme_name = $current_theme->name;

		$content .= "<table width='600'><thead><tr><th>Theme Name</th><th>Theme URL</th><th>Version</th><th>Enabled</th></tr></thead><tbody>";
		foreach ( $themes as $theme ) {
			$content .= "<tr>";
			$content .= "<td width='20%'>" . $theme->get( 'Name' ) . "</td>";
			$content .= "<td width='60%'><a href='" . $theme->get( 'ThemeURI' ) . "'>" . $theme->get( 'ThemeURI' ) . "</a></td>";
			$content .= "<td width='10%'>" . $theme->get( 'Version' ) . "</td>";
			if ( $theme->get( 'Name' ) === $current_theme_name ) {
				$content .= "<td width='10%' style='color: #008000; font-weight: bold;'>Yes</td>";
			} else {
				$content .= "<td width='10%' style='color: #dc143c; font-weight: bold;'>No</td>";
			}
			$content .= "</tr>";
		}
		$content .= "</tbody></table><br />";

		$plugins = get_plugins();

		$content .= "<table width='600'><thead><tr><th>Plugin Name</th><th>Plugin URL</th><th>Version</th><th>Enabled</th></tr></thead><tbody>";
		foreach ( $plugins as $plugin_path => $plugin_data ) {
			$content .= "<tr>";
			$content .= "<td width='20%'>" . $plugin_data['Name'] . "</td>";
			$content .= "<td width='60%'><a href='" . $plugin_data['PluginURI'] . "'>" . $plugin_data['PluginURI'] . "</a></td>";
			$content .= "<td width='10%'>" . $plugin_data['Version'] . "</td>";
			if ( is_plugin_active( $plugin_path ) ) {
				$content .= "<td width='10%' style='color: #008000; font-weight: bold;'>Yes</td>";
			} else {
				$content .= "<td width='10%' style='color: #dc143c; font-weight: bold;'>No</td>";
			}
			$content .= "</tr>";
		}
		$content .= "</tbody></table><br />";

		$content .= "<br /><br /></div>";

		ob_start();
		phpinfo();
		$phpinfo = ob_get_contents();
		ob_get_clean();

		$content .= $phpinfo;

		return $content;
	}

	/**
	 * Fetch a POST variable by name and sanitize it
	 *
	 * @param string $variable_name Name of the POST variable to fetch
	 *
	 * @return string                Value of the POST variable
	 */
	public function fetch_post_value( $variable_name ) {
		$input = filter_input( INPUT_POST, $variable_name );

		// using explode/implode to keep linebreaks in text areas
		return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $input ) ) );
	}

	/**
	 * Fetch a POST array variable by name and sanitize it
	 *
	 * @param string $variable_name Name of the POST variable to fetch
	 *
	 * @return string                Value of the POST variable
	 */
	public function fetch_post_array_value( $variable_name ) {
		return filter_input( INPUT_POST, $variable_name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}

	/**
	 * Reset the plugin back to its original state
	 * @return void
	 */
	public function reset_plugin() {
		check_admin_referer( 'simply-static_reset' );

		// Delete Simply Static's settings
		delete_option( 'simply-static' );
		// Drop the Pages table
		Page::drop_table();
		// Set up a new instance of Options
		$this->options = Options::reinstance();
		// Set up default options and re-create the Pages table
		Upgrade_Handler::run();

		$message = __( 'Plugin reset complete.', 'simply-static' );
		$this->view->add_flash( 'updated', $message );
	}

	/**
	 * Loads the plugin language files
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			self::SLUG,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Set wp_mail to send out html emails
	 * @return string
	 */
	public function filter_wp_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Set HTTP Basic Auth for wp-background-processing
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
	 * Display plugin version in footer when on plugin page
	 * @return string
	 */
	public function filter_update_footer( $text ) {
		if ( ! self::$instance->in_plugin() ) {
			return $text;
		}

		$version_text = __( 'Simply Static Version' ) . ': <a title="' . __( 'View what changed in this version', 'simply-static' ) . '" href="https://wordpress.org/plugins/simply-static/changelog/">' . self::VERSION . '</a>';

		return $version_text;
	}

	/**
	 * Return the task list for the Archive Creation Job to process
	 *
	 * @param array $task_list The list of tasks to process
	 * @param string $delivery_method The method of delivering static files
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
	 * Are we currently within the plugin?
	 * @return boolean true if we're within the plugin, false otherwise
	 */
	public function in_plugin() {
		return strpos( $this->current_page, self::SLUG ) === 0;
	}

	/**
	 * Return whether or not debug mode is on
	 * @return boolean Debug mode enabled?
	 */
	public function debug_on() {
		return $this->options->get( 'debugging_mode' ) === '1';
	}

	/**
	 * Add information links in admin header.
	 *
	 * @return void
	 */
	public function add_info_links( $info_text ) {
		ob_start();
		?>
        <a class="button button-secondary" href="https://simplycdn.io" target="_blank">Simply CDN</a>
        <a class="button button-secondary" href="https://patrickposner.dev/plugins/simply-static" target="_blank">Simply
            Static Pro</a>
		<?php
		$info_text = apply_filters( 'simply_static_info_links', ob_get_clean() );
		echo $info_text;
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
