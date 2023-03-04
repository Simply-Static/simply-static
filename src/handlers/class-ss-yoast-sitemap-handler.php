<?php

namespace Simply_Static;

class Yoast_Sitemap_Handler extends Page_Handler {

	/**
	 * Run hooks on page request.
	 *
	 * Useful in case a type of page requires different hooks to be ran before the static page is generated.
	 *
	 * @return void
	 */
	public function run_hooks() {
		// Filter XSL.
		add_filter( 'wpseo_stylesheet_url', [ $this, 'stylesheet_url' ] );
	}

	/**
	 * Get Stylesheet URL for XSL.
	 *
	 * @param string $xsl_string given XSL string.
	 *
	 * @return string
	 */
	public function stylesheet_url( $xsl_string ) {
		// Using Origin URL to make sure the URL gets swapped correctly with destination url.
		return '<?xml-stylesheet type="text/xsl" href="' . trailingslashit( Util::origin_url() ) . 'main-sitemap.xsl' . '"?>';
	}

	/**
	 * Add file to destination directory.
	 *
	 * @param string $destination_dir given destination dir.
	 *
	 * @return void
	 */
	public function after_file_fetch( $destination_dir ) {
        $this->copy_xsl( $destination_dir );
        $this->rename_sitemap( $destination_dir );
	}

    /**
     * Rename sitemap to sitemap.xml
     *
     * @param string $destination_dir Destination directory.
     * @return void
     */
    protected function rename_sitemap( $destination_dir ) {
        $sitemap_xml = Util::combine_path( $destination_dir, '/sitemap_index.xml' );
        if ( ! file_exists( $sitemap_xml ) ) {
            return;
        }

        $new_sitemap_xml = Util::combine_path( $destination_dir, '/sitemap.xml' );

        $copy = copy( $sitemap_xml, $new_sitemap_xml );
        if ( $copy === false ) {
            Util::debug_log( 'Cannot copy ' . $sitemap_xml . ' to ' . $new_sitemap_xml );
        } else {

            Util::debug_log( 'Copied ' . $sitemap_xml . ' to ' . $new_sitemap_xml );
        }
    }

    /**
     * Copy XSL for sitemap
     *
     * @param string $destination_dir Destination directory.
     * @return void
     */
    protected function copy_xsl( $destination_dir ) {
        $xsl_path         = dirname( WPSEO_FILE ) . '/css/main-sitemap.xsl';
        $destination_path = Util::combine_path( $destination_dir, '/main-sitemap.xsl' );
        if ( file_exists( $destination_path ) ) {
            return;
        }
        $copy = copy( $xsl_path, $destination_path );
        if ( $copy === false ) {
            Util::debug_log( 'Cannot copy ' . $xsl_path . ' to ' . $destination_path );
        } else {

            Util::debug_log( 'Copied ' . $xsl_path . ' to ' . $destination_path );
        }
    }
}