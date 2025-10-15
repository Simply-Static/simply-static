<?php

namespace Simply_Static;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use Simply_Static\Options;

class Rank_Math_Sitemap_Handler extends Page_Handler {

	use Hooker;

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
		foreach ( Helper::get_accessible_taxonomies() as $taxonomy ) {
			if ( 'post_format' === $taxonomy->name ) {
				continue;
			}
			\add_filter( 'rank_math/sitemap/' . $taxonomy->name . '_stylesheet_url', [ $this, 'stylesheet_url' ] );
		}

		foreach ( Helper::get_accessible_post_types() as $post_type ) {
			$object = get_post_type_object( $post_type );
			\add_filter( 'rank_math/sitemap/' . $object->name . '_stylesheet_url', [ $this, 'stylesheet_url' ] );
		}
		// Main: 1_stylesheet_Url.
		\add_filter( 'rank_math/sitemap/1_stylesheet_url', [ $this, 'stylesheet_url' ] );
		\add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );
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
	 * Add file after generation.
	 *
	 * @param string $destination_dir given destination directory.
	 *
	 * @return void
	 */
	public function after_file_fetch( $destination_dir ) {
        $this->save_xsl( $destination_dir );
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
        // For RankMath, we don't know the exact path, so we'll create a common location
        $plugin_dir = Util::combine_path( $destination_dir, '/wp-content/plugins/seo-by-rank-math/assets' );

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
	 * Code copied from RankMath \RankMath\Sitemap\Stylesheet class.
	 *
	 * @return void
	 * @see \RankMath\Sitemap\Stylesheet
	 */
	public function generate_xsl() {
		/* translators: 1. separator, 2. blogname */
		$title = sprintf( __( 'XML Sitemap %1$s %2$s', 'rank-math' ), '-', get_bloginfo( 'name', 'display' ) );

		/* translators: 1. separator, 2. blogname */
		$kml_title = sprintf( __( 'Locations Sitemap %1$s %2$s', 'rank-math' ), '-', get_bloginfo( 'name', 'display' ) );

		require_once RANK_MATH_PATH . 'includes/modules/sitemap/sitemap-xsl.php';
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
     * Perform generic URL replacements in sitemap XML files for Rank Math.
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
            Util::debug_log( 'Error updating Rank Math sitemap URLs: ' . $e->getMessage() );
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
