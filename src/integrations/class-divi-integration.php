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
		$this->description = __( 'Adds Divi crawler when Divi theme is active to export Divi cache and theme assets.', 'simply-static' );
	}

	/**
	 * Determine if Divi dependency is active (theme detection).
	 *
	 * @return bool
	 */
	public function dependency_active() {
		$tpl        = function_exists( 'get_template' ) ? get_template() : '';
		$stylesheet = function_exists( 'get_stylesheet' ) ? get_stylesheet() : '';
		if ( 'Divi' === $tpl || 'Divi' === $stylesheet ) {
			return true;
		}
		// Fallback checks using common Divi constants or theme directory presence.
		if ( defined( 'ET_CORE_VERSION' ) || defined( 'ET_BUILDER_VERSION' ) ) {
			return true;
		}
		return is_dir( ABSPATH . 'wp-content/themes/Divi' );
	}

	/**
	 * Run the integration.
	 * Only ensure the Divi crawler is active; keep it minimal.
	 */
	public function run() {
		$this->activate_divi_crawler();
	}

	/**
	 * Ensure the Divi crawler is part of the active crawlers without overwriting user choices.
	 * Mirrors Elementor integration behaviour.
	 *
	 * @return void
	 */
	protected function activate_divi_crawler() {
		$options  = Options::instance();
		$crawlers = $options->get( 'crawlers' );

		if ( is_array( $crawlers ) && ! empty( $crawlers ) ) {
			if ( ! in_array( 'divi', $crawlers, true ) ) {
				$crawlers[] = 'divi';
				$options->set( 'crawlers', array_values( array_unique( $crawlers ) ) );
				$options->save();
				Util::debug_log( 'Divi Crawler added to existing crawlers list.' );
			}
		} else {
			// Leave defaults intact when empty/not an array; Divi crawler will be active by default logic.
			Util::debug_log( 'Crawlers option empty or not an array; not modifying. Divi may be active by default.' );
		}
	}
}
