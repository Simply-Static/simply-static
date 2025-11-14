<?php
namespace Simply_Static;

class Pro_Integration extends Integration {

	public function is_pro() {
		return true;
	}

	public function can_run() {
		return false;
	}

}