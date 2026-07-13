<?php

namespace Simply_Static;

trait canProcessPages {

	/**
	 * Start Time of the Task to process pages.
	 *
	 * @var null
	 */
	protected $start_time = null;

	/**
	 * Batch Size.
	 *
	 * @var int
	 */
	protected $batch_size = 50;

	/**
	 * Processing Column.
	 *
	 * @var string
	 */
	protected $processing_column = 'last_transferred_at';

	/**
	 * If this is false, it won't check for file path in queries.
	 *
	 * @var bool
	 */
	protected $needs_file_path = true;

	/**
	 * Get batch size.
	 *
	 * @return mixed
	 */
	protected function get_batch_size() {
		return apply_filters( 'simply_static_' . static::$task_name . '_batch_size', $this->batch_size );
	}

	/**
	 * Set Start Time
	 *
	 * @return void
	 */
	public function set_start_time() {
		$this->start_time = $this->options->get( 'archive_start_time' );
	}

	/**
	 * Get the start time.
	 * If null, it'll set the start time as well.
	 *
	 * @return null
	 */
	public function get_start_time() {
		if ( null === $this->start_time ) {
			$this->set_start_time();
		}

		return $this->start_time;
	}

	/**
	 * Get generation type (export, update, build)
	 *
	 * @return mixed|string|null
	 */
	public function get_generate_type() {
		$type = $this->options->get( 'generate_type' );

		if ( ! $type ) {
			$type = 'export';
		}

		return $type;
	}

	/**
	 * Maximum number of seconds process_pages() should run before yielding
	 * back to the background process dispatcher.
	 *
	 * Keeping this below the background process' default 20 second
	 * continuation window prevents the PHP process from spending too long
	 * inside a single task before the dispatcher can hand off the next
	 * request.
	 *
	 * Filterable via `simply_static_max_batch_time`.
	 *
	 * @var int
	 */
	protected $max_batch_time = 15;

	/**
	 * Process Pages that have to be processed/transferred.
	 *
	 * @return bool True when done or only skipped records remain, false when work remains.
	 * @throws \Exception
	 */
	public function process_pages() {
		$pages_to_process       = $this->get_pages_to_process();
		$total_pages            = $this->get_total_pages();
		$pages_to_process_count = count( $pages_to_process );

		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . $pages_to_process_count );

		if ( $pages_to_process_count === 0 ) {
			$processed_pages = $this->get_processed_pages();
			$message         = $this->processed_pages_message( $processed_pages, $total_pages );
			// In 404-only exports, force the transfer log to reflect a single artifact.
			$only_404 = get_option( 'simply-static-404-only' );
			if ( ! empty( $only_404 ) ) {
				$message = sprintf( __( 'Transferred %d of %d files', 'simply-static' ), 1, 1 );
			}
			$this->save_status_message( $message );

			$skipped_pages = $this->get_exhausted_pages_count();
			if ( $skipped_pages > 0 ) {
				$skipped_message = sprintf(
					/* translators: %d: number of skipped pages/files. */
					_n(
						'Skipped %d page or file after repeated processing attempts.',
						'Skipped %d pages or files after repeated processing attempts.',
						$skipped_pages,
						'simply-static'
					),
					$skipped_pages
				);
				$this->save_status_message( $skipped_message, static::$task_name . '_warning' );
			}

			return true; // No Pages to process anymore. It's done.
		}

		// Time-limit awareness: yield back to handle() before the server
		// hard-kills the PHP process on restricted hosting.
		$batch_start    = time();
		$max_batch_time = (int) apply_filters( 'simply_static_max_batch_time', $this->max_batch_time );

 	while ( $static_page = array_shift( $pages_to_process ) ) {
			// Yield if we are approaching the time limit.
			if ( ( time() - $batch_start ) >= $max_batch_time ) {
				Util::debug_log( 'Batch time limit (' . $max_batch_time . 's) reached; yielding to allow re-dispatch.' );
				break;
			}

			if ( method_exists( $this, 'check_if_running' ) ) {
				$this->check_if_running();
			}

			// Atomically claim this page so that concurrent workers (e.g.
			// overlapping PHP-FPM requests in web export mode) cannot process
			// the same page simultaneously, which would cause file corruption
			// (0-byte files) due to concurrent file_put_contents writes.
			global $wpdb;
			$table_name = Page::table_name();
			$start_time = $this->get_start_time();
			$now        = Util::formatted_datetime();
			$claimed    = $wpdb->query( $wpdb->prepare(
				"UPDATE {$table_name} SET {$this->processing_column} = %s WHERE id = %d AND ( {$this->processing_column} IS NULL OR {$this->processing_column} < %s )",
				$now,
				$static_page->id,
				$start_time
			) );

 		if ( ! $claimed ) {
				continue;
			}

			// Refresh the in-memory object so it reflects the claimed timestamp.
			$static_page->{$this->processing_column} = $now;

			// Increment fetch attempts.
			$static_page->fetch_attempts = (int) $static_page->fetch_attempts + 1;
			$static_page->save();

			try {
				$this->process_page( $static_page );

				// Retry attempts belong to the current processing stage. Once a
				// stage succeeds, clear the counter so a later Core or Pro stage
				// receives its own full retry allowance for the same page.
				$static_page->fetch_attempts = 0;
				$static_page->{$this->processing_column} = Util::formatted_datetime();
				$static_page->save();

				// Refresh the background-process lock after each page so it
				// does not expire during long batches, which would cause the
				// cron healthcheck to spawn a concurrent worker.
				$this->maybe_refresh_process_lock();
			} catch ( Skip_Further_Processing_Exception $e ) {
				Util::debug_log( 'Encountered Processing Error. We are skipping further until next iteration. Error: ' . $e->getMessage() );
				// Reset the claim so the page can be retried on next iteration.
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$table_name} SET {$this->processing_column} = NULL WHERE id = %d",
					$static_page->id
				) );
				$static_page->set_error_message( $e->getMessage() );
				$static_page->save();
				break;
			} catch ( \Exception $e ) {
				Util::debug_log( 'Page URL: ' . $static_page->url . ' not being processed. Error: ' . $e->getMessage() );
				// Reset the claim so the page can be retried on next iteration.
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$table_name} SET {$this->processing_column} = NULL WHERE id = %d",
					$static_page->id
				) );
				$static_page->set_error_message( $e->getMessage() );
				$static_page->save();
			} catch ( \Throwable $e ) {
				Util::debug_log( 'Page URL: ' . $static_page->url . ' not being processed. Error: ' . $e->getMessage() );
				// Reset the claim so the page can be retried on next iteration.
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$table_name} SET {$this->processing_column} = NULL WHERE id = %d",
					$static_page->id
				) );
				$static_page->set_error_message( $e->getMessage() );
				$static_page->save();
			}
		}

		$total_pages     = $this->get_total_pages();
		$processed_pages = $this->get_processed_pages();
		$message         = $this->processed_pages_message( $processed_pages, $total_pages );
		$this->save_status_message( $message );
		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . ( $total_pages - $processed_pages ) );

		return $processed_pages >= $total_pages;
	}

	/**
	 * Count pending rows that have exhausted their retry allowance.
	 *
	 * @return int
	 */
	protected function get_exhausted_pages_count() {
		$start_time = $this->get_start_time();
		$query      = $this->get_main_query();

		if ( 'export' === $this->get_generate_type() ) {
			$query->where( "( {$this->processing_column} < ? OR {$this->processing_column} IS NULL )", $start_time );
		}

		if ( 'update' === $this->get_generate_type() ) {
			$query->where( "( ( {$this->processing_column} < last_modified_at AND {$this->processing_column} < ? ) OR {$this->processing_column} IS NULL )", $start_time );
		}

		$query->where( 'fetch_attempts >= 3' );

		$use_single = get_option( 'simply-static-use-single' );
		if ( ! empty( $use_single ) ) {
			$ids = array_values( array_filter( array_map( 'intval', explode( ',', $use_single ) ) ) );
			if ( count( $ids ) === 1 ) {
				$query->where( 'post_id = ?', $ids[0] );
			} elseif ( ! empty( $ids ) ) {
				$query->where( 'post_id IN (' . implode( ',', $ids ) . ')' );
			}
		}

		$build_id = get_option( 'simply-static-use-build' );
		if ( ! empty( $build_id ) ) {
			$query->where( 'build_id = ?', $build_id );
		}

		return (int) $query->count();
	}

	/**
	 * @param Page $static_page Page object.
	 *
	 * @return void
	 */
	protected function process_page( $static_page ) {
	}

	/**
	 * Message to set when processed pages.
	 *
	 * @param integer $processed Number of pages processed.
	 * @param integer $total Number of total pages to process.
	 *
	 * @return string
	 */
	protected function processed_pages_message( $processed, $total ) {
		return sprintf( __( "Uploaded %d of %d files", 'simply-static' ), $processed, $total );
	}

	/**
	 * Return the number of processed pages.
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	public function get_processed_pages() {
		$query = $this->get_processed_pages_sql();

		return $query->count();
	}

	/**
	 * Return the query for processed pages.
	 *
	 * @return Query
	 * @throws \Exception
	 */
	public function get_processed_pages_sql() {
		$start_time = $this->get_start_time();
		$query      = $this->get_main_query();

		if ( 'export' === $this->get_generate_type() ) {
			$query->where( "{$this->processing_column} >= ?", $start_time );
		}

		if ( 'update' === $this->get_generate_type() ) {
			$query->where( "{$this->processing_column} >= last_modified_at" );
			$query->where( "{$this->processing_column} >= ?", $start_time );
		}

		return $query;
	}

	/**
	 * Get pages to process, in batches.
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	public function get_pages_to_process() {
		$batch_size = $this->get_batch_size();
		$query      = $this->get_pages_to_process_sql();

		return $query->limit( $batch_size )->find();
	}

	/**
	 * Get the Pages to process SQL.
	 *
	 * @return Query
	 * @throws \Exception
	 */
	public function get_pages_to_process_sql() {
		$start_time = $this->get_start_time();
		$query      = $this->get_main_query();

		if ( 'export' === $this->get_generate_type() ) {
			$query->where( "( {$this->processing_column} < ? OR {$this->processing_column} IS NULL )", $start_time );
		}

		if ( 'update' === $this->get_generate_type() ) {
			$query->where( "( ( {$this->processing_column} < last_modified_at AND {$this->processing_column} < ? ) OR {$this->processing_column} IS NULL )", $start_time );
		}

		$query->where( "fetch_attempts < 3" );

		// Modify the query based on post id column.
		$use_single = get_option( 'simply-static-use-single' );

		if ( ! empty( $use_single ) ) {
			$ids = array_map( 'intval', explode( ',', $use_single ) );
			if ( count( $ids ) === 1 ) {
				$query->where( "post_id = ?", $ids[0] );
			} else {
				$in_clause = implode( ',', $ids );
				$query->where( "post_id IN ({$in_clause})" );
			}
		}

		// Modify the query based on build id column.
		$build_id = get_option( 'simply-static-use-build' );

		if ( ! empty( $build_id ) ) {
			$query->where( "build_id = ?", $build_id );
		}

		return $query;
	}

	public function get_total_pages( $cached = true ) {
		if ( ! $cached ) {
			return $this->get_total_pages_sql();
		}

		$option_name = 'simply_static_' . static::$task_name . '_total_pages';
		$count       = get_option( $option_name );

		if ( false === $count ) {
			$count = $this->get_total_pages_sql();
			update_option( $option_name, $count );
		} else {
			$count = (int) $count;
		}

		if ( 'update' === $this->get_generate_type() ) {
			$current_total = $this->get_processed_pages() + $this->get_total_pages_sql();
		} else {
			$current_total = $this->get_total_pages_sql();
		}

		if ( $current_total > $count ) {
			$count = $current_total;
			update_option( $option_name, $count );
		}

		return $count;
	}

	public static function delete_total_pages() {
		delete_option( 'simply_static_' . static::$task_name . '_total_pages' );
	}

	/**
	 * Get number of total pages.
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	public function get_total_pages_sql() {
		$query      = $this->get_main_query();
		$start_time = $this->get_start_time();

		// Caching totals so this is fetched on first run (all pages already fetched).
		if ( 'update' === $this->get_generate_type() ) {
			$query->where( "( ( {$this->processing_column} < last_modified_at AND {$this->processing_column} < ? ) OR {$this->processing_column} IS NULL )", $start_time );
			Util::debug_log( 'Total Pages Query: ' . $query->get_raw_sql( "COUNT(*)" ) );
		}

		// Modify the query based on post id column.
		$use_single = get_option( 'simply-static-use-single' );

		if ( ! empty( $use_single ) ) {
			$ids = array_map( 'intval', explode( ',', $use_single ) );
			if ( count( $ids ) === 1 ) {
				$query->where( "post_id = ?", $ids[0] );
			} else {
				$in_clause = implode( ',', $ids );
				$query->where( "post_id IN ({$in_clause})" );
			}
		}

		// Modify the query based on build id column.
		$build_id = get_option( 'simply-static-use-build' );

		if ( ! empty( $build_id ) ) {
			$query->where( "build_id = ?", $build_id );
		}

		$query->where( "fetch_attempts < 3" );

		return $query->count();
	}

	/**
	 * Get the main Query.
	 *
	 * @return Query|\Simply_Static\Query
	 * @throws \Exception
	 */
	public function get_main_query() {

		$query = Page::query();

		if ( $this->needs_file_path ) {
			$query->where( "file_path IS NOT NULL" );
			$query->where( "file_path != ''" );
		}

		return $query;
	}

	/**
	 * Refresh the background-process lock to prevent it from expiring
	 * during long-running page batches.
	 *
	 * When `process_pages()` takes longer than the lock TTL (default 60 s),
	 * the cron healthcheck sees no lock and dispatches a second worker,
	 * leading to race conditions and potential stalls. Calling this after
	 * each page keeps the lock alive.
	 *
	 * @return void
	 */
	protected function maybe_refresh_process_lock() {
		try {
			$job = Plugin::instance()->get_archive_creation_job();
			if ( $job && method_exists( $job, 'lock_process' ) ) {
				$job->lock_process( false );
			}
		} catch ( \Exception $e ) {
			// Silently ignore — lock refresh is best-effort.
		}
	}

	/**
	 * Cleanup
	 *
	 * @return void
	 */
	public function cleanup() {
		self::delete_total_pages();
	}
}
