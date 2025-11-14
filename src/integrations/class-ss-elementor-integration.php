<?php

namespace Simply_Static;

use function Clue\StreamFilter\fun;

class Elementor_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'elementor';

	/**
	 * @var null|Url_Extractor
	 */
	protected $extractor = null;

	public function __construct() {
		$this->name        = __( 'Elementor', 'simply-static' );
		$this->description = __( 'Exports assets required for Elementor and Elementor Pro widgets and prepares data used by them.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Check if Elementor Pro is active.
	 *
	 * @return boolean
	 */
	public function is_elementor_pro_active() {
		return defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		//add_filter( 'ss_html_after_restored_attributes', [ $this, 'extract_elementor_settings' ], 20, 2 );

		// Register Elementor widgets
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_widget_categories' ] );

		// Get options instance
		$options = Options::instance();

		// Add Elementor Crawler to active crawlers
		$this->activate_elementor_crawler();

		// Set Elementor defaults once when integration becomes active
		$this->set_elementor_default_options();

		// Register Elementor Pro specific functionality if available and if smart_crawl is not enabled
		if ( $this->is_elementor_pro_active() ) {
			if ( ! $options->get( 'smart_crawl' ) ) {
				add_action( 'ss_after_setup_task', [ $this, 'register_lottie_files' ] );
			}
		}

		// Register Elementor assets only if smart_crawl is not enabled
		if ( ! $options->get( 'smart_crawl' ) ) {
			add_action( 'ss_after_setup_task', [ $this, 'register_assets' ] );
		}
	}

	/**
	 * @param string|object $html_content HTML content or DOM object.
	 * @param Url_Extractor $extractor Extractor.
	 *
	 * @return string|object Modified HTML content or DOM object
	 */
	public function extract_elementor_settings( $html_content, $extractor ) {
		$this->extractor = $extractor;

		// If we're passed a string (HTML content), process it with regex
		if (is_string($html_content)) {
			// Find all elements with data-settings attribute
			$pattern = '/<[^>]*\sdata-settings=(["\'])([^"\']*)\1[^>]*>/i';

			return preg_replace_callback($pattern, function($matches) {
				$full_tag = $matches[0];
				$json = $matches[2];
				Util::debug_log('extract_elementor_settings: ' . $json);
				// Process the JSON data
				$decoded = htmlspecialchars_decode($json);
				$decoded = json_decode($decoded, true);

				if ($decoded) {
					$decoded = $this->replace_urls_array($decoded);
					$new_json = esc_attr(wp_json_encode($decoded));
					Util::debug_log('extract_elementor_settings: ' . $decoded);
					// Replace the old JSON with the new one
					return str_replace('data-settings="' . $json . '"', 'data-settings="' . $new_json . '"', $full_tag);
				}

				return $full_tag;
			}, $html_content);
		}

		// For backward compatibility, if we're passed an object (old DOM object)
		// just return it unchanged
		return $html_content;
	}

	/**
	 * Replace URL in a string.
	 *
	 * @param $string
	 *
	 * @return array|string|string[]
	 */
	public function replace_urls_in_text( $string ) {
		// Skip if it's not a string.
		if ( ! is_string( $string ) ) {
			return $string;
		}

		$options         = Options::instance();
		$destination_url = $options->get_destination_url();
		$pattern         = "/https?:\/\/[^\s\"'<]+/";

		$string = preg_replace_callback( $pattern, array( $this, 'replace_urls' ), $string );

		// replace any instance of the origin url, whether it starts with https://, http://, or //.
		$string = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $destination_url, $string );

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`.
		// e.g. {"concatemoji":"http:\/\/www.example.org\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.6.1"}.
		$string = str_replace( addcslashes( Util::origin_url(), '/' ), addcslashes( $destination_url, '/' ), $string );


		return $string;
	}

	public function replace_urls( $matches ) {
		$extracted_url = $matches[0];

		$updated_extracted_url = null;
		if ( isset( $extracted_url ) && $extracted_url !== '' ) {
			$updated_extracted_url = $this->extractor->add_to_extracted_urls( $extracted_url );
		}

		return $updated_extracted_url ? $updated_extracted_url : $extracted_url;
	}

	/**
	 * Replace URLs in an array.
	 *
	 * @param $var
	 *
	 * @return array
	 */
	public function replace_urls_array( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ $this, 'replace_urls_array' ], $var );
		} else {
			return is_scalar( $var ) ? $this->replace_urls_in_text( $var ) : $var;
		}
	}

	public function unslash_json( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( $this, 'unslash_json' ), $var );
		} else {
			return is_scalar( $var ) ? wp_unslash( $var ) : $var;
		}
	}

	/**
	 * Move Elementor Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_lib_files() {
		return $this->get_files_in_url( 'lib' );
	}

	/**
	 * Move Elementor Pro Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_pro_lib_files() {
		if ( ! $this->is_elementor_pro_active() ) {
			return [];
		}
		return $this->get_pro_files_in_url( 'lib' );
	}

	/**
	 * Move Elementor Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_files_in_url( $asset_dir ) {
		$dir   = trailingslashit( ELEMENTOR_PATH ) . 'assets/' . $asset_dir;
		$files = $this->get_files_in_dir( $dir );
		$urls  = [];

		foreach ( $files as $file ) {
			// If file size is empty, skip it.
			if ( ! filesize( $file ) ) {
				continue;
			}

			$urls[] = str_replace( trailingslashit( ELEMENTOR_PATH ), trailingslashit( ELEMENTOR_URL ), $file );
		}

		return $urls;
	}

	/**
	 * Move Elementor Pro Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_pro_files_in_url( $asset_dir ) {
		if ( ! $this->is_elementor_pro_active() ) {
			return [];
		}

		$dir   = trailingslashit( ELEMENTOR_PRO_PATH ) . 'assets/' . $asset_dir;
		$files = $this->get_files_in_dir( $dir );
		$urls  = [];

		foreach ( $files as $file ) {
			// If file size is empty, skip it.
			if ( ! filesize( $file ) ) {
				continue;
			}

			$urls[] = str_replace( trailingslashit( ELEMENTOR_PRO_PATH ), trailingslashit( ELEMENTOR_PRO_URL ), $file );
		}

		return $urls;
	}

	/**
	 * Get fields in directory
	 *
	 * @param string $source_dir Directory path.
	 * @param array $files
	 *
	 * @return array
	 */
	public function get_files_in_dir( string $source_dir, array $files = [] ) {

		if ( is_dir( $source_dir ) ) {
			$directory = opendir( $source_dir );

			while ( ( $file = readdir( $directory ) ) !== false ) {
				if ( $file === '.' || $file === '..' ) {
					continue;
				}

				if ( is_dir( "$source_dir/$file" ) === true ) {
					$files = $this->get_files_in_dir( "$source_dir/$file", $files );
				} else {
					$files[] = "$source_dir/$file";
				}
			}

			closedir( $directory );
		}

		return $files;
	}


	protected function get_bundle_files() {
		$js_bundles_folder = trailingslashit( ELEMENTOR_PATH ) . 'assets/js/';
		$files             = scandir( $js_bundles_folder );
		$only_bundle_min   = array_filter( $files, function ( $file ) {
			return strpos( $file, 'bundle.min.js' );
		} );

		$urls = [];

		foreach ( $only_bundle_min as $minified_file ) {
			// If file size is empty, skip it.
			if ( ! filesize( $minified_file ) ) {
				continue;
			}

			$urls[] = trailingslashit( ELEMENTOR_URL ) . 'assets/js/' . $minified_file;
		}

		return $urls;
	}

	/**
	 * Get Elementor Pro bundle files
	 *
	 * @return array
	 */
	protected function get_pro_bundle_files() {
		if ( ! $this->is_elementor_pro_active() ) {
			return [];
		}

		$js_bundles_folder = trailingslashit( ELEMENTOR_PRO_PATH ) . 'assets/js/';
		$files             = scandir( $js_bundles_folder );
		$only_bundle_min   = array_filter( $files, function ( $file ) {
			return strpos( $file, 'bundle.min.js' );
		} );

		$urls = [];

		foreach ( $only_bundle_min as $minified_file ) {
			// If file size is empty, skip it.
			if ( ! filesize( $minified_file ) ) {
				continue;
			}

			$urls[] = trailingslashit( ELEMENTOR_PRO_URL ) . 'assets/js/' . $minified_file;
		}

		return $urls;
	}

	/**
	 * Register Elementor Assets to be added that are loaded conditionally
	 *
	 * @return void
	 */
	public function register_assets() {
		$file_urls = [];

		// Get Elementor core assets
		$lib_urls  = $this->get_lib_files();
		$css_urls  = $this->get_files_in_dir( '/uploads/elementor/css/' );
		$js_urls   = $this->get_files_in_dir( '/uploads/elementor/js/' );
		$file_urls = array_merge( $file_urls, $lib_urls, $css_urls, $js_urls );

		// Add bundle files?
		$add_bundle = apply_filters( 'ss_elementor_add_bundle_files', false );

		if ( $add_bundle ) {
			$bundle_urls = $this->get_bundle_files();
			$file_urls   = array_merge( $file_urls, $bundle_urls );
		}

		// Add Elementor core asset directories
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'css' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'js' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'images' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'shapes' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'mask-shapes' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'svg-paths' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'data' ) );

		// Add Elementor Pro assets if available
		if ( $this->is_elementor_pro_active() ) {
			// Get Elementor Pro lib files
			$pro_lib_urls = $this->get_pro_lib_files();
			$file_urls = array_merge( $file_urls, $pro_lib_urls );

			// Add Elementor Pro bundle files if needed
			if ( $add_bundle ) {
				$pro_bundle_urls = $this->get_pro_bundle_files();
				$file_urls = array_merge( $file_urls, $pro_bundle_urls );
			}

			// Add Elementor Pro asset directories
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'js' ) );
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'css' ) );
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'images' ) );
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'mask-shapes' ) );
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'svg-paths' ) );
			$file_urls = array_merge( $file_urls, $this->get_pro_files_in_url( 'data' ) );
		}

		foreach ( $file_urls as $url ) {
			Util::debug_log( 'Adding elementor bundle asset to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Elementor Asset', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}

	/**
	 * Register widget categories.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public function register_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'simply-static',
			[
				'title' => __( 'Simply Static', 'simply-static' ),
				'icon' => 'fa fa-plug',
			]
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		// Include the widget file
		require_once __DIR__ . '/elementor/class-ss-search-widget.php';

		// Register the widget
		$widgets_manager->register( new Elementor_Search_Widget() );
	}

	/**
	 * Register Elementor Assets to be added that are loaded conditionally
	 *
	 * @return void
	 */
	public function register_lottie_files() {
		global $wpdb;

		$elementor_data = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data'", ARRAY_A );

		if ( ! $elementor_data ) {
			return;
		}

		$files = [];

		foreach ( $elementor_data as $data ) {

			foreach ( json_decode( $data['meta_value'], true ) as $widget_data ) {

				$flat_widget  = $this->flatten_data( $widget_data );
				$lottie_files = array_filter( $flat_widget, function ( $item ) {
					if ( ! isset( $item['widgetType'] ) ) {
						return false;
					}

					if ( empty( $item['settings'] ) ) {
						return false;
					}

					if ( empty( $item['settings']['source_json'] ) ) {
						return false;
					}

					if ( 'library' !== $item['settings']['source_json']['source'] ) {
						return false;
					}

					return $item['widgetType'] === 'lottie';
				} );

				if ( ! $lottie_files ) {
					continue;
				}

				foreach ( $lottie_files as $lottie_widget ) {
					$files[] = $lottie_widget['settings']['source_json']['url'];
				}

			}

		}


		$files = array_unique( $files );

		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file_url ) {
			Util::debug_log( 'Adding elementor pro Lottie File to queue: ' . $file_url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $file_url );
			$static_page->set_status_message( __( 'Elementor Pro Lottie', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}


	/**
	 * Set Elementor default options inside wp_options once when the integration is activated.
	 * Uses update_option as requested and guards with a one-time flag to avoid overriding user changes.
	 *
	 * @return void
	 */
	protected function set_elementor_default_options() {
		// Prevent repeated overrides; run only once per site unless flag is removed.
		if ( get_option( 'simply_static_elementor_defaults_set' ) ) {
			return;
		}

		// Apply requested defaults
		update_option( 'elementor_meta_generator_tag', 1 );
		update_option( 'elementor_css_print_method', 'internal' );
		update_option( 'elementor_lazy_load_background_images', 0 );
		update_option( 'elementor_element_cache_ttl', 'disable' );
		update_option( 'elementor_experiment-e_font_icon_svg', 'active' );
		update_option( 'elementor_experiment-e_optimized_markup', 'inactive' );

		// Mark done
		update_option( 'simply_static_elementor_defaults_set', 1 );
	}

	/**
	 * Activate the Elementor Crawler
	 *
	 * @return void
	 */
	protected function activate_elementor_crawler() {
		// Get options instance
		$options = Options::instance();

		// Get current active crawlers
		$crawlers = $options->get( 'crawlers' );

		// Respect user selections completely without verbose logging to avoid log spam during exports.
		// - If crawlers is an array and does NOT contain 'elementor', treat as explicit opt-out and do not re-add.
		// - If crawlers is null or not an array, do not modify; fall back to default is_active logic.
		if ( is_array( $crawlers ) ) {
			// Intentionally no debug logging here to prevent repeated log entries when run multiple times.
			return;
		}
		// If option not set or not an array, also do nothing silently.
		return;
	}

	/**
	 * Get all widget
	 *
	 * @param $type
	 *
	 * @return array
	 */
	protected function flatten_data( $data, $flat_array = [] ) {

		if ( ! is_array( $data ) ) {
			return $flat_array;
		}

		if ( ! empty( $data['elements'] ) ) {
			$flat_array = $this->flatten_data( $data['elements'], $flat_array );
			unset( $data['elements'] );
		}

		$array_keys = array_keys( $data );

		foreach ( $array_keys as $number ) {
			if ( ! is_integer( $number ) ) {
				continue;
			}

			$flat_array = $this->flatten_data( $data[ $number ], $flat_array );
			unset( $data[ $number ] );
		}


		if ( isset( $data['elements'] ) ) {
			unset( $data['elements'] );
		}

		$flat_array[] = array_merge( $data, $flat_array );

		return $flat_array;
	}
}
