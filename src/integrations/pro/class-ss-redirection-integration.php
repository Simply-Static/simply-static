<?php
namespace Simply_Static;

class Redirection_Integration extends Pro_Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'redirection';

	public function __construct() {
		$this->name = __( 'Redirects', 'simply-static' );
		$this->description = __( 'Automatically includes exact redirects from Redirection, Safe Redirect Manager, and Page Links To in each export.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'REDIRECTION_FILE' )
			|| function_exists( 'srm_get_redirects' )
			|| class_exists( '\\SRM_Safe_Redirect_Manager' )
			|| function_exists( 'plt_get_original_permalink' )
			|| function_exists( 'plt_get_original_link' )
			|| class_exists( '\\CWS_PageLinksTo' );
	}
}
