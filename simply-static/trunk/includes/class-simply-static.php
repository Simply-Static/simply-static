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
		if ( null === self::$instance )
		{
			self::$instance = new self();
			self::$instance->includes();
			self::$instance->options = new Simply_Static_Options( self::SLUG );
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
	 * @param string $bootstrap_file
	 * @return Simply_Static
	 */
	public static function init( $bootstrap_file )
	{
		$instance = self::instance();

		// Activation
		register_activation_hook( $bootstrap_file, array( $instance, 'activate' ) );

		return $instance;
	}

	/**
	 * Performs activation
	 * @return void
	 */
	public function activate()
	{
		// Not installed?
		if ( null === $this->options->get( 'version' ) ) {
			$this->options
				->set( 'version', self::VERSION )
				->set( 'destination_scheme', '' )
				->set( 'destination_host', '' )
				->set( 'additional_urls', '' )
				->set( 'generate_zip', '0' )
				->set( 'retain_static_files', '0' )
				->save();
		}
	}

	/**
	 * Include required files
	 * @return void
	 */
	private function includes() {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-options.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-view.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-url-fetcher.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-archive-creator.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/misc-functions.php';
	}

	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_style( self::SLUG . '-admin-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css', array(), self::VERSION );
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
			array( self::$instance, 'display_generate_page' ),
			plugin_dir_url( dirname( __FILE__ ) ) . 'images/icon-16x16.png'
		);

		add_submenu_page(
			self::SLUG,
			__( 'Generate Static Site', self::SLUG ),
			__( 'Generate', self::SLUG ),
			'manage_options',
			self::SLUG,
			array( self::$instance, 'display_generate_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Settings', self::SLUG ),
			__( 'Settings', self::SLUG ),
			'manage_options',
			self::SLUG . '-options',
			array( self::$instance, 'display_options_page' )
		);
	}

	/**
	 * Render the page for generating a static site.
	 * @return void
	 */
	public function display_generate_page() {
		if ( isset( $_POST['generate'] ) ) {
			$archive_creator = new Simply_Static_Archive_Creator(
				self::SLUG,
				$this->options->get('destination_scheme'),
				$this->options->get('destination_host'),
				$this->options->get('additional_urls')
			);

			$archive_dir = $archive_creator->get_archive_directory();
			// fyi: archive_url could be a WP_Error
			$archive_url = $archive_creator->create_zip( $archive_dir );
			$deleted_successfully = $archive_creator->delete_static_files( $archive_dir );
		} else {
			$archive_dir = null;
			$archive_url = null;
		}

		$this->view
			->set_template( 'generate' )
			->assign( 'export_log', $archive_creator->get_export_log() )
			->assign( 'archive_url', $archive_url )
			->render();
	}

	/**
	 * Render the options page.
	 * @return void
	 */
	public function display_options_page() {
		if ( isset( $_POST['save'] ) ) {
			$this->save_options();
		}

		$this->view
			->set_template( 'options' )
			->assign( 'slug', self::SLUG )
			->assign( 'origin_scheme', sist_get_origin_scheme() )
			->assign( 'origin_host', sist_get_origin_host() )
			->assign( 'destination_scheme', $this->options->get( 'destination_scheme' ) )
			->assign( 'destination_host', $this->options->get( 'destination_host' ) )
			->assign( 'additional_urls', $this->options->get( 'additional_urls' ) )
			->assign( 'generate_zip', $this->options->get( 'generate_zip' ) )
			->assign( 'retain_static_files', $this->options->get( 'retain_static_files' ) )
			->render();
	}

	/**
	 * Save the options from the options page.
	 * @return void
	 */
	public function save_options() {
		$this->options
			->set( 'destination_scheme', filter_input( INPUT_POST, 'destination_scheme' ) )
			->set( 'destination_host', untrailingslashit( filter_input( INPUT_POST, 'destination_host', FILTER_SANITIZE_URL ) ) )
			->set( 'additional_urls', filter_input( INPUT_POST, 'additional_urls' ) )
			->set( 'generate_zip', filter_input( INPUT_POST, 'generate_zip' ) )
			->set( 'retain_static_files', filter_input( INPUT_POST, 'retain_static_files' ) )
			->save();
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
