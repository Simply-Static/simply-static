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
	 * Process Pages that have to be processed/transferred.
	 *
	 * @return bool If true, it's done with all pages. If false, there are still pages to process/transfer.
	 */
	public function process_pages() {
		$pages_to_process       = $this->get_pages_to_process();
		$total_pages            = $this->get_total_pages();
		$pages_to_process_count = count( $pages_to_process );

		if ( $pages_to_process_count === 0 ) {
			$processed_pages = $this->get_processed_pages();
			$message         = $this->processed_pages_message( $processed_pages, $total_pages );
			$this->save_status_message( $message );

			return true; // No Pages to process anymore. It's done.
		}

		while ( $static_page = array_shift( $pages_to_process ) ) {
			if ( method_exists( $this, 'check_if_running' ) ) {
				$this->check_if_running();
			}

			try {
				$this->process_page( $static_page );

				$static_page->{$this->processing_column} = Util::formatted_datetime();
				$static_page->save();
			} catch ( Skip_Further_Processing_Exception $e ) {
				Util::debug_log( 'Encountered Processing Error. We are skipping further until next iteration. Error: ' . $e->getMessage() );
				$static_page->set_error_message( $e->getMessage() );
				$static_page->save();
				break;
			} catch ( \Exception $e ) {
				Util::debug_log( 'Page URL: ' . $static_page->url . ' not being processed. Error: ' . $e->getMessage() );
				$static_page->set_error_message( $e->getMessage() );
				$static_page->save();
			}
		}

		$processed_pages = $this->get_processed_pages();
		$message         = $this->processed_pages_message( $processed_pages, $total_pages );
		$this->save_status_message( $message );
		Util::debug_log( "Total pages: " . $total_pages . '; Pages remaining: ' . ( $total_pages - $processed_pages ) );

		return $processed_pages >= $total_pages;
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

		// Modify the query based on post id column.
		$post_id = get_option( 'simply-static-use-single' );

		if ( ! empty( $post_id ) ) {
			$query->where( "post_id = ?", $post_id );
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

		$count = get_option( 'simply_static_' . static::$task_name . '_total_pages' );
		if ( false === $count ) {
			$count = $this->get_total_pages_sql();
			update_option( 'simply_static_' . static::$task_name . '_total_pages', $count );
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
		$post_id = get_option( 'simply-static-use-single' );

		if ( ! empty( $post_id ) ) {
			$query->where( "post_id = ?", $post_id );
		}

		// Modify the query based on build id column.
		$build_id = get_option( 'simply-static-use-build' );

		if ( ! empty( $build_id ) ) {
			$query->where( "build_id = ?", $build_id );
		}

		return $query->count();
	}

	/**
	 * Get the main Query.
	 *
	 * @return Query|\Simply_Static\Query
	 * @throws \Exception
	 */
	public function get_main_query() {
		return Page::query()
		           ->where( "file_path IS NOT NULL" )
		           ->where( "file_path != ''" );
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