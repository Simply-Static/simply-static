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
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * The slug of the plugin; used in actions, filters, i18n, etc.
	 * @var string
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
			// Enqueue admin styles
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_styles' ) );
			// Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_scripts' ) );
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
				->set( 'temp_files_dir', trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' ) )
				->set( 'additional_urls', '' )
				->set( 'delivery_method', 'zip' )
				->set( 'local_dir', '' )
				->set( 'delete_temp_files', '1' )
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
	 * @return null Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_style( self::SLUG . '-admin-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css', array(), self::VERSION );
	}

	/**
	 * Enqueue admin-specific javascript files for this plugin's admin pages only
	 * @return null Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_script( self::SLUG . '-admin-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'js/admin.js', array(), self::VERSION );
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
				$this->options->get( 'destination_scheme' ),
				$this->options->get( 'destination_host' ),
				$this->options->get( 'temp_files_dir' ),
				$this->options->get( 'additional_urls' )
			);
			$archive_creator->create_archive();

			// TODO: archive_url could be a WP_Error
			if ( $this->options->get( 'delivery_method' ) == 'zip' ) {

				$archive_url = $archive_creator->create_zip();
				if ( is_wp_error( $archive_url ) ) {
					$error = $archive_url->get_error_message();
					$this->view->add_flash( 'error', $error );
				} else {
					$message = __( 'Archive created.', self::SLUG );
					$message .= ' <a href="' . $archive_url . '">' . __( 'Download archive', self::SLUG ) . '</a>';
					$this->view->add_flash( 'updated', $message );
				}

			} elseif ( $this->options->get( 'delivery_method' ) == 'local' ) {

				$local_dir = $this->options->get( 'local_dir' );
				$result = $archive_creator->copy_static_files( $local_dir );

				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_message();
					$this->view->add_flash( 'error', $error );
				} else {
					$message = __( 'Static files copied to: ' . $local_dir, self::SLUG );
					$this->view->add_flash( 'updated', $message );
				}

			}

			if ( $this->options->get( 'delete_temp_files' ) == '1' ) {
				$deleted_successfully = $archive_creator->delete_static_files();
			}

			$export_log = $archive_creator->get_export_log();
			$this->view->assign( 'export_log', $export_log );
		}

		$this->view
			->set_template( 'generate' )
			->render();
	}

	/**
	 * Render the options page.
	 * @return void
	 */
	public function display_options_page() {
		if ( isset( $_POST['save'] ) ) {
			$this->save_options();
			$message = __( 'Settings saved.', self::SLUG );
			$this->view->add_flash( 'updated', $message );
		}

		$this->view
			->set_template( 'options' )
			->assign( 'slug', self::SLUG )
			->assign( 'origin_scheme', sist_get_origin_scheme() )
			->assign( 'origin_host', sist_get_origin_host() )
			->assign( 'destination_scheme', $this->options->get( 'destination_scheme' ) )
			->assign( 'destination_host', $this->options->get( 'destination_host' ) )
			->assign( 'temp_files_dir', $this->options->get( 'temp_files_dir' ) )
			->assign( 'additional_urls', $this->options->get( 'additional_urls' ) )
			->assign( 'delivery_method', $this->options->get( 'delivery_method' ) )
			->assign( 'local_dir', $this->options->get( 'local_dir' ) )
			->assign( 'delete_temp_files', $this->options->get( 'delete_temp_files' ) )
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
			->set( 'temp_files_dir', sist_trailingslashit_unless_blank( filter_input( INPUT_POST, 'temp_files_dir' ) ) )
			->set( 'additional_urls', filter_input( INPUT_POST, 'additional_urls' ) )
			->set( 'delivery_method', filter_input( INPUT_POST, 'delivery_method' ) )
			->set( 'local_dir', sist_trailingslashit_unless_blank( filter_input( INPUT_POST, 'local_dir' ) ) )
			->set( 'delete_temp_files', filter_input( INPUT_POST, 'delete_temp_files' ) )
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

	public function check_system_requirements() {
		$errors = [];
		if ( ! is_writable( $this->temp_files_dir ) ) {
			sprintf( __( 'Your temporary save directory is not writeable: %s' , $this->slug ), $this->temp_files_dir );
		}

		return $errors;
	}
}
