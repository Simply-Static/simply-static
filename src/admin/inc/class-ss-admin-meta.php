<?php

namespace Simply_Static;

/**
 * Class to handle meta for Admin_Meta.
 */
class Admin_Meta {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Admin_Meta.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Admin_Meta.
	 */
	public function __construct() {
		if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
			add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		}
	}

	/**
	 * Adds the meta box container.
	 *
	 * @param array $post_type array of post types.
	 *
	 * @return void
	 */
	public function add_metaboxes( $post_type ) {
		$post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ), 'names' );
		$capability = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );

		if ( current_user_can( $capability ) ) {
			add_meta_box( 'single-export-teaser', __( 'Simply Static', 'simply-static-pro' ), array(
				$this,
				'render_simply_static_teaser'
			), apply_filters( 'ssh_single_export_post_types', $post_types ), 'side', 'high' );
		}
	}

	/**
	 * Add static export button.
	 *
	 * @param object $post current post object.
	 *
	 * @return void
	 */
	public function render_simply_static_teaser( $post ) {
		$current_screen = get_current_screen();
		?>
		<?php if ( 'publish' === $post->post_status || method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) : ?>
            <div class="export-actions">
                <p id="export-file-container">
                    <a href="#" class="button button-primary"
                       disabled="true"><?php esc_html_e( 'Export static page', 'simply-static' ); ?></a>
                </p>
                <p><?php esc_html_e( 'Export posts and pages directly with ', 'simply-static' ); ?><a target="_blank"
                                                                                                      href="https://simplystatic.com/pricing/">Simply
                        Static Pro</a>.</p>
            </div>
            <style>
                .export-actions {
                    width: 100%;
                    padding-bottom: 15px;
                }

                div#export-file-container {
                    margin-bottom: 10px;
                }
            </style>
		<?php endif; ?>
		<?php
	}
}
