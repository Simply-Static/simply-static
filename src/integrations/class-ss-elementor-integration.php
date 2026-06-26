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
		add_filter( 'ss_html_after_restored_attributes', [ $this, 'extract_elementor_settings' ], 20, 2 );

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

		// Always register Google Fonts files regardless of smart_crawl to prevent
		// CORS issues when the static site serves font CSS with unreplaced origin URLs.
		add_action( 'ss_after_setup_task', [ $this, 'register_google_fonts' ] );

		add_action( 'ssp_before_form_template_scripts', [ $this, 'dequeue_scripts' ] );

		if ( class_exists( 'simply_static_pro\Single' ) ) {
			add_filter( 'ssp_single_related_attachment_urls', [ $this, 'add_elementor_thumbnails' ], 10, 2 );
		}
	}

	/**
	 * Dequeue Elementor scripts on ssp-form single pages.
	 *
	 * @return void
	 */
	public function dequeue_scripts() {
		wp_dequeue_script( 'elementor-frontend' );
		wp_dequeue_script( 'elementor-pro-frontend' );
		wp_dequeue_script( 'elementor-frontend-modules' );
		wp_dequeue_script( 'elementor-sticky' );
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
		$string = preg_replace( '/(https?:)?\/\/' . Util::origin_host_pattern() . '/i', $destination_url, $string );

		// replace wp_json_encode'd urls, as used by WP's `concatemoji`.
		// e.g. {"concatemoji":"http:\/\/www.example.org\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.6.1"}.
		$string = preg_replace( '/' . Util::json_escaped_origin_url_pattern() . '/i', addcslashes( untrailingslashit( $destination_url ), '/' ), $string );


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
	 * Register Elementor Google Fonts CSS and font files to the export queue.
	 *
	 * Elementor's self-hosted Google Fonts feature stores CSS and font files in
	 * wp-content/uploads/elementor/google-fonts/. These must always be included
	 * in the static export so the URL extractor can replace origin URLs inside
	 * the CSS files, preventing CORS errors on the static site.
	 *
	 * @return void
	 */
	public function register_google_fonts() {
		$upload_dir = wp_upload_dir();
		$gf_base    = $upload_dir['basedir'] . '/elementor/google-fonts';

		if ( ! is_dir( $gf_base ) ) {
			return;
		}

		$gf_base_url = $upload_dir['baseurl'] . '/elementor/google-fonts';
		$file_paths  = $this->get_files_in_dir( $gf_base );

		foreach ( $file_paths as $file_path ) {
			// Build the URL by replacing the filesystem base with the URL base.
			$relative = str_replace( $gf_base, '', $file_path );
			$url      = $gf_base_url . $relative;

			Util::debug_log( 'Adding Elementor Google Font asset to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Elementor Google Font', 'simply-static' ) );
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

	/**
	 * Add Elementor thumbnails to Single Export.
	 *
	 * @param array $urls    Existing related URLs.
	 * @param int   $post_id The post ID being exported.
	 * @return array
	 */
	public function add_elementor_thumbnails( $urls, $post_id ) {
		$upload_dir = wp_upload_dir();
		$thumbs_dir = $upload_dir['basedir'] . '/elementor/thumbs';

		// 1. Find media referenced by Elementor post data.
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! empty( $elementor_data ) ) {
			$data = json_decode( $elementor_data, true );

			if ( is_array( $data ) ) {
				$found_attachment_ids = array();
				$found_media_urls     = array();

				$this->collect_elementor_media_references( $data, $found_attachment_ids, $found_media_urls );

				foreach ( $found_media_urls as $media_url ) {
					$urls = $this->add_local_asset_url( $urls, $media_url );
				}

				$found_attachment_ids = array_unique( array_filter( $found_attachment_ids ) );

				foreach ( $found_attachment_ids as $attachment_id ) {
					$urls = array_merge( $urls, $this->get_attachment_export_urls( $attachment_id, $upload_dir, $thumbs_dir ) );
				}
			}
		}

		// 2. Also ensure Elementor CSS for this post is included and its assets are found
		$css_file = $upload_dir['basedir'] . '/elementor/css/post-' . $post_id . '.css';
		if ( file_exists( $css_file ) ) {
			$css_url = $upload_dir['baseurl'] . '/elementor/css/post-' . $post_id . '.css';
			if ( Util::is_local_asset_url( $css_url ) ) {
				$urls = $this->add_local_asset_url( $urls, $css_url );

				$css_content = file_get_contents( $css_file );
				if ( preg_match_all( '/url\(\s*[\'"]?([^\'"\)]+)[\'"]?\s*\)/i', $css_content, $matches ) ) {
					foreach ( $matches[1] as $extracted_url ) {
						$abs_url = Util::relative_to_absolute_url( $extracted_url, $css_url );
						if ( $abs_url ) {
							$urls = $this->add_local_asset_url( $urls, $abs_url );
						}
					}
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Recursively collect attachment IDs and asset URLs from Elementor data.
	 *
	 * Elementor gallery widgets store image data in nested arrays, so checking
	 * only direct settings values misses gallery attachments during Single Export.
	 *
	 * @param mixed $value          Elementor data value.
	 * @param array $attachment_ids Found attachment IDs.
	 * @param array $media_urls     Found media URLs.
	 * @return void
	 */
	private function collect_elementor_media_references( $value, &$attachment_ids, &$media_urls ) {
		if ( is_array( $value ) ) {
			if ( isset( $value['id'] ) && is_numeric( $value['id'] ) && (int) $value['id'] > 0 ) {
				$attachment_ids[] = (int) $value['id'];
			}

			if ( isset( $value['url'] ) && is_string( $value['url'] ) ) {
				$media_urls[] = $value['url'];
			}

			foreach ( $value as $child ) {
				$this->collect_elementor_media_references( $child, $attachment_ids, $media_urls );
			}

			return;
		}

		if ( is_string( $value ) ) {
			$media_urls[] = $value;
		}
	}

	/**
	 * Get all export-relevant URLs for an attachment.
	 *
	 * Includes the original URL, generated intermediate sizes, and Elementor
	 * thumbnails created in uploads/elementor/thumbs.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $upload_dir    wp_upload_dir() data.
	 * @param string $thumbs_dir    Elementor thumbs directory.
	 * @return array
	 */
	private function get_attachment_export_urls( $attachment_id, $upload_dir, $thumbs_dir ) {
		$urls = array();

		$attachment_url = wp_get_attachment_url( $attachment_id );
		$urls           = $this->add_local_asset_url( $urls, $attachment_url );

		$file = get_attached_file( $attachment_id );
		if ( $file ) {
			$urls = $this->add_local_asset_url( $urls, $this->upload_file_path_to_url( $file, $upload_dir ) );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $metadata ) && ! empty( $metadata['file'] ) ) {
			$urls = $this->add_local_asset_url( $urls, trailingslashit( $upload_dir['baseurl'] ) . ltrim( $metadata['file'], '/' ) );

			$relative_dir = dirname( $metadata['file'] );
			$relative_dir = '.' === $relative_dir ? '' : trim( $relative_dir, '/' );

			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size ) {
					if ( empty( $size['file'] ) ) {
						continue;
					}

					$relative_file = ltrim( $relative_dir . '/' . $size['file'], '/' );
					$urls          = $this->add_local_asset_url( $urls, trailingslashit( $upload_dir['baseurl'] ) . $relative_file );
				}
			}
		}

		if ( is_dir( $thumbs_dir ) ) {
			$basenames = array();

			if ( $file ) {
				$pathinfo = pathinfo( $file );
				if ( ! empty( $pathinfo['filename'] ) ) {
					$basenames[] = $pathinfo['filename'];
				}
			}

			if ( is_array( $metadata ) && ! empty( $metadata['file'] ) ) {
				$pathinfo = pathinfo( $metadata['file'] );
				if ( ! empty( $pathinfo['filename'] ) ) {
					$basenames[] = $pathinfo['filename'];
				}
			}

			foreach ( array_unique( $basenames ) as $basename ) {
				$thumb_basenames = array( $basename );
				if ( substr( $basename, - 7 ) === '-scaled' ) {
					$thumb_basenames[] = substr( $basename, 0, - 7 );
				}

				foreach ( array_unique( $thumb_basenames ) as $thumb_basename ) {
					$files = glob( trailingslashit( $thumbs_dir ) . $thumb_basename . '-*.*' );
					if ( empty( $files ) ) {
						continue;
					}

					foreach ( $files as $thumb_file ) {
						$urls = $this->add_local_asset_url( $urls, $this->upload_file_path_to_url( $thumb_file, $upload_dir ) );
					}
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Convert a file in wp_upload_dir()['basedir'] to its public URL.
	 *
	 * @param string $file_path  Absolute file path.
	 * @param array  $upload_dir wp_upload_dir() data.
	 * @return string
	 */
	private function upload_file_path_to_url( $file_path, $upload_dir ) {
		if ( empty( $file_path ) || empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
			return '';
		}

		$base_dir  = wp_normalize_path( $upload_dir['basedir'] );
		$file_path = wp_normalize_path( $file_path );

		if ( strpos( $file_path, trailingslashit( $base_dir ) ) !== 0 ) {
			return '';
		}

		$relative = ltrim( substr( $file_path, strlen( $base_dir ) ), '/' );

		return trailingslashit( $upload_dir['baseurl'] ) . $relative;
	}

	/**
	 * Add a local static asset URL to the URL list.
	 *
	 * @param array  $urls URL list.
	 * @param string $url  URL to add.
	 * @return array
	 */
	private function add_local_asset_url( $urls, $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $urls;
		}

		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );

		if ( Util::is_local_asset_url( $url ) ) {
			$urls[] = Util::remove_params_and_fragment( $url );
		}

		return $urls;
	}
}
