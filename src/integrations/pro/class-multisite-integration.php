<?php
namespace Simply_Static;

class Multisite_Integration extends Pro_Integration {
	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'multisite';

	public function __construct() {
		$this->name = __( 'Multisite (Core)', 'simply-static' );
		$this->description = __( 'Allows queued multisite exports and management through network dashboard.', 'simply-static' );
		// Making sure this shows onc Integrations screen if only free plugin is there.
		$this->always_active = defined( 'SIMPLY_STATIC_PRO_VERSION' ) ? true : false;
	}

	public function dependency_active() {
		return is_multisite();
	}

}