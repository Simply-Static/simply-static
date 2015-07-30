<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static view class
 */
class Simply_Static_View {
	/**
	 * Layout (template) for the plugin
	 * @var string
	 */
	const LAYOUT = 'layouts/admin.phtml';

	/**
	 * Base directory for views
	 * @var string
	 */
	const DIRECTORY = 'views';

	/**
	 * View script extension
	 * @var string
	 */
	const EXTENSION = '.phtml';

	/**
	 * View variables array
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Absolute path for view
	 * @var string
	 */
	protected $path = null;

	/**
	 * Template file name to render
	 * @var string
	 */
	protected $template = null;

	/**
	 * Performs initialization of the absolute path for views
	 */
	public function __construct() {
		// Looking for a basic directory where plugin resides
		list($plugin_dir) = explode( '/', plugin_basename( __FILE__ ) );

		// making up an absolute path to views directory
		$path_array = array( WP_PLUGIN_DIR, $plugin_dir, self::DIRECTORY );

		$this->path = implode( '/', $path_array );
	}

	/**
	 * Sets a template filename that will be used later in render() method.
	 * Performs a reset of the view variables
	 *
	 * @param string $template The template filename, without extension
	 * @return Simply_Static_View
	 */
	public function set_template( $template ) {
		$this->template = trailingslashit( $this->path ) . $template . self::EXTENSION;
		return $this;
	}

	/**
	 * Returns a value of the option identified by $name
	 *
	 * @param string $name The option name
	 * @return mixed|null
	 */
	public function __get( $name ) {
		$value = array_key_exists( $name, $this->variables ) ? $this->variables[$name] : null;
		return $value;
	}

	/**
	* Updates the view variable identified by $name with the value provided in $value
	*
	* @param string $name The variable name
	* @param mixed  $value The variable value
	* @return Simply_Static_View
	*/
	public function __set( $name, $value ) {
		$this->variables[$name] = $value;
		return $this;
	}

	/**
	 * Updates the view variable identified by $name with the value provided in $value
	 *
	 * @param string $name The variable name
	 * @param mixed $value The variable value
	 * @return Simply_Static_View
	 */
	public function assign( $name, $value ) {
		return $this->__set( $name, $value );
	}

	/**
	 * Renders the view script
	 *
	 * @throws WP_Error
	 * @return Simply_Static_View
	 */
	public function render() {
		$layout = trailingslashit( $this->path ) . self::LAYOUT;

		if ( ! is_readable( $this->template ) ) {
			throw new WP_Error( "Can't find view template: " . $this->template );
		}

		include $layout;

		return $this;
	}
}
