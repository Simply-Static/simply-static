<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Upgrade Handler class
 *
 * Used for handling upgrades/downgrades of Simply Static
 */
class Upgrade_Handler {

	/**
	 * An instance of the options structure containing all options for this plugin
	 *
	 * @var Simply_Static\Options
	 */
	protected static $options = null;

	/**
	 * Default options to set for the plugin
	 *
	 * @var array
	 */
	protected static $default_options = null;

	/**
	 * Disable usage of "new"
	 *
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Disable cloning of the class
	 *
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Create settings and setup database
	 * @return void
	 */
	public static function run() {
		self::$options = Options::instance();

		// Check if directory exists, if not, create it.
		Util::get_temp_dir();

		self::$default_options = array(
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
			'fuse_threshold'                 => 0.1,
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
			'add_feeds'                     => false,
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

		$version = self::$options->get( 'version' );

		// New installation, set default options.
		if ( null === $version ) {
			Page::create_or_update_table();
			self::set_default_options();
		} else {
			if ( version_compare( $version, SIMPLY_STATIC_VERSION, '!=' ) ) {
				// Sync database.
				Page::create_or_update_table();

				// Update version.
				self::$options
					->set( 'version', SIMPLY_STATIC_VERSION )
					->save();
			}
		}
	}

	/**
	 * Add default options where they don't exist
	 *
	 * @return void
	 */
	protected static function set_default_options() {
		foreach ( self::$default_options as $option_key => $option_value ) {
			if ( self::$options->get( $option_key ) === null ) {
				self::$options->set( $option_key, $option_value );
			}
		}
	}
}
