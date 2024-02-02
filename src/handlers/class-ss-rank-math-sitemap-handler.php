<?php

namespace Simply_Static;

use RankMath\Helper;
use RankMath\Traits\Hooker;

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
        $destination_path = Util::combine_path( $destination_dir, '/main-sitemap.xsl' );

        if ( file_exists( $destination_path ) ) {
            return;
        }

        Util::debug_log( 'Getting content for main-sitemap.xsl' );
        ob_start();
        $this->generate_xsl();
        $xsl_content   = ob_get_clean();
        $temp_filename = wp_tempnam();
        file_put_contents( $temp_filename, $xsl_content );
        $rename = rename( $temp_filename, $destination_path );

        if ( $rename === false ) {
            Util::debug_log( 'Cannot create ' . $destination_path );
        } else {
            Util::debug_log( 'Created ' . $destination_path );
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
}
