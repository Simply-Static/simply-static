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
		$this->name = __( 'Redirection', 'simply-static' );
		$this->description = __( 'Integrates redirections from the "Redirection" Plugin automatically on each export.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'REDIRECTION_FILE' );
	}
}