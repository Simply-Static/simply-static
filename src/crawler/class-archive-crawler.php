<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Archive Crawler class
 *
 * This crawler detects archive URLs (yearly, monthly, daily).
 */
class Archive_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'archive';

	/**
	 * Not active by default on new installations.
	 *
	 * @var bool
	 */
	protected $active_by_default = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Archive URLs', 'simply-static' );
		$this->description = __( 'Detects yearly, monthly, and daily archive URLs.', 'simply-static' );
	}

	/**
	 * Detect archive URLs.
	 *
	 * @return array List of archive URLs
	 */
	public function detect() : array {
		$archive_urls = [];

		// Get yearly archives
		$yearly_archives = wp_get_archives(
			[
				'type'   => 'yearly',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|'
			]
		);

		// Get monthly archives
		$monthly_archives = wp_get_archives(
			[
				'type'   => 'monthly',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|'
			]
		);

		// Get daily archives
		$daily_archives = wp_get_archives(
			[
				'type'   => 'daily',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|'
			]
		);

		// Combine all archive HTML
		$archive_html = $yearly_archives . $monthly_archives . $daily_archives;

		// Extract URLs from the HTML
		preg_match_all( '/<a href=["\']([^"\']+)["\']/', $archive_html, $matches );

		if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
			$archive_urls = $matches[1];
		}

		return $archive_urls;
	}
}