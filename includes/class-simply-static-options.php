<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static options class
 * @package Simply_Static
 */
class Simply_Static_Options {
	/**
	 * Singleton instance
	 * @var Simply_Static_Options
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
	protected function __construct() {}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Return an instance of Simply_Static_Options
	 * @return Simply_Static
	 */
	public static function instance()
	{
		if ( null === self::$instance ) {
			self::$instance = new self();

			$options = get_option( Simply_Static::SLUG );
			if ( false === $options ) {
				$options = array();
			}

			self::$instance->options = $options;
		}

		return self::$instance;
	}

	/**
	 * Updates the option identified by $name with the value provided in $value
	 * @param string $name The option name
	 * @param mixed $value The option value
	 * @return Simply_Static_Options
	 */
	public function set( $name, $value ) {
		$this->options[ $name ] = $value;
		return $this;
	}

	/**
	 * Returns a value of the option identified by $name
	 * @param string $name The option name
	 * @return mixed|null
	 */
	public function get( $name ) {
		return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : null;
	}

	/**
	 * Saves the internal options data to the wp_options table
	 * @return boolean
	 */
	public function save() {
		return update_option( Simply_Static::SLUG, $this->options );
	}

	/**
	 * Get the current path to the temp static archive directory
	 * @return string The path to the temp static archive directory
	 */
	public function get_archive_dir() {
		return sist_add_trailing_directory_separator( $this->get( 'temp_files_dir' ) . $this->get( 'archive_name' )  );
	}
}
