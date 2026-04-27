<?php

namespace Simply_Static;

class Handler_404 extends Page_Handler {


	public function get_relative_dir( $dir ) {
		return '';
	}

	public function get_path_info( $path_info ) {
		$path_info['filename'] = '404';
		$path_info['extension'] = 'html';
		return $path_info;
	}
}