<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Divi_Integration extends Integration {
	/**
	 * Temporary backup used while a static export is running.
	 *
	 * @var string
	 */
	const PERFORMANCE_OPTIONS_BACKUP = 'simply_static_divi_performance_options_backup';

	/**
	 * Divi option containers that may be changed during an export.
	 *
	 * @var string[]
	 */
	private const PERFORMANCE_OPTION_CONTAINERS = array( 'et_divi', 'et_pb_options', 'et_core_options', 'et_core_option' );

	/**
	 * Given handler ID.
	 * @var string
	 */
	protected $id = 'divi';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Divi', 'simply-static' );
		$this->description = __( 'Optimizes DIVI for using it on static sites including a custom crawler to include cache files', 'simply-static' );
	}

 /**
  * Determine if Divi dependency is active (theme detection).
  *
  * @return bool
  */
	public function dependency_active() {
		// WP_Theme::get_template() returns the parent theme directory for child themes.
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();

			if ( $theme ) {
				$template = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';
				$name     = method_exists( $theme, 'get' ) ? $theme->get( 'Name' ) : '';

				if ( $this->is_divi_identifier( $template ) || $this->is_divi_identifier( $name ) ) {
					return true;
				}

				if ( method_exists( $theme, 'parent' ) ) {
					$parent = $theme->parent();

					if ( $parent ) {
						$parent_name       = method_exists( $parent, 'get' ) ? $parent->get( 'Name' ) : '';
						$parent_stylesheet = method_exists( $parent, 'get_stylesheet' ) ? $parent->get_stylesheet() : '';

						if ( $this->is_divi_identifier( $parent_name ) || $this->is_divi_identifier( $parent_stylesheet ) ) {
							return true;
						}
					}
				}
			}
		}

		$template = function_exists( 'get_template' ) ? get_template() : '';

		return $this->is_divi_identifier( $template );
	}

	/**
	 * Run the integration.
	 */
	public function run() {
		add_action( 'ss_before_static_export', [ $this, 'prepare_divi_performance_options' ], 10, 0 );
		add_action( 'ss_after_cleanup', [ $this, 'restore_divi_performance_options' ], 10, 0 );
		add_action( 'ss_archive_creation_job_start_failed', [ $this, 'restore_divi_performance_options' ], 10, 0 );
		add_action( 'ss_after_background_queue_reset', [ $this, 'restore_divi_performance_options' ], 10, 0 );
		add_action( 'simply_static_deactivated', [ $this, 'restore_divi_performance_options' ], 10, 0 );
		add_filter( 'ss_after_replace_urls_in_html', [ $this, 'replace_data_fac_urls' ], 10, 2 );
	}

	/**
	 * Disable problematic Divi performance options for the duration of an export.
	 *
	 * Original option containers are persisted before any changes are made so a
	 * later request can restore them during the wrap-up task.
	 *
	 * @return void
	 */
	public function prepare_divi_performance_options() {
		// Recover a backup left by an interrupted export before creating a new one.
		if ( is_array( get_option( self::PERFORMANCE_OPTIONS_BACKUP, null ) ) ) {
			$this->restore_divi_performance_options();
		}

		if ( ! $this->dependency_active() ) {
			return;
		}

		$changes = $this->get_divi_performance_option_changes();

		if ( empty( $changes ) ) {
			return;
		}

		$backup = array();

		foreach ( $changes as $option_name => $change ) {
			$backup[ $option_name ] = array(
				'original'  => $change['original_values'],
				'temporary' => $change['temporary_values'],
			);
		}

		// Store the backup first, so cleanup can recover from a partial update.
		if ( ! update_option( self::PERFORMANCE_OPTIONS_BACKUP, $backup, false ) ) {
			Util::debug_log( 'Divi Integration: could not back up performance options; leaving them unchanged.' );
			return;
		}

		foreach ( $changes as $option_name => $change ) {
			update_option( $option_name, $change['updated'] );
			Util::debug_log( sprintf( 'Divi Integration: temporarily disabled %d performance option(s) in "%s".', $change['count'], $option_name ) );
		}
	}

	/**
	 * Restore Divi performance options after an export or cancellation.
	 *
	 * @return void
	 */
	public function restore_divi_performance_options() {
		$backup = get_option( self::PERFORMANCE_OPTIONS_BACKUP, null );

		if ( ! is_array( $backup ) ) {
			return;
		}

		foreach ( $backup as $option_name => $values ) {
			if ( ! is_string( $option_name ) || ! in_array( $option_name, self::PERFORMANCE_OPTION_CONTAINERS, true ) || ! is_array( $values ) ) {
				continue;
			}

			$original  = isset( $values['original'] ) && is_array( $values['original'] ) ? $values['original'] : array();
			$temporary = isset( $values['temporary'] ) && is_array( $values['temporary'] ) ? $values['temporary'] : array();
			$current    = get_option( $option_name );

			if ( ! is_array( $current ) ) {
				continue;
			}

			$restored = $current;

			foreach ( $original as $key => $original_value ) {
				if ( ! array_key_exists( $key, $temporary ) || ! array_key_exists( $key, $current ) ) {
					continue;
				}

				// Preserve an administrator's concurrent change during a long export.
				if ( $current[ $key ] === $temporary[ $key ] ) {
					$restored[ $key ] = $original_value;
				}
			}

			if ( $restored !== $current ) {
				update_option( $option_name, $restored );
			}
		}

		delete_option( self::PERFORMANCE_OPTIONS_BACKUP );
		Util::debug_log( 'Divi Integration: restored performance options after export.' );
	}

	/**
	 * Replace base64-encoded URLs stored in Divi's data-fac navigation attribute.
	 *
	 * Divi stores navigation link URLs as base64-encoded strings:
	 * <span class="fac_menu" data-fac="BASE64_ENCODED_URL">Link</span>
	 *
	 * This method decodes each data-fac value, replaces the origin URL with the
	 * destination URL, and re-encodes the result so the static site links correctly.
	 *
	 * @param string $body        The HTML body.
	 * @param Page   $static_page The static page being processed.
	 *
	 * @return string Updated HTML body.
	 */
	public function replace_data_fac_urls( string $body, $static_page ): string {
		if ( ! $this->dependency_active() ) {
			return $body;
		}

		$destination_url = Options::instance()->get_destination_url();
		$attrs           = apply_filters( 'ss_divi_base64_url_attributes', array( 'data-fac' ) );

		foreach ( $attrs as $attr ) {
			$attr_pattern = preg_quote( $attr, '/' );
			$body         = preg_replace_callback(
				'/' . $attr_pattern . '=(["\'])([A-Za-z0-9+\/=]+)\1/i',
				function ( $matches ) use ( $destination_url, $attr ) {
					$quote   = $matches[1];
					$encoded = $matches[2];
					$decoded = base64_decode( $encoded, true );

					// Only process values that look like URLs containing our origin host.
					if ( false === $decoded || strpos( $decoded, Util::origin_host() ) === false ) {
						return $matches[0];
					}

					$updated = preg_replace(
						'/(https?:)?\/\/' . Util::origin_host_pattern() . '/i',
						$destination_url,
						$decoded
					);

					$re_encoded = base64_encode( $updated );

					Util::debug_log( sprintf( 'Divi: replaced base64 URL in %s: %s → %s', $attr, $decoded, $updated ) );

					return $attr . '=' . $quote . $re_encoded . $quote;
				},
				$body
			);
		}

		return $body;
	}

	/**
	 * Build temporary changes for Divi performance-related options.
	 *
	 * @return array
	 */
	protected function get_divi_performance_option_changes() {
		// Map of known/likely Divi performance keys to desired "disabled" state.
		$keys = [
			// Minify/Combine
			'minify_combine_js',
			'minify_and_combine_js',
			'minify_combine_css',
			'minify_and_combine_css',
			// Dynamic/Critical CSS
			'enable_dynamic_assets',
			'enable_dynamic_css',
			'enable_critical_css',
			'inline_dynamic_css',
			// Defer/Move jQuery
			'defer_jquery_js',
			'jquery_body',
			'jquery_compatibility',
			// Defer CSS
			'defer_css',
			'defer_styles',
			// Other potentially problematic toggles
			'improve_google_fonts',
			'critical_css',
			'load_dynamic_stylesheet_inline',
			'load_dynamic_stylesheet_in_line',
			'load_dynamic_stylesheet_inline_instead',
			'wp_emoji_disable',
		];

		$changes = array();

		foreach ( self::PERFORMANCE_OPTION_CONTAINERS as $option_name ) {
			$opt = get_option( $option_name );
			if ( ! is_array( $opt ) || empty( $opt ) ) {
				continue;
			}

			$original_values  = array();
			$temporary_values = array();
			$changed          = 0;

			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $opt ) ) {
					$desired = $this->coerce_disabled_value( $opt[ $key ] );
					if ( $opt[ $key ] !== $desired ) {
						$original_values[ $key ]  = $opt[ $key ];
						$temporary_values[ $key ] = $desired;
						$opt[ $key ]               = $desired;
						$changed ++;
					}
				}
			}

			if ( $changed > 0 ) {
				$changes[ $option_name ] = array(
					'original_values'  => $original_values,
					'temporary_values' => $temporary_values,
					'updated'          => $opt,
					'count'            => $changed,
				);
			}
		}

		return $changes;
	}

	/**
	 * Determine whether a theme name or directory is exactly Divi.
	 *
	 * @param mixed $identifier Theme name or directory.
	 *
	 * @return bool
	 */
	protected function is_divi_identifier( $identifier ) {
		if ( ! is_string( $identifier ) ) {
			return false;
		}

		$identifier = trim( trim( str_replace( '\\', '/', $identifier ) ), '/' );

		if ( '' === $identifier ) {
			return false;
		}

		return 0 === strcasecmp( $identifier, 'divi' );
	}

	/**
	 * Convert a current option value to a disabled equivalent while preserving type style.
	 *
	 * @param mixed $current
	 *
	 * @return mixed
	 */
	protected function coerce_disabled_value( $current ) {
		if ( is_bool( $current ) ) {
			return false;
		}
		if ( is_int( $current ) ) {
			return 0;
		}
		if ( is_string( $current ) ) {
			// Common Divi/WordPress toggle convention is 'on'/'off'.
			return 'off';
		}

		// Default fallback
		return false;
	}
}
