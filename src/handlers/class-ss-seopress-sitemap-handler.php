<?php

namespace Simply_Static;

use Simply_Static\Options;

class SEOPress_Sitemap_Handler extends Page_Handler {

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
		add_filter( 'seopress_sitemaps_stylesheet_url', [ $this, 'stylesheet_url' ] );
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
        // Target paths for main-sitemap.xsl
        $destination_path = Util::combine_path( $destination_dir, '/main-sitemap.xsl' );
        $plugin_dir       = Util::combine_path( $destination_dir, '/wp-content/plugins/wp-seopress/inc/functions/sitemap' );
        $plugin_xsl_path  = Util::combine_path( $plugin_dir, '/main-sitemap.xsl' );

        // Ensure plugin directory exists
        if ( ! file_exists( $plugin_dir ) ) {
            wp_mkdir_p( $plugin_dir );
        }
        
        // Generate main-sitemap.xsl content if missing in either location
        $need_root   = ! file_exists( $destination_path );
        $need_plugin = ! file_exists( $plugin_xsl_path );

        if ( $need_root || $need_plugin ) {
            Util::debug_log( 'Generating content for main-sitemap.xsl' );
            ob_start();
            $this->generate_xsl();
            $xsl_content = ob_get_clean();

            if ( $need_root ) {
                if ( false === @file_put_contents( $destination_path, $xsl_content ) ) {
                    Util::debug_log( 'Cannot create ' . $destination_path );
                } else {
                    Util::debug_log( 'Created ' . $destination_path );
                }
            }

            if ( $need_plugin ) {
                if ( false === @file_put_contents( $plugin_xsl_path, $xsl_content ) ) {
                    Util::debug_log( 'Cannot create ' . $plugin_xsl_path );
                } else {
                    Util::debug_log( 'Created ' . $plugin_xsl_path );
                }
            }
        }
    }

    /**
     * Mirror main-sitemap.xsl from archive into Local Directory destination.
     * Runs on ss_before_finish_transferring_files_locally.
     * Only copies main-sitemap.xsl (no legacy fallbacks).
     *
     * @param string $destination_dir
     * @param string $archive_dir
     * @return void
     */
    public static function transfer_xsl_to_local_dir( $destination_dir, $archive_dir ) : void {
        try {
            // Root main-sitemap.xsl
            $src_root = Util::combine_path( $archive_dir, '/main-sitemap.xsl' );
            $dst_root = Util::combine_path( $destination_dir, '/main-sitemap.xsl' );
            if ( file_exists( $src_root ) ) {
                if ( ! @copy( $src_root, $dst_root ) ) {
                    Util::debug_log( '[SEOPress] Failed to copy main-sitemap.xsl (root): ' . $src_root . ' -> ' . $dst_root );
                } else {
                    Util::debug_log( '[SEOPress] Copied main-sitemap.xsl (root) to Local Directory.' );
                }
            }

            // Plugin path main-sitemap.xsl
            $plugin_rel_dir = '/wp-content/plugins/wp-seopress/inc/functions/sitemap';
            $src_plugin     = Util::combine_path( $archive_dir, $plugin_rel_dir . '/main-sitemap.xsl' );
            if ( file_exists( $src_plugin ) ) {
                $dst_plugin_dir = Util::combine_path( $destination_dir, $plugin_rel_dir );
                if ( ! is_dir( $dst_plugin_dir ) ) {
                    wp_mkdir_p( $dst_plugin_dir );
                }
                $dst_plugin = Util::combine_path( $dst_plugin_dir, '/main-sitemap.xsl' );
                if ( ! @copy( $src_plugin, $dst_plugin ) ) {
                    Util::debug_log( '[SEOPress] Failed to copy main-sitemap.xsl (plugin path): ' . $src_plugin . ' -> ' . $dst_plugin );
                } else {
                    Util::debug_log( '[SEOPress] Copied main-sitemap.xsl (plugin path) to Local Directory.' );
                }
            }
        } catch ( \Throwable $e ) {
            Util::debug_log( '[SEOPress] Error copying main-sitemap.xsl: ' . $e->getMessage() );
        }
    }

    /**
     * Generate XSL
     *
     * @return void
     */
    public function generate_xsl() {
        if ( function_exists( 'seopress_xml_sitemap_index_xsl' ) ) {
            seopress_xml_sitemap_index_xsl();
        } else {
            // Fallback to a basic XSL if the function doesn't exist
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
            Util::combine_path( $destination_dir, '/sitemaps.xml' ),
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
     * Perform generic URL replacements in sitemap XML files for SEOPress.
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
                Util::combine_path( $destination_dir, '/sitemaps.xml' ),
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
            Util::debug_log( 'Error updating SEOPress sitemap URLs: ' . $e->getMessage() );
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
        $pattern = '/(?:(https?:)?\/\/)' . preg_quote( $origin_host, '/' ) . '/i';
        return preg_replace( $pattern, $dest, $xml );
    }

}

// Hook: ensure XSL is present in Local Directory before finishing transfer (Local Directory deployment)
add_action( 'ss_before_finish_transferring_files_locally', [ '\Simply_Static\SEOPress_Sitemap_Handler', 'transfer_xsl_to_local_dir' ], 10, 2 );
