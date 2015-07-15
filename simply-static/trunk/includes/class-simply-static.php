<?php
/**
 * The core plugin class.
 *
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Simply_Static {
	/**
	 * Plugin version
	 */
	const VERSION = '1.0.0';

	/**
	 * The slug of the plugin; used in actions, filters, i18n, etc.
	 */
	const SLUG = 'simply-static';

	/**
	 * Singleton instance
	 * @var Simply_Static
	 */
	protected static $instance = null;

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static_Options
	 */
	protected $options = null;

	/**
	 * View object
	 * @var Simply_Static_View
	 */
	protected $view = null;

	/**
	 * Export log (list of processed urls)
	 * @var array
	 */
	protected $export_log = array();

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
	 * Return an instance of the Simply Static plugin
	 * @return Simply_Static
	 */
	public static function instance()
	{
		if (null === self::$instance)
		{
			self::$instance = new self();
			self::$instance->includes();
			self::$instance->options = new Simply_Static_Options(self::SLUG);
			self::$instance->view = new Simply_Static_View();

			// Load the text domain for i18n
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			// Enqueue admin styles and scripts
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_styles' ) );
			// Add the options page and menu item.
			add_action( 'admin_menu', array( self::$instance, 'add_plugin_admin_menu' ), 2 );


		}

		return self::$instance;
	}

	/**
	 * Initialize singleton instance
	 * @param string $bootstrapFile
	 * @return Simply_Static
	 */
	public static function init($bootstrapFile)
	{
		$instance = self::instance();

		// Activation
		register_activation_hook($bootstrapFile, array($instance, 'activate'));

		return $instance;
	}

	/**
	 * Performs activation
	 * @return void
	 */
	public function activate()
	{
		// Not installed?
		//if (null === $this->options->get_option('version'))
		//{
			$this->options
				->set('version', self::VERSION)
				// ->setOption('baseUrl', home_url())
				// ->setOption('additionalUrls', '')
				// ->setOption('generateZip', '')
				// ->setOption('retainStaticFiles', '')
				// ->setOption('sendViaFTP', '')
				// ->setOption('ftpServer', '')
				// ->setOption('ftpUsername', '')
				// ->setOption('ftpPassword', '')
				// ->setOption('ftpRemotePath', '')
				->save();
		//}
	}


	/**
	 * Include required files
	 * @return void
	 */
	private function includes() {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-options.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-view.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-url-request.php';
	}

	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_style( self::SLUG . '-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		// Add main menu item
		add_menu_page(
			__( 'Simply Static Settings', self::SLUG ),
			__( 'Simply Static', self::SLUG ),
			'manage_options',
			self::SLUG,
			array( self::$instance, 'display_plugin_admin_page' ),
			plugins_url( 'images/icon-16x16.png', __FILE__ )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Settings', self::SLUG ),
			__( 'Settings', self::SLUG ),
			'manage_options',
			self::SLUG,
			array( self::$instance, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Render the admin pages for this plugin.
	 * @return void
	 */
	public function display_plugin_admin_page() {
		$this->view
			->set_template('options')
			->assign('slug', self::SLUG)
			->render();
	}

	/**
	 * Loads the plugin language files
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			self::SLUG,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
