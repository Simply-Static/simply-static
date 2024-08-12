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
		$type = $this->options->get('generate_type');

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

		$message = $this->processing_pages_message( $pages_to_process_count, $total_pages );
		$this->save_status_message( $message );

		while ( $static_page = array_shift( $pages_to_process ) ) {
			try {
				$this->process_page( $static_page );

				$static_page->last_transferred_at = Util::formatted_datetime();
				$static_page->save();
			} catch (\Exception $e) {
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
	protected function process_page( $static_page ) {}

	/**
	 * Message to see when starting to process new pages.
	 *
	 * @param integer $to_process Number of pages to process.
	 * @param integer $total Total of pages.
	 *
	 * @return string
	 */
	protected function processing_pages_message( $to_process, $total ) {
		return sprintf( __( "Uploading %d of %d files", 'simply-static' ), $to_process, $total );
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
		$start_time = $this->get_start_time();
		$query      = $this->get_main_query();

		if ( 'export' === $this->get_generate_type() ) {
			$query->where("last_transferred_at >= ?", $start_time );
		}

		if ( 'update' === $this->get_generate_type() ) {
			$query->where("last_transferred_at >= last_modified_at" );
			$query->where("last_transferred_at >= ?", $start_time );
		}

		return $query->count();
	}

	/**
	 * Get pages to process, in batches.
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	public function get_pages_to_process() {
		$start_time = $this->get_start_time();
		$batch_size = $this->get_batch_size();
		$query      = $this->get_main_query();

		if ( 'export' === $this->get_generate_type() ) {
			$query->where("( last_transferred_at < ? OR last_transferred_at IS NULL )", $start_time );
		}

		if ( 'update' === $this->get_generate_type() ) {
			$query->where("( ( last_transferred_at < last_modified_at AND last_transferred_at < ? ) OR last_transferred_at IS NULL )", $start_time );
		}

		return $query->limit( $batch_size )->find();
	}

	public function get_total_pages( $cached = true ) {
		if ( ! $cached ) {
			return $this->get_total_pages_sql();
		}

		$count = get_transient( 'simply_static_' . static::$task_name . '_total_pages' );
		if ( false === $count ) {
			$count = $this->get_total_pages_sql();
			set_transient( 'simply_static_' . static::$task_name . '_total_pages', $count, MINUTE_IN_SECONDS );
		}

		return $count;
	}

	public static function delete_transients() {
		delete_transient( 'simply_static_' . static::$task_name . '_total_pages' );
	}

	/**
	 * Get number of total pages.
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	public function get_total_pages_sql() {
		$query = $this->get_main_query();
		$start_time = $this->get_start_time();

		// Caching totals so this is fetched on first run (all pages already fetched).
		if ( 'update' === $this->get_generate_type() ) {
			$query->where("( ( last_transferred_at < last_modified_at AND last_transferred_at < ? ) OR last_transferred_at IS NULL )", $start_time );
			Util::debug_log('Total Pages Query: ' . $query->get_raw_sql("COUNT(*)") );
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
}