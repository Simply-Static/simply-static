<?php

namespace Simply_Static;

use function Clue\StreamFilter\fun;

class Elementor_Pro_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'elementor-pro';

	/**
	 * Can this integration run?
	 *
	 * @return bool
	 */
	public function can_run() {
		return defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_assets' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_lottie_files' ] );
	}

	/**
	 * Move Elementor Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_lib_files() {
		return $this->get_files_in_url( 'lib' );
	}

	/**
	 * Register Elementor Assets to be added that are loaded conditionally
	 *
	 * @return void
	 */
	public function register_assets() {
		$file_urls   = [];
		$bundle_urls = $this->get_bundle_files();
		$lib_urls    = $this->get_lib_files();
		$file_urls   = array_merge( $file_urls, $bundle_urls );
		$file_urls   = array_merge( $file_urls, $lib_urls );
		$file_urls   = array_merge( $file_urls, $this->get_files_in_url( 'css' ) );
		$file_urls   = array_merge( $file_urls, $this->get_files_in_url( 'images' ) );
		$file_urls   = array_merge( $file_urls, $this->get_files_in_url( 'mask-shapes' ) );
		$file_urls   = array_merge( $file_urls, $this->get_files_in_url( 'svg-paths' ) );
		$file_urls   = array_merge( $file_urls, $this->get_files_in_url( 'data' ) );

		foreach ( $file_urls as $url ) {
			Util::debug_log( 'Adding elementor bundle asset to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Elementor Pro Asset', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}
	}

	protected function get_bundle_files() {
		$js_bundles_folder = trailingslashit( ELEMENTOR_PRO_PATH ) . 'assets/js/';
		$files             = scandir( $js_bundles_folder );
		$only_bundle_min   = array_filter( $files, function ( $file ) {
			return strpos( $file, 'bundle.min.js' );
		} );

		$urls = [];

		foreach ( $only_bundle_min as $minified_file ) {
			$urls[] = trailingslashit( ELEMENTOR_PRO_URL ) . 'assets/js/' . $minified_file;
		}

		return $urls;
	}

	/**
	 * Move Elementor Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_files_in_url( $asset_dir ) {
		$dir   = trailingslashit( ELEMENTOR_PRO_PATH ) . 'assets/' . $asset_dir;
		$files = $this->get_files_in_dir( $dir );
		$urls  = [];

		foreach ( $files as $file ) {
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
	 * Get all widget
	 *
	 * @param $type
	 *
	 * @return array
	 */
	protected function flatten_data( $data, $flat_array = [] ) {

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