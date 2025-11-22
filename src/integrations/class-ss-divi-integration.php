<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Divi_Integration extends Integration {
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
     // Only consider Divi available when the Divi THEME is active (including child themes).
     // Be lenient with directory/name casing and possible customizations of the folder name.
     // We detect by checking the active theme's template (parent theme directory) for "divi".

     // Prefer wp_get_theme(), which correctly returns the parent template directory for child themes.
     if ( function_exists( 'wp_get_theme' ) ) {
         $theme = wp_get_theme();
         if ( $theme ) {
             // get_template() on WP_Theme is the parent theme directory (string).
             $parent_template_dir = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';
             if ( is_string( $parent_template_dir ) && false !== stripos( $parent_template_dir, 'divi' ) ) {
                 return true;
             }

             // As an additional safeguard, look at the theme names (current and parent) in case
             // the directory name was customized but still clearly refers to Divi.
             $current_name = method_exists( $theme, 'get' ) ? (string) $theme->get( 'Name' ) : '';
             if ( false !== stripos( $current_name, 'divi' ) ) {
                 return true;
             }
             if ( method_exists( $theme, 'parent' ) ) {
                 $parent = $theme->parent();
                 if ( $parent ) {
                     $parent_name = (string) $parent->get( 'Name' );
                     $parent_stylesheet = (string) $parent->get_stylesheet();
                     if ( false !== stripos( $parent_name, 'divi' ) || false !== stripos( $parent_stylesheet, 'divi' ) ) {
                         return true;
                     }
                 }
             }
         }
     }

     // Fallback to classic get_template() check (case-insensitive) if wp_get_theme() is unavailable.
     $tpl = function_exists( 'get_template' ) ? get_template() : '';
     return is_string( $tpl ) && 0 === strcasecmp( $tpl, 'Divi' );
 }

	/**
	 * Run the integration.
	 */
	public function run() {
		$this->disable_divi_performance_options();
	}

	/**
	 * Disable Divi performance-related options that cause issues on static sites.
	 * This only updates existing option keys (no new keys are created).
	 *
	 * @return void
	 */
	protected function disable_divi_performance_options() {
		if ( ! $this->dependency_active() ) {
			return;
		}

		$containers = [ 'et_divi', 'et_pb_options', 'et_core_options', 'et_core_option' ];

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

		$changed_total = 0;

		foreach ( $containers as $option_name ) {
			$opt = get_option( $option_name );
			if ( ! is_array( $opt ) || empty( $opt ) ) {
				continue;
			}

			$changed = 0;

			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $opt ) ) {
					$desired = $this->coerce_disabled_value( $opt[ $key ] );
					if ( $opt[ $key ] !== $desired ) {
						$opt[ $key ] = $desired;
						$changed ++;
					}
				}
			}

			if ( $changed > 0 ) {
				update_option( $option_name, $opt );
				$changed_total += $changed;
				Util::debug_log( sprintf( 'Divi Integration: disabled %d performance option(s) in "%s".', $changed, $option_name ) );
			}
		}

		if ( 0 === $changed_total ) {
			Util::debug_log( 'Divi Integration: no performance options needed changes or were not found.' );
		}
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
