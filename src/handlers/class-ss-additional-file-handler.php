<?php

namespace Simply_Static;

class Additional_File_Handler extends Page_Handler {
	/**
	 * Additional files must retain their source filename even without an extension.
	 *
	 * @return bool
	 */
	public function should_preserve_extensionless_filename() {
		return true;
	}
}
