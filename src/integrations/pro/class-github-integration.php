<?php
namespace Simply_Static;

class Github_Integration extends Pro_Integration {
	protected $id = 'github';

	protected $always_active = true;

	public function __construct() {
		$this->name = __( 'Github', 'simply-static' );
		$this->description = __( 'Used when deploying the exported sites to Github', 'simply-static' );
	}
}