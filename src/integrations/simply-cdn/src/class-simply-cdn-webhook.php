<?php

namespace Simply_Static;

/**
 * Class to handle form webhooks.
 */
class Simply_CDN_Webhook {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Webhook.
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
	 * Constructor for Simply_CDN_Webhook.
	 *
	 * @return void
	 */
	public function __construct() {
		$options = get_option( 'simply-static' );

		if ( $options['ssh_use_forms'] ) {
			add_action( 'wp_head', array( $this, 'add_meta_tags' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_webhook_scripts' ) );
			add_filter( 'wpcf7_load_js', '__return_false' );
			add_filter( 'gform_form_args', array( $this, 'disable_ajax' ) );
			add_action( 'wp_footer', array( $this, 'hide_elementor_ajax_errors' ) );
		}
	}

	/**
	 * Add thank you page path as meta tag.
	 *
	 * @return void
	 */
	public function add_meta_tags() {
		$options            = get_option( 'simply-static' );
		$cdn_thank_you_path = str_replace( home_url(), '', get_permalink( $options['ssh_thank_you_page_id'] ) );
		?>

		<?php if ( $cdn_thank_you_path ) : ?>
            <meta name="ssh-thank-you-path" content="<?php echo esc_html( $cdn_thank_you_path ); ?>">
		<?php endif; ?>
		<?php
	}

	/**
	 * Enqueue scripts for webhook.
	 *
	 * @return void
	 */
	public function add_webhook_scripts() {
		wp_enqueue_script( 'ssh-form-webhook-js', SIMPLY_STATIC_URL . '/src/integrations/simply-cdn/assets/ssh-form-webhook.js', array(), SIMPLY_STATIC_VERSION, true );
	}

	/**
	 * Disable ajax in Gravity Forms.
	 *
	 * @param array $args given list or arguments.
	 *
	 * @return mixed
	 */
	public function disable_ajax( $args ) {
		$args['ajax'] = false;

		return $args;
	}

	/**
	 * Hide elementor ajax errors.
	 *
	 * @return void
	 */
	public function hide_elementor_ajax_errors() {
		?>
        <style>
            .elementor-message.elementor-message-danger {
                display: none;
            }
        </style>
		<?php
	}
}
