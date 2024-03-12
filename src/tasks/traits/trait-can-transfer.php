<?php
namespace Simply_Static;

trait canTransfer {

	/**
	 * @param Page $static_page object.
	 *
	 * @return string
	 */
	protected function get_page_file_path( $static_page ) {
		return apply_filters( 'ss_get_page_file_path_for_transfer', $static_page->file_path, $static_page );
	}
}