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

			$db_options = get_option( Plugin::SLUG );

			$options = apply_filters( 'ss_get_options', $db_options );
			if ( false === $options ) {
				$options = array();
			}

			self::$instance->options = $options;
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
		$this->options = $options;

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
		return array_key_exists( $name, $this->options ) ?
			(
			defined( 'SIMPLY_STATIC_' . strtoupper( $name ) ) ?
				constant( 'SIMPLY_STATIC_' . strtoupper( $name ) ) :
				apply_filters( 'ss_get_option_' . strtolower( $name ), $this->options[ $name ], $this )
			)
			: null;
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
		return is_network_admin() ? update_site_option( Plugin::SLUG, $this->options ) : update_option( Plugin::SLUG, $this->options );
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
     * @param $message
     * @param $task_name
     * @return $this
     */
    public function add_status_message( $message, $task_name ) : self
    {
        $messages = $this->get( 'archive_status_messages' );
        $messages = Util::add_archive_status_message( $messages, $task_name, $message );
        return $this->set( 'archive_status_messages', $messages );
    }
}
