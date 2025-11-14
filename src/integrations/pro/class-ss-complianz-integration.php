<?php
namespace Simply_Static;

class Complianz_Integration extends Pro_Integration {
	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'complianz';

	public function __construct() {
		$this->name        = __( 'Complianz | GDPR/CCPA Cookie Consent', 'simply-static' );
		$this->description = __( 'Integrates Complianz Cookie banner to work on the static site.', 'simply-static' );
	}

	public function dependency_active() {
		return class_exists( 'COMPLIANZ' );
	}

}