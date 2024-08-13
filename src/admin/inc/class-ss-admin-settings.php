<?php

namespace Simply_Static;

class Admin_Settings {
	/**
	 * Contains the number of failed tests.
	 *
	 * @var int
	 */
	public int $failed_tests = 0;

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

		$this->failed_tests = intval( get_transient( 'simply_static_failed_tests' ) );

		Admin_Meta::get_instance();
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

		// Generate settings page.
		add_menu_page(
			__( 'Simply Static', 'simply-static' ),
			__( 'Simply Static', 'simply-static' ),
			apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ),
			'simply-static-generate',
			array( $this, 'render_settings' ),
			SIMPLY_STATIC_URL . '/assets/simply-static-icon.svg',
		);

		$generate_suffix = add_submenu_page(
			'simply-static-generate',
			__( 'Generate', 'simply-static' ),
			__( 'Generate', 'simply-static' ),
			apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ),
			'simply-static-generate',
			array( $this, 'render_settings' )
		);

		add_action( "admin_print_scripts-{$generate_suffix}", array( $this, 'add_settings_scripts' ) );

		if ( ! is_network_admin() ) {
			// Add settings page.
			$settings_suffix = add_submenu_page(
				'simply-static-generate',
				__( 'Settings', 'simply-static' ),
				__( 'Settings', 'simply-static' ),
				apply_filters( 'ss_user_capability', 'manage_options', 'settings' ),
				'simply-static-settings',
				array( $this, 'render_settings' )
			);

			add_action( "admin_print_scripts-{$settings_suffix}", array( $this, 'add_settings_scripts' ) );

			$notifications = sprintf( '<span class="update-plugins diagnostics-error"><span class="plugin-count" aria-hidden="true">%s</span><span class="screen-reader-text">errors in diagnostics</span></span>', $this->failed_tests );

			// Add diagnostics page.
			$diagnostics_suffix = add_submenu_page(
				'simply-static-generate',
				__( 'Diagnostics', 'simply-static' ),
				$this->failed_tests > 0 ? __( 'Diagnostics', 'simply-static' ) . ' ' . wp_kses_post( $notifications ) : __( 'Diagnostics', 'simply-static' ),
				apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ),
				'simply-static-diagnostics',
				array( $this, 'render_settings' )
			);

			add_action( "admin_print_scripts-{$diagnostics_suffix}", array( $this, 'add_settings_scripts' ) );
		}
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

		// Maybe switch to Diagnostics.
		if ( 'simply-static_page_simply-static-diagnostics' === $screen->base ) {
			$initial = '/diagnostics';
		}


		// Check if directory exists, if not, create it.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';

		// Check if directory exists.
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$args = apply_filters(
			'ss_settings_args',
			array(
				'screen'         => 'simplystatic-settings',
				'version'        => SIMPLY_STATIC_VERSION,
				'logo'           => SIMPLY_STATIC_URL . '/assets/simply-static-logo.svg',
				'plan'           => 'free',
				'initial'        => $initial,
				'home'           => home_url(),
				'home_path'      => get_home_path(),
				'admin_email'    => get_bloginfo( 'admin_email' ),
				'temp_files_dir' => trailingslashit( $temp_dir ),
				'blog_id'        => get_current_blog_id(),
				'need_upgrade'   => 'no',
				'builds'         => array(),
				'integrations'   => array_map( function ( $item ) {
					$object = new $item;

					return $object->js_object();
				}, Plugin::instance()->get_integrations() ),
			)
		);

		if ( defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
			$args['version_pro'] = SIMPLY_STATIC_PRO_VERSION;

			// Pass in additional data.
			$data = get_option( 'fs_accounts' );

			if ( ! empty( $data['plugin_data']['simply-static-pro'] ) ) {
				if ( isset( $data['plugin_data']['simply-static-pro']['connectivity_test'] ) ) {
					$args['connect'] = $data['plugin_data']['simply-static-pro']['connectivity_test'];
				}
			}
		}

		// Multisite?
		if ( is_multisite() && function_exists( 'get_sites' ) ) {
			$sites            = [];
			$selectable_sites = [];
			$public_sites     = get_sites( [ 'public' => true ] );

			if ( $public_sites ) {
				foreach ( $public_sites as $site ) {
					$sites[] = [
						'blog_id'          => $site->blog_id,
						'name'             => $site->blogname,
						'url'              => $site->siteurl,
						'settings_url'     => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-settings' ),
						'activity_log_url' => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-generate' )
					];

					if ( $site->blog_id != get_current_blog_id() ) {
						$selectable_sites[] = [
							'blog_id' => $site->blog_id,
							'name'    => $site->blogname,
						];
					}
				}
			}

			$args['sites']            = $sites;
			$args['selectable_sites'] = $selectable_sites;
			$args['is_network']       = is_network_admin();
			$args['is_multisite']     = is_multisite();
		}

		// Check if debug log exists.
		$debug_file = Util::get_debug_log_filename();

		if ( file_exists( $debug_file ) ) {
			$uploads_dir       = wp_upload_dir();
			$simply_static_dir = $uploads_dir['baseurl'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR;
			$args['log_file']  = $simply_static_dir . $options->get( 'encryption_key' ) . '-debug.txt';
		}

		// Maybe show migration notice.
		$version = $options->get( 'version' );

		if ( floatval( $version ) < floatval( '3.0.4' ) ) {
			$args['need_upgrade'] = 'yes';
		}

		// Forms enabled?
		if ( ! empty( $options->get( 'use_forms' ) ) ) {
			$args['form_connection_url'] = esc_url( get_admin_url() . 'post-new.php?post_type=ssp-form' );
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

		register_rest_route( 'simplystatic/v1', '/update-from-network', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_from_network' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/pages', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_pages' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/pages-slugs', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_pages_slugs' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/migrate', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'migrate_settings' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/reset-diagnostics', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'reset_diagnostics' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/system-status', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_system_status' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'diagnostics' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/system-status/passed', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'check_system_status_passed' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'diagnostics' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/delete-log', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'clear_log' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/activity-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_activity_log' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/export-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_export_log' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/start-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'start_export' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/cancel-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'cancel_export' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/is-running', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'is_running' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
			},
		) );
	}

	/**
	 * Get settings via Rest API.
	 *
	 * @return false|mixed|null
	 */
	public function get_settings() {
		$settings = get_option( 'simply-static' );
		if ( empty( $settings['integrations'] ) ) {
			$integrations             = Plugin::instance()->get_integrations();
			$settings['integrations'] = array_keys( $integrations );
		}

		return $settings;
	}

	/**
	 * Get System Status via Rest API.
	 *
	 * @return array[]
	 */
	public function get_system_status() {
		$checks = get_transient( 'simply_static_checks' );

		if ( ! $checks ) {
			$diagnostics = new Diagnostic();
			$checks      = $diagnostics->get_checks();
		}

		return $checks;
	}

	/**
	 * Clear transient for diagnostics.
	 *
	 * @return string
	 */
	public function reset_diagnostics() {
		delete_transient( 'simply_static_checks' );
		delete_transient( 'simply_static_failed_tests' );

		return json_encode( [ 'status' => 200 ] );
	}

	/**
	 * All diagnostics passed?
	 *
	 * @return false|string
	 */
	public function check_system_status_passed() {
		$diagnostics = new Diagnostic();
		$passed      = 'yes';
		$checks      = $diagnostics->get_checks();

		foreach ( $checks as $topics ) {
			foreach ( $topics as $check ) {
				if ( ! $check['test'] ) {
					$passed = 'no';
					break;
				}
			}
		}

		return json_encode( [ 'status' => 200, 'passed' => $passed ] );
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

			$multiline_fields = [
				'additional_urls',
				'additional_files',
				'urls_to_exclude',
				'search_excludable',
				'iframe_urls',
				'iframe_custom_css',
				'whitelist_plugins'
			];

			$array_fields = [ 'integrations' ];

			// Sanitize each key/value pair in options.
			foreach ( $options as $key => $value ) {
				if ( in_array( $key, $multiline_fields ) ) {
					$options[ $key ] = sanitize_textarea_field( $value );
				} elseif ( in_array( $key, $array_fields ) ) {
					$options[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					// Exclude Basic Auth fields from sanitize.
					if ( $key === 'http_basic_auth_username' || $key === 'http_basic_auth_password' ) {
						continue;
					}
					$options[ $key ] = sanitize_text_field( $value );
				}
			}

			// Maybe update network settings.
			if ( is_multisite() ) {
				$blog_id = get_current_blog_id();

				if ( $blog_id > 1 ) {
					update_site_option( 'simply-static-' . $blog_id, $options );
				}
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

			// Update settings.
			update_option( 'simply-static', $options );

			return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
		}

		return json_encode( [ 'status' => 400, 'message' => "No options updated." ] );
	}

	/**
	 * Save settings via rest API from another subsite in the network.
	 *
	 * @param object $request given request.
	 *
	 * @return false|string
	 */
	public function update_from_network( $request ) {
		$params = $request->get_params();

		if ( $request->get_params() ) {
			$blog_id = intval( $params['blog_id'] );

			// Get Settings from selected subsite.
			$options = get_site_option( 'simply-static-' . $blog_id );

			// Output notice if there are no network settings for the blog id.
			if ( ! $options ) {
				return json_encode(
					[
						'status'  => 400,
						'message' => "Please save the settings on the selected subsite before importing them into a new site."
					]
				);
			}

			// Update current site settings.
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
	 * Get pages slugs for settings.
	 * @return array
	 */
	public function get_pages_slugs() {
		$args = array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => - 1,
		);

		$pages = get_posts( $args );

		// Build selectable pages array.
		$selectable_pages = array();

		foreach ( $pages as $page ) {
			$permalink = get_permalink( $page->ID );

			$selectable_pages[] = array( 'label' => $page->post_title, 'value' => $permalink );
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
	public function get_activity_log( $request ) {
		$params       = $request->get_params();
		$activity_log = Plugin::instance()->get_activity_log( $params['blog_id'] );

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
		$params     = $request->get_params();
		$export_log = Plugin::instance()->get_export_log( $params['per_page'], $params['page'], $params['blog_id'] );

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
		$type    = ! empty( $params['type'] ) ? $params['type'] : 'export';

		do_action( 'ss_before_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );

		$type = apply_filters( 'ss_export_type', $type );

		Plugin::instance()->run_static_export( $blog_id, $type );

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
