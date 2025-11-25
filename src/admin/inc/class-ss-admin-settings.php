<?php

namespace Simply_Static;

class Admin_Settings {

    /**
     * Trigger a 404-only export via REST.
     *
     * @return \WP_REST_Response|array|string
     */
    public function export_404() {
        // Ensure generate_404 option is enabled via UI gating; proceed regardless.
        update_option( 'simply-static-404-only', 1, false );

        // Clear conflicting flags that could alter the task list.
        delete_option( 'simply-static-use-single' );
        delete_option( 'simply-static-use-build' );

        try {
            Plugin::instance()->run_static_export();

            return [ 'success' => true ];
        } catch ( \Throwable $e ) {
            return [ 'success' => false, 'message' => $e->getMessage() ];
        }
    }

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
        // REST route registration moved to Admin_Rest. Keep method for BC, but do not hook here.
        // Prevent WP core from altering the admin URL with history.replaceState on Simply Static pages.
        // This avoids a SecurityError when Basic Auth credentials are present in the URL.
        add_action( 'admin_head', array( $this, 'maybe_disable_admin_canonical' ), 1 );

        // Ensure the "View Site" link points to the static site even if the admin bar integration is disabled.
        add_action( 'admin_bar_menu', array( $this, 'filter_view_site_link' ), 200 );

        // Handle cancel via URL param as a fallback when REST API is unavailable.
        add_action( 'admin_init', array( $this, 'maybe_handle_cancel_export' ) );

        // Multisite: Free shows only an upgrade notice; full lock management lives in Pro.
        if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
            // Guard against fatal if method is unavailable in older installs/caches.
            if ( method_exists( $this, 'render_network_lock_upgrade_notice' ) && is_callable( [
                            $this,
                            'render_network_lock_upgrade_notice'
                    ] ) ) {
                add_action( 'network_admin_notices', array( $this, 'render_network_lock_upgrade_notice' ) );
            }
        }

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
        $page      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
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

        // Determine if the UAM integration is enabled (supports Pro override)
        $uam_enabled = false;
        try {
            $uam_object = Plugin::instance()->get_integration( 'ss-uam' );
            if ( $uam_object && method_exists( $uam_object, 'is_enabled' ) ) {
                $uam_enabled = (bool) $uam_object->is_enabled();
            }
        } catch ( \Throwable $e ) {
            $uam_enabled = false;
        }

        // Compute default allowed pages (Free baseline). Pro/UAM may override via filter below.
        $allowed_pages_default = array(
                '/',
                '/diagnostics',
                '/general',
                '/deployment',
                '/forms',
                '/search',
                '/optimize',
                '/workflow',
                '/utilities',
                '/integrations',
                '/debug',
        );

        // Let integrations (e.g., UAM in Pro) refine the allowed pages list. They may also add '/uam'.
        $allowed_pages = apply_filters( 'ss_allowed_pages', $allowed_pages_default, $current_settings );

        $args = apply_filters(
                'ss_settings_args',
                array(
                        'screen'           => 'simplystatic-settings',
                        'version'          => SIMPLY_STATIC_VERSION,
                        'logo'             => SIMPLY_STATIC_URL . '/assets/simply-static-logo.svg',
                        'plan'             => 'free',
                        'initial'          => $initial,
                        'home'             => home_url(),
                        'home_path'        => get_home_path(),
                        'admin_email'      => get_bloginfo( 'admin_email' ),
                        'temp_files_dir'   => $temp_dir,
                        'blog_id'          => get_current_blog_id(),
                        'need_upgrade'     => 'no',
                        'builds'           => array(),
                        'hidden_settings'  => apply_filters( 'ss_hidden_settings', array() ),
                        'last_export_end'  => $options->get( 'archive_end_time' ),
                    // Build integrations as an associative array keyed by integration ID
                    // to make lookups reliable in the admin app (no numeric reindexing).
                        'integrations'     => ( function () {
                            $out   = array();
                            $items = Plugin::instance()->get_integrations(); // [ id => class ]
                            foreach ( $items as $id => $class ) {
                                $object = new $class();
                                $js     = $object->js_object();
                                // Ensure the id is present and matches the key
                                $js['id']   = isset( $js['id'] ) && $js['id'] ? $js['id'] : $id;
                                $out[ $id ] = $js;
                            }

                            return $out;
                        } )(),
                    // Add the current settings to the args
                        'current_settings' => $current_settings,
                        'allowed_pages'    => $allowed_pages,
                        'uam_enabled'      => $uam_enabled,
                )
        );

        if ( defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
            // Mark plan as Pro when the Pro plugin is active so the admin UI can enable Pro-only features/toggles.
            $args['plan']        = 'pro';
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
            // Allow filtering of get_sites() args, e.g., to set 'number' => 0 to list all sites.
            $public_sites_args = apply_filters( 'ss_multisite_get_sites_args', [ 'public' => true ], 'settings_sites' );
            $public_sites      = get_sites( $public_sites_args );

            if ( $public_sites ) {
                foreach ( $public_sites as $site ) {
                    $sites[] = [
                            'blog_id'          => $site->blog_id,
                            'name'             => wp_specialchars_decode( $site->blogname, ENT_QUOTES | ENT_HTML5 ),
                            'url'              => $site->siteurl,
                            'settings_url'     => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-settings' ),
                            'activity_log_url' => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-generate' )
                    ];

                    if ( $site->blog_id != get_current_blog_id() ) {
                        $selectable_sites[] = [
                                'blog_id' => $site->blog_id,
                                'name'    => wp_specialchars_decode( $site->blogname, ENT_QUOTES | ENT_HTML5 ),
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
     * Fallback: handle cancel export via URL param when REST API is unavailable.
     * Example: /wp-admin/admin.php?page=simply-static-generate&cancel-export=true
     */
    public function maybe_handle_cancel_export() {
        // Only run in admin and on our Generate page.
        if ( ! is_admin() ) {
            return;
        }
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'simply-static-generate' !== $page ) {
            return;
        }
        $cancel = isset( $_GET['cancel-export'] ) ? sanitize_text_field( wp_unslash( $_GET['cancel-export'] ) ) : '';
        if ( 'true' !== $cancel ) {
            return;
        }

        // Permission check mirrors access to the Generate page.
        if ( ! current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) ) ) {
            return;
        }

        // Trigger same actions as REST endpoint without relying on REST.
        $blog_id = 0;
        do_action( 'ss_before_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );
        Plugin::instance()->cancel_static_export();
        do_action( 'ss_after_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );

        // Redirect to remove the query parameter and avoid re-triggering on refresh.
        $redirect_url = remove_query_arg( 'cancel-export' );
        wp_safe_redirect( $redirect_url );
        exit;
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
                    'methods'             => 'GET',
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

        // Public taxonomies for UI token field
        register_rest_route( 'simplystatic/v1', '/taxonomies', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_taxonomies' ],
                'permission_callback' => function () {
                    return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
                },
        ) );

        // Active plugins for Enhanced Crawl UI
        register_rest_route( 'simplystatic/v1', '/active-plugins', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_active_plugins' ],
                'permission_callback' => function () {
                    return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
                },
        ) );

        // Active theme (and parent if child) for Enhanced Crawl UI
        register_rest_route( 'simplystatic/v1', '/active-themes', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_active_themes' ],
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

        // Read-only export of settings with sensitive/site-specific keys removed
        register_rest_route( 'simplystatic/v1', '/settings/export', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings_export' ],
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

        // Export 404-only run
        register_rest_route( 'simplystatic/v1', '/export-404', array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'export_404' ],
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

        // Clear temporary files directory.
        register_rest_route( 'simplystatic/v1', '/clear-temp-files', array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'clear_temp_files' ],
                'permission_callback' => function () {
                    return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
                },
        ) );

    }

    public function check_if_can_run_export() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->check_if_can_run_export();
    }

    /**
     * Get settings via Rest API.
     *
     * @deprecated 2.0.3 Moved to Admin_Rest::get_settings(). This is a thin wrapper for BC.
     * @return array
     */
    public function get_settings() {
        return Admin_Rest::get_instance()->get_settings();
    }

    /**
     * Return settings sanitized for export: remove site-specific/sensitive options.
     * Uses filterable list from get_export_excluded_options().
     *
     * @return false|string
     */
    public function get_settings_export() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_settings_export();
    }

    /**
     * Get System Status via Rest API.
     *
     * @return array[]
     */
    public function get_system_status() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_system_status();
    }

    /**
     * Clear transient for diagnostics.
     *
     * @return string
     */
    public function reset_diagnostics() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->reset_diagnostics();
    }

    /**
     * All diagnostics passed?
     *
     * @return false|string
     */
    public function check_system_status_passed() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->check_system_status_passed();
    }

    /**
     * Save settings via REST API.
     *
     * @deprecated 2.0.3 Moved to Admin_Rest::save_settings(). This is a thin wrapper for BC.
     * @param object $request The REST request.
     * @return false|string JSON-encoded response
     */
    public function save_settings( $request ) {
        return Admin_Rest::get_instance()->save_settings( $request );
    }

    /**
     * Return a list of admin-only plugin slugs (directory names) that should never be
     * included in the Enhanced Crawl "Plugins to Include" setting.
     *
     * These are typically development or admin utilities that don't add front-end assets
     * relevant to the static export. Keep the defaults conservative; site owners and
     * integrations can extend/override via the `ss_admin_only_plugins` filter.
     *
     * Example values: query-monitor, debug-bar, health-check, user-switching, wp-crontrol
     *
     * @return string[] Array of plugin directory slugs.
     */
    private function get_admin_only_plugins() {
        $defaults = array(
                'advanced-custom-fields',
                'secure-custom-fields',
                'query-monitor',
                'debug-bar',
                'health-check',
                'user-switching',
                'wp-crontrol',
                'theme-check',
                'regenerate-thumbnails',
                'wp-migrate-db',
                'wp-migrate-db-pro',
                'wp-staging',
                'wp-staging-pro',
                'rollback',
                'wp-rollback',
                'classic-editor',
                'artiss-transient-cleaner',
                'updraftplus',
                'user-switchting',
                'view-admin-as',
                'wp-beta-tester',
                'wp-downgrade',
                'wp-rest-cache',
                'wp-reset',
                'wpvidid-backuprestore',
                'duplicate-post'
        );

        /**
         * Filter the list of admin-only plugin slugs that should be excluded from Enhanced Crawl.
         *
         * @param string[] $defaults Directory slugs of admin-only plugins.
         */
        $list = apply_filters( 'ss_admin_only_plugins', $defaults );

        if ( ! is_array( $list ) ) {
            return $defaults;
        }
        // Sanitize values to simple slugs.
        $list = array_map( 'sanitize_title', array_filter( array_map( 'strval', $list ) ) );

        return array_values( array_unique( $list ) );
    }

    /**
     * Return a filterable list of option keys that should not be included in exported settings JSON.
     *
     * @return string[]
     */
    private function get_export_excluded_options() {
        $defaults = array(
                'temp_files_dir',
                'local_dir',
                'http_basic_auth_username',
                'http_basic_auth_password',
                'tiiny_email',
                'cdn_pull_zone',
                'cdn_storage_zone',
                'github_repository',
                'aws_bucket',
                's3_bucket',
                'algolia_index',
                'sftp_folder',
                'archive_status_messages',
                'pages_status',
                'archive_name',
                'archive_start_time',
                'archive_end_time',
                'http_basic_auth_on',
                'plugins_to_include',
                'themes_to_include',
                'ss_single_pages',
        );

        /**
         * Filter the list of option keys excluded from settings export.
         *
         * @param string[] $defaults Option keys to exclude from export.
         */
        $list = apply_filters( 'ss_export_excluded_options', $defaults );
        if ( ! is_array( $list ) ) {
            return $defaults;
        }
        $list = array_map( 'sanitize_key', array_filter( array_map( 'strval', $list ) ) );

        return array_values( array_unique( $list ) );
    }

    /**
     * Get public taxonomies for settings UI.
     *
     * @return array
     */
    public function get_taxonomies() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_taxonomies();
    }

    /**
     * Reset settings to default values via rest API.
     *
     * @param object $request given request.
     *
     * @return false|string
     */
    public function reset_settings( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->reset_settings( $request );
    }


    /**
     * Reset database via rest API.
     *
     * @return false|string
     */
    public function reset_database() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->reset_database();
    }

    /**
     * Reset the background queue (delete all batches, status, locks and clear cron).
     * Useful when the export is stuck with message: "There is already an export running".
     *
     * @return false|string
     */
    public function reset_background_queue() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->reset_background_queue();
    }

    /**
     * Save settings via rest API from another subsite in the network.
     *
     * @param object $request given request.
     *
     * @return false|string
     */
    public function update_from_network( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->update_from_network( $request );
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
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_activity_log( $request );
    }

    /**
     * Get Export Log
     *
     * @return false|string
     */
    public function get_export_log( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_export_log( $request );
    }

    /**
     * Get Export Type
     *
     * @return false|string
     */
    public function get_export_type() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_export_type();
    }

    /**
     * Start Export
     *
     * @return false|string
     */
    public function start_export( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->start_export( $request );
    }

    /**
     * Cancel Export
     *
     * @return false|string
     */
    public function cancel_export( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->cancel_export( $request );
    }

    /**
     * Clear temporary, generated static files via REST.
     *
     * @return false|string JSON-encoded response
     */
    public function clear_temp_files() {
        // Moved to Admin_Rest; keep as thin BC wrapper.
        return Admin_Rest::get_instance()->clear_temp_files();
    }

    /**
     * Is running
     *
     * @return false|string
     */
    public function is_running( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->is_running( $request );
    }

    /**
     * Pause Export
     *
     * @return false|string
     */
    public function pause_export( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->pause_export( $request );
    }

    /**
     * Resume Export
     *
     * @return false|string
     */
    public function resume_export( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->resume_export( $request );
    }

    /**
     * Trigger CRON for specific site
     *
     * @return false|string
     */
    public function trigger_cron( $request ) {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->trigger_cron( $request );
    }

    /**
     * Get crawlers for JS
     *
     * @return false|string
     */
    public function get_crawlers() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_crawlers();
    }

    public function get_sites() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_sites();
    }

    /**
     * Return list of active plugins (id = plugin directory, label = plugin name)
     *
     * @return false|string
     */
    public function get_active_plugins() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_active_plugins();
    }

    /**
     * Return list of active theme slugs (child and parent if applicable)
     *
     * @return false|string
     */
    public function get_active_themes() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_active_themes();
    }

    /**
     * Get post types for JS
     *
     * @return false|string
     */
    public function get_post_types() {
        // Deprecated: moved to Admin_Rest
        return Admin_Rest::get_instance()->get_post_types();
    }

    /**
     * Filter the default "View Site" admin bar link to point to the static site.
     * This is registered here so it remains active even if the Admin Bar integration is disabled.
     *
     * @param \WP_Admin_Bar $admin_bar
     *
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

    /**
     * Free (no Pro): Show an upgrade notice in Network Admin if a multisite export lock is detected.
     */
    public function render_network_lock_upgrade_notice() {
        if ( defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
            return; // Pro handles full UI.
        }
        if ( ! is_multisite() || ! function_exists( 'is_network_admin' ) || ! is_network_admin() ) {
            return;
        }
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'simply-static-settings' !== $page && 'simply-static-generate' !== $page ) {
            return;
        }
        $running = get_site_option( Plugin::SLUG . '_multisite_export_running', false );
        if ( empty( $running ) ) {
            return; // No lock set.
        }
        $upgrade_url = 'https://simplystatic.com/?utm_source=plugin&utm_medium=notice&utm_campaign=ms-lock-reset';
        ?>
        <div class="notice notice-info">
            <p>
                <?php echo esc_html__( 'A multisite export lock is currently set. Resetting the lock and using queued exports are available in Simply Static Pro.', 'simply-static' ); ?>
                <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank"
                   rel="noopener noreferrer"><?php echo esc_html__( 'Learn more about Pro', 'simply-static' ); ?></a>
            </p>
        </div>
        <?php
    }

}
