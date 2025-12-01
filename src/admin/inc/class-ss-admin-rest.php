<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin REST controller: owns all wp-admin related REST API routes for Simply Static.
 *
 * This class only registers routes and forwards callbacks to the existing
 * implementations in Admin_Settings to keep behavior 100% backward compatible.
 *
 * Over time, callbacks can be migrated here, but the external REST API surface
 * (namespaces, routes, permissions and responses) remains unchanged.
 */
class Admin_Rest {

    /** @var Admin_Rest|null */
    protected static $instance = null;

    /**
     * Return singleton instance.
     *
     * @return Admin_Rest
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Hook REST API initialization.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
    }

    /**
     * Register all admin REST routes. Callbacks delegate to Admin_Settings for now.
     *
     * @return void
     */
    public function rest_api_init() {
        $settings = Admin_Settings::get_instance();

        // Multisite-only endpoints
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

        // Data providers
        register_rest_route( 'simplystatic/v1', '/post-types', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_post_types' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/taxonomies', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_taxonomies' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/active-plugins', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_active_plugins' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

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

        // Export type helper
        register_rest_route( 'simplystatic/v1', '/export-type', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_export_type' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        // Settings CRUD
        register_rest_route( 'simplystatic/v1', '/settings', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

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

        // 404-only export
        register_rest_route( 'simplystatic/v1', '/export-404', array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'maybe_export_404' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
            },
        ) );

        // Maintenance/Utilities
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
            'callback'            => [ $this, 'migrate' ],
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
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/system-status/passed', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'check_system_status_passed' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/delete-log', array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'delete_log' ],
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

        register_rest_route( 'simplystatic/v1', '/clear-temp-files', array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'clear_temp_files' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'publish_pages', 'generate' ) );
            },
        ) );
    }

    // ---- Endpoint implementations (migrated from Admin_Settings) ----

    /** Multisite: list sites with export status */
    public function get_sites() {
        $get_sites_args = [
            'spam'                   => 0,
            'deleted'                => 0,
            'archived'               => 0,
            'network_id'             => get_current_network_id(),
            'number'                 => 999,
            'offset'                 => 0,
            'fields'                 => 'ids',
            'order'                  => 'DESC',
            'orderby'                => 'id',
            'update_site_meta_cache' => false,
        ];
        // Allow filtering of REST get_sites() args for multisite endpoints
        $get_sites_args = apply_filters( 'ss_multisite_get_sites_args', $get_sites_args, 'rest_sites' );
        $site_ids       = get_sites( $get_sites_args );

        /** @var Archive_Creation_Job $job */
        $job = Plugin::instance()->get_archive_creation_job();

        $sites = [];
        foreach ( $site_ids as $site_id ) {
            $site = get_blog_details( $site_id );

            switch_to_blog( $site_id );

            $options = Options::reinstance();
            $job->set_options( $options );
            $running = $job->is_running();
            $paused  = $job->is_paused();

            $sites[] = [
                'id'               => $site->blog_id,
                'name'             => $site->blogname,
                'url'              => $site->siteurl,
                'path'             => $site->path,
                'running'          => $running,
                'paused'           => $paused,
                'status'           => $running ? __( 'Running', 'simply-static' ) : ( $paused ? __( 'Paused', 'simply-static' ) : __( 'Idle', 'simply-static' ) ),
                'settings_url'     => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-settings' ),
                'activity_log_url' => esc_url( get_admin_url( $site->blog_id ) . 'admin.php?page=simply-static-generate' ),
            ];

            restore_current_blog();
        }

        $sites = apply_filters( 'ss_rest_multisite_get_sites', $sites );

        return wp_send_json_success( $sites );
    }

    /** Multisite: trigger cron on a specific site */
    public function trigger_cron( $request ) {
        $params  = $request->get_params();
        $blog_id = ! empty( $params['blog_id'] ) ? (int) $params['blog_id'] : 0;

        if ( ! is_multisite() ) {
            return json_encode( [
                'status'  => 400,
                'message' => __( 'This endpoint is only available for multisite installations.', 'simply-static' ),
            ] );
        }

        if ( empty( $blog_id ) || ! get_blog_details( $blog_id ) ) {
            return json_encode( [
                'status'  => 400,
                'message' => __( 'Invalid blog ID provided.', 'simply-static' ),
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
                'message' => sprintf( __( 'CRON triggered successfully for site %d.', 'simply-static' ), $blog_id ),
            ] );
        } catch ( \Exception $e ) {
            // Make sure to restore blog context even on error
            restore_current_blog();

            return json_encode( [
                'status'  => 500,
                'message' => $e->getMessage(),
            ] );
        }
    }

    /** Multisite: can current network run export? */
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
            'can_run' => $can_run,
        ];

        return json_encode( $stats );
    }

    /** Data: public post types (filtered) */
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

    /** Data: public taxonomies */
    public function get_taxonomies() {
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        $list       = array();
        if ( is_array( $taxonomies ) ) {
            foreach ( $taxonomies as $slug => $tax ) {
                $label  = isset( $tax->labels->name ) ? $tax->labels->name : $slug;
                $list[] = array( 'label' => $label, 'value' => $slug );
            }
        }

        return $list;
    }

    /** Data: active plugins */
    public function get_active_plugins() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $active = (array) Util::get_all_active_plugins();
        $all    = (array) get_plugins();
        $list   = [];
        foreach ( $active as $plugin_file ) {
            $dir    = dirname( $plugin_file );
            $label  = isset( $all[ $plugin_file ]['Name'] ) ? $all[ $plugin_file ]['Name'] : $dir;
            $list[] = [ 'slug' => $dir, 'label' => $label ];
        }

        return json_encode( [ 'status' => 200, 'data' => $list ] );
    }

    /** Data: active theme (and parent) */
    public function get_active_themes() {
        $themes      = [];
        $child_slug  = get_stylesheet();
        $child       = wp_get_theme( $child_slug );
        $themes[]    = [ 'slug' => $child_slug, 'label' => $child->get( 'Name' ) ];
        $parent_slug = get_template();
        if ( $parent_slug && $parent_slug !== $child_slug ) {
            $parent   = wp_get_theme( $parent_slug );
            $themes[] = [ 'slug' => $parent_slug, 'label' => $parent->get( 'Name' ) ];
        }

        return json_encode( [ 'status' => 200, 'data' => $themes ] );
    }

    /** Data: crawlers for JS */
    public function get_crawlers() {
        // Load the Crawlers class
        require_once SIMPLY_STATIC_PATH . 'src/crawler/class-ss-crawlers.php';

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

    /** Get Export Type */
    public function get_export_type() {
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
     * Get settings via REST API.
     * Migrated from Admin_Settings to colocate REST logic in Admin_Rest. Response shape unchanged.
     *
     * @return array Settings array
     */
    public function get_settings() {
        $settings = get_option( 'simply-static' );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        // Ensure integrations list contains only enabled integrations by default.
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

        // Provide on-the-fly defaults for new Single Export settings if missing.
        if ( ! array_key_exists( 'ss_use_single_exports', $settings ) ) {
            $settings['ss_use_single_exports'] = true;
        }

        // Builds toggle default false
        if ( ! array_key_exists( 'ss_use_builds', $settings ) ) {
            $settings['ss_use_builds'] = false;
        }

        // Auto-enable Builds in returned settings if build terms already exist (no persistence here)
        if ( empty( $settings['ss_use_builds'] ) ) {
            $has_build_terms = get_transient( 'simply_static_has_build_terms' );
            if ( false === $has_build_terms ) {
                global $wpdb;
                $has_build_terms = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", 'ssp-build' ) );
                set_transient( 'simply_static_has_build_terms', $has_build_terms, 5 * MINUTE_IN_SECONDS );
            }
            if ( $has_build_terms > 0 ) {
                $settings['ss_use_builds'] = true;
            }
        }

        // Prefill Single pages with homepage and posts page if not set
        if ( ! isset( $settings['ss_single_pages'] ) || ! is_array( $settings['ss_single_pages'] ) ) {
            $prefill  = [];
            $front_id = get_option( 'page_on_front' );
            if ( $front_id ) {
                $prefill[] = (int) $front_id;
            }
            $blog_id = get_option( 'page_for_posts' );
            if ( $blog_id ) {
                $prefill[] = (int) $blog_id;
            }
            $settings['ss_single_pages'] = $prefill;
        }

        // Defaults for related URL toggles
        if ( ! array_key_exists( 'ss_single_include_categories', $settings ) ) {
            $settings['ss_single_include_categories'] = true;
        }
        if ( ! array_key_exists( 'ss_single_include_tags', $settings ) ) {
            $settings['ss_single_include_tags'] = true;
        }
        if ( ! array_key_exists( 'ss_single_include_archives', $settings ) ) {
            $settings['ss_single_include_archives'] = true;
        }
        if ( ! array_key_exists( 'ss_single_include_pagination', $settings ) ) {
            $settings['ss_single_include_pagination'] = true;
        }

        // Default for XML sitemap update during Single Export
        if ( ! array_key_exists( 'ss_single_export_add_xml_sitemap', $settings ) ) {
            $settings['ss_single_export_add_xml_sitemap'] = false;
        }

        // Single Export auto-export toggles and defaults
        if ( ! array_key_exists( 'ss_single_auto_export', $settings ) ) {
            $settings['ss_single_auto_export'] = false;
        }
        if ( ! array_key_exists( 'ss_single_auto_export_delay', $settings ) ) {
            $settings['ss_single_auto_export_delay'] = 3; // seconds
        }
        if ( ! array_key_exists( 'ss_single_export_webhook_url', $settings ) ) {
            $settings['ss_single_export_webhook_url'] = '';
        }

        // Unified webhook settings (URL + enabled types) with legacy support
        if ( ! array_key_exists( 'ss_webhook_url', $settings ) || empty( $settings['ss_webhook_url'] ) ) {
            $settings['ss_webhook_url'] = ! empty( $settings['ss_single_export_webhook_url'] ) ? $settings['ss_single_export_webhook_url'] : '';
        }
        if ( ! array_key_exists( 'ss_webhook_enabled_types', $settings ) || ! is_array( $settings['ss_webhook_enabled_types'] ) ) {
            $settings['ss_webhook_enabled_types'] = ! empty( $settings['ss_single_export_webhook_url'] ) ? array( 'single' ) : array( 'export', 'update', 'build', 'single' );
        }

        // Taxonomy archives selection defaults derived from legacy booleans
        if ( ! isset( $settings['ss_single_taxonomy_archives'] ) || ! is_array( $settings['ss_single_taxonomy_archives'] ) ) {
            $tax_archives = array();
            if ( ! isset( $settings['ss_single_include_categories'] ) || (bool) $settings['ss_single_include_categories'] ) {
                $tax_archives[] = 'category';
            }
            if ( ! isset( $settings['ss_single_include_tags'] ) || (bool) $settings['ss_single_include_tags'] ) {
                $tax_archives[] = 'post_tag';
            }
            $settings['ss_single_taxonomy_archives'] = array_values( array_unique( $tax_archives ) );
        }

        // Ensure admin-only plugins are never suggested for inclusion in Enhanced Crawl.
        if ( isset( $settings['plugins_to_include'] ) && is_array( $settings['plugins_to_include'] ) ) {
            $admin_only = Util::get_admin_only_plugins();
            if ( ! empty( $admin_only ) ) {
                $current       = array_map( 'strval', $settings['plugins_to_include'] );
                $current_lc    = array_map( 'strtolower', $current );
                $admin_only_lc = array_map( 'strtolower', $admin_only );
                $filtered_lc   = array_values( array_diff( $current_lc, $admin_only_lc ) );
                $rebuild       = array();
                foreach ( $filtered_lc as $slug_lc ) {
                    foreach ( $current as $orig ) {
                        if ( strtolower( $orig ) === $slug_lc ) {
                            $rebuild[] = $orig;
                            break;
                        }
                    }
                }
                $settings['plugins_to_include'] = $rebuild;
            }
        }

        return $settings;
    }

    /**
     * Return settings sanitized for export: remove site-specific/sensitive options.
     * Uses filterable list from get_export_excluded_options().
     *
     * @return false|string JSON-encoded export settings
     */
    public function get_settings_export() {
        $settings = get_option( 'simply-static' );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $excluded = $this->get_export_excluded_options();
        if ( is_array( $excluded ) ) {
            foreach ( $excluded as $key ) {
                if ( array_key_exists( $key, $settings ) ) {
                    unset( $settings[ $key ] );
                }
            }
        }

        return json_encode( $settings );
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
        $list = apply_filters( 'ss_export_excluded_options', $defaults );
        if ( ! is_array( $list ) ) {
            return $defaults;
        }
        $list = array_map( 'sanitize_key', array_filter( array_map( 'strval', $list ) ) );
        return array_values( array_unique( $list ) );
    }
    /**
     * Save settings via REST API.
     *
     * Migrated from Admin_Settings to colocate REST logic in Admin_Rest. Response shape unchanged.
     *
     * @param object $request
     * @return false|string JSON-encoded response
     */
    public function save_settings( $request ) {
        if ( ! $request || ! method_exists( $request, 'get_params' ) ) {
            return json_encode( [ 'status' => 400, 'message' => 'No options updated.' ] );
        }

        $params = $request->get_params();
        if ( ! $params ) {
            return json_encode( [ 'status' => 400, 'message' => 'No options updated.' ] );
        }

        // Sanitize full options array using WP core sanitize_option for baseline cleaning
        $options = sanitize_option( 'simply-static', $params );

        // Field groups by type
        $multiline_fields = [
            'additional_urls',
            'additional_files',
            'urls_to_exclude',
            'search_excludable',
            'iframe_urls',
            'iframe_custom_css',
            'whitelist_plugins',
            'minify_css_exclude',
            'minify_js_exclude',
        ];

        $array_fields = [
            'integrations',
            'crawlers',
            'post_types',
            'plugins_to_include',
            'themes_to_include',
            'ss_single_pages',
            'ss_single_taxonomy_archives',
            'ss_webhook_enabled_types',
            'ss_single_auto_export_types',
        ];

        // Explicit booleans that must be normalized
        $boolean_fields = [
            'ss_use_single_exports',
            'ss_use_builds',
            'ss_single_include_categories',
            'ss_single_include_tags',
            'ss_single_include_archives',
            'ss_single_include_pagination',
            'ss_single_export_add_xml_sitemap',
            'ss_single_auto_export',
        ];

        foreach ( $options as $key => $value ) {
            if ( in_array( $key, $multiline_fields, true ) ) {
                $options[ $key ] = sanitize_textarea_field( $value );
            } elseif ( in_array( $key, $array_fields, true ) ) {
                if ( is_array( $value ) ) {
                    $options[ $key ] = array_map( 'sanitize_text_field', $value );
                } else {
                    $options[ $key ] = [];
                }
            } elseif ( 'ss_uam_access' === $key ) {
                // Expect assoc array: page_slug => role
                $sanitized = [];
                if ( is_array( $value ) ) {
                    foreach ( $value as $page => $role ) {
                        $sanitized[ sanitize_key( $page ) ] = sanitize_text_field( $role );
                    }
                }
                $options[ $key ] = $sanitized;
            } elseif ( in_array( $key, $boolean_fields, true ) ) {
                $options[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
            } elseif ( 'ss_single_auto_export_delay' === $key ) {
                $options[ $key ] = max( 0, absint( $value ) );
            } elseif ( 'ss_single_export_webhook_url' === $key ) {
                $san = esc_url_raw( $value );
                if ( empty( $san ) || ! in_array( wp_parse_url( $san, PHP_URL_SCHEME ), [ 'http', 'https' ], true ) ) {
                    $san = '';
                }
                $options[ $key ] = $san;
            } elseif ( 'ss_webhook_url' === $key ) {
                $san = esc_url_raw( $value );
                if ( empty( $san ) || ! in_array( wp_parse_url( $san, PHP_URL_SCHEME ), [ 'http', 'https' ], true ) ) {
                    $san = '';
                }
                $options[ $key ] = $san;
            } else {
                // Exclude Basic Auth fields from sanitize, but clear server vars if emptied
                if ( 'http_basic_auth_username' === $key || 'http_basic_auth_password' === $key ) {
                    if ( 'http_basic_auth_username' === $key && empty( $value ) && isset( $_SERVER['PHP_AUTH_USER'] ) ) {
                        unset( $_SERVER['PHP_AUTH_USER'] );
                    }
                    if ( 'http_basic_auth_password' === $key && empty( $value ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
                        unset( $_SERVER['PHP_AUTH_PW'] );
                    }
                    continue;
                }
                $options[ $key ] = sanitize_text_field( $value );
            }
        }

        // Scrub admin-only plugins from Enhanced Crawl selection before persisting.
        if ( isset( $options['plugins_to_include'] ) && is_array( $options['plugins_to_include'] ) ) {
            $admin_only = Util::get_admin_only_plugins();
            if ( ! empty( $admin_only ) ) {
                $current       = array_map( 'strval', $options['plugins_to_include'] );
                $current_lc    = array_map( 'strtolower', $current );
                $admin_only_lc = array_map( 'strtolower', $admin_only );
                $filtered_lc   = array_values( array_diff( $current_lc, $admin_only_lc ) );
                $rebuild       = array();
                foreach ( $filtered_lc as $slug_lc ) {
                    foreach ( $current as $orig ) {
                        if ( strtolower( $orig ) === $slug_lc ) {
                            $rebuild[] = $orig;
                            break;
                        }
                    }
                }
                $options['plugins_to_include'] = $rebuild;
            }
        }

        // Multisite: also persist per-site copy under site option when not main site
        if ( is_multisite() ) {
            $blog_id = get_current_blog_id();
            if ( $blog_id > 1 ) {
                update_site_option( 'simply-static-' . $blog_id, $options );
            }
        }

        // Mark UAM user-saved flag to avoid runtime default corrections later
        if ( isset( $options['ss_uam_access'] ) && is_array( $options['ss_uam_access'] ) ) {
            $options['ss_uam_access_user_saved'] = true;
        }

        // Back-compat: copy legacy single webhook into unified webhook field if needed
        if ( ( empty( $options['ss_webhook_url'] ) || ! isset( $options['ss_webhook_url'] ) ) && ! empty( $options['ss_single_export_webhook_url'] ) ) {
            $options['ss_webhook_url'] = $options['ss_single_export_webhook_url'];
            if ( empty( $options['ss_webhook_enabled_types'] ) || ! is_array( $options['ss_webhook_enabled_types'] ) ) {
                $options['ss_webhook_enabled_types'] = array( 'single' );
            }
        }

        // Validate webhook enabled types
        if ( isset( $options['ss_webhook_enabled_types'] ) && is_array( $options['ss_webhook_enabled_types'] ) ) {
            $allowed                             = array( 'export', 'update', 'build', 'single' );
            $options['ss_webhook_enabled_types'] = array_values( array_intersect( $allowed, $options['ss_webhook_enabled_types'] ) );
        }

        // Persist options
        update_option( 'simply-static', $options );

        return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
    }

    // Admin-only plugin list centralized in Util::get_admin_only_plugins().

    /**
     * Reset settings to default values via REST API.
     * Mirrors previous implementation from Admin_Settings for BC.
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
            // (the full list continues in Admin_Settings; retain same defaults here)
        );

        // Merge with any defaults that Admin_Settings might add in the future by instantiating Upgrade_Handler if needed
        // but keep existing reset behavior intact.

        // Add missing required keys used by the UI so that reset produces a working config
        $default_options = array_merge( $default_options, array(
            'generate_type'                 => 'export',
            'destination_url'               => '',
            'destination_url_type'          => 'relative',
            'archive_start_time'            => null,
            'archive_end_time'              => null,
            'version'                       => SIMPLY_STATIC_VERSION,
        ) );

        // Update settings with default options.
        update_option( 'simply-static', $default_options );

        return json_encode( [ 'status' => 200, 'message' => 'Ok', 'data' => $default_options ] );
    }

    public function maybe_export_404( $request ) { return Admin_Settings::get_instance()->maybe_export_404( $request ); }

    /** Reset database (drop and recreate SS pages table) */
    public function reset_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simply_static_pages';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
        Page::create_or_update_table();
        return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
    }

    /** Reset background queue: delete batches, clear cron, and remove locks */
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
            $site_id  = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : null;
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

    /** Import settings from another site in network */
    public function update_from_network( $request ) {
        $params = $request->get_params();
        if ( $request->get_params() ) {
            $blog_id = intval( $params['blog_id'] );
            // Get Settings from selected subsite.
            $options = get_site_option( 'simply-static-' . $blog_id );
            if ( ! $options ) {
                return json_encode( [
                    'status'  => 400,
                    'message' => 'Please save the settings on the selected subsite before importing them into a new site.'
                ] );
            }
            update_option( 'simply-static', $options );
            return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
        }
        return json_encode( [ 'status' => 400, 'message' => 'No options updated.' ] );
    }

    public function get_pages() { return Admin_Settings::get_instance()->get_pages(); }
    public function get_pages_slugs() { return Admin_Settings::get_instance()->get_pages_slugs(); }
    public function migrate( $request ) { return Admin_Settings::get_instance()->migrate( $request ); }

    /** Reset diagnostics (clear transient) */
    public function reset_diagnostics() {
        delete_transient( 'simply_static_checks' );
        delete_transient( 'simply_static_failed_tests' );
        return json_encode( [ 'status' => 200 ] );
    }

    /** Get system status checks */
    public function get_system_status() {
        $checks = get_transient( 'simply_static_checks' );
        if ( ! $checks ) {
            $diagnostics = new Diagnostic();
            $checks      = $diagnostics->get_checks();
        }
        return $checks;
    }

    /** Determine if all system checks passed */
    public function check_system_status_passed() {
        $passed = 'yes';
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

    /** Delete/clear the debug log */
    public function delete_log() {
        Util::clear_debug_log();
        return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
    }

    /** Activity log */
    public function get_activity_log( $request ) {
        $params       = $request->get_params();
        $activity_log = Plugin::instance()->get_activity_log( $params['blog_id'] );
        return json_encode( [
            'status'  => 200,
            'data'    => $activity_log,
            'running' => Plugin::instance()->get_archive_creation_job()->is_running(),
        ] );
    }

    /** Export log */
    public function get_export_log( $request ) {
        $params     = $request->get_params();
        $export_log = Plugin::instance()->get_export_log( $params['per_page'], $params['page'], $params['blog_id'] );
        return json_encode( [ 'status' => 200, 'data' => $export_log ] );
    }

    /**
     * Start Export
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
            Util::debug_log( "Is job done: " . ( $archive_creation_job->is_job_done() ? 'true' : 'false' ) );

            return json_encode( [
                'status'  => 409,
                'message' => __( 'An export is already running. Please wait for it to complete or cancel it before starting a new one.', 'simply-static' )
            ] );
        }

        try {
            do_action( 'ss_before_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );

            $type = apply_filters( 'ss_export_type', $type );

            if ( Plugin::instance()->run_static_export( $blog_id, $type ) ) {
                do_action( 'ss_after_perform_archive_action', $blog_id, 'start', Plugin::instance()->get_archive_creation_job() );
            }

            return json_encode( [ 'status' => 200 ] );
        } catch ( \Exception $e ) {
            return json_encode( [ 'status' => 500, 'message' => $e->getMessage() ] );
        }
    }

    /** Cancel Export */
    public function cancel_export( $request ) {
        Util::debug_log( "Received request to cancel static archive generation" );
        $params  = $request->get_params();
        $blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

        do_action( 'ss_before_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );
        Plugin::instance()->cancel_static_export();
        do_action( 'ss_after_perform_archive_action', $blog_id, 'cancel', Plugin::instance()->get_archive_creation_job() );

        return json_encode( [ 'status' => 200 ] );
    }

    /** Pause Export */
    public function pause_export( $request ) {
        Util::debug_log( "Received request to pause static archive generation" );
        $params  = $request->get_params();
        $blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

        do_action( 'ss_before_perform_archive_action', $blog_id, 'pause', Plugin::instance()->get_archive_creation_job() );
        Plugin::instance()->pause_static_export();
        do_action( 'ss_after_perform_archive_action', $blog_id, 'pause', Plugin::instance()->get_archive_creation_job() );

        return json_encode( [ 'status' => 200 ] );
    }

    /** Resume Export */
    public function resume_export( $request ) {
        Util::debug_log( "Received request to resume static archive generation" );
        $params  = $request->get_params();
        $blog_id = ! empty( $params['blog_id'] ) ? $params['blog_id'] : 0;

        do_action( 'ss_before_perform_archive_action', $blog_id, 'resume', Plugin::instance()->get_archive_creation_job() );
        Plugin::instance()->resume_static_export();
        do_action( 'ss_after_perform_archive_action', $blog_id, 'resume', Plugin::instance()->get_archive_creation_job() );

        return json_encode( [ 'status' => 200 ] );
    }

    /** Is running */
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
     * Clear temporary, generated static files via REST.
     *
     * Moved from Admin_Settings to Admin_Rest to colocate REST callbacks with the REST controller.
     *
     * @return false|string JSON-encoded response
     */
    public function clear_temp_files() {
        try {
            $setup_task = new Setup_Task();
            $result     = $setup_task->delete_temp_static_files();

            return json_encode( [
                'status'  => 200,
                'cleared' => (bool) $result,
            ] );
        } catch ( \Throwable $e ) {
            if ( class_exists( '\\Simply_Static\\Util' ) ) {
                Util::debug_log( 'Error clearing temporary files via REST: ' . $e->getMessage() );
            }

            return json_encode( [
                'status'  => 500,
                'message' => $e->getMessage(),
            ] );
        }
    }
}
