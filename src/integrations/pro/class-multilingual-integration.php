<?php
namespace Simply_Static;

class Multilingual_Integration extends Pro_Integration {
	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'multilingual';

	public function __construct() {
		$this->name = __( 'WPML - Multilingual', 'simply-static' );
		$this->description = __( 'Integrates WPML to work with exported sites.', 'simply-static' );
	}

}