<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Integration {

	/**
	 * Integration Name.
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var bool
	 */
	protected $always_active = false;

	/**
	 * Integration Description.
	 * @var string
	 */
	protected $description = '';

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
	 * Check if the integration is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$options = Options::instance();
		$integrations = $options->get('integrations');

		// Mainly for backwards compatibility. If there is no such option, it means it's all active.
		if ( empty( $integrations ) ) {
			return true;
		}

		return in_array( $this->id, $integrations, true );
	}

	/**
	 * A Check if this integration can run.
	 * Example: Check if a plugin is activated in DB or a class exists.
	 *
	 * @return boolean
	 */
	public function can_run() {
		if ( $this->always_active ) {
			return true;
		}

		if ( ! $this->dependency_active() ) {
			return false;
		}

		return $this->is_active();
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return true;
	}

	/**
	 * Set if the integration is pro or not.
	 *
	 * @return boolean
	 */
	public function is_pro() {
		return false;
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

	/**
	 * Object used for JS part.
	 *
	 * @return array
	 */
	public function js_object() {
		return [
			'id'            => $this->id,
			'name'          => $this->name,
			'description'   => $this->description,
			'active'        => $this->is_active(),
			'pro'           => $this->is_pro(),
			'can_run'       => $this->dependency_active(),
			'always_active' => $this->always_active
		];
	}
}