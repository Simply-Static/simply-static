<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Beaver Builder Crawler class
 *
 * This crawler detects URLs for Beaver Builder cached assets stored in
 * wp-content/uploads/bb-plugin/cache/ and adds them to the export queue.
 */
class Beaver_Builder_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'beaver-builder';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Beaver Builder Cache', 'simply-static' );
		$this->description = __( 'Detects Beaver Builder cache files in uploads/bb-plugin/cache.', 'simply-static' );
	}

	/**
	 * Check if Beaver Builder is active.
	 *
	 * @return bool
	 */
	public function dependency_active() : bool {
		return defined( 'FL_BUILDER_VERSION' );
	}

	/**
	 * Ensure dependency is active and user selection allows this crawler.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! $this->dependency_active() ) {
			return false;
		}
		return parent::is_active();
	}

	/**
	 * Detect Beaver Builder cache asset URLs.
	 * Note: For large caches we prefer the streaming add_urls_to_queue(), but this
	 * method is implemented for completeness and potential UI previews.
	 *
	 * @return array
	 */
	public function detect() : array {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return [];
		}

		$dir = trailingslashit( $uploads['basedir'] ) . 'bb-plugin/cache';
		$base_url = trailingslashit( $uploads['baseurl'] ) . 'bb-plugin/cache';

		if ( ! is_dir( $dir ) ) {
			return [];
		}

		$urls = $this->scan_directory_for_assets( $dir, $base_url );
		$urls = array_values( array_unique( $urls ) );
		\Simply_Static\Util::debug_log( sprintf( 'Beaver Builder crawler detected %d cache URLs', count( $urls ) ) );
		return $urls;
	}

	/**
	 * Stream Beaver Builder cache URLs directly into the queue in batches.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return 0;
		}

		$dir = trailingslashit( $uploads['basedir'] ) . 'bb-plugin/cache';
		$base_url = trailingslashit( $uploads['baseurl'] ) . 'bb-plugin/cache';

		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$count    = 0;
		$batch    = [];
		$batch_sz = (int) apply_filters( 'simply_static_crawler_batch_size', 100 );

		$extensions = [ 'css','js','png','jpg','jpeg','gif','svg','webp','woff','woff2','ttf','eot','otf','ico','mp4','webm','json','map' ];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_beaver_builder_directories', [ '.git','node_modules','vendor/bin','vendor/composer','tests' ] );

		try {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ( $it as $file ) {
				if ( $file->isDir() ) { continue; }
				$rel = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
				$skip = false;
				foreach ( (array) $skip_dirs as $sd ) {
					if ( $sd && strpos( $rel, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
				}
				if ( $skip ) { continue; }
				$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
				if ( $ext && ! in_array( $ext, $extensions, true ) ) { continue; }
				$batch[] = \Simply_Static\Util::safe_join_url( $base_url, $rel );
				if ( count( $batch ) >= $batch_sz ) {
					$count += $this->enqueue_urls_batch( $batch );
					$batch = [];
					usleep( 100000 );
				}
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( 'Error streaming Beaver Builder cache crawl: ' . $e->getMessage() );
		}

		if ( ! empty( $batch ) ) {
			$count += $this->enqueue_urls_batch( $batch );
		}

		\Simply_Static\Util::debug_log( sprintf( 'Beaver Builder crawler added %d URLs (streamed)', $count ) );
		return $count;
	}

	/**
	 * Helper: enqueue a batch of URLs.
	 *
	 * @param array $urls
	 * @return int
	 */
	protected function enqueue_urls_batch( array $urls ): int {
		$added = 0;
  foreach ( $urls as $url ) {
			// Skip URLs that are excluded by settings/patterns
			if ( \Simply_Static\Util::is_url_excluded( $url ) ) {
				\Simply_Static\Util::debug_log( sprintf( 'Beaver Builder crawler skipping excluded URL: %s', $url ) );
				continue;
			}
			$static_page = \Simply_Static\Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Beaver Builder Cache', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
			$added++;
		}
		return $added;
	}

	/**
	 * Scan directory for assets and return their URLs.
	 *
	 * @param string $dir_root
	 * @param string $url_base
	 * @return array
	 */
	protected function scan_directory_for_assets( $dir_root, $url_base ) : array {
		$urls = [];
		$extensions = [ 'css','js','png','jpg','jpeg','gif','svg','webp','woff','woff2','ttf','eot','otf','ico','mp4','webm','json','map' ];
		$skip_dirs  = apply_filters( 'ss_skip_crawl_beaver_builder_directories', [ '.git','node_modules','vendor/bin','vendor/composer','tests' ] );

		try {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir_root, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ( $it as $file ) {
				if ( $file->isDir() ) { continue; }
				$rel = \Simply_Static\Util::safe_relative_path( $dir_root, $file->getPathname() );
				$skip = false;
				foreach ( (array) $skip_dirs as $sd ) {
					if ( $sd && strpos( $rel, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
				}
				if ( $skip ) { continue; }
				$ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
				if ( $ext && ! in_array( $ext, $extensions, true ) ) { continue; }
				$urls[] = \Simply_Static\Util::safe_join_url( $url_base, $rel );
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( 'Error scanning Beaver Builder cache: ' . $e->getMessage() );
		}

		return $urls;
	}
}
