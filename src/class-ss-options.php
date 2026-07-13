<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static options class
 */
class Options {
	/**
	 * Singleton instance
	 * @var \Simply_Static\Options
	 */
	protected static $instance = null;

	/**
	 * Options array
	 * @var array
	 */
	protected $options = array();

	/** @var array<string,bool> Keys changed by this instance since its last save. */
	protected $dirty_keys = array();

	/** @var array<string,bool> Keys removed by this instance since its last save. */
	protected $deleted_keys = array();

	/** @var bool Whether set_options() requested an intentional full replacement. */
	protected $replace_all = false;

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Return an instance of Simply_Static\Options
	 * @return Simply_Static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->options = self::load_runtime_options();
		}

		return self::$instance;
	}

	/**
	 * Return a fresh instance of Simply_Static\Options
	 * @return Simply_Static
	 */
	public static function reinstance() {
		self::$instance = null;

		return self::instance();
	}

	/**
	 * Updates the option identified by $name with the value provided in $value
	 *
	 * @param string $name The option name
	 * @param mixed $value The option value
	 *
	 * @return \Simply_Static\Options
	 */
	public function set( $name, $value ) {
		$this->options[ $name ] = $value;
		$this->dirty_keys[ $name ] = true;
		unset( $this->deleted_keys[ $name ] );

		return $this;
	}

	/**
	 * Set all options.
	 *
	 * @param array $options All options.
	 *
	 * @return \Simply_Static\Options
	 */
	public function set_options( $options ) {
		$this->options     = is_array( $options ) ? $options : array();
		$this->dirty_keys  = array_fill_keys( array_keys( $this->options ), true );
		$this->deleted_keys = array();
		$this->replace_all = true;

		return $this;
	}

	/**
	 * Returns a value of the option identified by $name
	 *
	 * Also checks if option exists in wp-config.php, and uses it to override the database value
	 *
	 * Fore example:
	 * SIMPLY_STATIC_TEMP_FILES_DIR     in wp-config.php overrides temp_files_dir loaded from database
	 * SIMPLY_STATIC_DELIVERY_METHOD    in wp-config.php overrides delivery_method loaded from database
	 *
	 * @param string $name The option name
	 *
	 * @return mixed|null
	 */
	public function get( $name = '' ) {
		$normalized_name = strtoupper( $name );
		$constant_name   = 'SIMPLY_STATIC_' . $normalized_name;

		// Constants are runtime configuration and do not need a placeholder key
		// in wp_options. This also keeps secret overrides out of database exports.
		if ( 'VERSION' !== $normalized_name && defined( $constant_name ) ) {
			return constant( $constant_name );
		}

		if ( ! array_key_exists( $name, $this->options ) ) {
			return null;
		}

		return apply_filters( 'ss_get_option_' . strtolower( $name ), $this->options[ $name ], $this );
	}

	/**
	 * Destroy an option
	 *
	 * @param string $name The option name to destroy
	 *
	 * @return boolean true if the key existed, false if it didn't
	 */
	public function destroy( $name ) {
		if ( array_key_exists( $name, $this->options ) ) {
			unset( $this->options[ $name ] );
			unset( $this->dirty_keys[ $name ] );
			$this->deleted_keys[ $name ] = true;

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns all options as an array
	 * @return array
	 */
	public function get_as_array() {
		return $this->options;
	}

	/**
	 * Saves the internal options data to the wp_options table
	 * @return boolean
	 */
	public function save() {
		$network = is_network_admin();
		$current = $this->load_current_options_for_merge( $network );
		$current = is_array( $current ) ? $current : array();

		if ( $this->replace_all ) {
			$merged = $this->options;
		} else {
			$merged = $current;
			foreach ( array_keys( $this->dirty_keys ) as $name ) {
				if ( array_key_exists( $name, $this->options ) ) {
					$merged[ $name ] = $this->options[ $name ];
				}
			}
			foreach ( array_keys( $this->deleted_keys ) as $name ) {
				unset( $merged[ $name ] );
			}
		}

		$saved = $network ? update_site_option( Plugin::SLUG, $merged ) : update_option( Plugin::SLUG, $merged );
		if ( ! $saved ) {
			$stored = $this->load_current_options_for_merge( $network );
			$saved  = is_array( $stored ) && $stored === $merged;
		}

		if ( $saved ) {
			// The persisted row deliberately excludes values supplied by runtime
			// filters. Reload the public option view so those values remain available
			// to long-running background workers after they save export progress.
			$this->options      = self::load_runtime_options( $network );
			$this->dirty_keys   = array();
			$this->deleted_keys = array();
			$this->replace_all  = false;
		}

		return $saved;
	}

	/**
	 * Load the filtered runtime option view.
	 *
	 * @param bool $network Whether to read the network option.
	 * @return array
	 */
	protected static function load_runtime_options( $network = false ) {
		$db_options = $network ? get_site_option( Plugin::SLUG ) : get_option( Plugin::SLUG );
		$options    = apply_filters( 'ss_get_options', $db_options );

		return is_array( $options ) ? $options : array();
	}

	/**
	 * Load the latest database value before merging dirty keys.
	 *
	 * A long-running worker's request-local alloptions cache can predate an
	 * administrator save. Reading the row directly avoids merging against that
	 * stale cache without globally evicting every autoloaded WordPress option.
	 * Network-admin writes are rare and retain the network-option API fallback.
	 *
	 * @param bool $network Whether this is a network-admin option write.
	 * @return array
	 */
	protected function load_current_options_for_merge( $network ) {
		if ( $network ) {
			$current = get_site_option( Plugin::SLUG );
			return is_array( $current ) ? $current : array();
		}

		global $wpdb;
		if (
			isset( $wpdb->options )
			&& method_exists( $wpdb, 'prepare' )
			&& method_exists( $wpdb, 'get_var' )
		) {
			$raw = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
					Plugin::SLUG
				)
			);
			$fresh = is_string( $raw ) ? maybe_unserialize( $raw ) : $raw;
			if ( is_array( $fresh ) ) {
				return $fresh;
			}
			if ( null === $raw ) {
				return array();
			}
		}

		$current = get_option( Plugin::SLUG );

		return is_array( $current ) ? $current : array();
	}

	/**
	 * Get the current path to the temp static archive directory
	 * @return string The path to the temp static archive directory
	 */
	public function get_archive_dir() {
		$temp_files_dir = Util::get_temp_dir();

		return Util::add_trailing_directory_separator( $temp_files_dir . apply_filters( 'ss_archive_file_name', $this->get( 'archive_name' ) ) );
	}

	/**
	 * Get the destination URL (scheme + host)
	 * @return string The destination URL
	 */
	public function get_destination_url() {

		switch ( $this->get( 'destination_url_type' ) ) {
			case 'absolute':
				return untrailingslashit( $this->get( 'destination_scheme' ) . $this->get( 'destination_host' ) );
				break;
			case 'relative':
				return $this->get( 'relative_path' );
		}

		return './';
	}

	/**
	 * Add status message
	 *
	 * @param string $message The status message to add.
	 * @param string $task_name The name of the task associated with the status message.
	 * @param bool $unique Whether the status message should be unique (default: false).
	 *
	 * @return $this
	 */
	public function add_status_message( $message, $task_name, $unique = false ) : self
	{
		$messages = $this->get( 'archive_status_messages' );
		$messages = Util::add_archive_status_message( $messages, $task_name, $message, $unique );
		return $this->set( 'archive_status_messages', $messages );
	}
}
