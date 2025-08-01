<?php

namespace Simply_Static;


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
		$this->description = __( 'Exports assets required for Elementor widgets and prepares data used by them.', 'simply-static' );
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
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_assets' ] );
		//add_filter( 'ss_html_after_restored_attributes', [ $this, 'extract_elementor_settings' ], 20, 2 );

		// Register Elementor widgets
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_widget_categories' ] );
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
	 * Register Elementor Assets to be added that are loaded conditionally
	 *
	 * @return void
	 */
	public function register_assets() {
		$file_urls = [];
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

		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'css' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'js' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'images' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'shapes' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'mask-shapes' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'svg-paths' ) );
		$file_urls = array_merge( $file_urls, $this->get_files_in_url( 'data' ) );

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
		require_once __DIR__ . '/elementor/class-search-widget.php';

		// Register the widget
		$widgets_manager->register( new Elementor_Search_Widget() );
	}
}
