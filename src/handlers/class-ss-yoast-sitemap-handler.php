<?php

namespace Simply_Static;

use Simply_Static\Options;

class Yoast_Sitemap_Handler extends Page_Handler {

	/**
	 * Run hooks on page request.
	 *
	 * Useful in case a type of page requires different hooks to be ran before the static page is generated.
	 *
	 * @return void
	 */
	public function run_hooks() {
		parent::run_hooks();

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
        $this->fix_sitemap_xsl_references( $destination_dir );
        $this->replace_urls_in_sitemaps( $destination_dir );
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

        // Copy to the root directory (original behavior)
        if ( ! file_exists( $destination_path ) ) {
            $copy = copy( $xsl_path, $destination_path );
            if ( $copy === false ) {
                Util::debug_log( 'Cannot copy ' . $xsl_path . ' to ' . $destination_path );
            } else {
                Util::debug_log( 'Copied ' . $xsl_path . ' to ' . $destination_path );
            }
        }

        // Also copy to the path referenced in the sitemap.xml
        $plugin_css_dir = Util::combine_path( $destination_dir, '/wp-content/plugins/wordpress-seo/css' );

        // Create directory structure if it doesn't exist
        if ( ! file_exists( $plugin_css_dir ) ) {
            wp_mkdir_p( $plugin_css_dir );
        }

        $plugin_xsl_path = Util::combine_path( $plugin_css_dir, '/main-sitemap.xsl' );

        if ( ! file_exists( $plugin_xsl_path ) ) {
            $copy = copy( $xsl_path, $plugin_xsl_path );
            if ( $copy === false ) {
                Util::debug_log( 'Cannot copy ' . $xsl_path . ' to ' . $plugin_xsl_path );
            } else {
                Util::debug_log( 'Copied ' . $xsl_path . ' to ' . $plugin_xsl_path );
            }
        }
    }

    /**
     * Fix XSL references in sitemap XML files
     *
     * @param string $destination_dir Destination directory.
     * @return void
     */
    protected function fix_sitemap_xsl_references( $destination_dir ) {
        // List of sitemap files to check
        $sitemap_files = [
            Util::combine_path( $destination_dir, '/sitemap.xml' ),
            Util::combine_path( $destination_dir, '/sitemap_index.xml' ),
            // Add other sitemap files if needed
        ];

        // Find all XML files that might be sitemaps
        $xml_files = glob( Util::combine_path( $destination_dir, '/*-sitemap.xml' ) );
        if ( is_array( $xml_files ) ) {
            $sitemap_files = array_merge( $sitemap_files, $xml_files );
        }

        foreach ( $sitemap_files as $sitemap_file ) {
            if ( file_exists( $sitemap_file ) ) {
                $content = file_get_contents( $sitemap_file );

                // Replace the XSL reference with the one pointing to the root directory
                $content = preg_replace(
                    '/<\?xml-stylesheet type="text\/xsl" href="[^"]*wp-content\/plugins\/wordpress-seo\/css\/main-sitemap.xsl"\?>/',
                    '<?xml-stylesheet type="text/xsl" href="' . trailingslashit( Options::instance()->get_destination_url() ) . 'main-sitemap.xsl"?>',
                    $content
                );

                file_put_contents( $sitemap_file, $content );
                Util::debug_log( 'Fixed XSL reference in ' . $sitemap_file );
            }
        }
    }

    /**
     * This fixes missing URL replacements for Yoast SEO sitemaps.
     *
     * @param string $destination_dir Destination directory.
     * @return void
     */
    protected function replace_urls_in_sitemaps( $destination_dir ) {
        try {
            $options         = Options::instance();
            $destination_url = trailingslashit( $options->get_destination_url() );
            $origin_host     = Util::origin_host();

            // Collect sitemap files to process
            $sitemap_files = [
                Util::combine_path( $destination_dir, '/sitemap.xml' ),
                Util::combine_path( $destination_dir, '/sitemap_index.xml' ),
            ];

            $xml_files = glob( Util::combine_path( $destination_dir, '/*-sitemap.xml' ) );
            if ( is_array( $xml_files ) ) {
                $sitemap_files = array_merge( $sitemap_files, $xml_files );
            }

            $sitemap_files = array_unique( array_filter( $sitemap_files, 'file_exists' ) );

            if ( empty( $sitemap_files ) ) {
                return;
            }

            foreach ( $sitemap_files as $file ) {
                $content = @file_get_contents( $file );
                if ( false === $content || '' === $content ) {
                    continue;
                }

                // Perform a generic, tag-agnostic replacement of any origin host URLs
                $updated = $this->replace_all_origin_urls_with_destination( $content, $destination_url, $origin_host );

                if ( is_string( $updated ) && $updated !== $content ) {
                    file_put_contents( $file, $updated );
                    Util::debug_log( 'Updated URLs in sitemap (generic replace): ' . $file );
                }
            }
        } catch ( \Throwable $e ) {
            Util::debug_log( 'Error updating Yoast sitemap URLs: ' . $e->getMessage() );
        }
    }


    /**
     * Perform a generic, tag-agnostic replacement of any origin host URLs in the given XML content.
     * Replaces http(s)://origin-host and protocol-relative //origin-host with the absolute destination URL.
     *
     * @param string $xml
     * @param string $destination_url Absolute destination base URL with trailing slash preferred.
     * @param string $origin_host Origin host (example.com[/subpath]).
     * @return string
     */
    private function replace_all_origin_urls_with_destination( $xml, $destination_url, $origin_host ) {
        if ( ! is_string( $xml ) || $xml === '' ) {
            return $xml;
        }
        $dest = rtrim( $destination_url, '/' );
        $pattern = '/(?:(https?:)?\/\/)' . preg_quote( $origin_host, '/' ) . '/i';
        return preg_replace( $pattern, $dest, $xml );
    }
}
