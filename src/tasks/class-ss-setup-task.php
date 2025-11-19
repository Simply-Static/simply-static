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
				throw new \Exception( sprintf( __( 'Cannot create archive directory %s' ), $archive_dir ) );
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

		$this->add_origin_and_additional_urls_to_db( $additional_urls );
		$this->add_additional_files_to_db( $this->options->get( 'additional_files' ) );

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
	public function add_origin_and_additional_urls_to_db( $additional_urls ) {
		$origin_url = trailingslashit( Util::origin_url() );
		Util::debug_log( 'Adding origin URL to queue: ' . $origin_url );
		$static_page = Page::query()->find_or_initialize_by( 'url', $origin_url );
		$static_page->set_status_message( __( 'Origin URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->save();

		// Convert raw textarea to array
		$raw_urls = Util::string_to_array( $additional_urls );
		$urls     = apply_filters( 'ss_additional_urls', $raw_urls );
		$parsed   = Util::parse_patterns( (array) $urls );
		$literals = (array) $parsed['literals'];
		$regexes  = (array) $parsed['regex'];

		// Process literal Additional URLs in batches to avoid memory exhaustion with very large lists.
		$batch_size = (int) apply_filters( 'ss_additional_urls_batch_size', 50 );
		if ( $batch_size < 1 ) { $batch_size = 50; }

		foreach ( array_chunk( $literals, $batch_size ) as $chunk ) {
			$chunk = array_unique( $chunk );
			foreach ( $chunk as $url ) {
				if ( Util::is_local_url( $url ) ) {
					Util::debug_log( 'Adding additional URL to queue: ' . $url );
					$static_page = Page::query()->find_or_initialize_by( 'url', $url );
					$static_page->set_status_message( __( 'Additional URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$path_part = parse_url( $url, PHP_URL_PATH );
					$lower     = is_string( $path_part ) ? strtolower( $path_part ) : '';
					if ( $lower === '/robots.txt' || $lower === '/llms.txt' ) {
						$static_page->handler = Text_File_Handler::class;
					}
					$static_page->save();
				}
			}
			unset( $chunk );
			if ( function_exists( 'gc_collect_cycles' ) ) { gc_collect_cycles(); }
		}

		// Handle regex Additional URLs by matching against a candidate set of site URLs
		if ( ! empty( $regexes ) ) {
			$candidates = apply_filters( 'ss_regex_candidate_urls', Util::candidate_urls_for_regex() );
			$max_matches = (int) apply_filters( 'ss_additional_url_regex_max_matches', 10000 );
			$added = 0;
			foreach ( $candidates as $cand ) {
				foreach ( $regexes as $pattern ) {
					if ( @preg_match( $pattern, $cand ) && preg_match( $pattern, $cand ) ) {
						if ( ! Util::is_local_url( $cand ) ) { continue; }
						$static_page = Page::query()->find_or_initialize_by( 'url', $cand );
						$static_page->set_status_message( __( 'Additional URL (regex)', 'simply-static' ) );
						$static_page->found_on_id = 0;
						$static_page->save();
						$added++;
						break; // already matched one pattern
					}
				}
				if ( $added >= $max_matches ) { break; }
			}
			Util::debug_log( sprintf( 'Additional URL regex matched %d URLs', $added ) );
		}
	}

	/**
	 * Convert Additional Files/Directories to URLs and add them to the database.
	 *
	 * @param string $additional_files array of additional files.
	 *
	 * @return void
	 */
	public function add_additional_files_to_db( $additional_files ) {
		$additional_files = apply_filters( 'ss_additional_files', Util::string_to_array( $additional_files ) );
		$parsed   = Util::parse_patterns( (array) $additional_files );
		$file_literals = (array) $parsed['literals'];
		$file_regexes  = (array) $parsed['regex'];

  // Add robots.txt if exists and not globally disabled.
  $robots_txt = ABSPATH . 'robots.txt';
  $include_robots = (bool) apply_filters( 'ss_include_robots_txt_in_export', true );

  if ( $include_robots && file_exists( $robots_txt ) ) {
      $file_literals[] = $robots_txt;
  } else if ( ! $include_robots ) {
      Util::debug_log( 'robots.txt inclusion disabled via ss_include_robots_txt_in_export filter' );
  }

  // Add llms.txt if exists and not globally disabled.
  $llms_txt = ABSPATH . 'llms.txt';
  $include_llms = (bool) apply_filters( 'ss_include_llms_txt_in_export', true );

  if ( $include_llms && file_exists( $llms_txt ) ) {
      $additional_files[] = $llms_txt;
  } else if ( ! $include_llms ) {
      Util::debug_log( 'llms.txt inclusion disabled via ss_include_llms_txt_in_export filter' );
  }

		// Add feeds if enabled.
		if ( $this->options->get( 'add_feeds' ) ) {
			// Create feed directory it doesn't exist.
			$feed_directory = untrailingslashit( $this->options->get_archive_dir() ) . '/feed';

			if ( ! file_exists( $feed_directory ) ) {
				wp_mkdir_p( $feed_directory );
			}

			// Create index.html file for feed directory.
			file_put_contents( $feed_directory . '/index.html',
				'<!DOCTYPE html>
			<html>
				<head>
					<title>Redirecting...</title>
					<meta http-equiv="refresh" content="0;url=index.xml">
				</head>
				<body>
					<script type="text/javascript">
						window.location = "index.xml";
					</script>
					<p>You are being redirected to <a href="index.xml">index.xml</a></p>
				</body>
			</html>'
			);

			// Add feed redirect file to additional files.
			$additional_files[] = $feed_directory . '/index.html';
		}

		// Process Additional Files/Directories in batches to reduce peak memory usage.
		$batch_size = (int) apply_filters( 'ss_additional_files_batch_size', 50 );
		if ( $batch_size < 1 ) {
			$batch_size = 50;
		}

		foreach ( array_chunk( (array) $file_literals, $batch_size ) as $chunk ) {
			foreach ( $chunk as $item ) {
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
						// Use the Text_File_Handler for robots.txt and llms.txt so URLs are replaced in plain text.
						$base = strtolower( basename( $item ) );
						if ( $base === 'robots.txt' || $base === 'llms.txt' ) {
							$static_page->handler = Text_File_Handler::class;
						} else {
							$static_page->handler = Additional_File_Handler::class;
						}
						$static_page->save();
					} else {
						Util::debug_log( "Adding files from directory: " . $item );
						$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $item, \RecursiveDirectoryIterator::SKIP_DOTS ) );

						foreach ( $iterator as $file_name => $file_object ) {
							$url = self::convert_path_to_url( $file_name );
							Util::debug_log( "Adding file " . $file_name . ' to queue as: ' . $url );
							$static_page = Page::query()->find_or_initialize_by( 'url', $url );
							$static_page->set_status_message( __( "Additional Dir", 'simply-static' ) );
							$base = strtolower( basename( $file_name ) );
							if ( $base === 'robots.txt' || $base === 'llms.txt' ) {
								$static_page->handler = Text_File_Handler::class;
							} else {
								$static_page->handler = Additional_File_Handler::class;
							}
							$static_page->found_on_id = 0;
							$static_page->save();
						}
					}
				} else {
					Util::debug_log( "File doesn't exist: " . $item );
				}
			}

			// Free memory for processed chunk explicitly.
			unset( $chunk );
			if ( function_exists( 'gc_collect_cycles' ) ) {
				gc_collect_cycles();
			}
		}

		// Resolve regex file/directory patterns by scanning allowed roots
		if ( ! empty( $file_regexes ) ) {
			$roots = apply_filters( 'ss_additional_file_regex_roots', [ WP_CONTENT_DIR, untrailingslashit( ABSPATH ) ] );
			$max_matches = (int) apply_filters( 'ss_additional_file_regex_max_matches', 10000 );
			$added = 0;
			$skip_dirs = (array) apply_filters( 'ss_additional_file_regex_skip_dirs', [ '.git', 'node_modules', 'vendor', 'cache', 'tmp', 'temp', basename( $this->options->get_archive_dir() ) ] );
			foreach ( (array) $roots as $root ) {
				if ( ! is_dir( $root ) ) { continue; }
				try {
					$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \RecursiveDirectoryIterator::SKIP_DOTS ) );
					foreach ( $it as $path => $fileobj ) {
						$rel = Util::normalize_slashes( (string) $path );
						// Quick skip: directories we don't want
						$skip = false;
						foreach ( $skip_dirs as $sd ) {
							$sd = trim( $sd, '/' ); if ( $sd === '' ) { continue; }
							if ( strpos( $rel, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
						}
						if ( $skip ) { continue; }
						foreach ( $file_regexes as $pattern ) {
							if ( @preg_match( $pattern, $rel ) && preg_match( $pattern, $rel ) ) {
								if ( is_dir( $path ) ) { continue; }
								$url = self::convert_path_to_url( $path );
								$static_page = Page::query()->find_or_initialize_by( 'url', $url );
								$static_page->set_status_message( __( 'Additional File (regex)', 'simply-static' ) );
								$base = strtolower( basename( $path ) );
								$static_page->handler = ( $base === 'robots.txt' || $base === 'llms.txt' ) ? Text_File_Handler::class : Additional_File_Handler::class;
								$static_page->found_on_id = 0;
								$static_page->save();
								$added++;
								break; // already matched one pattern
							}
						}
						if ( $added >= $max_matches ) { break 2; }
					}
				} catch ( \Exception $e ) {
					Util::debug_log( 'Error scanning for additional file regex: ' . $e->getMessage() );
				}
			}
			Util::debug_log( sprintf( 'Additional file regex matched %d files', $added ) );
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
		$options           = Options::instance();
		$dir               = $options->get( 'temp_files_dir' );
		$delete_temp_files = apply_filters( 'ss_delete_temp_files', true );

		if ( ! $delete_temp_files ) {
			return false;
		}

		if ( empty( $dir ) ) {
			$upload_dir = wp_upload_dir();
			$dir        = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
		}

		if ( false === file_exists( $dir ) || 'update' === $options->get( 'generate_type' ) ) {
			return false;
		}

		if ( ! is_dir( $dir ) ) {
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
