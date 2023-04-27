<?php

namespace Simply_Static;

use voku\helper\HtmlDomParser;

class Elementor_Integration extends Integration {
    /**
     * Given plugin handler ID.
     *
     * @var string Handler ID.
     */
    protected $id = 'elementor';

    /**
     * Can this integration run?
     *
     * @return bool
     */
    public function can_run() {
        return defined( 'ELEMENTOR_VERSION' );
    }

    /**
     * Run the integration.
     *
     * @return void
     */
    public function run() {
        add_action( 'ss_finished_fetching_pages', [ $this, 'move_files' ] );
        add_action( 'ss_after_setup_task', [ $this, 'register_assets' ] );
        add_action( 'ss_after_extract_and_replace_urls_in_html', [ $this, 'extract_elementor_settings' ], 20, 2 );
    }

    /**
     * @param HtmlDomParser $dom DOM object.
     * @param Url_Extractor $extractor Extractor.
     * @return void
     */
    public function extract_elementor_settings( $dom, $extractor ) {
        $settings = $dom->find('[data-settings]');
        $pattern  = '/"url":"(https?:\/\/\S+?)"/i';
        foreach ( $settings as $node ) {
            $json = $node->{'data-settings'};
            $decoded = html_entity_decode( wp_unslash( $json ) );
            $json = preg_replace_callback( $pattern, array( $extractor, 'css_matches' ), $decoded );
            $json = json_decode( $json );
            $node->{'data-settings'} = esc_attr( wp_json_encode( $json ) );


        }
    }



    /**
     * Move Elementor Files to make sure all assets that might be required are there.
     * @return void
     */
    public function move_files() {
        $lib_assets   = trailingslashit( ELEMENTOR_PATH ) . 'assets/lib/';
        $options      = Options::instance();
        $archive_dir  = $options->get_archive_dir();
        $relative_dir = str_replace( trailingslashit( ABSPATH ), '', $lib_assets );
        $destination  = trailingslashit( $archive_dir ) . $relative_dir;

        $this->recurseCopy( $lib_assets, $destination );
    }

    protected function recurseCopy(
        string $sourceDirectory,
        string $destinationDirectory,
        string $childFolder = ''
    ): void {
        $directory = opendir($sourceDirectory);

        if (is_dir($destinationDirectory) === false) {
            mkdir($destinationDirectory);
        }

        if ($childFolder !== '') {
            if (is_dir("$destinationDirectory/$childFolder") === false) {
                mkdir("$destinationDirectory/$childFolder");
            }

            while (($file = readdir($directory)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                if (is_dir("$sourceDirectory/$file") === true) {
                    $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                } else {
                    copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                }
            }

            closedir($directory);

            return;
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
            }
            else {
                copy("$sourceDirectory/$file", "$destinationDirectory/$file");
            }
        }

        closedir($directory);
    }

    /**
     * Register Elementor Assets to be added that are loaded conditionally
     *
     * @return void
     */
    public function register_assets() {
        $js_bundles_folder = trailingslashit( ELEMENTOR_PATH ) . 'assets/js/';
        $files             = scandir( $js_bundles_folder );
        $only_bundle_min   = array_filter( $files, function( $file ) {
            return strpos( $file, 'bundle.min.js' );
        });

        foreach ( $only_bundle_min as $minified_file ) {
            $url = trailingslashit( ELEMENTOR_URL ) . 'assets/js/' . $minified_file;
            Util::debug_log( 'Adding elementor bundle asset to queue: ' . $url );
            /** @var \Simply_Static\Page $static_page */
            $static_page = Page::query()->find_or_initialize_by( 'url', $url );
            $static_page->set_status_message( __( 'Elementor Asset', 'simply-static' ) );
            $static_page->found_on_id = 0;
            $static_page->save();
        }
    }
}