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

		$settings_suffix = add_submenu_page(
			'simply-static',
			__( 'Settings', 'simply-static' ),
			__( 'Settings', 'simply-static' ),
			apply_filters( 'ss_user_capability', 'manage_options' ),
			'simply-static-settings',
			array( $this, 'render_settings' )
		);

		add_action( "admin_print_scripts-{$settings_suffix}", array( $this, 'add_settings_scripts' ) );
	}

	public function add_settings_scripts() {
		$screen = get_current_screen();

		wp_enqueue_script( 'simplystatic-settings', SIMPLY_STATIC_URL . '/src/admin/build/index.js', array(
			'wp-api',
			'wp-components',
			'wp-element',
			'wp-api-fetch',
			'wp-data',
			'wp-i18n'
		), '2.3.2', true );


		$options = Options::reinstance();

		$args = array(
			'screen'         => 'simplystatic-settings',
			'version'        => '2.3.2',
			'logo'           => SIMPLY_STATIC_URL . '/assets/simply-static-logo.svg',
			'is_pro'         => false,
			'home'           => home_url(),
			'home_path'      => get_home_path(),
			'admin_email'    => get_bloginfo( 'admin_email' ),
			'temp_files_dir' => $options->get( 'temp_files_dir' ),
			'token'          => get_option( 'sch_token' )
		);


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
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/system-status', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_system_status' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/settings', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_settings' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/migrate', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'migrate_settings' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
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
	public function get_system_status(): array {
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
	public function save_settings( object $request ) {
		if ( $request->get_params() ) {
			$options = sanitize_option( 'simply-static', $request->get_params() );

			// Handle basic auth.
			if ( isset( $options['http_basic_auth_username'] ) && isset( $options['http_basic_auth_password'] ) ) {
				$options['http_basic_auth_digest'] = base64_encode( $options['http_basic_auth_username'] . ':' . $options['http_basic_auth_password'] );
			}

			// Update settings.
			update_option( 'simply-static', $options );

			return json_encode( [ "status" => 200, "message" => "Ok" ] );
		}

		return json_encode( [ "status" => 400, "message" => "No options updated." ] );
	}

	/**
	 * Migrate settings via rest API.
	 *
	 * @return false|string
	 */
	public function migrate_settings() {
		Migrate_Settings::migrate();

		return json_encode( [ "status" => 200, "message" => "Ok" ] );
	}
}
