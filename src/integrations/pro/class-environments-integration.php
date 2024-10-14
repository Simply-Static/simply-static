<?php
namespace Simply_Static;

class Environments_Integration extends Pro_Integration {

	protected $id = 'environments';

	public function __construct() {
		$this->name = __( 'Environments', 'simply-static' );
		$this->description = __( 'Define multiple environments of Simply Static so you can easily change between saved configurations.', 'simply-static' );
	}

}