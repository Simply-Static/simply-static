<?php

namespace Simply_Static;

class Compatibility {
	/**
	 * Slug for simply-static-compatible.
	 *
	 * @var string
	 */
	const SS_COMPATIBLE = 'simply-static-compatible';

	/**
	 * List of AMP plugins.
	 *
	 * @var array
	 */
	protected $plugins = [];

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Compatibility.
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
	 * Run the integration.
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'install_plugins_tabs', [ $this, 'add_tab' ] );
		add_filter( 'install_plugins_table_api_args_' . self::SS_COMPATIBLE, [
			$this,
			'filter_plugins_table_api_args'
		] );
		add_filter( 'plugin_install_action_links', [ $this, 'filter_action_links' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'filter_plugin_row_meta' ], 10, 3 );

		add_action( 'install_plugins_' . self::SS_COMPATIBLE, 'display_plugins_table' );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Get list of AMP plugins.
	 *
	 * @return array List of AMP plugins.
	 */
	public function get_plugins() {

		if ( count( $this->plugins ) === 0 ) {
			$this->plugins = array_map(
				static function ( $plugin ) {
					return self::normalize_plugin_data( $plugin );
				},
				require SIMPLY_STATIC_PATH . 'src/plugins.php'
			);

			usort(
				$this->plugins,
				static function ( $a, $b ) {
					return strcasecmp( $a['name'], $b['name'] );
				}
			);
		}

		return $this->plugins;
	}

	/**
	 * Normalize plugin data.
	 *
	 * @param array $plugin Plugin data.
	 *
	 * @return array Normalized plugin data.
	 */
	public static function normalize_plugin_data( $plugin = [] ) {

		$default = [
			'name'                     => '',
			'slug'                     => '',
			'version'                  => '',
			'author'                   => '',
			'author_profile'           => '',
			'requires'                 => '',
			'tested'                   => '',
			'requires_php'             => '',
			'rating'                   => 0,
			'ratings'                  => [
				1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
			],
			'num_ratings'              => 0,
			'support_threads'          => 0,
			'support_threads_resolved' => 0,
			'active_installs'          => 0,
			'downloaded'               => 0,
			'last_updated'             => '',
			'added'                    => '',
			'homepage'                 => '',
			'short_description'        => '',
			'description'              => '',
			'download_link'            => '',
			'tags'                     => [],
			'donate_link'              => '',
			'icons'                    => [
				'1x'  => '',
				'2x'  => '',
				'svg' => '',
			],
			'wporg'                    => false,
		];

		$plugin['ratings'] = ( ! empty( $plugin['ratings'] ) && is_array( $plugin['ratings'] ) ) ? $plugin['ratings'] : [];
		$plugin['ratings'] = $plugin['ratings'] + $default['ratings'];

		$plugin['icons'] = ( ! empty( $plugin['icons'] ) && is_array( $plugin['icons'] ) ) ? $plugin['icons'] : [];
		$plugin['icons'] = wp_parse_args( $plugin['icons'], $default['icons'] );

		return wp_parse_args( $plugin, $default );
	}

	/**
	 * Enqueue style for plugin install page.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, [
			'plugin-install',
			'plugin-install-network'
		], true ) ) {
			wp_enqueue_script( 'simply_static_plugins', SIMPLY_STATIC_URL . '/assets/simply-static-plugins.js', array(), '1.0', true );

			wp_localize_script( 'simply_static_plugins', 'compatible_plugins', [
				'SS_COMPATIBLE' => self::SS_COMPATIBLE,
				'AMP_PLUGINS'   => wp_list_pluck( $this->get_plugins(), 'slug' ),
				'icon'          => SIMPLY_STATIC_URL . '/assets/simply-static-icon.svg',
			] );
		}
	}

	/**
	 * Add extra tab in plugin install screen.
	 *
	 * @param array $tabs List of tab in plugin install screen.
	 *
	 * @return array List of tab in plugin install screen.
	 */
	public function add_tab( $tabs ) {

		return array_merge(
			$tabs,
			[
				self::SS_COMPATIBLE => esc_html__( 'Simply Static Compatible', 'simply-static' ),
			]
		);
	}

	/**
	 * Modify args for the plugins_api query on the AMP-compatible tab in plugin install screen.
	 *
	 * @return array
	 */
	public function filter_plugins_table_api_args() {

		$per_page   = 36;
		$total_page = ceil( count( $this->get_plugins() ) / $per_page );
		$pagenum    = isset( $_REQUEST['paged'] ) ? (int) $_REQUEST['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pagenum    = ( $pagenum > $total_page ) ? $total_page : $pagenum;
		$page       = max( 1, $pagenum );

		return [
			self::SS_COMPATIBLE => true,
			'per_page'          => $per_page,
			'page'              => $page,
		];
	}


	/**
	 * Update action links for plugin card in plugin install screen.
	 *
	 * @param array $actions List of action button's markup for plugin card.
	 * @param array $plugin Plugin detail.
	 *
	 * @return array List of action button's markup for plugin card.
	 */
	public function filter_action_links( $actions, $plugin ) {

		if ( isset( $plugin['wporg'] ) && true !== $plugin['wporg'] ) {
			$actions       = [];
			$external_icon = '<span aria-hidden="true" class="dashicons dashicons-external"></span>';

			if ( ! empty( $plugin['homepage'] ) ) {
				$actions[] = sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s<span class="screen-reader-text">%s</span>%s</a>',
					esc_url( $plugin['homepage'] ),
					esc_attr(
					/* translators: %s: Plugin name */
						sprintf( __( 'Site link of %s', 'amp' ), $plugin['name'] )
					),
					esc_html__( 'Visit site', 'amp' ),
					esc_html__( '(opens in a new tab)', 'amp' ),
					$external_icon
				);
			}
		}

		return $actions;
	}

	/**
	 * Add plugin metadata for AMP compatibility in plugin listing page.
	 *
	 * @param string[] $plugin_meta An array of the plugin's metadata, including
	 *                              the version, author, author URI, and plugin URI.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array $plugin_data An array of plugin data.
	 *
	 * @return string[] An array of the plugin's metadata
	 */
	public function filter_plugin_row_meta( $plugin_meta, /** @noinspection PhpUnusedParameterInspection */ $plugin_file, $plugin_data ) {

		$amp_plugins = wp_list_pluck( $this->get_plugins(), 'slug' );

		if ( ! empty( $plugin_data['slug'] ) && in_array( $plugin_data['slug'], $amp_plugins, true ) ) {
			$plugin_meta[] = esc_html__( 'Simply Static Compatible', 'amp' );
		}

		return $plugin_meta;
	}
}