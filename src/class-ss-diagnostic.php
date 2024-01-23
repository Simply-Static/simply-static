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
		'php'  => '8.0',
		'curl' => '7.6'
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
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	public function __construct() {
		$this->options = Options::instance();

		$this->checks = array(
			'URLs'       => array(
				__( 'SSL', 'simply-static' ) => $this->is_ssl()
			),
			'PHP'        => array(
				__( 'VERSION', 'simply-static' ) => $this->php_version(),
				__( 'php-xml', 'simply-static' ) => $this->is_xml_active(),
				__( 'cURL', 'simply-static' )    => $this->has_curl(),
			),
			'WordPress'  => array(
				__( 'Permalinks', 'simply-static' )         => $this->is_permalink_structure_set(),
				__( 'WP-CRON', 'simply-static' )            => $this->is_wp_cron_running(),
				__( 'WP REST API', 'simply-static' )        => $this->is_wp_rest_running(),
				__( 'Requests to itself', 'simply-static' ) => $this->can_wp_make_requests_to_itself(),
			),
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
			),

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

	public function is_ssl() {
		return array(
			'test'        => is_ssl(),
			'description' => __( 'You have a valid SSL certificate.', 'simply-static' ),
			'error'       => __( 'You need an SSL certificate to connect with external APIs like GitHub or Algolia.', 'simply-static' )
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

	public function is_wp_cron_running() {
		if ( ! defined( 'DISABLE_WP_CRON' ) || DISABLE_WP_CRON !== true || defined( 'SS_CRON' ) ) {
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

	/**
	 * Is Rest API up and running.
	 * @return array
	 */
	public function is_wp_rest_running() {
		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$is_rest = false;
		} else {
			$is_rest = true;
		}

		return array(
			'test'        => $is_rest,
			'description' => __( 'Rest API is available and running', 'simply-static' ),
			'error'       => __( 'Rest API is disabled or blocked', 'simply-static' ),
		);
	}

	/**
	 * Check if WP can make requests.
	 *
	 * @return array
	 */
	public function can_wp_make_requests_to_itself() {
		$ip_address = getHostByName( getHostName() );
		$url        = Util::origin_url();
		$response   = Url_Fetcher::remote_get( $url );

		$infos = $this->check_error_from_response( $response );

		return array(
			'test'        => $infos['test'],
			'description' => sprintf( __( "WordPress can make requests to itself from %s", 'simply-static' ), $ip_address ),
			'error'       => sprintf( __( "WordPress can not make requests to itself from %s", 'simply-static' ), $ip_address ),
		);
	}

	public function is_temp_files_dir_readable() {
		$temp_files_dir = $this->options->get( 'temp_files_dir' );

		return array(
			'test'        => is_readable( $temp_files_dir ),
			'description' => sprintf( __( "Web server can read from Temp Files Directory: %s", 'simply-static' ), $temp_files_dir ),
			'error'       => sprintf( __( "Web server can't read from Temp Files Directory: %s", 'simply-static' ), $temp_files_dir )
		);
	}

	public function is_temp_files_dir_writeable() {
		$temp_files_dir = $this->options->get( 'temp_files_dir' );

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
