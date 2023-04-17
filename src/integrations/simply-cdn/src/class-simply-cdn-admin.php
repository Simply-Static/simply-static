<?php

namespace Simply_Static;

/**
 * Class to handle admin settings
 */
class Simply_CDN_Admin {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Simply_CDN_Admin.
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
	 * Constructor for Simply_CDN_Admin.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'added_option', array( $this, 'set_default_configuration' ), 10, 2 );
		add_action( 'wp_ajax_update_token', array( $this, 'update_token' ) );
		add_action( 'wp_ajax_clear_cache', array( $this, 'reset_cache' ) );

		// Include only if connected.
		$token = get_option( 'sch_token' );

		if ( ! empty( $token ) ) {
			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );

			// Only include if Simply Static Pro is not installed.
			if ( ! class_exists( '\simply_static_pro\Build_Settings' ) ) {
				add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 500 );
			}
		}
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @return void
	 */
	public function add_admin_scripts() {
		wp_enqueue_style( 'sch-admin-style', SIMPLY_STATIC_URL . '/src/integrations/simply-cdn/assets/sch-admin.css', array(), Plugin::VERSION, 'all' );
		wp_enqueue_script( 'sch-admin', SIMPLY_STATIC_URL . '/src/integrations/simply-cdn/assets/sch-admin.js', array( 'jquery' ), Plugin::VERSION, true );

		$args = array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'cache_nonce'     => wp_create_nonce( 'sch-cache-nonce' ),
			'token_nonce'     => wp_create_nonce( 'sch-token-nonce' ),
			'token_connected' => esc_html__( 'Your website is successfully connected to the Simply CDN.', 'simply-static' ),
			'cache_cleared'   => esc_html__( 'Cache cleared successfully.', 'simply-static' ),
		);

		wp_localize_script( 'sch-admin', 'sch_ajax', $args );

	}

	/**
	 * Register settings in WordPress.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'sch_options_group', 'sch_token', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => null
		) );
		register_setting( 'sch_cdn_group', 'sch_static_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => null
		) );
		register_setting( 'sch_cdn_group', 'sch_404_path', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => null
		) );

		register_setting( 'sch_forms_group', 'sch_use_forms', array(
			'type'              => 'string',
			'sanitize_callback' => array(
				$this,
				'sanitize_checkbox'
			),
			'default'           => false
		) );

		register_setting( 'sch_automation_group', 'sch_use_auto_publish', array(
			'type'              => 'string',
			'sanitize_callback' => array(
				$this,
				'sanitize_checkbox'
			),
			'default'           => false
		) );
	}

	public function sanitize_checkbox( $input ) {
		return isset( $input );
	}

	/**
	 * Register menu page for settings.
	 *
	 * @return void
	 */
	public function register_menu_page() {
		add_submenu_page( 'simply-static', esc_html__( 'Simply CDN', 'simply-static' ), esc_html__( 'Simply CDN', 'simply-static' ), 'manage_options', 'simply-static_cdn', array(
			$this,
			'render_options'
		), 10 );
	}

	/**
	 * Render options form.
	 *
	 * @return void
	 */
	public function render_options() {
		$screen = get_current_screen();

		if ( 'simply-static_page_simply-static_cdn' !== $screen->id ) {
			return;
		}

		$token = get_option( 'sch_token' );
		$data  = Simply_CDN_Api::get_data( $token );
		?>
        <div class="sch-container">
        <h1><?php esc_html_e( 'Simply CDN', 'simply-static' ); ?></h1>
		<?php if ( $token ) : ?>
            <div class="wrap">
                <div>
                    <p>
                    <h2><?php esc_html_e( 'Configure your static website', 'simply-static' ); ?></h2>
                    </p>
                    <p>
						<?php esc_html_e( 'Once your website is connected you can configure all settings related to the CDN here. This includes settings up redirects, proxy URLs and setting up a custom 404 error page.', 'simply-static' ); ?>
                    </p>
                    <form method="post" action="options.php">
						<?php settings_fields( 'sch_cdn_group' ); ?>
                        <p>
                            <label for="sch_static_url"><?php esc_html_e( 'Static URL', 'simply-static' ); ?></label><br>
                            <input type="url" id="sch_static_url" name="sch_static_url"
                                   value="<?php echo esc_html( get_option( 'sch_static_url' ) ); ?>"
                                   disabled="disabled"/>
                            <small><?php esc_html_e( 'This is your static site URL. We automatically change it based on your project configuration on simplycdn.io', 'simply-static' ); ?></small>
                        </p>
                        <p>
                            <label for="sch_404_path"><?php esc_html_e( 'Relative path to your 404 page', 'simply-static' ); ?></label><br>
                            <input type="text" id="sch_404_path" name="sch_404_path"
                                   value="<?php echo esc_html( get_option( 'sch_404_path' ) ); ?>"/>
                        </p>
						<?php submit_button(); ?>
                    </form>
                </div>
                <div>
                </div>
            </div>
			<?php if ( ! class_exists( '\simply_static_pro\Form_Webhook' ) ) : ?>
                <div class="wrap">
                    <div>
                        <p>
                        <h2><?php esc_html_e( 'Forms integration', 'simply-static' ); ?></h2>
                        </p>
                        <p>
							<?php esc_html_e( 'We automatically send form submissions to the configured e-mail address of your project from message@simplycdn.io.', 'simply-static' ); ?>
                        </p>
                        <p>
							<?php esc_html_e( 'Make sure to add your form token as a hidden field to each form of your website.', 'simply-static' ); ?>
                        </p>
                        <p>
                            <b><?php esc_html_e( 'Your form token: ', 'simly-static' ); ?></b><?php echo esc_html( $data->cdn->form_token ); ?>
                        </p>
                        <form method="post" action="options.php">
							<?php settings_fields( 'sch_forms_group' ); ?>
                            <p>
                                <label for="sch_use_forms">
                                    <input type="checkbox" name="sch_use_forms"
                                           value="1" <?php checked( 1, get_option( 'sch_use_forms' ), true ); ?> />
									<?php esc_html_e( 'Use Forms integration', 'simply-static' ); ?>
                                </label>
                            </p>
							<?php submit_button(); ?>
                        </form>
                    </div>
                    <div>
                    </div>
                </div>
			<?php endif; ?>
            <div class="wrap">
                <div>
					<?php if ( ! class_exists( '\simply_static_pro\Single' ) ) : ?>
                        <p>
                        <h2><?php esc_html_e( 'Automation & Utilities', 'simply-static' ); ?></h2>
                        </p>
                        <p>
							<?php esc_html_e( 'Automatically updates a post/page on your static website once you saved it in WordPress.', 'simply-static' ); ?>
                        </p>
                        <form method="post" action="options.php">
							<?php settings_fields( 'sch_automation_group' ); ?>
                            <p>
                                <label for="sch_use_auto_publish">
                                    <input type="checkbox" name="sch_use_auto_publish"
                                           value="1" <?php checked( 1, get_option( 'sch_use_auto_publish' ), true ); ?> />
									<?php esc_html_e( 'Use Auto-Publish', 'simply-static' ); ?>
                                </label>
                            </p>
							<?php submit_button(); ?>
                        </form>
					<?php endif; ?>
                    <div>
                        <h2><?php esc_html_e( 'Caching', 'simly-static' ); ?></h2>
						<?php esc_html_e( 'The CDN cache is cleared automatically after each static export. Sometimes you want to clear the cache manually to make sure you get the latest results in your browser.', 'simply-static' ); ?>
                        <p>
                        <span class="button-secondary button sch-secondary-button"
                              id="sch-clear-cache"><?php esc_html_e( 'Clear Cache', 'simply-static' ); ?></span>
                        </p>
                    </div>
                </div>
            </div>

            </div>
		<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Add admin bar menu to visit static website.
	 *
	 * @param \WP_Admin_Bar $admin_bar current admin bar object.
	 *
	 * @return void
	 */
	public function add_admin_bar_menu( $admin_bar ) {
		global $post;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$static_url = get_option( 'sch_static_url' );

		// Additional Path set?
		if ( ! empty( $options['relative_path'] ) ) {
			$static_url = $static_url . $options['relative_path'];
		}

		// If the current page has an post id we get the permalink and replace it.
		if ( ! empty( $post ) && ! empty( $static_url ) ) {
			$permalink  = get_permalink( $post->ID );
			$static_url = str_replace( untrailingslashit( get_bloginfo( 'url' ) ), untrailingslashit( $static_url ), $permalink );
		}

		if ( ! empty( $static_url ) ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'static-site',
					'parent' => null,
					'group'  => null,
					'title'  => esc_html__( 'View static URL', 'simply-static' ),
					'href'   => $static_url,
					'meta'   => array(
						'title' => esc_html__( 'View static URL', 'simply-static' ),
					),
				)
			);
		}
	}

	/**
	 * Update token with ajax.
	 *
	 * @return void
	 */
	public function update_token() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'sch-token-nonce' ) ) {
			die();
		}

		$token = sanitize_text_field( $_POST['security-token'] );
		update_option( 'sch_token', $token );

		$data = Simply_CDN_Api::get_data( $token );

		if ( $data && ! empty( $data->cdn->url ) ) {
			update_option( 'sch_static_url', esc_url( $data->cdn->url ) );

			$response = array( 'success' => true );
		} else {
			$response = array(
				'success'       => false,
				'error_message' => esc_html__( 'There is something wrong with that security token.', 'simply-static' )
			);

			delete_option( 'sch_token' );
		}

		print wp_json_encode( $response );
		exit;
	}

	/**
	 * Clear cache
	 *
	 * @return void
	 */
	public function reset_cache() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'sch-cache-nonce' ) ) {
			die();
		}

		$token    = get_option( 'sch_token' );
		$response = wp_remote_get( 'https://simplycdn.io?security-token=' . $token . '&clear-cache=true', array() );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$response = array( 'success' => true );
			} else {
				$response = array(
					'success'       => false,
					'error_message' => esc_html__( 'There is something wrong with that security token.', 'simply-static' )
				);
			}
		} else {
			$response = array(
				'success'       => false,
				'error_message' => esc_html__( 'There is something wrong with that security token.', 'simply-static' )
			);
		}

		print wp_json_encode( $response );
		exit;
	}

	/**
	 * Set default configuration for Simply Static on saving security token.
	 *
	 * @param string $option given option.
	 * @param string $value the new value.
	 *
	 * @return void
	 */
	public function set_default_configuration( $option, $value ) {

		if ( 'sch_token' === $option && ! empty( $value ) ) {
			// Apply default configuration.
			$options = get_option( 'simply-static' );
			$token   = get_option( 'sch_token' );
			$data    = Simply_CDN_Api::get_data( $token );

			if ( $data && $options ) {
				$static_url = wp_parse_url( $data->cdn->url );

				$options['destination_url_type'] = 'absolute';
				$options['destination_scheme']   = 'https://';
				$options['destination_host']     = $static_url['host'];
				$options['delivery_method']      = 'simply-cdn';
				$options['force_replace_url']    = 'on';

				update_option( 'simply-static', $options );
			}
		}
	}
}



