<?php

namespace Simply_Static;

class Admin_Settings {
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
	 * Setting up admin fields
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Register submenu page.
	 *
	 * @return void
	 */
	public function add_menu() {
		if ( apply_filters( 'ss_hide_admin_menu', false ) ) {
			return;
		}

		add_menu_page(
			__( 'Simply Static', 'simply-static' ),
			__( 'Simply Static', 'simply-static' ),
            apply_filters( 'ss_user_capability', 'publish_pages' , 'generate'),
			'simply-static-generate',
			array( $this, 'render_settings' ),
			SIMPLY_STATIC_URL . '/assets/simply-static-icon.svg',
		);

		$generate_suffix = add_submenu_page(
			'simply-static-generate',
			__( 'Generate', 'simply-static' ),
			__( 'Generate', 'simply-static' ),
            apply_filters( 'ss_user_capability', 'publish_pages' , 'generate'),
			'simply-static-generate',
			array( $this, 'render_settings' )
		);

		add_action( "admin_print_scripts-{$generate_suffix}", array( $this, 'add_settings_scripts' ) );

		$settings_suffix = add_submenu_page(
			'simply-static-generate',
			__( 'Settings', 'simply-static' ),
			__( 'Settings', 'simply-static' ),
            apply_filters( 'ss_user_capability', 'manage_options' , 'settings'),
			'simply-static-settings',
			array( $this, 'render_settings' )
		);

		add_action( "admin_print_scripts-{$settings_suffix}", array( $this, 'add_settings_scripts' ) );
	}

	public function add_settings_scripts() {
		$screen  = get_current_screen();
		$options = Options::reinstance();

		wp_enqueue_script( 'simplystatic-settings', SIMPLY_STATIC_URL . '/src/admin/build/index.js', array(
			'wp-api',
			'wp-components',
			'wp-element',
			'wp-api-fetch',
			'wp-data',
			'wp-i18n',
			'wp-block-editor'
		), SIMPLY_STATIC_VERSION, true );


		// Determine initial screen.
		$initial = '/';

		if ( 'simply-static_page_simply-static-settings' === $screen->base ) {
			$initial = '/general';
		}

		// Check if directory exists, if not, create it.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';

		// Check if directory exists.
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$sites = [];

		if ( is_multisite() && is_network_admin() && function_exists( 'get_sites' ) ) {
			$public_sites = get_sites( [ 'public' => true ] );
			if ( $public_sites ) {
				foreach ( $public_sites as $site ) {
					$sites[] = [
						'blog_id' => $site->blog_id,
						'name'    => $site->blogname,
						'url'     => $site->siteurl
					];
				}
			}
		}

		$args = apply_filters(
			'ss_settings_args',
			array(
				'screen'         => 'simplystatic-settings',
				'version'        => SIMPLY_STATIC_VERSION,
				'logo'           => SIMPLY_STATIC_URL . '/assets/simply-static-logo.svg',
				'plan'           => 'free',
				'is_network'     => is_network_admin(),
				'is_multisite'   => is_multisite(),
				'initial'        => $initial,
				'home'           => home_url(),
				'home_path'      => get_home_path(),
				'admin_email'    => get_bloginfo( 'admin_email' ),
				'temp_files_dir' => trailingslashit( $temp_dir ),
				'blog_id'        => get_current_blog_id(),
				'sites'          => $sites,
				'need_upgrade'   => 'no',
			)
		);

		// Check if debug log exists.
		$debug_file = Util::get_debug_log_filename();

		if ( file_exists( $debug_file ) ) {
            $uploadsDir = wp_upload_dir();
			$args['log_file'] = $uploadsDir['baseurl'] . '/simply-static/debug.txt';
		}

		// Maybe show migration notice.
		$version = $options->get( 'version' );

		if ( floatval( $version ) < floatval( '3.0.4' ) ) {
			$args['need_upgrade'] = 'yes';
		}

		wp_localize_script( 'simplystatic-settings', 'options', $args );

		// Make the blocks translatable.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'simplystatic-settings', 'simply-static', SIMPLY_STATIC_PATH . '/languages' );
		}

		wp_enqueue_style( 'simplystatic-settings-style', SIMPLY_STATIC_URL . '/src/admin/build/index.css', array( 'wp-components' ) );
	}

	public function render_settings() {
		?>
        <div id="simplystatic-settings"></div>
		<?php
	}

	/**
	 * Setup Rest API endpoints.
	 *
	 * @return void
	 */
	public function rest_api_init() {
		register_rest_route( 'simplystatic/v1', '/settings', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_settings' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/settings', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_settings' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/settings/reset', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'reset_settings' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/pages', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_pages' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/migrate', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'migrate_settings' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/system-status', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_system_status' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'diagnostics') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/delete-log', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'clear_log' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/activity-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_activity_log' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/export-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_export_log' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/start-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'start_export' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/cancel-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'cancel_export' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate') );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/is-running', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'is_running' ],
			'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate') );
			},
		) );
	}

	/**
	 * Get settings via Rest API.
	 *
	 * @return false|mixed|null
	 */
	public function get_settings() {
		return get_option( 'simply-static' );
	}

	/**
	 * Get System Status via Rest API.
	 *
	 * @return array[]
	 */
	public function get_system_status() {
		$diagnostics = new Diagnostic();

		return $diagnostics->get_checks();
	}

	/**
	 * Save settings via rest API.
	 *
	 * @param object $request given request.
	 *
	 * @return false|string
	 */
	public function save_settings( $request ) {
		if ( $request->get_params() ) {
			$options = sanitize_option( 'simply-static', $request->get_params() );

			// Handle basic auth.
			if ( isset( $options['http_basic_auth_username'] ) && $options['http_basic_auth_username'] && isset( $options['http_basic_auth_password'] ) && $options['http_basic_auth_password'] ) {
				$options['http_basic_auth_digest'] = base64_encode( $options['http_basic_auth_username'] . ':' . $options['http_basic_auth_password'] );
			} else {
                $options['http_basic_auth_digest'] = '';
            }

			// Update settings.
			update_option( 'simply-static', $options );

			return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
		}

		return json_encode( [ 'status' => 400, 'message' => "No options updated." ] );
	}

	/**
	 * Save settings via rest API.
	 *
	 * @param object $request given request.
	 *
	 * @return false|string
	 */
	public function reset_settings( $request ) {
		if ( $request->get_params() ) {
			// Check table.
			Page::create_or_update_table();

			// Reset options.
			$options = sanitize_option( 'simply-static', $request->get_params() );

			// Handle basic auth.
			if ( isset( $options['http_basic_auth_username'] ) && $options['http_basic_auth_username'] && isset( $options['http_basic_auth_password'] ) && $options['http_basic_auth_password'] ) {
				$options['http_basic_auth_digest'] = base64_encode( $options['http_basic_auth_username'] . ':' . $options['http_basic_auth_password'] );
			} else {
				$options['http_basic_auth_digest'] = '';
			}

			// Update settings.
			update_option( 'simply-static', $options );

			return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
		}

		return json_encode( [ 'status' => 400, 'message' => "No options updated." ] );
	}

	/**
	 * Get pages for settings.
	 * @return array
	 */
	public function get_pages() {
		$args = array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => - 1,
		);

		$pages = get_posts( $args );

		// Build selectable pages array.
		$selectable_pages = array();

		foreach ( $pages as $page ) {
			$selectable_pages[] = array( 'label' => $page->post_title, 'value' => $page->ID );
		}

		return $selectable_pages;
	}

	/**
	 * Migrate settings via rest API.
	 *
	 * @return false|string
	 */
	public function migrate_settings() {
		Migrate_Settings::migrate();

		return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
	}

	/**
	 * Clear log file.
	 *
	 * @return false|string
	 */
	public function clear_log() {
		Util::clear_debug_log();

		return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
	}

	/**
	 * Get Activity Log.
	 *
	 * @return false|string
	 */
	public function get_activity_log() {
		$activity_log = Plugin::instance()->get_activity_log();

		return json_encode( [
			'status'  => 200,
			'data'    => $activity_log,
			'running' => Plugin::instance()->get_archive_creation_job()->is_running(),
		] );
	}

	/**
	 * Get Export Log
	 *
	 * @return false|string
	 */
	public function get_export_log( $request ) {
		$params = $request->get_params();

		$export_log = Plugin::instance()->get_export_log( $params['per_page'], $params['page'] );

		return json_encode( [
			'status' => 200,
			'data'   => $export_log,
		] );
	}

	/**
	 * Start Export
	 *
	 * @return false|string
	 */
	public function start_export( $request ) {
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

		do_action( 'ss_before_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );

		Plugin::instance()->run_static_export( $blog_id );

		do_action( 'ss_after_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );

		return json_encode( [
			'status' => 200,
		] );
	}

	/**
	 * Start Export
	 *
	 * @return false|string
	 */
	public function cancel_export() {
		Util::debug_log( "Received request to cancel static archive generation" );
		$blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

		do_action( 'ss_before_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );

		Plugin::instance()->cancel_static_export();

		do_action( 'ss_after_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );

		return json_encode( [ 'status' => 200 ] );
	}

	/**
	 * Is running
	 *
	 * @return false|string
	 */
	public function is_running( $request ) {
		return json_encode( [
			'status'  => 200,
			'running' => Plugin::instance()->get_archive_creation_job()->is_running()
		] );
	}
}
