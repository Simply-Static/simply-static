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
		// Prevent WP core from altering the admin URL with history.replaceState on Simply Static pages.
		// This avoids a SecurityError when Basic Auth credentials are present in the URL.
		add_action( 'admin_head', array( $this, 'maybe_disable_admin_canonical' ), 1 );

		// Ensure the "View Site" link points to the static site even if the admin bar integration is disabled.
		add_action( 'admin_bar_menu', array( $this, 'filter_view_site_link' ), 200 );

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
			apply_filters( 'ss_menu_position', 100 )
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
				array( $this, 'render_settings' ),
				5
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
				array( $this, 'render_settings' ),
				10
			);

			add_action( "admin_print_scripts-{$diagnostics_suffix}", array( $this, 'add_settings_scripts' ) );
		}

		if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
			// Add Simply Static Studio submenu that links to external URL
			add_submenu_page(
				'simply-static-generate',
				__( 'Static Studio', 'simply-static' ),
				__( 'Static Studio<i class="dashicons dashicons-external" style="font-size:12px;vertical-align:-2px;height:10px;"></i>', 'simply-static' ),
				apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ),
				'simply-static-studio',
				function () {
					exit;
				},
				100
			);

			// Add JavaScript to open the Studio link in a new tab
			add_action( 'admin_footer', function () {
				?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        // Find the Simply Static Studio menu item and modify its behavior
                        $('a[href="admin.php?page=simply-static-studio"]').attr('href', 'https://simplystatic.com/simply-static-studio/').attr('target', '_blank');
                    });
                </script>
				<?php
			} );
		}
	}

	public function maybe_disable_admin_canonical() {
		// Only run in admin and on Simply Static pages.
		if ( ! is_admin() ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$our_pages = array(
			'simply-static-generate',
			'simply-static-settings',
			'simply-static-diagnostics',
		);
		if ( in_array( $page, $our_pages, true ) ) {
			// Remove the core canonical URL handler that uses history.replaceState on admin pages.
			remove_action( 'admin_head', 'wp_admin_canonical_url' );
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
		$temp_dir = Util::get_temp_dir();

		// Get the current settings
		$current_settings = $this->get_settings();

		$args = apply_filters(
			'ss_settings_args',
			array(
				'screen'          => 'simplystatic-settings',
				'version'         => SIMPLY_STATIC_VERSION,
				'logo'            => SIMPLY_STATIC_URL . '/assets/simply-static-logo.svg',
				'plan'            => 'free',
				'initial'         => $initial,
				'home'            => home_url(),
				'home_path'       => get_home_path(),
				'admin_email'     => get_bloginfo( 'admin_email' ),
				'temp_files_dir'  => $temp_dir,
				'blog_id'         => get_current_blog_id(),
				'need_upgrade'    => 'no',
				'builds'          => array(),
				'hidden_settings' => apply_filters( 'ss_hidden_settings', array() ),
				'last_export_end' => $options->get( 'archive_end_time' ),
				'integrations'    => array_map( function ( $item ) {
					$object = new $item;

					return $object->js_object();
				}, Plugin::instance()->get_integrations() ),
				// Add the current settings to the args
				'current_settings' => $current_settings,
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

		if ( defined( 'SSS_VERSION' ) ) {
			$args['version_studio'] = SSS_VERSION;
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
        if ( is_multisite() ) {
            register_rest_route( 'simplystatic/v1', '/sites', array(
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_sites' ],
                    'permission_callback' => function () {
                        return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
                    },
            ) );

            register_rest_route( 'simplystatic/v1', '/trigger-cron', array(
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'trigger_cron' ],
                    'permission_callback' => function () {
                        return current_user_can( apply_filters( 'ss_user_capability', 'manage_network', 'cron' ) );
                    },
            ) );

            register_rest_route( 'simplystatic/v1', '/check-can-run', array(
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'check_if_can_run_export' ],
                    'permission_callback' => function () {
                        return current_user_can( apply_filters( 'ss_user_capability', 'manage_network', 'cron' ) );
                    },
            ) );


        }

		register_rest_route( 'simplystatic/v1', '/post-types', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_post_types' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/crawlers', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_crawlers' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/export-type', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_export_type' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
			},
		) );

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

		register_rest_route( 'simplystatic/v1', '/settings/reset-database', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'reset_database' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/settings/reset-background-queue', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'reset_background_queue' ],
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

		register_rest_route( 'simplystatic/v1', '/pause-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'pause_export' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
			},
		) );

		register_rest_route( 'simplystatic/v1', '/resume-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'resume_export' ],
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

    public function check_if_can_run_export() {
        $can_run = true;

        try {
            $multisite = Multisite::get_instance();
            $multisite->check_for_export();
        } catch ( \Exception $e ) {
            $can_run = false;
        }

        $stats = [
            'status'  => 200,
            'can_run' => $can_run
        ];

        return json_encode( $stats );
    }

	/**
	 * Get settings via Rest API.
	 *
	 * @return false|mixed|null
	 */
	public function get_settings() {
		$settings = get_option( 'simply-static' );
		if ( empty( $settings['integrations'] ) ) {
			$integrations         = Plugin::instance()->get_integrations();
			$enabled_integrations = [];

			foreach ( $integrations as $integration => $class ) {
				$object = new $class;

				if ( ! $object->is_enabled() ) {
					continue;
				}

				$enabled_integrations[] = $integration;
			}

			$settings['integrations'] = $enabled_integrations;
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
		$passed = 'yes';

		// Prefer cached checks to avoid heavy recomputation on frequent requests.
		$checks = get_transient( 'simply_static_checks' );
		if ( false === $checks || empty( $checks ) ) {
			$diagnostics = new Diagnostic();
			$checks      = $diagnostics->get_checks();
		}

		foreach ( $checks as $topics ) {
			foreach ( $topics as $check ) {
				if ( isset( $check['test'] ) && ! $check['test'] ) {
					$passed = 'no';
					break 2;
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
				'whitelist_plugins',
				'minify_css_exclude',
				'minify_js_exclude'
			];

			$array_fields = [ 'integrations', 'crawlers', 'post_types' ];

			// Sanitize each key/value pair in options.
			foreach ( $options as $key => $value ) {
				if ( in_array( $key, $multiline_fields ) ) {
					$options[ $key ] = sanitize_textarea_field( $value );
				} elseif ( in_array( $key, $array_fields ) ) {
					// Ensure value is an array before using array_map
					if ( is_array( $value ) ) {
						$options[ $key ] = array_map( 'sanitize_text_field', $value );
					} else {
						// If not an array, initialize as empty array
						$options[ $key ] = [];
					}
				} else {
					// Exclude Basic Auth fields from sanitize.
					if ( $key === 'http_basic_auth_username' || $key === 'http_basic_auth_password' ) {
						// If they are empty, also clear $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW']
						if ( $key === 'http_basic_auth_username' && empty( $value ) ) {
							if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
								unset( $_SERVER['PHP_AUTH_USER'] );
							}
						}

						if ( $key === 'http_basic_auth_password' && empty( $value ) ) {
							if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
								unset( $_SERVER['PHP_AUTH_PW'] );
							}
						}

						// Continue with other options.
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
	 * Reset settings to default values via rest API.
	 *
	 * @param object $request given request.
	 *
	 * @return false|string
	 */
	public function reset_settings( $request ) {
		// Check table.
		Page::create_or_update_table();

		// Define default options (copied from Upgrade_Handler class)
		$default_options = array(
			'destination_scheme'            => 'https://',
			'destination_host'              => '',
			'temp_files_dir'                => '',
			'additional_urls'               => '',
			'additional_files'              => '',
			'urls_to_exclude'               => "",
			'delivery_method'               => 'zip',
			'local_dir'                     => '',
			'relative_path'                 => '',
			'destination_url_type'          => 'relative',
			'debugging_mode'                => true,
			'server_cron'                   => false,
			'whitelist_plugins'             => '',
			'http_basic_auth_username'      => '',
			'http_basic_auth_password'      => '',
			'origin_url'                    => '',
			'force_replace_url'             => true,
			'clear_directory_before_export' => false,
			'iframe_urls'                   => '',
			'iframe_custom_css'             => '',
			'tiiny_email'                   => get_bloginfo( 'admin_email' ),
			'tiiny_subdomain'               => '',
			'tiiny_domain_suffix'           => 'tiiny.site',
			'tiiny_password'                => '',
			'cdn_api_key'                   => '',
			'cdn_storage_host'              => 'storage.bunnycdn.com',
			'cdn_access_key'                => '',
			'cdn_pull_zone'                 => '',
			'cdn_storage_zone'              => '',
			'cdn_directory'                 => '',
			'github_account_type'           => 'personal',
			'github_user'                   => '',
			'github_email'                  => '',
			'github_personal_access_token'  => '',
			'github_repository'             => '',
			'github_repository_visibility'  => 'public',
			'github_branch'                 => 'main',
			'github_webhook_url'            => '',
			'github_folder_path'            => '',
			'github_throttle_requests'      => false,
			'aws_auth_method'               => 'aws-iam-key',
			'aws_region'                    => 'us-east-2',
			'aws_access_key'                => '',
			'aws_access_secret'             => '',
			'aws_bucket'                    => '',
			'aws_subdirectory'              => '',
			'aws_distribution_id'           => '',
			'aws_webhook_url'               => '',
			'aws_empty'                     => false,
			's3_access_key'                 => '',
			's3_base_url'                   => '',
			's3_access_secret'              => '',
			's3_bucket'                     => '',
			's3_subdirectory'               => '',
			'fix_cors'                      => 'allowed_http_origins',
			'static_url'                    => '',
			'use_forms'                     => false,
			'use_comments'                  => false,
			'comment_redirect'              => '',
			'use_search'                    => false,
			'search_type'                   => 'fuse',
			'search_index_title'            => 'title',
			'search_index_content'          => 'body',
			'search_index_excerpt'          => '.entry-content',
			'search_excludable'             => '',
			'search_metadata'               => '',
			'fuse_selector'                 => '.search-field',
			'fuse_threshold'                => 0.1,
			'algolia_app_id'                => '',
			'algolia_admin_api_key'         => '',
			'algolia_search_api_key'        => '',
			'algolia_index'                 => 'simply_static',
			'algolia_selector'              => '.search-field',
			'use_minify'                    => false,
			'minify_html'                   => false,
			'minify_css'                    => false,
			'minify_inline_css'             => false,
			'minify_js'                     => false,
			'minify_inline_js'              => false,
   'generate_404'                  => false,
            'custom_404_page'              => 0,
			'add_feeds'                     => false,
			'add_rest_api'                  => false,
			'smart_crawl'                   => true,
			'wp_content_folder'             => '',
			'wp_includes_folder'            => '',
			'wp_uploads_folder'             => '',
			'wp_plugins_folder'             => '',
			'wp_themes_folder'              => '',
			'theme_style_name'              => 'style',
			'author_url'                    => '',
			'hide_comments'                 => false,
			'hide_version'                  => false,
			'hide_generator'                => false,
			'hide_prefetch'                 => false,
			'hide_rsd'                      => false,
			'hide_emotes'                   => false,
			'disable_xmlrpc'                => false,
			'disable_embed'                 => false,
			'disable_db_debug'              => false,
			'disable_wlw_manifest'          => false,
			'sftp_host'                     => '',
			'sftp_user'                     => '',
			'sftp_pass'                     => '',
			'sftp_folder'                   => '',
			'sftp_port'                     => 22,
			'archive_status_messages'       => array(),
			'pages_status'                  => array(),
			'archive_name'                  => null,
			'archive_start_time'            => null,
			'archive_end_time'              => null,
			'version'                       => SIMPLY_STATIC_VERSION,
		);

		// Update settings with default options.
		update_option( 'simply-static', $default_options );

		return json_encode( [ 'status' => 200, 'message' => "Ok", 'data' => $default_options ] );
	}


	/**
	 * Reset database via rest API.
	 *
	 * @return false|string
	 */
	public function reset_database() {
		// Drop Simply Static database table.
		global $wpdb;
		$table_name = $wpdb->prefix . 'simply_static_pages';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		// Check table.
		Page::create_or_update_table();

		return json_encode( [ 'status' => 200, 'message' => "Ok" ] );
	}

	/**
	 * Reset the background queue (delete all batches, status, locks and clear cron).
	 * Useful when the export is stuck with message: "There is already an export running".
	 *
	 * @return false|string
	 */
	public function reset_background_queue() {
		try {
			/** @var Archive_Creation_Job $job */
			$job = Plugin::instance()->get_archive_creation_job();

			// Delete all batches and status for this job.
			$job->delete_all();

			// Clear any scheduled cron for this job using known hook name.
			$identifier = 'wp_' . 'archive_creation_job'; // Background_Process identifier is prefix + action
			$cron_hook  = $identifier . '_cron';
			while ( $timestamp = wp_next_scheduled( $cron_hook ) ) {
				wp_unschedule_event( $timestamp, $cron_hook );
			}
			wp_clear_scheduled_hook( $cron_hook );

			// Remove process lock transient so a new run can start immediately.
			$site_id = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : null;
			$lock_key = $identifier . '_process_lock';
			if ( is_multisite() && ! is_null( $site_id ) ) {
				$lock_key .= '_site_' . $site_id;
			}
			delete_site_transient( $lock_key );

			return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
		} catch ( \Throwable $e ) {
			return json_encode( [ 'status' => 500, 'message' => $e->getMessage() ] );
		}
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
	 * Get Export Type
	 *
	 * @return false|string
	 */
	public function get_export_type() {
		// Check the export type.
		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );

		$options = Options::reinstance();

		$export_type    = 'Export';
		$export_type_id = null;

		if ( ! empty( $use_single ) ) {
			$export_type    = 'Single';
			$export_type_id = $use_single;
		} else if ( ! empty( $use_build ) ) {
			$export_type    = 'Build';
			$export_type_id = $use_build;
		} else if ( $options->get( 'generate_type' ) === 'update' ) {
			$export_type = 'Update';
		}

		return json_encode( [
			'status' => 200,
			'data'   => [
				'export_type'    => $export_type,
				'export_type_id' => $export_type_id,
			],
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

        // Check if an export is already running
        $archive_creation_job = Plugin::instance()->get_archive_creation_job();
        do_action( 'ss_before_perform_archive_running_check', $blog_id, $archive_creation_job );
        if ( $archive_creation_job->is_running() ) {
            Util::debug_log( "Export already running. Blocking new export request." );
            Util::debug_log( "Current task: " . $archive_creation_job->get_current_task() );
            Util::debug_log( "Is job done: " . ($archive_creation_job->is_job_done() ? 'true' : 'false') );

            // Return a 409 Conflict status code with an error message
            return json_encode( [
                    'status'  => 409, // Conflict status code
                    'message' => __( 'An export is already running. Please wait for it to complete or cancel it before starting a new one.', 'simply-static' )
            ] );
        }

		try {
			do_action( 'ss_before_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );

			$type = apply_filters( 'ss_export_type', $type );

			// Only trigger the after action if the export was successfully started
			if (Plugin::instance()->run_static_export( $blog_id, $type )) {
				do_action( 'ss_after_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );
			}

			return json_encode( [
				'status' => 200,
			] );

		} catch ( \Exception $e ) {

			return json_encode( [
				'status'  => 500,
				'message' => $e->getMessage()
			] );

		}
	}

	/**
	 * Cancel Export
	 *
	 * @return false|string
	 */
	public function cancel_export( $request ) {
		Util::debug_log( "Received request to cancel static archive generation" );
		$params  = $request->get_params();
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
		$stats = [
			'status'  => 200,
			'running' => Plugin::instance()->get_archive_creation_job()->is_running(),
			'paused'  => Plugin::instance()->get_archive_creation_job()->is_paused()
		];

		$stats = apply_filters( 'ss_is_running_statuses', $stats );

		return json_encode( $stats );
	}

	/**
	 * Pause Export
	 *
	 * @return false|string
	 */
	public function pause_export( $request ) {
		Util::debug_log( "Received request to pause static archive generation" );
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

		do_action( 'ss_before_perform_archive_action', $blog_id, 'pause', Plugin::instance()->get_archive_creation_job() );

		Plugin::instance()->pause_static_export();

		do_action( 'ss_after_perform_archive_action', $blog_id, 'pause', Plugin::instance()->get_archive_creation_job() );

		return json_encode( [ 'status' => 200 ] );
	}

	/**
	 * Resume Export
	 *
	 * @return false|string
	 */
	public function resume_export( $request ) {
		Util::debug_log( "Received request to resume static archive generation" );
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

		do_action( 'ss_before_perform_archive_action', $blog_id, 'resume', Plugin::instance()->get_archive_creation_job() );

		Plugin::instance()->resume_static_export();

		do_action( 'ss_after_perform_archive_action', $blog_id, 'resume', Plugin::instance()->get_archive_creation_job() );

		return json_encode( [ 'status' => 200 ] );
	}

	/**
	 * Trigger CRON for specific site
	 *
	 * @return false|string
	 */
	public function trigger_cron( $request ) {
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? (int) $params['blog_id'] : 0;

		if ( ! is_multisite() ) {
			return json_encode( [
				'status'  => 400,
				'message' => __( 'This endpoint is only available for multisite installations.', 'simply-static' )
			] );
		}

		if ( empty( $blog_id ) || ! get_blog_details( $blog_id ) ) {
			return json_encode( [
				'status'  => 400,
				'message' => __( 'Invalid blog ID provided.', 'simply-static' )
			] );
		}

		try {
			// Switch to the specified blog
			switch_to_blog( $blog_id );

            do_action( 'wp_archive_creation_job' );

			// Restore the previous blog
			restore_current_blog();

			return json_encode( [
				'status'  => 200,
				'message' => sprintf( __( 'CRON triggered successfully for site %d.', 'simply-static' ), $blog_id )
			] );

		} catch ( \Exception $e ) {
			// Make sure to restore blog context even on error
			restore_current_blog();

			return json_encode( [
				'status'  => 500,
				'message' => $e->getMessage()
			] );
		}
	}

	/**
	 * Get crawlers for JS
	 *
	 * @return false|string
	 */
	public function get_crawlers() {
		// Load the Crawlers class
		require_once SIMPLY_STATIC_PATH . 'src/crawler/class-crawlers.php';

		// Get the crawler manager
		$crawlers = \Simply_Static\Crawlers::instance();

		// Get all crawlers for JS
		$crawlers_for_js = $crawlers->get_crawlers_for_js();

		// Post-process: ensure Pro multilingual crawler shows can_run=false unless a supported plugin is active
		try {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$has_multilingual = (
				is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ||
				is_plugin_active( 'polylang/polylang.php' ) ||
				is_plugin_active( 'polylang-pro/polylang.php' ) ||
				is_plugin_active( 'translatepress-multilingual/index.php' )
			);
			foreach ( $crawlers_for_js as &$crawler_js ) {
				if ( isset( $crawler_js['id'] ) && 'multilingual' === $crawler_js['id'] ) {
					$crawler_js['can_run'] = (bool) $has_multilingual;
				}
			}
			unset( $crawler_js );
		} catch ( \Throwable $e ) {
			\Simply_Static\Util::debug_log( 'Post-process crawlers failed: ' . $e->getMessage() );
		}

		return json_encode( [
			'status' => 200,
			'data'   => $crawlers_for_js,
		] );
	}

    public function get_sites() {
        $site_ids = get_sites([
            "spam"                   => 0,
            "deleted"                => 0,
            "archived"               => 0,
            "network_id"             => get_current_network_id(),
            "number"                 => 999,
            "offset"                 => 0,
            "fields"                 => "ids",
            "order"                  => "DESC",
            "orderby"                => "id",
            "update_site_meta_cache" => false
        ]);

        /** @var Archive_Creation_Job $job */
        $job = Plugin::instance()->get_archive_creation_job();

        $sites = [];
        foreach ($site_ids as $site_id) {
            $site = get_blog_details( $site_id );

            switch_to_blog( $site_id );

            $options = Options::reinstance();
            $job->set_options( $options );
            $running = $job->is_running();
            $paused = $job->is_paused();

            $sites[] = [
                'id'               => $site->blog_id,
                'name'             => $site->blogname,
                'url'              => $site->siteurl,
                'path'             => $site->path,
                'running'          => $running,
                'paused'           => $paused,
                'status'           => $running ? __( 'Running', 'simply-static' ) : ( $paused ? __( 'Paused', 'simply-static' ) : __( 'Idle', 'simply-static' ) ),
                'settings_url'     => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-settings' ),
                'activity_log_url' => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-generate' )
            ];

            restore_current_blog();

        }

        $sites = apply_filters( 'ss_rest_multisite_get_sites', $sites );

        return wp_send_json_success( $sites );
    }

	/**
	 * Get post types for JS
	 *
	 * @return false|string
	 */
	public function get_post_types() {
		// Get all public post types
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		// Exclude attachment post type
		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		// Exclude Elementor's element_library post type
		if ( isset( $post_types['elementor_library'] ) ) {
			unset( $post_types['elementor_library'] );
		}

		// Exclude ssp-form post type
		if ( isset( $post_types['ssp-form'] ) ) {
			unset( $post_types['ssp-form'] );
		}

		// Format post types for JS
		$post_types_for_js = [];
		foreach ( $post_types as $post_type ) {
			$post_types_for_js[] = [
				'name'  => $post_type->name,
				'label' => $post_type->label,
			];
		}

		return json_encode( [
			'status' => 200,
			'data'   => $post_types_for_js,
		] );
	}

	/**
	 * Filter the default "View Site" admin bar link to point to the static site.
	 * This is registered here so it remains active even if the Admin Bar integration is disabled.
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 * @return void
	 */
	public function filter_view_site_link( $admin_bar ) {
		// Allow disabling this behavior via filter.
		if ( ! apply_filters( 'ss_enable_view_static_site_link', true ) ) {
			return;
		}

		// Only proceed if admin bar is visible.
		if ( ! function_exists( 'is_admin_bar_showing' ) || ! is_admin_bar_showing() ) {
			return;
		}

		// Ensure we have the default node to modify.
		$node = $admin_bar->get_node( 'view-site' );
		if ( ! $node ) {
			return;
		}

		$target_url = Util::get_static_site_url();
		if ( $target_url === '' ) {
			return; // Nothing to change or not configured.
		}

		// Update node title and href.
		$node->title = __( 'View Static Site', 'simply-static' );
		$node->href  = esc_url( $target_url );
		// Open in a new tab for convenience and safety.
		if ( ! isset( $node->meta ) || ! is_array( $node->meta ) ) {
			$node->meta = [];
		}
		$node->meta['target'] = '_blank';
		$node->meta['rel']    = 'noopener noreferrer';

		$admin_bar->add_node( (array) $node );
	}
}
