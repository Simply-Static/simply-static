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

	/** SHA-256 of the reviewed Static Studio migration package. */
	const STUDIO_MIGRATE_PACKAGE_SHA256 = '4e9e4e86a429603367e15f181f1631d8459fe0ba2a7c16b5b89add72da0f954f';

    /** @var Admin_Rest|null */
    protected static $instance = null;

	/** @var bool */
	protected $routes_registered = false;

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
		if ( $this->routes_registered ) {
			return;
		}
		$this->routes_registered = true;

        // Multisite-only endpoints
        if ( is_multisite() ) {
            register_rest_route( 'simplystatic/v1', '/sites', array(
                'methods'             => 'GET',
				'callback'            => [ $this, 'get_sites' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_network_options' );
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

            register_rest_route( 'simplystatic/v1', '/reset-export-lock', array(
                'methods'             => 'POST',
				'callback'            => [ $this, 'reset_export_lock' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_network_options' );
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

        // Unpushed changes count
        register_rest_route( 'simplystatic/v1', '/unpushed-changes', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_unpushed_changes' ],
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
				return current_user_can( 'manage_network_options' );
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
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'diagnostics' ) );
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
            'callback'            => [ $this, 'delete_log' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

		register_rest_route( 'simplystatic/v1', '/activity-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_activity_log' ],
			'permission_callback' => [ $this, 'can_view_activity_log_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/export-log', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_export_log' ],
			'permission_callback' => [ $this, 'can_view_activity_log_for_request' ],
		) );

        register_rest_route( 'simplystatic/v1', '/exports', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_deploy_manifests' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/exports/latest', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_latest_deploy_manifest' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/exports/(?P<export_id>[a-zA-Z0-9][a-zA-Z0-9_-]{7,79})', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_deploy_manifest' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/exports/(?P<export_id>[a-zA-Z0-9][a-zA-Z0-9_-]{7,79})/urls', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_deploy_manifest_urls' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/exports/(?P<export_id>[a-zA-Z0-9][a-zA-Z0-9_-]{7,79})/files', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_deploy_manifest_files' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/exports/(?P<export_id>[a-zA-Z0-9][a-zA-Z0-9_-]{7,79})/warnings', array(
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_deploy_manifest_warnings' ],
            'permission_callback' => function () {
                return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' ) );
            },
        ) );

		register_rest_route( 'simplystatic/v1', '/start-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'start_export' ],
			'permission_callback' => [ $this, 'can_generate_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/cancel-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'cancel_export' ],
			'permission_callback' => [ $this, 'can_generate_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/pause-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'pause_export' ],
			'permission_callback' => [ $this, 'can_generate_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/resume-export', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'resume_export' ],
			'permission_callback' => [ $this, 'can_generate_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/is-running', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'is_running' ],
			'permission_callback' => [ $this, 'can_generate_for_request' ],
		) );

		register_rest_route( 'simplystatic/v1', '/clear-temp-files', array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'clear_temp_files' ],
			'permission_callback' => function () {
				return current_user_can( apply_filters( 'ss_user_capability', 'manage_options', 'settings' ) );
            },
        ) );

        register_rest_route( 'simplystatic/v1', '/install-studio-migrate', array(
            'methods'             => 'POST',
            'callback'            => [ $this, 'install_studio_migrate' ],
            'permission_callback' => function () {
                return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
            },
        ) );
    }

    /**
     * Authorize an export action against the requested target site.
     *
     * @param \WP_REST_Request|null $request REST request.
     * @return bool
     */
    public function can_generate_for_request( $request = null ) {
        $capability = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );

        return $this->current_user_can_for_target_site( $request, $capability );
    }

    /**
     * Authorize activity/export-log access against the requested target site.
     *
     * @param \WP_REST_Request|null $request REST request.
     * @return bool
     */
    public function can_view_activity_log_for_request( $request = null ) {
        $capability = apply_filters( 'ss_user_capability', 'manage_options', 'activity-log' );

        return $this->current_user_can_for_target_site( $request, $capability );
    }

    /**
     * Check a capability on the current site, or require network authorization
     * before accessing a different site.
     *
     * @param \WP_REST_Request|null $request    REST request.
     * @param string                $capability Capability to check.
     * @return bool
     */
    private function current_user_can_for_target_site( $request, $capability ) {
        $blog_id = 0;
        if ( $request && method_exists( $request, 'get_param' ) ) {
            $blog_id = absint( $request->get_param( 'blog_id' ) );
        }

        if ( ! $blog_id || $blog_id === get_current_blog_id() ) {
            return current_user_can( $capability );
        }

        if ( ! is_multisite() || ! current_user_can( 'manage_network_options' ) ) {
            return false;
        }

        $site = get_blog_details( $blog_id );
        if ( ! $site || ! empty( $site->deleted ) || ! empty( $site->spam ) || ! empty( $site->archived ) ) {
            return false;
        }

        if ( function_exists( 'current_user_can_for_site' ) ) {
            return current_user_can_for_site( $blog_id, $capability );
        }

        if ( function_exists( 'current_user_can_for_blog' ) ) {
            return current_user_can_for_blog( $blog_id, $capability );
        }

        return false;
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
			if ( ! $site ) {
				continue;
			}

			$state = $this->run_in_blog_context(
				$site_id,
				function () use ( $job ) {
					return array(
						'running' => $job->is_running(),
						'paused'  => $job->is_paused(),
					);
				}
			);
			$running = $state['running'];
			$paused  = $state['paused'];

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
			$this->run_in_blog_context(
				$blog_id,
				function () {
					do_action( 'wp_archive_creation_job' );
				}
			);

            return json_encode( [
                'status'  => 200,
                'message' => sprintf( __( 'CRON triggered successfully for site %d.', 'simply-static' ), $blog_id ),
            ] );
        } catch ( \Throwable $e ) {
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

    /** Multisite: reset a stale export lock left by a crashed/interrupted export. */
    public function reset_export_lock() {
        delete_site_option( Plugin::SLUG . '_multisite_export_running' );

        return json_encode( [
            'status'  => 200,
            'message' => __( 'Export lock cleared.', 'simply-static' ),
        ] );
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
        $active   = (array) Util::get_all_active_plugins();
        $all      = (array) get_plugins();
        $required = Util::get_required_plugins();
        $req_lc   = array_map( 'strtolower', $required );
        $list     = [];
        foreach ( $active as $plugin_file ) {
            $dir    = dirname( $plugin_file );
            $label  = isset( $all[ $plugin_file ]['Name'] ) ? $all[ $plugin_file ]['Name'] : $dir;
            $list[] = [
                'slug'     => $dir,
                'label'    => $label,
                'required' => in_array( strtolower( sanitize_title( $dir ) ), $req_lc, true ),
            ];
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
     * Count unpushed changes since the last export.
     *
     * Considers:
     * 1. Posts/pages modified after the last export end time.
     * 2. Rows in the Pro delete-tracker table (if it exists).
     *
     * @return string JSON response with total count.
     */
    public function get_unpushed_changes() {
        global $wpdb;

        $options          = Options::reinstance();
        $last_export_end  = $options->get( 'archive_end_time' );
        $modified_count   = 0;
        $media_count      = 0;
        $deleted_count    = 0;

        // 1. Count posts modified since last export.
        if ( ! empty( $last_export_end ) ) {
            $post_types          = get_post_types( array( 'public' => true ), 'names' );
            unset( $post_types['attachment'] );
            $placeholders        = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
            $last_export_end_gmt = get_gmt_from_date( $last_export_end );

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ( ! empty( $post_types ) ) {
                $modified_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) AND ( post_date_gmt > %s OR post_modified_gmt > %s )",
                        array_merge( array_values( $post_types ), array( $last_export_end_gmt, $last_export_end_gmt ) )
                    )
                );
            }

            $media_count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'inherit' AND post_type = 'attachment' AND ( post_date_gmt > %s OR post_modified_gmt > %s )",
                    $last_export_end_gmt,
                    $last_export_end_gmt
                )
            );
        }

        // 2. Count rows in the delete tracker table (Pro feature).
        //    Only count rows added after the last export so that stale/structural
        //    entries (e.g. plugin_deactivate) already handled by an export are excluded.
        $delete_table = $wpdb->prefix . 'simply_static_delete_pages';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$delete_table}'" );
        if ( $table_exists === $delete_table ) {
            if ( ! empty( $last_export_end ) ) {
                // deleted_at is stored in GMT; convert local archive_end_time to GMT for comparison.
                $last_export_end_gmt = get_gmt_from_date( $last_export_end );
                $deleted_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$delete_table} WHERE site_id = %d AND deleted_at > %s",
                        get_current_blog_id(),
                        $last_export_end_gmt
                    )
                );
            } else {
                // No previous export — count all tracked deletions.
                $deleted_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$delete_table} WHERE site_id = %d",
                        get_current_blog_id()
                    )
                );
            }
        }

        $total = $modified_count + $media_count + $deleted_count;

        return json_encode( array(
            'status' => 200,
            'data'   => array(
                'total'          => $total,
                'modified_count' => $modified_count,
                'media_count'    => $media_count,
                'deleted_count'  => $deleted_count,
            ),
        ) );
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

        // Populate defaults only for legacy installs where the setting is
        // absent. Preserve an explicit empty list so users can disable all
        // optional integrations.
        if ( ! array_key_exists( 'integrations', $settings ) ) {
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

        // Ensure required plugins (e.g. Simply Static Pro) are always present.
        if ( isset( $settings['plugins_to_include'] ) && is_array( $settings['plugins_to_include'] ) ) {
            $settings['plugins_to_include'] = Util::ensure_required_plugins( $settings['plugins_to_include'] );
        }

        return apply_filters( 'ss_get_settings', $settings );
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

		// Defense in depth for credentials added by Pro or future integrations.
		$settings = Util::remove_sensitive_options( $settings );

		return wp_json_encode( $settings );
    }

    /**
     * Return a filterable list of option keys that should not be included in exported settings JSON.
     *
     * @return string[]
     */
    private function get_export_excluded_options() {
		$defaults = array(
			'origin_url',
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
			'archive_deploy_id',
			'archive_task_list',
			'generate_type',
			'deploy_manifest_schema_version',
			'zip_batch_offset',
			'zip_total_files',
			'zip_files',
			'version',
            'http_basic_auth_on',
            'plugins_to_include',
            'themes_to_include',
            'ss_single_pages',
        );
		// Filters may add exclusions, but cannot remove the built-in security list.
		$filtered = apply_filters( 'ss_export_excluded_options', $defaults );
		$list     = is_array( $filtered ) ? array_merge( $defaults, $filtered ) : $defaults;
		$list     = array_merge( $list, Util::get_sensitive_option_keys() );
		$list = array_map( 'sanitize_key', array_filter( array_map( 'strval', $list ) ) );
        return array_values( array_unique( $list ) );
    }

	/**
	 * Merge non-portable destination values back into imported settings.
	 *
	 * @param array $incoming Imported settings.
	 * @param mixed $current  Existing destination settings.
	 *
	 * @return array
	 */
	private function preserve_destination_options( $incoming, $current ) {
		$incoming = is_array( $incoming ) ? $incoming : array();
		$current  = is_array( $current ) ? $current : array();
		$incoming = Util::preserve_sensitive_options( $incoming, $current );

		foreach ( $this->get_export_excluded_options() as $excluded_key ) {
			if ( array_key_exists( $excluded_key, $current ) ) {
				$incoming[ $excluded_key ] = $current[ $excluded_key ];
			} else {
				unset( $incoming[ $excluded_key ] );
			}
		}

		return $incoming;
	}

	/**
	 * Return a fail-closed conflict response for destructive maintenance actions.
	 *
	 * @param string $message User-facing conflict message.
	 *
	 * @return string|null
	 */
	private function get_maintenance_conflict_response( $message ) {
		try {
			$job    = Plugin::instance()->get_archive_creation_job();
			$active = method_exists( $job, 'is_active' )
				? $job->is_active()
				: ( $job->is_running() || $job->is_paused() );

			if ( $active ) {
				return wp_json_encode(
					array(
						'status'  => 409,
						'message' => $message,
					)
				);
			}
		} catch ( \Throwable $exception ) {
			Util::debug_log( 'Unable to verify archive state before maintenance: ' . $exception->getMessage() );

			return wp_json_encode(
				array(
					'status'  => 500,
					'message' => __( 'Unable to verify that the export queue is idle.', 'simply-static' ),
				)
			);
		}

		return null;
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

		$portable_import = ! empty( $params['__simply_static_import'] );
		unset( $params['__simply_static_import'] );

		if ( $portable_import ) {
			$conflict = $this->get_maintenance_conflict_response(
				__( 'Settings cannot be imported while an export is active or paused.', 'simply-static' )
			);
			if ( null !== $conflict ) {
				return $conflict;
			}

			$params = $this->preserve_destination_options( $params, get_option( 'simply-static' ) );
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
			'sftp_private_key',
			'css_js_aggregate_exclude_patterns',
			'css_optimize_critical_patterns',
			'css_optimize_defer_js_excludes',
			'css_optimize_delay_js_patterns',
			'critical_css_additional_urls',
			'critical_css_custom',
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
            'use_search',
            'use_search_results_page',
            'search_show_submit',
            'search_show_excerpt',
            'fuse_use_extended_search',
            'fuse_ignore_location',
            'ss_use_single_exports',
            'ss_use_builds',
            'ss_single_include_categories',
            'ss_single_include_tags',
            'ss_single_include_archives',
            'ss_single_include_pagination',
            'ss_single_export_add_xml_sitemap',
            'ss_single_auto_export',
            'ss_tools_submenu',
			'post_types_configured',
			'server_cron',
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
				if ( empty( $san ) || ! wp_http_validate_url( $san ) ) {
					$san = '';
                }
                $options[ $key ] = $san;
			} elseif ( 'ss_webhook_url' === $key ) {
				$san = esc_url_raw( $value );
				if ( empty( $san ) || ! wp_http_validate_url( $san ) ) {
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
				$options[ $key ] = is_array( $value ) ? map_deep( $value, 'sanitize_text_field' ) : sanitize_text_field( $value );
			}
		}

		// Preserve internal/runtime values that are not owned by the settings form.
		$current_options = get_option( 'simply-static' );
		$internal_keys   = array(
			'encryption_key',
			'archive_status_messages',
			'archive_deploy_id',
			'deploy_manifest_schema_version',
			'pages_status',
			'archive_name',
			'archive_start_time',
			'archive_end_time',
			'generate_type',
			'archive_task_list',
			'zip_batch_offset',
			'zip_total_files',
			'zip_files',
			'version',
		);
		if ( is_array( $current_options ) ) {
			foreach ( $internal_keys as $internal_key ) {
				if ( array_key_exists( $internal_key, $current_options ) ) {
					$options[ $internal_key ] = $current_options[ $internal_key ];
				} else {
					unset( $options[ $internal_key ] );
				}
			}
		} else {
			foreach ( $internal_keys as $internal_key ) {
				unset( $options[ $internal_key ] );
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

        // Ensure required plugins (e.g. Simply Static Pro) are always present.
        if ( isset( $options['plugins_to_include'] ) && is_array( $options['plugins_to_include'] ) ) {
            $options['plugins_to_include'] = Util::ensure_required_plugins( $options['plugins_to_include'] );
        }

        // Multisite: also persist per-site copy under site option when not main site
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			if ( $blog_id > 1 ) {
				$portable_options = Util::remove_sensitive_options( $options );
				foreach ( $this->get_export_excluded_options() as $excluded_key ) {
					unset( $portable_options[ $excluded_key ] );
				}
				update_site_option( 'simply-static-' . $blog_id, $portable_options );
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

        // Post type selections must be valid public post type slugs.
        $allowed_post_types = get_post_types( [ 'public' => true ], 'names' );
        $allowed_post_types = array_values( array_diff( $allowed_post_types, [ 'attachment', 'elementor_library', 'ssp-form' ] ) );

        if ( isset( $options['post_types'] ) && is_array( $options['post_types'] ) ) {
            $options['post_types'] = array_values( array_intersect( $options['post_types'], $allowed_post_types ) );
            if ( ! isset( $options['post_types_configured'] ) && ! empty( $options['post_types'] ) ) {
                $options['post_types_configured'] = true;
            }
        }

        if ( isset( $options['ss_single_auto_export_types'] ) && is_array( $options['ss_single_auto_export_types'] ) ) {
            $options['ss_single_auto_export_types'] = array_values( array_intersect( $options['ss_single_auto_export_types'], $allowed_post_types ) );
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
		$conflict = $this->get_maintenance_conflict_response(
			__( 'Plugin settings cannot be reset while an export is active or paused.', 'simply-static' )
		);
		if ( null !== $conflict ) {
			return $conflict;
		}

        // Keep installation and reset on the same complete, typed source of truth.
        Page::create_or_update_table();
		$default_options = Upgrade_Handler::get_default_options();
        update_option( 'simply-static', $default_options );
		Options::reinstance();

		return wp_json_encode( array( 'status' => 200, 'message' => 'Ok', 'data' => $default_options ) );
    }

    public function maybe_export_404( $request ) { return Admin_Settings::get_instance()->export_404(); }

    /** Reset database (drop and recreate SS pages table) */
    public function reset_database() {
		$conflict = $this->get_maintenance_conflict_response(
			__( 'The database table cannot be reset while an export is active or paused.', 'simply-static' )
		);
		if ( null !== $conflict ) {
			return $conflict;
		}

        global $wpdb;
        $table_name = $wpdb->prefix . 'simply_static_pages';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
        Page::create_or_update_table();
        return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
    }

	/** Reset background queue and all request/runtime state owned by that queue. */
	public function reset_background_queue() {
		try {
			/** @var Archive_Creation_Job $job */
			$job     = Plugin::instance()->get_archive_creation_job();
			$site_id = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : null;

			if ( method_exists( $job, 'set_current_site_id' ) && null !== $site_id ) {
				$job->set_current_site_id( $site_id );
			}
			if ( method_exists( $job, 'has_active_process_lock' ) && $job->has_active_process_lock() ) {
				return wp_json_encode(
					array(
						'status'  => 409,
						'message' => __( 'The background worker is still active. Wait for it to finish before resetting the queue.', 'simply-static' ),
					)
				);
			}

			// Delete queued batches and the background-process status marker.
			$job->delete_all();
			$identifier = method_exists( $job, 'get_identifier' ) ? (string) $job->get_identifier() : 'wp_archive_creation_job';
			if ( '' === $identifier ) {
				$identifier = 'wp_archive_creation_job';
			}
			delete_option( $identifier . '_status' );

			// Clear only this process's healthcheck, never recurring export schedules.
			wp_clear_scheduled_hook( $identifier . '_cron' );

			// Clear the lock owned by this site without disturbing another site's
			// process in multisite.
			$lock_key = $identifier . '_process_lock';
			if ( is_multisite() && null !== $site_id ) {
				delete_site_transient( $lock_key . '_site_' . $site_id );
			} else {
				delete_site_transient( $lock_key );
			}

			$options = method_exists( $job, 'get_options' ) ? $job->get_options() : Options::instance();
			if ( ! is_object( $options ) || ! method_exists( $options, 'set' ) || ! method_exists( $options, 'save' ) ) {
				$options = Options::instance();
			}
			$runtime_defaults = array(
				'archive_status_messages' => array(),
				'archive_deploy_id'       => null,
				'pages_status'             => array(),
				'archive_name'             => null,
				'archive_start_time'       => null,
				'archive_end_time'         => null,
				'generate_type'            => 'export',
				'archive_task_list'        => array(),
				'zip_batch_offset'         => null,
				'zip_total_files'          => null,
				'zip_files'                => null,
			);
			foreach ( $runtime_defaults as $key => $value ) {
				$options->set( $key, $value );
			}
			$options->save();
			delete_option( 'simply_static_zip_files' );

			if ( method_exists( $job, 'reset_runtime_state' ) ) {
				$job->reset_runtime_state();
			}

			// Special export modes are one-shot state and must never survive a reset.
			foreach ( array( 'simply-static-404-only', 'simply-static-use-single', 'simply-static-use-build', 'simply-static-use-language' ) as $option_name ) {
				delete_option( $option_name );
			}

			// Release this site's multisite-wide export marker without affecting a
			// different site's active export.
			if ( is_multisite() && null !== $site_id ) {
				$network_lock = get_site_option( Plugin::SLUG . '_multisite_export_running', false );
				if ( absint( $network_lock ) === absint( $site_id ) ) {
					delete_site_option( Plugin::SLUG . '_multisite_export_running' );
				}
			}

			do_action( 'ss_after_background_queue_reset', $job, $site_id );

			return wp_json_encode( array( 'status' => 200, 'message' => 'Ok' ) );
		} catch ( \Throwable $e ) {
			return wp_json_encode( array( 'status' => 500, 'message' => $e->getMessage() ) );
		}
	}

    /** Import settings from another site in network */
	public function update_from_network( $request ) {
		if ( ! is_multisite() || ! current_user_can( 'manage_network_options' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to import settings across the network.', 'simply-static' ), array( 'status' => 403 ) );
		}

		$conflict = $this->get_maintenance_conflict_response(
			__( 'Settings cannot be imported while an export is active or paused.', 'simply-static' )
		);
		if ( null !== $conflict ) {
			return $conflict;
		}

		$params = $request->get_params();
		if ( $request->get_params() ) {
			$blog_id = isset( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;
			$site = $blog_id ? get_blog_details( $blog_id ) : null;
			if ( ! $site || ! empty( $site->deleted ) || ! empty( $site->spam ) || ! empty( $site->archived ) ) {
				return new \WP_Error( 'invalid_blog_id', __( 'Invalid source site.', 'simply-static' ), array( 'status' => 400 ) );
			}
			// Get Settings from selected subsite.
			$options = get_site_option( 'simply-static-' . $blog_id );
            if ( ! $options ) {
                return json_encode( [
                    'status'  => 400,
                    'message' => 'Please save the settings on the selected subsite before importing them into a new site.'
                ] );
            }
			$options = Util::remove_sensitive_options( is_array( $options ) ? $options : array() );
			foreach ( $this->get_export_excluded_options() as $excluded_key ) {
				unset( $options[ $excluded_key ] );
			}
			$options = $this->preserve_destination_options( $options, get_option( 'simply-static' ) );
			update_option( 'simply-static', $options );
            return json_encode( [ 'status' => 200, 'message' => 'Ok' ] );
        }
        return json_encode( [ 'status' => 400, 'message' => 'No options updated.' ] );
    }

    public function get_pages() { return Admin_Settings::get_instance()->get_pages(); }
    public function get_pages_slugs() { return Admin_Settings::get_instance()->get_pages_slugs(); }
	public function migrate( $request ) {
		$conflict = $this->get_maintenance_conflict_response(
			__( 'Settings cannot be migrated while an export is active or paused.', 'simply-static' )
		);
		if ( null !== $conflict ) {
			return $conflict;
		}

		return Admin_Settings::get_instance()->migrate_settings();
	}

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
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;

		return $this->run_in_blog_context( $blog_id, function () use ( $blog_id ) {
			$activity_log = Plugin::instance()->get_activity_log( $blog_id );
			$running      = Plugin::instance()->get_archive_creation_job()->is_running();

			return json_encode( [
				'status'  => 200,
				'data'    => $activity_log,
				'running' => $running,
			] );
		} );
    }

    /** Export log */
	public function get_export_log( $request ) {
		$params     = $request->get_params();
		$blog_id    = ! empty( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;
		$search     = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$page       = ! empty( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
		$per_page   = ! empty( $params['per_page'] ) ? min( 200, max( 1, absint( $params['per_page'] ) ) ) : 25;

		return $this->run_in_blog_context( $blog_id, function () use ( $per_page, $page, $blog_id, $search ) {
			$export_log = Plugin::instance()->get_export_log( $per_page, $page, $blog_id, $search );

			return json_encode( [ 'status' => 200, 'data' => $export_log ] );
		} );
    }

    /** Deploy manifest summaries */
    public function get_deploy_manifests( $request ) {
        $params   = $request->get_params();
        $page     = ! empty( $params['page'] ) ? absint( $params['page'] ) : 1;
        $per_page = ! empty( $params['per_page'] ) ? absint( $params['per_page'] ) : 20;

        return rest_ensure_response( Deploy_Manifest_Service::get_instance()->get_exports( $page, $per_page ) );
    }

    /** Latest deploy manifest */
    public function get_latest_deploy_manifest() {
        $manifest = Deploy_Manifest_Service::get_instance()->get_latest_manifest();

        if ( empty( $manifest ) ) {
            return new \WP_Error( 'ss_manifest_not_found', __( 'No deploy manifest available.', 'simply-static' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $manifest );
    }

    /** Deploy manifest by ID */
    public function get_deploy_manifest( $request ) {
        $deploy_id = sanitize_text_field( $request->get_param( 'export_id' ) );
        $manifest  = Deploy_Manifest_Service::get_instance()->get_manifest( $deploy_id );

        if ( empty( $manifest ) ) {
            return new \WP_Error( 'ss_manifest_not_found', __( 'Deploy manifest not found.', 'simply-static' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $manifest );
    }

    /** Deploy manifest URL records */
    public function get_deploy_manifest_urls( $request ) {
        $params    = $request->get_params();
        $deploy_id = sanitize_text_field( $request->get_param( 'export_id' ) );

        return rest_ensure_response(
            Deploy_Manifest_Service::get_instance()->get_manifest_urls(
                $deploy_id,
                array(
                    'page'     => ! empty( $params['page'] ) ? absint( $params['page'] ) : 1,
                    'per_page' => ! empty( $params['per_page'] ) ? absint( $params['per_page'] ) : 100,
                    'status'   => ! empty( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '',
                    'type'     => ! empty( $params['type'] ) ? sanitize_text_field( $params['type'] ) : '',
                    'search'   => ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
                )
            )
        );
    }

    /** Deploy manifest file records */
    public function get_deploy_manifest_files( $request ) {
        $deploy_id = sanitize_text_field( $request->get_param( 'export_id' ) );

        return rest_ensure_response( Deploy_Manifest_Service::get_instance()->get_manifest_files( $deploy_id ) );
    }

    /** Deploy manifest warnings */
    public function get_deploy_manifest_warnings( $request ) {
        $deploy_id = sanitize_text_field( $request->get_param( 'export_id' ) );

        return rest_ensure_response( Deploy_Manifest_Service::get_instance()->get_manifest_warnings( $deploy_id ) );
    }

    /**
     * Start Export
     */
	public function start_export( $request ) {
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;
		$type    = ! empty( $params['type'] ) ? sanitize_key( $params['type'] ) : 'export';
        $language = ! empty( $params['language'] ) ? sanitize_text_field( wp_unslash( $params['language'] ) ) : '';

		try {
			return $this->run_in_blog_context( $blog_id, function () use ( $blog_id, $type, $language ) {
				$archive_creation_job = Plugin::instance()->get_archive_creation_job();
				do_action( 'ss_before_perform_archive_running_check', $blog_id, $archive_creation_job );
				if ( $archive_creation_job->is_running() ) {
					return wp_json_encode( array(
						'status'  => 409,
						'message' => __( 'An export is already running. Please wait for it to complete or cancel it before starting a new one.', 'simply-static' ),
					) );
				}

				if ( ! empty( $language ) ) {
					update_option( 'simply-static-use-language', $language, false );
				} else {
					delete_option( 'simply-static-use-language' );
				}

				do_action( 'ss_before_perform_archive_action', $blog_id, 'start', $archive_creation_job );
				$export_type = apply_filters( 'ss_export_type', $type );
				$started = Plugin::instance()->run_static_export( $blog_id, $export_type );
				if ( $started ) {
					do_action( 'ss_after_perform_archive_action', $blog_id, 'start', $archive_creation_job );
					return wp_json_encode( array( 'status' => 200 ) );
				}

				return wp_json_encode( array(
					'status'  => 409,
					'message' => __( 'The export could not be started because another export is active or an export preflight check failed.', 'simply-static' ),
				) );
			} );
		} catch ( \Throwable $e ) {
			return json_encode( [ 'status' => 500, 'message' => $e->getMessage() ] );
		}
    }

    /** Cancel Export */
	public function cancel_export( $request ) {
		return $this->perform_archive_action( $request, 'cancel', 'cancel_static_export' );
	}

    /** Pause Export */
	public function pause_export( $request ) {
		return $this->perform_archive_action( $request, 'pause', 'pause_static_export' );
	}

    /** Resume Export */
	public function resume_export( $request ) {
		return $this->perform_archive_action( $request, 'resume', 'resume_static_export' );
	}

    /** Is running */
	public function is_running( $request ) {
        $params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;

		try {
			return $this->run_in_blog_context( $blog_id, function () {
				$job = Plugin::instance()->get_archive_creation_job();
				$stats = array(
					'status'   => 200,
					'running'  => $job->is_running(),
					'paused'   => $job->is_paused(),
					'progress' => method_exists( $job, 'get_progress' ) ? $job->get_progress() : 0,
				);

				if ( ! empty( $stats['running'] ) && empty( $stats['paused'] ) && ! $job->is_processing() ) {
					$job->dispatch();
				}

				return wp_json_encode( apply_filters( 'ss_is_running_statuses', $stats ) );
			} );
		} catch ( \Throwable $e ) {
			return wp_json_encode( array( 'status' => 500, 'message' => $e->getMessage() ) );
		}
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
			$conflict = $this->get_maintenance_conflict_response(
				__( 'Temporary files cannot be cleared while the export queue is active.', 'simply-static' )
			);
			if ( null !== $conflict ) {
				$response = json_decode( $conflict, true );
				if ( is_array( $response ) ) {
					$response['cleared'] = false;
					return wp_json_encode( $response );
				}

				return $conflict;
			}

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

    /**
     * Install and activate the Static Studio migration plugin.
     *
     * Downloads the plugin from the Static Studio API, installs it,
     * activates it and returns the redirect URL to the migration page.
     *
     * @return \WP_REST_Response
     */
    public function install_studio_migrate() {
        $plugin_slug = 'simply-static-studio-backup-migrate/simply-static-studio-backup-migrate.php';
        $plugin_zip  = 'https://api.static.studio/storage/v1/object/public/plugins/simply-static-studio-backup-migrate.zip';
        $redirect    = admin_url( 'tools.php?page=studio-backup' );

        // Already active — just redirect.
        if ( is_plugin_active( $plugin_slug ) ) {
            return new \WP_REST_Response( [
                'success'  => true,
                'redirect' => $redirect,
            ] );
        }

        // Already installed but not active — activate it.
        if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
            $result = activate_plugin( $plugin_slug );

            if ( is_wp_error( $result ) ) {
                return new \WP_REST_Response( [
                    'success' => false,
                    'message' => $result->get_error_message(),
                ], 500 );
            }

            return new \WP_REST_Response( [
                'success'  => true,
                'redirect' => $redirect,
            ] );
        }

        // Not installed — download and install.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $downloaded = download_url( $plugin_zip, 30 );

		if ( is_wp_error( $downloaded ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => $downloaded->get_error_message(),
			], 502 );
		}

		$max_package_bytes = max( 1024 * 1024, min( 200 * 1024 * 1024, (int) apply_filters( 'ss_studio_migrate_max_package_bytes', 50 * 1024 * 1024 ) ) );
		$package_size      = is_file( $downloaded ) ? filesize( $downloaded ) : false;
		$expected_hash     = (string) apply_filters( 'ss_studio_migrate_package_sha256', self::STUDIO_MIGRATE_PACKAGE_SHA256 );
		if (
			false === $package_size
			|| $package_size < 1
			|| $package_size > $max_package_bytes
			|| ! $this->downloaded_package_matches_checksum( $downloaded, $expected_hash )
		) {
			$this->delete_downloaded_package( $downloaded );

			return new \WP_REST_Response( [
				'success' => false,
				'message' => __( 'The migration plugin package failed integrity verification.', 'simply-static' ),
			], 502 );
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		try {
			$installed = $upgrader->install( $downloaded );
		} finally {
			$this->delete_downloaded_package( $downloaded );
		}

        if ( is_wp_error( $installed ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $installed->get_error_message(),
            ], 500 );
        }

        if ( ! $installed ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Plugin installation failed.', 'simply-static' ),
            ], 500 );
        }

        $result = activate_plugin( $plugin_slug );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        return new \WP_REST_Response( [
            'success'  => true,
            'redirect' => $redirect,
        ] );
    }

	/**
	 * Verify a downloaded package against a pinned SHA-256 digest.
	 *
	 * @param string $filename      Local package filename.
	 * @param string $expected_hash Expected lowercase/uppercase SHA-256 digest.
	 * @return bool
	 */
	private function downloaded_package_matches_checksum( $filename, $expected_hash ) {
		$expected_hash = strtolower( trim( (string) $expected_hash ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $expected_hash ) || ! is_file( $filename ) ) {
			return false;
		}

		$actual_hash = hash_file( 'sha256', $filename );

		return is_string( $actual_hash ) && hash_equals( $expected_hash, strtolower( $actual_hash ) );
	}

	/** @param string $filename Temporary package filename. */
	private function delete_downloaded_package( $filename ) {
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $filename );
		} elseif ( is_file( $filename ) ) {
			unlink( $filename );
		}
	}

	/**
	 * Perform pause/resume/cancel with guaranteed multisite context cleanup.
	 *
	 * @param object $request REST request.
	 * @param string $action Action label.
	 * @param string $method Plugin method.
	 * @return string
	 */
	private function perform_archive_action( $request, $action, $method ) {
		$params  = $request->get_params();
		$blog_id = ! empty( $params['blog_id'] ) ? absint( $params['blog_id'] ) : 0;

		try {
			return $this->run_in_blog_context( $blog_id, function () use ( $blog_id, $action, $method ) {
				$plugin = Plugin::instance();
				$job    = $plugin->get_archive_creation_job();
				do_action( 'ss_before_perform_archive_action', $blog_id, $action, $job );
				$plugin->{$method}();
				do_action( 'ss_after_perform_archive_action', $blog_id, $action, $job );

				return wp_json_encode( array( 'status' => 200 ) );
			} );
		} catch ( \Throwable $e ) {
			return wp_json_encode( array( 'status' => 500, 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Run a callback for a target site and always restore the original blog.
	 *
	 * @param int      $blog_id Target blog ID, or zero for current.
	 * @param callable $callback Callback to run.
	 * @return mixed
	 */
	private function run_in_blog_context( $blog_id, $callback ) {
		$switched = $blog_id && is_multisite() && absint( $blog_id ) !== get_current_blog_id();
		try {
			if ( $switched ) {
				$this->switch_to_blog_and_refresh_options( $blog_id );
			}

			return call_user_func( $callback );
		} finally {
			if ( $switched ) {
				$this->restore_current_blog_and_refresh_options();
			}
		}
	}

    /**
     * Switch to blog and refresh options references.
     *
     * @param int $blog_id Blog ID.
     * @return void
     */
    private function switch_to_blog_and_refresh_options( $blog_id ) {
        if ( ! $blog_id || ! is_multisite() ) {
            return;
        }
        switch_to_blog( $blog_id );
        $options = Options::reinstance();
        Plugin::instance()->set_options( $options );
		$job = Plugin::instance()->get_archive_creation_job();
		$job->set_options( $options );
		if ( method_exists( $job, 'set_current_site_id' ) ) {
			$job->set_current_site_id( get_current_blog_id() );
		}
    }

    /**
     * Restore current blog and refresh options references.
     *
     * @return void
     */
    private function restore_current_blog_and_refresh_options() {
        if ( ! is_multisite() ) {
            return;
        }
        restore_current_blog();
        $options = Options::reinstance();
        Plugin::instance()->set_options( $options );
		$job = Plugin::instance()->get_archive_creation_job();
		$job->set_options( $options );
		if ( method_exists( $job, 'set_current_site_id' ) ) {
			$job->set_current_site_id( get_current_blog_id() );
		}
    }
}
