<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Author Crawler class
 *
 * This crawler detects author URLs.
 */
class Author_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'author';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Author URLs', 'simply-static' );
		$this->description = __( 'Detects author archive URLs.', 'simply-static' );
	}

	/**
	 * Detect author URLs.
	 *
	 * @return array List of author URLs
	 */
	public function detect() : array {
		$author_urls = [];
		$users = get_users();

		foreach ( $users as $author ) {
			$author_link = get_author_posts_url( $author->ID );

			if ( ! is_string( $author_link ) ) {
				continue;
			}

			$permalink = trim( $author_link );
			$author_urls[] = $permalink;
		}

		return $author_urls;
	}
}