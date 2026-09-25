<?php
namespace Simply_Static;

class Github_Integration extends Pro_Integration {
	protected $id = 'github';

	protected $always_active = true;

	public function __construct() {
		$this->name = __( 'GitHub', 'simply-static' );
		$this->description = __( 'Used when deploying exported sites to GitHub.', 'simply-static' );
	}
}
