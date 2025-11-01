<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Crawler base class
 *
 * This is the base class for all crawler implementations.
 * Each crawler is responsible for detecting URLs of a specific type.
 */
abstract class Crawler {

	/**
	 * Crawler Name.
	 * @var string
	 */
	protected $name = '';

	/**
	 * Crawler Description.
	 * @var string
	 */
	protected $description = '';

	/**
	 * A string ID of crawler.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Active by default.
	 *
	 * @var bool
	 */
	protected $active_by_default = true;

	/**
	 * Whether the crawler's external dependency is active (e.g., plugin/theme).
	 * Crawler implementations can override this.
	 *
	 * @return bool
	 */
	public function dependency_active() : bool {
		return true;
	}

	/**
	 * Check if the crawler is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$options  = \Simply_Static\Options::instance();
		$crawlers = $options->get( 'crawlers' );

		// Distinguish between "not set" (null) and "set to array" (which may be empty).
		// - When null: option not saved yet -> fall back to default behavior.
		// - When array: respect exact selection; empty array means no crawlers active.
		if ( null === $crawlers ) {
			return (bool) $this->active_by_default;
		}

		if ( ! is_array( $crawlers ) ) {
			$crawlers = [];
		}

		return in_array( $this->id, $crawlers, true );
	}

	/**
	 * Detect URLs for this crawler type.
	 *
	 * @return array List of URLs
	 */
	abstract public function detect() : array;

	/**
	 * Add detected URLs to the Simply Static page queue.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue() : int {
		$urls = $this->detect();
		$count = 0;
		$batch_size = apply_filters( 'simply_static_crawler_batch_size', 100 );

		// Determine excluded URL if a custom 404 page is selected
		$opts = \Simply_Static\Options::instance();
		$exclude_url = '';
		if ( $opts->get( 'generate_404' ) && (int) $opts->get( 'custom_404_page' ) ) {
			$permalink = get_permalink( (int) $opts->get( 'custom_404_page' ) );
			if ( $permalink ) {
				$exclude_url = untrailingslashit( $permalink );
			}
		}

		// Process URLs in batches to prevent timeouts
		$batches = array_chunk( $urls, $batch_size );

		foreach ( $batches as $batch ) {
			\Simply_Static\Util::debug_log( sprintf( 'Processing batch of %d URLs for %s crawler', count( $batch ), $this->name ) );

   foreach ( $batch as $url ) {
   				// Skip excluded URLs to avoid adding to DB
   				if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
   					\Simply_Static\Util::debug_log( sprintf( 'Base crawler skipping excluded URL: %s', $url ) );
   					continue;
   				}
				// Skip selected custom 404 page from regular crawl/export
				if ( ! empty( $exclude_url ) ) {
					$normalized = untrailingslashit( $url );
					if ( 0 === strcasecmp( $normalized, $exclude_url ) ) {
						\Simply_Static\Util::debug_log( sprintf( 'Skipping custom 404 page URL "%s" from %s crawler', $url, $this->name ) );
						continue;
					}
				}

				// Create a new Simply_Static\Page for each URL
				$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( sprintf( __( 'Added by %s Crawler', 'simply-static' ), $this->name ) );
				$static_page->found_on_id = 0;
				$static_page->save();
				$count++;
			}

			// Allow other processes to run
			if ( count( $batches ) > 1 ) {
				\Simply_Static\Util::debug_log( 'Yielding to allow other processes to run' );
				usleep( 100000 ); // 0.1 seconds in microseconds
			}
		}

		return $count;
	}

	/**
	 * Get crawler information for JS part.
	 *
	 * @return array
	 */
	public function js_object() {
		return [
			'id'          => $this->id,
			'name'        => $this->name,
			'description' => $this->description,
			'active'      => $this->is_active(),
			'can_run'     => $this->dependency_active(),
		];
	}
}
