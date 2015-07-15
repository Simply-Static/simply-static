<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static view class
 */
class Simply_Static_View
{
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
	 * Base directory for views
	 * @var string
	 */
	protected $directory = 'views';

	/**
	 * View script extension
	 * @var string
	 */
	protected $extension = '.phtml';

	/**
	 * Template file name to render
	 * @var string
	 */
	protected $template = null;

	/**
	 * Performs initialization of the absolute path for views
	 */
	public function __construct()
	{
		// Looking for a basic directory where plugin resides
		list($plugin_dir) = explode('/', plugin_basename(__FILE__));

		// making up an absolute path to views directory
		$path_array = array(WP_PLUGIN_DIR, $plugin_dir, $this->directory);

		$this->path = implode('/', $path_array);
	}

	/**
	 * Sets a template filename that will be used later in render() method.
	 * Performs a reset of the view variables
	 *
	 * @param string $template The template filename, without extension
	 * @return Simply_Static_View
	 */
	public function set_template($template)
	{
		$this->template = $template;
		$this->variables = array();
		return $this;
	}

	/**
	 * Updates the view variable identified by $name with the value provided in $value
	 *
	 * @param string $name The variable name
	 * @param mixed $value The variable value
	 * @return Simply_Static_View
	 */
	public function assign($name, $value)
	{
		$this->variables[$name] = $value;
		return $this;
	}

	/**
	 * Renders the view script
	 *
	 * @throws Simply_Static_Exception
	 * @return Simply_Static_View
	 */
	public function render()
	{
		$file = $this->path . '/' . $this->template . $this->extension;

		if (!is_readable($file))
		{
			throw new Simply_Static_Exception("Can't find view template: " . $file);
		}

		include $file;

		return $this;
	}

	// /**
	//  * Returns the rendered view script
	//  *
	//  * @return string
	//  */
	// public function fetch()
	// {
	// 	ob_start();

	// 	$this->render();
	// 	$contents = ob_get_contents();

	// 	ob_end_clean();

	// 	return $contents;
	// }
}