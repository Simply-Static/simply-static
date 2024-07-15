<?php

namespace Simply_Static;

class Brizy_Integration extends Integration {

    /**
     * Given plugin handler ID.
     *
     * @var string Handler ID.
     */
    protected $id = 'brizy';

	public function __construct() {
		$this->name = __( 'Brizy', 'simply-static' );
		$this->description = __( 'Makes sure images optimized by Brizy are exported as well.', 'simply-static' );
	}

    /**
     * Run the integration.
     *
     * @return void
     */
    public function run() {
        add_action( 'simply_static_extracted_url', [ $this, 'maybe_retain_url' ], 20, 2 );
        add_filter( 'simply_static_handler_class_on_url_found', [ $this, 'set_brizy_page_handler' ], 20, 2 );
        add_filter( 'simply_static_pre_converted_url', [ $this, 'change_converted_url' ], 20, 2 );
        $this->include_file( 'handlers/class-brizy-image-handler.php');
    }

    /**
     * @param $url
     * @param \Simply_Static\Page $static_page
     * @return mixed
     */
    public function change_converted_url( $url, $static_page ) {
        if ( ! $this->is_brizy_media( $url ) ) {
            return $url;
        }

        $handler = new Brizy_Image_Handler( $static_page );

        return $handler->get_converted_url( $url );
    }

    public function set_brizy_page_handler( $handler_class, $child_url ) {
        if ( ! $this->is_brizy_media( $child_url ) ) {
            return $handler_class;
        }

        return Brizy_Image_Handler::class;
    }

    /**
     * Let's retain the URL when extracting the data.
     * Otherwise it will look like a homepage instead of image.
     *
     * @param $cleaned_url
     * @param $origin_url
     * @return mixed
     */
    public function maybe_retain_url( $cleaned_url, $origin_url ) {
        if ( ! $this->is_brizy_media( $origin_url ) ) {
            return $cleaned_url;
        }

        return $origin_url;
    }

    public function is_brizy_media( $url ) {
        $parsed_url = parse_url( $url );

        if ( empty( $parsed_url['query'] ) ) {
            return false;
        }

        if ( strpos( $parsed_url['query'], 'brizy_media' ) === false ) {
            return false;
        }

        return true;
    }

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
        return defined( 'BRIZY_VERSION' );
    }
}