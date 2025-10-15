<?php

namespace Simply_Static;

use Simply_Static\Options;

class AIO_SEO_Sitemap_Handler extends Page_Handler {

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
		add_filter( 'aioseo_sitemap_stylesheet', [ $this, 'stylesheet_url' ] );
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
        $this->save_xsl( $destination_dir );
        $this->fix_sitemap_xsl_references( $destination_dir );
        $this->replace_urls_in_sitemaps( $destination_dir );
	}

    /**
     * Save XSL.
     *
     * @param string $destination_dir Dir path.
     * @return void
     */
    protected function save_xsl( $destination_dir ) {
        // Generate XSL content once
        Util::debug_log( 'Getting content for main-sitemap.xsl' );
        ob_start();
        $this->generate_xsl();
        $xsl_content = ob_get_clean();
        $temp_filename = wp_tempnam();
        file_put_contents( $temp_filename, $xsl_content );

        // Copy to the root directory (original behavior)
        $destination_path = Util::combine_path( $destination_dir, '/main-sitemap.xsl' );
        if ( ! file_exists( $destination_path ) ) {
            $rename = rename( $temp_filename, $destination_path );
            if ( $rename === false ) {
                Util::debug_log( 'Cannot create ' . $destination_path );
                // Create a new temp file for the second copy
                $temp_filename = wp_tempnam();
                file_put_contents( $temp_filename, $xsl_content );
            } else {
                Util::debug_log( 'Created ' . $destination_path );
                // Create a new temp file for the second copy
                $temp_filename = wp_tempnam();
                file_put_contents( $temp_filename, $xsl_content );
            }
        }

        // Also copy to any potential path referenced in the sitemap XML
        // For All in One SEO, we'll create a common location
        $plugin_dir = Util::combine_path( $destination_dir, '/wp-content/plugins/all-in-one-seo-pack/app/Common/Sitemap' );

        // Create directory structure if it doesn't exist
        if ( ! file_exists( $plugin_dir ) ) {
            wp_mkdir_p( $plugin_dir );
        }

        $plugin_xsl_path = Util::combine_path( $plugin_dir, '/main-sitemap.xsl' );

        if ( ! file_exists( $plugin_xsl_path ) ) {
            $rename = rename( $temp_filename, $plugin_xsl_path );
            if ( $rename === false ) {
                Util::debug_log( 'Cannot create ' . $plugin_xsl_path );
            } else {
                Util::debug_log( 'Created ' . $plugin_xsl_path );
            }
        }
    }

    /**
     * Generate XSL
     *
     * @return void
     */
    public function generate_xsl() {
        if ( class_exists( 'AIOSEO\Plugin\Common\Sitemap\Xsl' ) && method_exists( 'AIOSEO\Plugin\Common\Sitemap\Xsl', 'output' ) ) {
            // Try to use the AIOSEO XSL class if available
            $xsl = new \AIOSEO\Plugin\Common\Sitemap\Xsl();
            $xsl->output();
        } else {
            // Fallback to a basic XSL if the class doesn't exist
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">';
            echo '<xsl:output method="html" encoding="UTF-8" indent="yes"/>';
            echo '<xsl:template match="/">';
            echo '<html><head><title>XML Sitemap</title>';
            echo '<style>body{font-family:Arial,sans-serif;font-size:14px;color:#333}h1{font-size:24px;font-weight:normal;margin:10px 0}table{border-collapse:collapse;width:100%;margin:20px 0}th,td{padding:10px;text-align:left}th{background-color:#f2f2f2}tr:nth-child(even){background-color:#f9f9f9}a{color:#337ab7;text-decoration:none}a:hover{text-decoration:underline}</style>';
            echo '</head><body>';
            echo '<h1>XML Sitemap</h1>';
            echo '<table>';
            echo '<tr><th>URL</th><th>Last Modified</th></tr>';
            echo '<xsl:for-each select="sitemap:urlset/sitemap:url">';
            echo '<tr>';
            echo '<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>';
            echo '<td><xsl:value-of select="sitemap:lastmod"/></td>';
            echo '</tr>';
            echo '</xsl:for-each>';
            echo '</table>';
            echo '</body></html>';
            echo '</xsl:template>';
            echo '</xsl:stylesheet>';
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
                    '/<\?xml-stylesheet type="text\/xsl" href="[^"]*"\?>/',
                    '<?xml-stylesheet type="text/xsl" href="' . trailingslashit( Options::instance()->get_destination_url() ) . 'main-sitemap.xsl"?>',
                    $content
                );

                file_put_contents( $sitemap_file, $content );
                Util::debug_log( 'Fixed XSL reference in ' . $sitemap_file );
            }
        }
    }

    /**
     * Perform generic URL replacements in sitemap XML files for All in One SEO.
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

                $updated = $this->replace_all_origin_urls_with_destination( $content, $destination_url, $origin_host );

                if ( is_string( $updated ) && $updated !== $content ) {
                    file_put_contents( $file, $updated );
                    Util::debug_log( 'Updated URLs in sitemap (generic replace): ' . $file );
                }
            }
        } catch ( \Throwable $e ) {
            Util::debug_log( 'Error updating AIOSEO sitemap URLs: ' . $e->getMessage() );
        }
    }

    /**
     * Generic, tag-agnostic replacement for origin host URLs.
     *
     * @param string $xml
     * @param string $destination_url
     * @param string $origin_host
     * @return string
     */
    private function replace_all_origin_urls_with_destination( $xml, $destination_url, $origin_host ) {
        if ( ! is_string( $xml ) || $xml === '' ) {
            return $xml;
        }
        $dest = rtrim( $destination_url, '/' );
        $pattern = '/(?:(https?:)?\\/\\/)' . preg_quote( $origin_host, '/' ) . '/i';
        return preg_replace( $pattern, $dest, $xml );
    }
}