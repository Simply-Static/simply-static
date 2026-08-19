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

	/** @var bool */
	protected $complete = true;

	/** @var array<string,int> */
	protected $progress = array();

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
	 * Whether this crawler finished its current discovery pass.
	 *
	 * Most crawlers complete in one call. Large filesystem crawlers can
	 * override this and persist a cursor so the background job can yield before
	 * the request time limit and resume in the next worker request.
	 *
	 * @return bool
	 */
	public function is_complete() : bool {
		return $this->complete;
	}

	/**
	 * Return optional progress for a resumable crawler.
	 *
	 * @return array<string,int>
	 */
	public function get_progress() : array {
		return $this->progress;
	}

	/**
	 * Add detected URLs to the Simply Static page queue.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue() : int {
		return $this->enqueue_urls( $this->detect() );
	}

	/**
	 * Add one set of URLs to the page queue.
	 *
	 * @param array $urls URLs to enqueue.
	 *
	 * @return int Number of URLs handled.
	 */
	protected function enqueue_urls( array $urls ) : int {
		$count = 0;
		$batch_size = max( 1, min( 1000, (int) apply_filters( 'simply_static_crawler_batch_size', 100 ) ) );

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
				if ( ! is_string( $url ) ) {
					continue;
				}

				$discovered_url = $url;
				$url            = trim( \Simply_Static\Util::remove_fragment( $url ) );

				// Fragments are browser-side navigation state, not separate
				// resources. A fragment-only value such as "#/page/2/" has no
				// server URL to crawl, while an absolute URL with a fragment
				// is queued once under its fragment-free page URL.
				if ( '' === $url ) {
					\Simply_Static\Util::debug_log( sprintf( 'Base crawler skipping fragment-only URL: %s', $discovered_url ) );
					continue;
				}

				// Normalize URL to handle posts with URL-encoded post_name values
				$url = \Simply_Static\Util::normalize_url( $url );

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
				$this->configure_static_page( $static_page, $url );
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
	 * Traverse one bounded batch from a set of directory roots.
	 *
	 * The state stores relative directory names and entry offsets rather than
	 * serializing iterator objects or absolute file names. This keeps the option
	 * small and lets a background request resume without trusting stale paths.
	 *
	 * @param string $state_option       Option used for the crawler checkpoint.
	 * @param array  $scan_directories   Directory roots with basedir/baseurl keys.
	 * @param array  $extensions         Allowed lowercase file extensions.
	 * @param array  $skip_directories   Directory names or relative paths to skip.
	 * @param string $entry_limit_filter Filter controlling entries per request.
	 * @param string $time_limit_filter  Filter controlling seconds per request.
	 *
	 * @return int URLs added by this invocation.
	 */
	protected function enqueue_directory_batch( $state_option, array $scan_directories, array $extensions, array $skip_directories, $entry_limit_filter, $time_limit_filter ) : int {
		if ( ! is_string( $state_option ) || 1 !== preg_match( '/^[a-z0-9_]+$/D', $state_option ) ) {
			throw new \InvalidArgumentException( 'A safe directory crawler state option is required.' );
		}

		$scan_directories = $this->normalize_scan_directories( $scan_directories );
		$extensions       = array_values( array_unique( array_filter( array_map( static function ( $extension ) {
			return is_string( $extension ) ? strtolower( ltrim( trim( $extension ), '.' ) ) : '';
		}, $extensions ) ) ) );
		$skip_directories = array_values( array_filter( array_map( static function ( $directory ) {
			return is_string( $directory ) ? trim( str_replace( '\\', '/', $directory ), '/' ) : '';
		}, $skip_directories ) ) );

		$this->complete = true;
		$signature      = hash( 'sha256', serialize( array(
			'archive_start_time' => \Simply_Static\Options::instance()->get( 'archive_start_time' ),
			'scan_directories'   => $scan_directories,
			'extensions'         => $extensions,
			'skip_directories'   => $skip_directories,
		) ) );
		$state          = $this->load_directory_crawler_state( $state_option, $signature );
		$queue_batch    = max( 1, min( 1000, (int) apply_filters( 'simply_static_crawler_batch_size', 100 ) ) );
		$entry_limit    = max( 1, min( 10000, (int) apply_filters( $entry_limit_filter, 500 ) ) );
		$seconds        = (float) apply_filters( $time_limit_filter, 10 );
		$deadline       = microtime( true ) + max( 0.5, min( 15, $seconds ) );
		$processed      = 0;
		$added_now      = 0;
		$buffer         = array();

		while ( $state['scan_index'] < count( $scan_directories ) ) {
			$scan = $scan_directories[ $state['scan_index'] ];
			if ( null === $state['current_dir'] ) {
				if ( empty( $state['pending_dirs'] ) ) {
					$state['scan_index']++;
					if ( $state['scan_index'] < count( $scan_directories ) ) {
						$state['pending_dirs'] = array( '' );
					}
					continue;
				}
				$state['current_dir']  = array_pop( $state['pending_dirs'] );
				$state['entry_offset'] = 0;
			}

			$directory = $this->resolve_crawler_directory( $scan['basedir'], $state['current_dir'] );
			if ( false === $directory ) {
				\Simply_Static\Util::debug_log( sprintf( '%s crawler skipped a missing or unsafe directory cursor.', $this->name ) );
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			}

			try {
				$iterator = $this->get_crawler_directory_iterator( $directory );
				if ( $state['entry_offset'] > 0 ) {
					$iterator->seek( $state['entry_offset'] );
				}
			} catch ( \UnexpectedValueException $exception ) {
				\Simply_Static\Util::debug_log( sprintf( '%s crawler could not read directory: %s', $this->name, $exception->getMessage() ) );
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			} catch ( \OutOfBoundsException $exception ) {
				$state['current_dir']  = null;
				$state['entry_offset'] = 0;
				continue;
			}

			while ( $iterator->valid() ) {
				$file = $iterator->current();
				$state['entry_offset']++;
				$state['scanned']++;
				$processed++;

				if ( $file instanceof \SplFileInfo && ! $file->isLink() ) {
					$relative_path = ltrim( \Simply_Static\Util::safe_relative_path( $scan['basedir'], $file->getPathname() ), '/' );
					if ( $file->isDir() ) {
						if ( ! $this->should_skip_crawler_path( $relative_path, $skip_directories ) ) {
							if ( count( $state['pending_dirs'] ) >= 50000 ) {
								throw new \RuntimeException( sprintf( '%s crawler directory queue exceeded its safety limit.', $this->name ) );
							}
							$state['pending_dirs'][] = $relative_path;
						}
					} elseif ( $file->isFile() && ! $this->should_skip_crawler_path( $relative_path, $skip_directories ) ) {
						$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
						if ( 'php' !== $extension && ( empty( $extensions ) || in_array( $extension, $extensions, true ) ) ) {
							$buffer[] = \Simply_Static\Util::safe_join_url( $scan['baseurl'], $relative_path );
							if ( count( $buffer ) >= $queue_batch ) {
								$added          = $this->enqueue_urls( $buffer );
								$added_now     += $added;
								$state['added'] += $added;
								$buffer          = array();
							}
						}
					}
				}
				$iterator->next();

				if ( $processed >= $entry_limit || microtime( true ) >= $deadline ) {
					break;
				}
			}

			if ( ! empty( $buffer ) ) {
				$added          = $this->enqueue_urls( $buffer );
				$added_now     += $added;
				$state['added'] += $added;
				$buffer          = array();
			}

			if ( $iterator->valid() ) {
				return $this->checkpoint_directory_crawler( $state_option, $state, $added_now );
			}

			$state['current_dir']  = null;
			$state['entry_offset'] = 0;
			if ( $processed >= $entry_limit || microtime( true ) >= $deadline ) {
				return $this->checkpoint_directory_crawler( $state_option, $state, $added_now );
			}
		}

		$this->progress = array(
			'added'   => $state['added'],
			'scanned' => $state['scanned'],
		);
		$this->clear_crawler_state( $state_option );
		\Simply_Static\Util::debug_log( sprintf( '%s crawler added %d URLs across resumable batches.', $this->name, $state['added'] ) );

		return $added_now;
	}

	/** @return array<int,array{basedir:string,baseurl:string}> */
	private function normalize_scan_directories( array $scan_directories ) : array {
		$normalized = array();
		$seen       = array();
		foreach ( $scan_directories as $candidate ) {
			if ( ! is_array( $candidate ) || ! isset( $candidate['basedir'], $candidate['baseurl'] ) || ! is_string( $candidate['basedir'] ) || ! is_string( $candidate['baseurl'] ) || ! is_dir( $candidate['basedir'] ) ) {
				continue;
			}
			$canonical = realpath( $candidate['basedir'] );
			if ( false === $canonical ) {
				continue;
			}
			$key = $canonical . "\0" . rtrim( $candidate['baseurl'], '/' );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$normalized[] = array(
				'basedir' => $canonical,
				'baseurl' => rtrim( $candidate['baseurl'], '/' ),
			);
		}

		return $normalized;
	}

	protected function get_crawler_directory_iterator( $directory ) : \FilesystemIterator {
		return new \FilesystemIterator( $directory, \FilesystemIterator::SKIP_DOTS );
	}

	/** @param string[] $skip_directories */
	private function should_skip_crawler_path( $relative_path, array $skip_directories ) : bool {
		$path = trim( str_replace( '\\', '/', (string) $relative_path ), '/' );
		if ( \Simply_Static\Util::is_private_backup_path( $path ) ) {
			return true;
		}
		foreach ( $skip_directories as $skip_directory ) {
			if ( $path === $skip_directory || 0 === strpos( $path, $skip_directory . '/' ) || false !== strpos( '/' . $path . '/', '/' . $skip_directory . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/** @return string|false */
	private function resolve_crawler_directory( $base_directory, $relative_directory ) {
		if ( ! is_string( $relative_directory ) || ! $this->is_safe_crawler_directory( $relative_directory ) ) {
			return false;
		}
		$root      = realpath( $base_directory );
		$candidate = realpath( rtrim( $base_directory, '/\\' ) . ( '' === $relative_directory ? '' : '/' . $relative_directory ) );
		if ( false === $root || false === $candidate || ! is_dir( $candidate ) ) {
			return false;
		}
		$root = rtrim( str_replace( '\\', '/', $root ), '/' );
		$path = str_replace( '\\', '/', $candidate );

		return $path === $root || 0 === strpos( $path, $root . '/' ) ? $candidate : false;
	}

	private function is_safe_crawler_directory( $path ) : bool {
		if ( ! is_string( $path ) || false !== strpos( $path, "\0" ) || 0 === strpos( str_replace( '\\', '/', $path ), '/' ) ) {
			return false;
		}
		foreach ( explode( '/', str_replace( '\\', '/', $path ) ) as $segment ) {
			if ( '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/** @return array<string,mixed> */
	private function load_directory_crawler_state( $state_option, $signature ) : array {
		$state = \Simply_Static\Options::instance()->get( $state_option );
		if ( ! is_array( $state ) || 1 !== ( $state['version'] ?? null ) || $signature !== ( $state['signature'] ?? null ) ) {
			return $this->new_directory_crawler_state( $signature );
		}
		if ( ! isset( $state['scan_index'], $state['entry_offset'], $state['pending_dirs'], $state['added'], $state['scanned'] ) || ! is_array( $state['pending_dirs'] ) ) {
			return $this->new_directory_crawler_state( $signature );
		}
		if ( count( $state['pending_dirs'] ) > 50000 || ! is_int( $state['scan_index'] ) || $state['scan_index'] < 0 || ! is_int( $state['entry_offset'] ) || $state['entry_offset'] < 0 ) {
			return $this->new_directory_crawler_state( $signature );
		}
		if ( ! is_int( $state['added'] ) || $state['added'] < 0 || ! is_int( $state['scanned'] ) || $state['scanned'] < 0 ) {
			return $this->new_directory_crawler_state( $signature );
		}
		$current_directory = $state['current_dir'] ?? null;
		if ( null !== $current_directory && ! $this->is_safe_crawler_directory( $current_directory ) ) {
			return $this->new_directory_crawler_state( $signature );
		}
		foreach ( $state['pending_dirs'] as $directory ) {
			if ( ! $this->is_safe_crawler_directory( $directory ) ) {
				return $this->new_directory_crawler_state( $signature );
			}
		}

		return $state;
	}

	/** @return array<string,mixed> */
	private function new_directory_crawler_state( $signature ) : array {
		return array(
			'version'      => 1,
			'signature'    => $signature,
			'scan_index'   => 0,
			'current_dir'  => null,
			'entry_offset' => 0,
			'pending_dirs' => array( '' ),
			'added'        => 0,
			'scanned'      => 0,
		);
	}

	/** @param array<string,mixed> $state */
	private function checkpoint_directory_crawler( $state_option, array $state, $added_now ) : int {
		$this->complete = false;
		$this->progress = array(
			'added'   => $state['added'],
			'scanned' => $state['scanned'],
		);
		$this->save_crawler_state( $state_option, $state );
		\Simply_Static\Util::debug_log( sprintf( '%s crawler checkpointed after %d entries with %d URLs queued.', $this->name, $state['scanned'], $state['added'] ) );

		return (int) $added_now;
	}

	/** @param array<string,mixed> $state */
	protected function save_crawler_state( $state_option, array $state ) : void {
		if ( ! \Simply_Static\Options::instance()->set( $state_option, $state )->save() ) {
			throw new \RuntimeException( sprintf( 'Unable to save the %s crawler checkpoint.', $this->name ) );
		}
	}

	protected function clear_crawler_state( $state_option ) : void {
		$options = \Simply_Static\Options::instance();
		$options->destroy( $state_option );
		if ( ! $options->save() ) {
			throw new \RuntimeException( sprintf( 'Unable to clear the %s crawler checkpoint.', $this->name ) );
		}
	}

	/**
	 * Allow a crawler to configure its Page record before it is queued.
	 *
	 * @param \Simply_Static\Page $static_page Page record being queued.
	 * @param string               $url         Detected URL.
	 *
	 * @return void
	 */
	protected function configure_static_page( $static_page, $url ) {
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
