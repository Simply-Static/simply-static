<?php
namespace Simply_Static;

class Shortpixel_Integration extends Pro_Integration {

	protected $id = 'shortpixel';

	protected $always_active = true;

	public function __construct() {
		$this->name = __( 'Shortpixel', 'simply-static' );
		$this->description = __( 'Optimizes Images before exporting them for static sites.', 'simply-static' );
	}

}