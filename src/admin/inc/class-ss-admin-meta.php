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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_single_push_teaser' ) );
		}
	}

	/**
	 * Enqueue the single push teaser script on post editor screens.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_single_push_teaser( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ), 'names' );
		$post_types = apply_filters( 'ss_single_export_post_types', $post_types );

		$current_post_type = get_post_type();
		if ( ! $current_post_type ) {
			$current_post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'post';
		}

		if ( ! in_array( $current_post_type, $post_types, true ) ) {
			return;
		}

		$capability = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		wp_enqueue_script(
			'ss-single-push-teaser',
			SIMPLY_STATIC_URL . '/assets/ss-single-push-teaser.js',
			array(),
			SIMPLY_STATIC_VERSION,
			true
		);
	}
}
