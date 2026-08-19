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
		$max_users = max( 1, min( 100000, (int) apply_filters( 'simply_static_author_detection_limit', 1000 ) ) );
		$users = get_users( array( 'number' => $max_users, 'orderby' => 'ID', 'order' => 'ASC' ) );

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

	/**
	 * Traverse authors across bounded background requests.
	 *
	 * @return int Number of URLs added by this invocation.
	 */
	public function add_urls_to_queue() : int {
		$signature = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'blog_id'            => get_current_blog_id(),
		) ) );
		$state = \Simply_Static\Options::instance()->get( 'author_crawler_state' );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) || ! is_int( $state['offset'] ?? null ) || $state['offset'] < 0 ) {
			$state = array( 'version' => 1, 'signature' => $signature, 'offset' => 0, 'added' => 0, 'scanned' => 0 );
		}

		$batch_size = max( 1, min( 500, (int) apply_filters( 'simply_static_author_crawler_batch_size', 100 ) ) );
		$user_ids   = get_users( array(
			'fields'  => 'ID',
			'number'  => $batch_size,
			'offset'  => $state['offset'],
			'orderby' => 'ID',
			'order'   => 'ASC',
		) );
		$user_ids = is_array( $user_ids ) ? $user_ids : array();
		$urls     = array();
		foreach ( $user_ids as $user_id ) {
			$url = get_author_posts_url( (int) $user_id );
			if ( is_string( $url ) && '' !== trim( $url ) ) {
				$urls[] = trim( $url );
			}
		}

		$added            = $this->enqueue_urls( $urls );
		$state['offset']  += count( $user_ids );
		$state['scanned'] += count( $user_ids );
		$state['added']   += $added;
		$this->progress    = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		$this->complete    = count( $user_ids ) < $batch_size;
		if ( $this->complete ) {
			$this->clear_crawler_state( 'author_crawler_state' );
		} else {
			$this->save_crawler_state( 'author_crawler_state', $state );
		}

		return $added;
	}
}
