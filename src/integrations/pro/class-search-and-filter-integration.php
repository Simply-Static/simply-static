<?php
namespace Simply_Static;

class SearchAndFilter_Integration extends Pro_Integration {
	/**
	 * A string ID of integration.
	 *
	 * @var string
	 */
	protected $id = 'search-and-filter';

	public function __construct() {
		$this->name        = __( 'Search and Filter', 'simply-static' );
		$this->description = __( 'Integrates the popular Search and Filter plugin to be used on static sites.', 'simply-static' );
	}

	public function dependency_active() {
		return class_exists( 'Search_Filter' );
	}

}