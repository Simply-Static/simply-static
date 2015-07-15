<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static options class
 */
class Simply_Static_Options {
	/**
	 * Options array
	 * @var array
	 */
	protected $options = array();

	/**
	 * Defines options record in the wp_options table
	 * @var string
	 */
	protected $option_key = null;

	/**
	 * Performs initializion of the options structure
	 *
	 * @param string $option_key The options key name
	 */
	public function __construct( $option_key ) {
		$options = get_option( $option_key );

		if ( false === $options ) {
			$options = array();
		}

		$this->options = $options;
		$this->option_key = $option_key;
	}

	/**
	 * Updates the option identified by $name with the value provided in $value
	 *
	 * @param string $name The option name
	 * @param mixed $value The option value
	 * @return Simply_Static_Options
	 */
	public function set( $name, $value ) {
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * Returns a value of the option identified by $name
	 *
	 * @param string $name The option name
	 * @return mixed|null
	 */
	public function get( $name ) {
		return array_key_exists( $name, $this->options ) ? $this->options[$name] : null;
	}

	/**
	 * Saves the internal options data to the wp_options table using the stored $option_key value as the key
	 *
	 * @return boolean
	 */
	public function save() {
		return update_option( $this->option_key, $this->options );
	}

	/**
	 * Deletes the internal options data from the wp_options table.
	 * This method is intended to be used as part of the uninstall process.
	 *
	 * @return boolean
	 */
	public function delete() {
		return delete_option( $this->option_key );
	}
}
