<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Diagnostic class
 *
 * Checks to ensure that the user's server and WP installation meet a set of
 * minimum requirements.
 */
class Diagnostic {

	/** @const */
	protected static $min_version = array(
		'php'  => '7.4',
		'curl' => '7.4'
	);

	/**
	 * Assoc. array of categories, and then functions to check
	 * @var array
	 */
	protected $checks = array();

	/**
	 * Assoc. array of results of the diagnostic check
	 * @var array
	 */
	public $results = array();

	/**
	 * List of incompatible plugins
	 * @var array
	 */
	public $incompatible_plugins = array();

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	public function __construct() {
		$this->options = Options::instance();

		$this->checks = array(
			'URLs'       => array(),
			'Server'     => array(
				__( 'PHP Version', 'simply-static' ) => $this->php_version(),
				__( 'Basic Auth', 'simply-static' )  => $this->check_basic_auth_status(),
				__( 'php-xml', 'simply-static' )     => $this->is_xml_active(),
				__( 'cURL', 'simply-static' )        => $this->has_curl(),
			),
			'WordPress'  => array(
				__( 'Permalinks', 'simply-static' ) => $this->is_permalink_structure_set(),
				__( 'Indexable', 'simply-static' )  => $this->is_set_to_index(),
				__( 'Caching', 'simply-static' )    => $this->is_cache_set(),
				__( 'WP-CRON', 'simply-static' )    => $this->is_wp_cron_running(),
			),
			'Plugins'    => array(),
			'Filesystem' => array(
				__( 'Temp dir readable', 'simply-static' )  => $this->is_temp_files_dir_readable(),
				__( 'Temp dir writeable', 'simply-static' ) => $this->is_temp_files_dir_writeable(),
			),
			'MySQL'      => array(
				__( 'DELETE', 'simply-static' ) => $this->user_can_delete(),
				__( 'INSERT', 'simply-static' ) => $this->user_can_insert(),
				__( 'SELECT', 'simply-static' ) => $this->user_can_select(),
				__( 'CREATE', 'simply-static' ) => $this->user_can_create(),
				__( 'ALTER', 'simply-static' )  => $this->user_can_alter(),
				__( 'DROP', 'simply-static' )   => $this->user_can_drop(),
			)
		);

		if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
			$this->checks['URLs'][ __( 'Destination URL', 'simply-static' ) ] = $this->is_destination_host_a_valid_url();
		}

		if ( $this->options->get( 'delivery_method' ) == 'local' ) {
			$this->checks['Filesystem'][ __( 'Local Dir', 'simply-static' ) ] = $this->is_local_dir_writeable();
		}

		$additional_urls = Util::string_to_array( $this->options->get( 'additional_urls' ) );

		foreach ( $additional_urls as $url ) {
			$this->checks['URLs'][ $url ] = $this->is_additional_url_valid( $url );
		}

		$additional_files = Util::string_to_array( $this->options->get( 'additional_files' ) );
		foreach ( $additional_files as $file ) {
			$this->checks['Filesystem'][ $file ] = $this->is_additional_file_valid( $file );
		}

		// Check if URLs checks are empty.
		if ( empty( $this->checks['URLs'] ) ) {
			unset( $this->checks['URLs'] );
		}

		// Check for incompatible plugins.
		$plugins           = get_plugins();
		$active_plugins    = get_option( 'active_plugins' );
		$plugin_count      = 0;
		$activated_plugins = array();

		foreach ( $active_plugins as $plugin ) {
			if ( isset( $plugins[ $plugin ] ) ) {
				$activated_plugins[] = $plugins[ $plugin ];
			}
		}

		$this->incompatible_plugins = apply_filters( 'ss_incompatible_plugins', $this->get_incompatible_plugins() );

		foreach ( $activated_plugins as $plugin ) {
			if ( in_array( $plugin['TextDomain'], $this->incompatible_plugins ) ) {
				$this->checks['Plugins'][ $plugin['Name'] ] = $this->is_incompatible_plugin( $plugin );
				$plugin_count ++;
			}
		}

		if ( $plugin_count === 0 ) {
			$this->checks['Plugins']['Incompatible Plugins'] = array(
				'test'        => true,
				'description' => __( 'No incompatible plugins are active on your website!', 'simply-static' ),
				'error'       => sprintf( __( '%d incompatible plugins are active', 'simply-static' ), $plugin_count )
			);
		}

		// Set transient for checks.
		if ( ! get_transient( 'simply_static_checks' ) ) {
			set_transient( 'simply_static_checks', $this->checks, MINUTE_IN_SECONDS );
		}

		// Set transient for failed tests.
		if ( ! get_transient( 'simply_static_failed_tests' ) ) {
			$failed_tests = 0;

			foreach ( $this->checks as $test ) {
				foreach ( $test as $key => $value ) {
					if ( ! $value['test'] ) {
						$failed_tests ++;
					}
				}
			}
			set_transient( 'simply_static_failed_tests', $failed_tests, MINUTE_IN_SECONDS );
		}
	}

	public function get_checks() {
		return $this->checks;
	}

	public function is_destination_host_a_valid_url() {
		$destination_scheme = $this->options->get( 'destination_scheme' );
		$destination_host   = $this->options->get( 'destination_host' );
		$destination_url    = $destination_scheme . $destination_host;

		return array(
			'test'        => filter_var( $destination_url, FILTER_VALIDATE_URL ) !== false,
			'description' => sprintf( __( 'Destination URL %s is valid', 'simply-static' ), $destination_url ),
			'error'       => sprintf( __( 'Destination URL %s is not valid', 'simply-static' ), $destination_url )
		);
	}

	public function is_additional_url_valid( $url ) {
		$response = Url_Fetcher::remote_get( $url );
		$infos    = $this->check_error_from_response( $response );

		return array(
			'test'        => $infos['test'],
			'description' => __( 'Is a valid URL.', 'simply-static' ),
			'error'       => __( 'Is not a valid URL.', 'simply-static' )
		);
	}

	public function is_additional_file_valid( $file ) {
		if ( stripos( $file, get_home_path() ) !== 0 && stripos( $file, WP_PLUGIN_DIR ) !== 0 && stripos( $file, WP_CONTENT_DIR ) !== 0 ) {
			$test    = false;
			$message = __( 'Not a valid path', 'simply-static' );
		} elseif ( ! is_readable( $file ) ) {
			$test    = false;
			$message = __( 'Not readable', 'simply-static' );;
		} else {
			$test    = true;
			$message = null;
		}

		return array(
			'test'        => $test,
			'description' => sprintf( __( 'Additional File/Dir %s is valid', 'simply-static' ), $file ),
			'error'       => $message
		);
	}

	public function is_permalink_structure_set() {
		return array(
			'test'        => strlen( get_option( 'permalink_structure' ) ) !== 0,
			'description' => __( 'WordPress permalink structure is set', 'simply-static' ),
			'error'       => __( 'WordPress permalink structure is not set', 'simply-static' ),
		);
	}

	public function is_set_to_index() {
		return array(
			'test'        => get_option( 'blog_public' ) === '1',
			'description' => __( 'Discourage search engines from indexing this site is disabled', 'simply-static' ),
			'error'       => __( 'Discourage search engines from indexing this site is enabled', 'simply-static' ),
		);
	}

	public function is_wp_cron_running() {
		$server_cron = $this->options->get( 'server_cron' );

		if ( ! defined( 'DISABLE_WP_CRON' ) || DISABLE_WP_CRON !== true || defined( 'SS_CRON' ) || $server_cron ) {
			$is_cron = true;
		} else {
			$is_cron = false;
		}

		return array(
			'test'        => $is_cron,
			'description' => __( 'WordPress cron is available and running', 'simply-static' ),
			'error'       => __( 'WordPress cron is not available and not running', 'simply-static' ),
		);
	}

	public function is_cache_set() {
		$incompatible_plugins = $this->get_incompatible_plugins();

		$response = array(
			'test'        => true,
			'description' => __( 'Caching is disabled, great!', 'simply-static' ),
			'error'       => __( 'Please disable caching before running a static export', 'simply-static' ),
		);

		// W3 Total Cache.
		if ( defined( 'W3TC_VERSION' ) && in_array( 'w3-total-cache', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'W3 Total Cache' ) );
		}

		// WP Fastest Cache.
		if ( defined( 'WPFC_WP_PLUGIN_DIR' ) && in_array( 'wp-fastest-cache', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'WP Fastest Cache' ) );
		}

		// WP Rocket.
		if ( defined( 'WP_ROCKET_VERSION' ) && in_array( 'wp-rocket', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'WP Rocket' ) );
		}

		// Litespeed Cache.
		if ( defined( 'LSCWP_V' ) && in_array( 'litespeed-cache', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'LiteSpeed Cache' ) );
		}

		// Speed Optimizer (Siteground)
		if ( defined( 'SiteGround_Optimizer\VERSION' ) && in_array( 'sg-cachepress', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Speed Optimizer' ) );
		}

		// WP Super Cache.
		if ( defined( 'WPSC_VERSION' ) && in_array( 'wp-super-cache', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'WP Super Cache' ) );
		}

		// Hummingbird.
		if ( defined( 'WPHB_VERSION' ) && in_array( 'hummingbird-performance', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Hummingbird' ) );
		}

		// Autoptimize.
		if ( defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) && in_array( 'autoptimize', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Autoptimize' ) );
		}

		// Breeze (Cloudways)
		if ( defined( 'BREEZE_VERSION' ) && in_array( 'breeze', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Breeze' ) );
		}

		// Cache Enabler.
		if ( defined( 'CACHE_ENABLER_VERSION' ) && in_array( 'cache-enabler', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Cache Enabler' ) );
		}

		// Redis Object Cache.
		if ( defined( 'WP_REDIS_VERSION' ) && in_array( 'wp-redis', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Redis Object Cache' ) );
		}

		// Cloudflare.
		if ( defined( 'CLOUDFLARE_PLUGIN_DIR' ) && in_array( 'cloudflare', $incompatible_plugins ) ) {
			$response['test']  = false;
			$response['error'] = sprintf( esc_html__( 'Please disable caching (%s) before running a static export.', 'simply-static' ), esc_html( 'Cloudflare' ) );
		}

		return $response;
	}

	/**
	 * Get incompatible plugins.
	 * @return array
	 */
	public function get_incompatible_plugins() {
		$whitelist_plugins = Util::string_to_array( $this->options->get( 'whitelist_plugins' ) );

		$incompatible_plugins = [
			'autoptimize',
			'wp-fastest-cache',
			'wp-rocket',
			'wp-search-with-algolia',
			'w3-total-cache',
			'coming-soon',
			'wp-super-cache',
			'hummingbird-performance',
			'cache-enabler',
			'cloudflare',
			'fluentcrm',
			'happyforms',
			'real-cookie-banner',
			'borlabs-cookie',
			'redis-cache',
			'wp-redis',
			'woocommerce',
			'popup-maker',
			'wpcf7-redirect',
			'weforms',
			'booking-system',
			'yet-another-stars-rating',
			'mailpoet',
			'the-events-calendar',
			'buddypress',
			'lifterlms',
			'wp-job-manager',
			'learnpress',
			'sg-security',
			'burst-statistics',
			'ajax-load-more',
			'wp-statistics',
			'wp-slimstat',
			'wp-power-stats',
			'nitropack',
			'ultimate-member',
			'paid-memberships-pro',
			'wp-members',
			'wp-private-content-plus',
			'forminator',
			'catch-infinite-scroll',
			'ultimate-post',
			'facetwp',
			'wp-ultimate-post-grid',
			'searchwp',
			'relevanssi',
			'siteorigin-panels',
			'wp-user-frontend',
			'optinmonster',
			'mailoptin',
			'wp-original-media-path',
		];

		if ( ! empty( $whitelist_plugins ) && is_array( $whitelist_plugins ) ) {
			// Remove whitelisted plugins from incompatible plugins array.
			foreach ( $whitelist_plugins as $plugin ) {
				$key = array_search( $plugin, $incompatible_plugins );
				unset( $incompatible_plugins[ $key ] );
			}
		}

		return $incompatible_plugins;
	}

	/**
	 * Check if plugins is compatible.
	 *
	 * @param string $plugin given plugin slug.
	 *
	 * @return array
	 */
	public function is_incompatible_plugin( $plugin ) {
		return array(
			'test'  => false,
			'error' => sprintf( __( '%s is not compatible with Simply Static.', 'simply-static' ), $plugin['Name'] )
		);
	}

	public function is_temp_files_dir_readable() {
		$temp_files_dir = Util::get_temp_dir();

		return array(
			'test'        => is_readable( $temp_files_dir ),
			'description' => sprintf( __( "Web server can read from Temp Files Directory: %s", 'simply-static' ), $temp_files_dir ),
			'error'       => sprintf( __( "Web server can't read from Temp Files Directory: %s", 'simply-static' ), $temp_files_dir )
		);
	}

	public function is_temp_files_dir_writeable() {
		$temp_files_dir = Util::get_temp_dir();

		return array(
			'test'        => is_writable( $temp_files_dir ),
			'description' => sprintf( __( "Web server can write to Temp Files Directory: %s", 'simply-static' ), $temp_files_dir ),
			'error'       => sprintf( __( "Web server can't write to Temp Files Directory: %s", 'simply-static' ), $temp_files_dir )
		);
	}

	public function is_local_dir_writeable() {
		$local_dir = $this->options->get( 'local_dir' );

		return array(
			'test'        => is_writable( $local_dir ),
			'description' => sprintf( __( "Web server can write to Local Directory: %s", 'simply-static' ), $local_dir ),
			'error'       => sprintf( __( "Web server can not write to Local Directory: %s", 'simply-static' ), $local_dir )
		);
	}

	public function user_can_delete() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'delete' ),
			'description' => __( 'MySQL user has DELETE privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no DELETE privilege', 'simply-static' )
		);
	}

	public function user_can_insert() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'insert' ),
			'description' => __( 'MySQL user has INSERT privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no INSERT privilege', 'simply-static' )
		);
	}

	public function user_can_select() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'select' ),
			'description' => __( 'MySQL user has SELECT privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no SELECT privilege', 'simply-static' )
		);
	}

	public function user_can_create() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'create' ),
			'description' => __( 'MySQL user has CREATE privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no CREATE privilege', 'simply-static' )
		);
	}

	public function user_can_alter() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'alter' ),
			'description' => __( 'MySQL user has ALTER privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no ALTER privilege', 'simply-static' )
		);
	}

	public function user_can_drop() {
		return array(
			'test'        => Sql_Permissions::instance()->can( 'drop' ),
			'description' => __( 'MySQL user has DROP privilege', 'simply-static' ),
			'error'       => __( 'MySQL user has no DROP privilege', 'simply-static' )
		);
	}

	public function php_version() {
		return array(
			'test'        => version_compare( phpversion(), self::$min_version['php'], '>=' ),
			'description' => sprintf( __( 'PHP version is >= %s', 'simply-static' ), self::$min_version['php'] ),
			'error'       => sprintf( __( 'PHP version < %s', 'simply-static' ), self::$min_version['php'] )
		);
	}

	public function is_xml_active() {
		return array(
			'test'        => extension_loaded( 'xml' ) ? true : false,
			'description' => __( 'php-xml is available', 'simply-static' ),
			'error'       => __( 'php-xml is not available', 'simply-static' )
		);
	}

	public function has_curl() {
		if ( is_callable( 'curl_version' ) ) {
			$version = curl_version();
			$test    = version_compare( $version['version'], self::$min_version['curl'], '>=' );
		} else {
			$test = false;
		}

		return array(
			'test'        => $test,
			'description' => __( 'cURL is available', 'simply-static' ),
			'error'       => sprintf( __( 'cURL version < %s', 'simply-static' ), self::$min_version['curl'] )
		);
	}

	public function check_basic_auth_status() {
		$test    = true;
		$message = __( 'Basic Auth is not enabled.', 'simply-static' );

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

		// Check for NGINX, Apache, and IIS basic auth.
		if ( $basic_auth_on ) {
			$basic_auth_user = $this->options->get( 'http_basic_auth_username' );
			$basic_auth_pass = $this->options->get( 'http_basic_auth_password' );

			if ( empty( $basic_auth_user ) && empty( $basic_auth_pass ) ) {
				$test    = false;
				$message = __( 'Basic Auth is enabled, but no username or password is set in Simply Static -> Settings -> Debug -> Basic Auth', 'simply-static' );
			} else {
				$message = __( 'Basic Auth is enabled, and username and password are set in Simply Static -> Settings -> Debug -> Basic Auth', 'simply-static' );
			}
		}

		return array(
			'test'        => $test,
			'description' => $message,
			'error'       => $message
		);
	}

	/**
	 * Check status from response
	 *
	 * @param array $response given response.
	 *
	 * @return array
	 */
	public function check_error_from_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$test    = false;
			$message = sprintf( __( "Not a valid url.", 'simply-static' ) );
		} else {
			$code = $response['response']['code'];

			if ( $code == 200 ) {
				$test    = true;
				$message = $code;
			} else if ( in_array( $code, Page::$processable_status_codes ) ) {
				$test    = false;
				$message = sprintf( __( "Received a %s response. This might indicate a problem.", 'simply-static' ), $code );
			} else {
				$test    = true;
				$message = sprintf( __( "Received a %s response.", 'simply-static' ), $code );
			}
		}

		return array(
			'test'        => $test,
			'description' => $message,
			'error'       => $message
		);
	}
}
