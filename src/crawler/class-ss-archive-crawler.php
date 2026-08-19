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
		$max_archives = max( 1, min( 100000, (int) apply_filters( 'simply_static_archive_detection_limit', 1000 ) ) );

		// Get yearly archives
		$yearly_archives = wp_get_archives(
			[
				'type'   => 'yearly',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|',
				'limit'  => $max_archives,
			]
		);

		// Get monthly archives
		$monthly_archives = wp_get_archives(
			[
				'type'   => 'monthly',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|',
				'limit'  => $max_archives,
			]
		);

		// Get daily archives
		$daily_archives = wp_get_archives(
			[
				'type'   => 'daily',
				'echo'   => 0,
				'format' => 'custom',
				'before' => '',
				'after'  => '|',
				'limit'  => $max_archives,
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

	/**
	 * Build archive URLs from a bounded page of published post dates.
	 *
	 * @return int Number of URLs added by this invocation.
	 */
	public function add_urls_to_queue() : int {
		global $wpdb;

		$signature = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'blog_id'            => get_current_blog_id(),
		) ) );
		$state = \Simply_Static\Options::instance()->get( 'archive_crawler_state' );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) || ! is_int( $state['last_post_id'] ?? null ) || $state['last_post_id'] < 0 ) {
			$state = array( 'version' => 1, 'signature' => $signature, 'last_post_id' => 0, 'added' => 0, 'scanned' => 0 );
		}

		$batch_size = max( 1, min( 500, (int) apply_filters( 'simply_static_archive_crawler_batch_size', 100 ) ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_date FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND ID > %d ORDER BY ID ASC LIMIT %d",
				$state['last_post_id'],
				$batch_size
			),
			ARRAY_A
		);
		$rows = is_array( $rows ) ? $rows : array();
		$urls = array();
		foreach ( $rows as $row ) {
			$state['last_post_id'] = max( $state['last_post_id'], (int) $row['ID'] );
			$date = isset( $row['post_date'] ) ? (string) $row['post_date'] : '';
			if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $date, $parts ) ) {
				continue;
			}
			$year  = (int) $parts[1];
			$month = (int) $parts[2];
			$day   = (int) $parts[3];
			$urls[ get_year_link( $year ) ]               = get_year_link( $year );
			$urls[ get_month_link( $year, $month ) ]       = get_month_link( $year, $month );
			$urls[ get_day_link( $year, $month, $day ) ]   = get_day_link( $year, $month, $day );
		}

		$added             = $this->enqueue_urls( array_values( $urls ) );
		$state['added']    += $added;
		$state['scanned']  += count( $rows );
		$this->progress     = array( 'added' => $state['added'], 'scanned' => $state['scanned'] );
		$this->complete     = count( $rows ) < $batch_size;
		if ( $this->complete ) {
			$this->clear_crawler_state( 'archive_crawler_state' );
		} else {
			$this->save_crawler_state( 'archive_crawler_state', $state );
		}

		return $added;
	}
}
