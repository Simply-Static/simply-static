<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Integration {

	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Load the integration.
	 *
	 * @return void
	 */
	public function load() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->run();
	}


	/**
	 * Check if such Integration is enabled
	 *
	 * @return mixed|null
	 */
	public function is_enabled() {
		return apply_filters( 'simply_static_integration_' . $this->id . 'enabled', $this->can_run() );
	}

	/**
	 * A Check if this integration can run.
	 * Example: Check if a plugin is activated in DB or a class exists.
	 *
	 * @return boolean
	 */
	public function can_run() {
		return true;
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
	}

	/**
	 * Include File.
	 *
	 * @param string $path given path.
	 *
	 * @return void
	 */
	public function include_file( $path ) {
		require_once trailingslashit( SIMPLY_STATIC_PATH ) . 'src/' . $path;
	}
}