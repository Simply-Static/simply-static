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
     * Register Elementor Assets to be added that are loaded conditionally
     *
     * @return void
     */
    public function register_assets() {
        $js_bundles_folder = trailingslashit( ELEMENTOR_PRO_PATH ) . 'assets/js/';
        $files             = scandir( $js_bundles_folder );
        $only_bundle_min   = array_filter( $files, function( $file ) {
            return strpos( $file, 'bundle.min.js' );
        });

        foreach ( $only_bundle_min as $minified_file ) {
            $url = trailingslashit( ELEMENTOR_PRO_URL ) . 'assets/js/' . $minified_file;
            Util::debug_log( 'Adding elementor pro bundle asset to queue: ' . $url );
            /** @var \Simply_Static\Page $static_page */
            $static_page = Page::query()->find_or_initialize_by( 'url', $url );
            $static_page->set_status_message( __( 'Elementor Pro Asset', 'simply-static' ) );
            $static_page->found_on_id = 0;
            $static_page->save();
        }
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

               $flat_widget = $this->flatten_data( $widget_data );
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
                });

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
     * @param $type
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