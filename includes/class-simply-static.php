<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The core plugin class
 * @package Simply_Static
 */
class Simply_Static {
	/**
	 * Plugin version
	 * @var string
	 */
	const VERSION = '1.7.0';

	/**
	 * The slug of the plugin; used in actions, filters, i18n, table names, etc.
	 * @var string
	 */
	const SLUG = 'simply-static'; // keep it short; stick to alphas & dashes

	// Base 64 encoded SVG image.
	const ICON_SVG = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgaWQ9InN2ZzM0MzQiICAgdmVyc2lvbj0iMS4xIiAgIGlua3NjYXBlOnZlcnNpb249IjAuOTEgcjEzNzI1IiAgIHdpZHRoPSIxODAiICAgaGVpZ2h0PSIzMDAiICAgdmlld0JveD0iMCAwIDE4MCAzMDAiICAgc29kaXBvZGk6ZG9jbmFtZT0iYm9sdC12ZWN0b3ItZ3JheS5zdmciPiAgPG1ldGFkYXRhICAgICBpZD0ibWV0YWRhdGEzNDQwIj4gICAgPHJkZjpSREY+ICAgICAgPGNjOldvcmsgICAgICAgICByZGY6YWJvdXQ9IiI+ICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4gICAgICAgIDxkYzp0eXBlICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPiAgICAgICAgPGRjOnRpdGxlIC8+ICAgICAgPC9jYzpXb3JrPiAgICA8L3JkZjpSREY+ICA8L21ldGFkYXRhPiAgPGRlZnMgICAgIGlkPSJkZWZzMzQzOCIgLz4gIDxzb2RpcG9kaTpuYW1lZHZpZXcgICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIgICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IiAgICAgYm9yZGVyb3BhY2l0eT0iMSIgICAgIG9iamVjdHRvbGVyYW5jZT0iMTAiICAgICBncmlkdG9sZXJhbmNlPSIxMCIgICAgIGd1aWRldG9sZXJhbmNlPSIxMCIgICAgIGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwIiAgICAgaW5rc2NhcGU6cGFnZXNoYWRvdz0iMiIgICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTUzNiIgICAgIGlua3NjYXBlOndpbmRvdy1oZWlnaHQ9IjgwMSIgICAgIGlkPSJuYW1lZHZpZXczNDM2IiAgICAgc2hvd2dyaWQ9ImZhbHNlIiAgICAgZml0LW1hcmdpbi10b3A9IjAiICAgICBmaXQtbWFyZ2luLWxlZnQ9IjAiICAgICBmaXQtbWFyZ2luLXJpZ2h0PSIwIiAgICAgZml0LW1hcmdpbi1ib3R0b209IjAiICAgICBpbmtzY2FwZTp6b29tPSIyLjE0MjM3MjkiICAgICBpbmtzY2FwZTpjeD0iOC44Njg2Njc5IiAgICAgaW5rc2NhcGU6Y3k9IjE0Ny41MDAwMSIgICAgIGlua3NjYXBlOndpbmRvdy14PSItOCIgICAgIGlua3NjYXBlOndpbmRvdy15PSItOCIgICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjEiICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJzdmczNDM0IiAvPiAgPHBhdGggICAgIHN0eWxlPSJmaWxsOiM5Y2ExYTY7ZmlsbC1vcGFjaXR5OjEiICAgICBkPSJNIDM5LjksMjMzLjUgODQuNDMzMzMzLDE2MS4xMzMzMyAzOS45LDE2MS4xMzMzMyAxNDAuMSw2Ni41IGwgLTQ0LjUzMzMzMyw3Mi4zNjY2NyA0NC41MzMzMzMsMCB6IiAgICAgaWQ9InBhdGgzNDQ2IiAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgIHNvZGlwb2RpOm5vZGV0eXBlcz0iY2NjY2NjYyIgLz48L3N2Zz4=';

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
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->includes();
			self::$instance->options = new Simply_Static_Options( self::SLUG );
			self::$instance->view = new Simply_Static_View();

			// Check for pending file download
			add_action( 'plugins_loaded', array( self::$instance, 'download_file' ) );
			// Load the text domain for i18n
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			// Enqueue admin styles
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_styles' ) );
			// Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_scripts' ) );
			// Add the options page and menu item.
			add_action( 'admin_menu', array( self::$instance, 'add_plugin_admin_menu' ), 2 );
			// Handle AJAX requests
			add_action( 'wp_ajax_generate_static_archive', array( self::$instance, 'generate_static_archive' ) );
			add_action( 'wp_ajax_render_export_log', array( self::$instance, 'render_export_log' ) );
			add_action( 'wp_ajax_render_activity_log', array( self::$instance, 'render_activity_log' ) );

			self::$instance->activate();
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
		return $instance;
	}

	/**
	 * Create settings and setup database
	 * @return void
	 */
	private function activate() {
		$version = $this->options->get( 'version' );

		// Never installed or options key changed
		if ( null === $version ) {
			// checking for legacy options key
			$old_ss_options = get_option( 'simply_static' );

			if ( $old_ss_options ) { // options key changed
				update_option( 'simply-static', $old_ss_options );
				delete_option( 'simply_static' );

				// update Simply_Static_Options again to pull in updated data
				$this->options = new Simply_Static_Options( self::SLUG );
			} else { // never installed
				$this->options
					->set( 'destination_scheme', sist_origin_scheme() )
					->set( 'destination_host', sist_origin_host() )
					->set( 'temp_files_dir', trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' ) )
					->set( 'additional_urls', '' )
					->set( 'delivery_method', 'zip' )
					->set( 'local_dir', '' )
					->set( 'delete_temp_files', '1' );
			}
		}

		// perform migrations if our saved version # doesn't match the current version
		if ( version_compare( $version, self::VERSION, '<' ) ) {

			// version 1.2 introduced the ability to specify additional files
			if ( version_compare( $version, '1.2.0', '<' ) ) {
				$this->options
					->set( 'additional_files', '' );
			}

			if ( version_compare( $version, '1.3.3', '<' ) ) {
				// version 1.3 added a database table for tracking urls/progress
				Simply_Static_Page::create_table();
			}

			if ( version_compare( $version, '1.4.0', '<' ) ) {
				// check for, and add, the WP emoji url if it's missing
				$emoji_url = includes_url( 'js/wp-emoji-release.min.js' );
				$additional_urls = $this->options->get( 'additional_urls' );
				$urls_array = sist_string_to_array( $additional_urls );

				if ( ! in_array( $emoji_url, $urls_array ) ) {
					$additional_urls = $emoji_url . "\n" . $additional_urls;
					$this->options->set( 'additional_urls', $additional_urls );
				}
			}

			if ( version_compare( $version, '1.4.0', '<' ) ) {
				$this->options
					->set( 'debugging_mode', '' );
			}

			if ( version_compare( $version, '1.7.0', '<' ) ) {
				$scheme = $this->options->get( 'destination_scheme' );
				$scheme = $scheme . '://';
				$this->options->set( 'destination_scheme', $scheme );
				$this->options->set( 'relative_path', '' );

				$host = $this->options->get( 'destination_host' );
				if ( $host == sist_origin_host() ) {
					$this->options->set( 'destination_url_type', 'relative' );
				} else {
					$this->options->set( 'destination_url_type', 'absolute' );
				}
			}
		}

		// always update the version and save
		$this->options
			->set( 'version', self::VERSION )
			->save();
	}

	/**
	 * Include required files
	 * @return void
	 */
	private function includes() {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libraries/pclzip.lib.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libraries/phpuri.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libraries/PhpSimple/HtmlDomParser.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-options.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-view.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-url-extractor.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-url-fetcher.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-url-response.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-archive-creator.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-query.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-model.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-page.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-archive-manager.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-diagnostic.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simply-static-sql-permissions.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/misc-functions.php';
	}

	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only
	 * @return void
	 */
	public function enqueue_admin_styles() {
		// Plugin admin CSS. Tack on plugin version.
		wp_enqueue_style( self::SLUG . '-admin-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css', array(), self::VERSION );
	}

	/**
	 * Enqueue admin-specific javascript files for this plugin's admin pages only
	 * @return void
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
			__( 'Simply Static', self::SLUG ),
			__( 'Simply Static', self::SLUG ),
			'edit_posts',
			self::SLUG,
			array( self::$instance, 'display_generate_page' ),
			self::ICON_SVG
		);

		add_submenu_page(
			self::SLUG,
			__( 'Generate Static Site', self::SLUG ),
			__( 'Generate', self::SLUG ),
			'edit_posts',
			self::SLUG,
			array( self::$instance, 'display_generate_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Settings', self::SLUG ),
			__( 'Settings', self::SLUG ),
			'manage_options',
			self::SLUG . '_settings',
			array( self::$instance, 'display_settings_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Simply Static Diagnostics', self::SLUG ),
			__( 'Diagnostics', self::SLUG ),
			'manage_options',
			self::SLUG . '_diagnostics',
			array( self::$instance, 'display_diagnostics_page' )
		);
	}

	/**
	 * Handle requests for generating a static archive and send a response via ajax
	 *
	 * Expects a POST param called 'perform' which is one of: start, continue,
	 * cancel. Those actions are passed along to the Archive Manager which will
	 * dole out small portions of work to the Archive Creator, slowly building
	 * out a static archive over repeated requests.
	 * @return void
	 */
	function generate_static_archive() {
		$action = $_POST['perform'];

		$archive_manager = new Simply_Static_Archive_Manager( $this->options );

		$archive_manager->perform( $action );

		$state_name = $archive_manager->get_state_name();
		$done = $archive_manager->has_finished();

		$activity_log_html = $this->view
			->set_template( '_activity_log' )
			->assign( 'status_messages', $archive_manager->get_status_messages() )
			->render_to_string();

		// send json response and die()
		wp_send_json( array(
			'state_name' => $state_name,
			'activity_log_html' => $activity_log_html,
			'done' => $done
		) );
	}

	/**
	 * Render the activity log and send it via ajax
	 * @return void
	 */
	public function render_activity_log() {
		$archive_manager = new Simply_Static_Archive_Manager( $this->options );

		$content = $this->view
			->set_template( '_activity_log' )
			->assign( 'status_messages', $archive_manager->get_status_messages() )
			->render_to_string();

		// send json response and die()
		wp_send_json( array(
			'html' => $content
		) );
	}

	/**
	 * Render the export log and send it via ajax
	 * @return void
	 */
	public function render_export_log() {
		$per_page = $_POST['per_page'];
		$current_page = $_POST['page'];
		$offset = ( intval( $current_page ) - 1 ) * intval( $per_page );

		$static_pages = Simply_Static_Page::query()
			->limit( $per_page )
			->offset( $offset )
			->find();
		$http_status_codes = Simply_Static_Page::get_http_status_codes_summary();
		$total_static_pages = array_sum( array_values( $http_status_codes ) );
		$total_pages = ceil( $total_static_pages / $per_page );

		$content = $this->view
			->set_template( '_export_log' )
			->assign( 'static_pages', $static_pages )
			->assign( 'http_status_codes', $http_status_codes )
			->assign( 'current_page' , $current_page )
			->assign( 'total_pages', $total_pages )
			->assign( 'total_static_pages', $total_static_pages )
			->render_to_string();

		// send json response and die()
		wp_send_json( array(
			'html' => $content
		) );
	}

	/**
	 * Render the page for generating a static site
	 * @return void
	 */
	public function display_generate_page() {
		$archive_manager = new Simply_Static_Archive_Manager( $this->options );

		$this->view
			->set_layout( 'admin' )
			->set_template( 'generate' )
			->assign( 'archive_generation_ready_to_start', $archive_manager->has_finished() )
			->render();
	}

	/**
	 * Render the options page
	 * @return void
	 */
	public function display_settings_page() {
		if ( isset( $_POST['_settings'] ) ) {
			$this->save_options();
			$message = __( 'Settings saved.', self::SLUG );
			$this->view->add_flash( 'updated', $message );
		}

		$this->view
			->set_layout( 'admin' )
			->set_template( 'settings' )
			->assign( 'origin_scheme', sist_origin_scheme() )
			->assign( 'origin_host', sist_origin_host() )
			->assign( 'destination_scheme', $this->options->get( 'destination_scheme' ) )
			->assign( 'destination_host', $this->options->get( 'destination_host' ) )
			->assign( 'temp_files_dir', $this->options->get( 'temp_files_dir' ) )
			->assign( 'additional_urls', $this->options->get( 'additional_urls' ) )
			->assign( 'additional_files', $this->options->get( 'additional_files' ) )
			->assign( 'delivery_method', $this->options->get( 'delivery_method' ) )
			->assign( 'local_dir', $this->options->get( 'local_dir' ) )
			->assign( 'delete_temp_files', $this->options->get( 'delete_temp_files' ) )
			->assign( 'destination_url_type', $this->options->get( 'destination_url_type' ) )
			->assign( 'relative_path', $this->options->get( 'relative_path' ) )
			->render();
	}

	public function display_diagnostics_page() {
		if ( isset( $_POST['_diagnostics'] ) ) {
			$this->save_diagnostics();
			$message = __( 'Settings saved.', self::SLUG );
			$this->view->add_flash( 'updated', $message );
		}

		$diagnostic = new Simply_Static_Diagnostic( $this->options );
		$results = $diagnostic->results;

		$this->view
			->set_layout( 'admin' )
			->set_template( 'diagnostics' )
			->assign( 'results', $results )
			->render();
	}

	/**
	 * Save the options from the options page
	 * @return void
	 */
	public function save_options() {
		$destination_url_type = filter_input( INPUT_POST, 'destination_url_type' );

		if ( $destination_url_type == 'offline' ) {
			$destination_scheme = '';
			$destination_host = '.';
		} else if ( $destination_url_type == 'relative' ) {
			$destination_scheme = '';
			$destination_host = '';
		} else {
			$destination_scheme = filter_input( INPUT_POST, 'destination_scheme' );
			$destination_host = untrailingslashit( filter_input( INPUT_POST, 'destination_host', FILTER_SANITIZE_URL ) );
		}

		$relative_path = filter_input( INPUT_POST, 'relative_path' );
		$relative_path = untrailingslashit( sist_add_leading_slash( $relative_path ) );

		$this->options
			->set( 'destination_scheme', $destination_scheme )
			->set( 'destination_host', $destination_host )
			->set( 'temp_files_dir', sist_trailingslashit_unless_blank( filter_input( INPUT_POST, 'temp_files_dir' ) ) )
			->set( 'additional_urls', filter_input( INPUT_POST, 'additional_urls' ) )
			->set( 'additional_files', filter_input( INPUT_POST, 'additional_files' ) )
			->set( 'delivery_method', filter_input( INPUT_POST, 'delivery_method' ) )
			->set( 'local_dir', sist_trailingslashit_unless_blank( filter_input( INPUT_POST, 'local_dir' ) ) )
			->set( 'delete_temp_files', filter_input( INPUT_POST, 'delete_temp_files' ) )
			->set( 'destination_url_type', $destination_url_type )
			->set( 'relative_path', $relative_path )
			->save();
	}

	/**
	 * Save the options from the options page
	 * @return void
	 */
	public function save_diagnostics() {
		$this->options
			->set( 'debugging_mode', filter_input( INPUT_POST, 'debugging_mode' ) )
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

	/**
	 * Check for a pending file download; prompt user to download file
	 * @return null
	 */
	public function download_file() {
		$file_name = isset( $_GET[ self::SLUG . '_zip_download' ] ) ? $_GET[ self::SLUG . '_zip_download' ] : null;
		if ( $file_name ) {
			// Force user to be logged in
			if ( ! is_user_logged_in() ) {
				return;
			}

			// Don't allow path traversal
			if ( strpos( $file_name, '../' ) !== false ) {
				exit( 'Invalid Request' );
			}

			// File must exist
			$file_path = path_join( self::$instance->options->get( 'temp_files_dir' ), $file_name );
			if ( ! file_exists( $file_path ) ) {
				exit( 'Files does not exist' );
			}

			// Send file
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $file_name );
			header( 'Content-Type: application/zip, application/octet-stream; charset=' . get_option( 'blog_charset' ), true );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			readfile( $file_path );
			exit();
		}
	}

	/**
	 * Return whether or not debug mode is on
	 * @return boolean Debug mode enabled?
	 */
	public function debug_on() {
		return $this->options->get( 'debugging_mode' ) === '1';
	}
}
