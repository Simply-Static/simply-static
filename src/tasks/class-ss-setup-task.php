<?php

namespace Simply_Static;

/**
 * Class to handle setup task.
 */
class Setup_Task extends Task {

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'setup';

	/**
	 * Do the initial setup for generating a static archive
	 *
	 * @return boolean true this always completes in one run, so returns true.
	 */
	public function perform() {
		$message = __( 'Setting up', 'simply-static' );
		$this->save_status_message( $message );

		// Delete files in temp dir.
		$this->delete_temp_static_files();

		$archive_dir = $this->options->get_archive_dir();

		// create temp archive directory.
		if ( ! file_exists( $archive_dir ) ) {
			Util::debug_log( 'Creating archive directory: ' . $archive_dir );
			$create_dir = wp_mkdir_p( $archive_dir );
			if ( $create_dir === false ) {
				return new \WP_Error( 'cannot_create_archive_dir', sprintf( __( 'Cannot create archive directory %s' ), $archive_dir ) );
			}
		}

		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );
		$type       = $this->options->get( 'generate_type' );

		if ( ! $type ) {
			$type = 'export';
		}

		if ( empty( $use_build ) && empty( $use_single ) && 'export' === $type ) {
			Page::query()->delete_all();
		}

		// add origin url and additional urls/files to database.
		$additional_urls = apply_filters( 'ss_setup_task_additional_urls', $this->options->get( 'additional_urls' ) );

		self::add_origin_and_additional_urls_to_db( $additional_urls );
		self::add_additional_files_to_db( $this->options->get( 'additional_files' ) );

		do_action( 'ss_after_setup_task' );

		return true;
	}

	/**
	 * Ensure the Origin URL and user-specified Additional URLs are in the DB.
	 *
	 * @param string $additional_urls array of additional urls.
	 *
	 * @return void
	 */
	public static function add_origin_and_additional_urls_to_db( $additional_urls ) {
		$origin_url = trailingslashit( Util::origin_url() );
		Util::debug_log( 'Adding origin URL to queue: ' . $origin_url );
		$static_page = Page::query()->find_or_initialize_by( 'url', $origin_url );
		$static_page->set_status_message( __( 'Origin URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->save();

		$urls = apply_filters( 'ss_additional_urls', array_unique( Util::string_to_array( $additional_urls ) ) );

		foreach ( $urls as $url ) {
			if ( Util::is_local_url( $url ) ) {
				Util::debug_log( 'Adding additional URL to queue: ' . $url );
				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( __( "Additional URL", 'simply-static' ) );
				$static_page->found_on_id = 0;
				$static_page->save();
			}
		}
	}

	/**
	 * Convert Additional Files/Directories to URLs and add them to the database.
	 *
	 * @param string $additional_files array of additional files.
	 *
	 * @return void
	 */
	public static function add_additional_files_to_db( $additional_files ) {
		$additional_files = apply_filters( 'ss_additional_files', Util::string_to_array( $additional_files ) );

		// Add robots.txt if exists.
		$robots_txt = ABSPATH . 'robots.txt';

		if ( file_exists( $robots_txt ) ) {
			$additional_files[] = $robots_txt;
		}

		// Convert additional files to URLs and add to queue.
		foreach ( $additional_files as $item ) {
			// If item is a file, convert to url and insert into database.
			// If item is a directory, recursively iterate and grab all files,
			// and for each file, convert to url and insert into database.
			if ( file_exists( $item ) ) {
				if ( is_file( $item ) ) {
					$url = self::convert_path_to_url( $item );
					Util::debug_log( "File " . $item . ' exists; adding to queue as: ' . $url );
					$static_page = Page::query()
					                   ->find_or_create_by( 'url', $url );
					$static_page->set_status_message( __( "Additional File", 'simply-static' ) );
					// setting found_on_id to 0 since this was user-specified
					$static_page->found_on_id = 0;
					$static_page->handler = Additional_File_Handler::class;
					$static_page->save();
				} else {
					Util::debug_log( "Adding files from directory: " . $item );
					$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $item, \RecursiveDirectoryIterator::SKIP_DOTS ) );

					foreach ( $iterator as $file_name => $file_object ) {
						$url = self::convert_path_to_url( $file_name );
						Util::debug_log( "Adding file " . $file_name . ' to queue as: ' . $url );
						$static_page = Page::query()->find_or_initialize_by( 'url', $url );
						$static_page->set_status_message( __( "Additional Dir", 'simply-static' ) );
						$static_page->handler = Additional_File_Handler::class;
						$static_page->found_on_id = 0;
						$static_page->save();
					}
				}
			} else {
				Util::debug_log( "File doesn't exist: " . $item );
			}
		}
	}

	/**
	 * Convert a directory path into a valid WordPress URL
	 *
	 * @param string $path The path to a directory or a file.
	 *
	 * @return string       The WordPress URL for the given path.
	 */
	private static function convert_path_to_url( $path ) {
		$url = $path;
		if ( stripos( $path, WP_PLUGIN_DIR ) === 0 ) {
			$url = str_replace( WP_PLUGIN_DIR, WP_PLUGIN_URL, $path );
		} elseif ( stripos( $path, WP_CONTENT_DIR ) === 0 ) {
			$url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );
		} elseif ( stripos( $path, get_home_path() ) === 0 ) {
			$url = str_replace( untrailingslashit( get_home_path() ), Util::origin_url(), $path );
		}

		// Windows support
		$url = Util::normalize_slashes( $url );

		return $url;
	}

	/**
	 * Delete temporary, generated static files.
	 *
	 * @return true|\WP_Error True on success, WP_Error otherwise.
	 */
	public function delete_temp_static_files() {
		$options = Options::instance();
		$dir     = $options->get( 'temp_files_dir' );

		if ( empty( $dir ) ) {
			$upload_dir = wp_upload_dir();
			$dir        = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
		}

		if ( false === file_exists( $dir ) || 'update' === $options->get( 'generate_type' ) ) {
			return false;
		}

		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $files as $fileinfo ) {
			$can_delete_file = apply_filters( 'ss_can_delete_file', true, $fileinfo, $dir );

			if ( ! $can_delete_file ) {
				continue;
			}

			if ( $fileinfo->isDir() ) {
				if ( false === rmdir( $fileinfo->getRealPath() ) ) {
					return false;
				}
			} else {
				if ( false === unlink( $fileinfo->getRealPath() ) ) {
					return false;
				}
			}
		}

		return true;
	}
}
