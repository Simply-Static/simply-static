<?php

namespace Simply_Static;

use Exception;
use Simply_Static;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Util;

/**
 * Class which handles GitHub commits.
 */
class Scan_Themes_Plugins_Dir_Task extends Simply_Static\Task {

	/**
	 * The task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'scan_themes_plugins_dir';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$options        = Options::instance();
		$this->options  = $options;
	}


	/**
	 * Get the current active WordPress theme details.
	 *
	 * @return array An array containing 'theme' and 'parent_theme' (if applicable).
	 */
	public function get_current_theme_paths() {
		$current_theme = wp_get_theme();
		$paths         = [];
		$paths[]       = $current_theme->get_stylesheet_directory();
		$paths[]       = $current_theme->get_template_directory();

		return array_unique( $paths );
	}

	/**
	 * Push a batch of files from the temp dir to GitHub.
	 *
	 * @return boolean true if done, false if not done.
	 * @throws Exception When the GitHub API returns an error.
	 */
	public function perform(): bool {
		// Prepare default option state.
		$active_plugin_files = wp_get_active_and_valid_plugins();
		$theme_paths         = $this->get_current_theme_paths();
		$processed           = get_option( 'ss_scanned_dirs', [] );
		$to_process          = array_merge( $active_plugin_files, $theme_paths );

		if ( empty( $to_process ) ) {
			return true;
		}

		$not_processed = array_diff( $to_process, $processed );

		if ( empty( $not_processed ) ) {
			return true;
		}

		while( ! empty( $not_processed ) ) {
			$file  = array_shift( $not_processed );
			$dir   = is_dir( $file ) ? $file : dirname( $file );
			$files = $this->get_files_in_url( $dir );

			foreach ( $files as $url ) {
				Util::debug_log( 'Adding scanned asset to queue: ' . $url );
				/** @var \Simply_Static\Page $static_page */
				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->found_on_id = 0;
				$static_page->set_status_message("Plugin/Theme Asset");
				$static_page->save();
			}

			$processed[] = $file;
			update_option( 'ss_scanned_dirs', $processed );
		}

		return false;
	}

	/**
	 * Move Elementor Files to make sure all assets that might be required are there.
	 * @return array
	 */
	public function get_files_in_url( $dir ) {
		$files              = $this->get_files_in_dir( $dir );
		$urls               = [];
		$allowed_extensions = apply_filters( 'simply_static_allowed_scanning_extensions', [
			'js',
			'css',
			'xml',
			'json',
			'gif',
			'jpg',
			'png',
			'jpeg',
			'webp',
			'woff',
			'woff2',
			'ttf',
			'eot',
			'svg',
			'ico',
			'mp4',
			'webm',
			'ogg',
			'mp3',
			'wav',
			'ttml',
			'vtt',
			'apng',
			'avif',
			'bmp',
		]);

		foreach ( $files as $file ) {
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if ( ! in_array( $ext, $allowed_extensions, true ) ) {
				continue;
			}

			if ( stripos( $file, WP_PLUGIN_DIR ) === 0 ) {
				$url = str_replace( WP_PLUGIN_DIR, WP_PLUGIN_URL, $file );
			} else {
				$url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file );
			}

			$urls[] = $url;
		}

		return $urls;
	}

	protected function is_dir_excluded( $dir ) {
		$escluded_dirs = apply_filters( 'simply_static_scan_excluded_dirs', [
			'node_modules',
		]);

		if ( ! $escluded_dirs ) {
			return false;
		}

		foreach ( $escluded_dirs as $excluded_dir ) {
			if ( stripos( $dir, $excluded_dir ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get fields in directory
	 *
	 * @param string $source_dir Directory path.
	 * @param array $files
	 *
	 * @return array
	 */
	public function get_files_in_dir( string $source_dir, array $files = [] ) {

		if ( is_dir( $source_dir ) ) {
			if ( $this->is_dir_excluded( $source_dir ) ) {
				return $files;
			}

			$directory = opendir( $source_dir );

			while ( ( $file = readdir( $directory ) ) !== false ) {
				if ( $file === '.' || $file === '..' ) {
					continue;
				}

				if ( is_dir( "$source_dir/$file" ) === true ) {
					$files = $this->get_files_in_dir( "$source_dir/$file", $files );
				} else {
					$files[] = "$source_dir/$file";
				}
			}

			closedir( $directory );
		}

		return $files;
	}
}
