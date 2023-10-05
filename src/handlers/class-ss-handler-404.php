<?php

namespace Simply_Static;

class Handler_404 extends Page_Handler {


	public function get_relative_dir( $dir ) {
		return '404/';
	}
}