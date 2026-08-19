<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Divi Crawler class
 *
 * This crawler detects URLs for Divi theme cached assets and theme asset files.
 */
class Divi_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'divi';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name        = __( 'Divi Assets', 'simply-static' );
		$this->description = __( 'Detects Divi theme cache and asset files.', 'simply-static' );
	}

	/**
	 * Check if Divi is the active (parent) theme.
	 *
	 * @return bool
	 */
	public function dependency_active() : bool {
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();

			if ( $theme ) {
				$template = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';
				$name = method_exists( $theme, 'get' ) ? (string) $theme->get( 'Name' ) : '';

				if ( $this->is_divi_identifier( $template ) || $this->is_divi_identifier( $name ) ) {
					return true;
				}

				if ( method_exists( $theme, 'parent' ) ) {
					$parent = $theme->parent();

					if ( $parent ) {
						$parent_name       = method_exists( $parent, 'get' ) ? $parent->get( 'Name' ) : '';
						$parent_stylesheet = method_exists( $parent, 'get_stylesheet' ) ? $parent->get_stylesheet() : '';

						if ( $this->is_divi_identifier( $parent_name ) || $this->is_divi_identifier( $parent_stylesheet ) ) {
							return true;
						}
					}
				}
			}
		}

		$tpl = function_exists( 'get_template' ) ? get_template() : '';

		return $this->is_divi_identifier( $tpl );
	}

	/**
	 * Determine whether a theme name or directory is exactly Divi.
	 *
	 * @param mixed $identifier Theme name or directory.
	 *
	 * @return bool
	 */
	private function is_divi_identifier( $identifier ) : bool {
		if ( ! is_string( $identifier ) ) {
			return false;
		}

		$identifier = trim( trim( str_replace( '\\', '/', $identifier ) ), '/' );

		if ( '' === $identifier ) {
			return false;
		}

		return 0 === strcasecmp( $identifier, 'divi' );
	}

	/**
	 * Check if the crawler is active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		if ( ! $this->dependency_active() ) {
			return false;
		}
		return parent::is_active();
	}

	/**
	 * Detect Divi-related asset URLs.
	 *
	 * @return array List of asset URLs
	 */
	public function detect() : array {
		$asset_urls = [];

		$site_url = site_url();
		$wp_path  = ABSPATH;

		$directories = [
			// Divi cache directory (generated assets)
			'/wp-content/et-cache'          => $wp_path . 'wp-content/et-cache',
			// Divi theme assets
			'/wp-content/themes/Divi'        => $wp_path . 'wp-content/themes/Divi',
		];

		foreach ( $directories as $url_path => $dir_path ) {
			if ( is_dir( $dir_path ) ) {
				$directory_urls = $this->scan_directory_for_assets( $dir_path, $site_url . $url_path );
				$asset_urls     = array_merge( $asset_urls, $directory_urls );
			} else {
				\Simply_Static\Util::debug_log( "Directory does not exist: $dir_path" );
			}
		}

		// Unique URLs only
		$asset_urls = array_values( array_unique( $asset_urls ) );

		\Simply_Static\Util::debug_log( sprintf( 'Divi crawler detected %d asset URLs', count( $asset_urls ) ) );

		return $asset_urls;
	}

	/**
	 * Stream Divi asset URLs directly into the queue in batches to reduce memory usage.
	 *
	 * @return int Number of URLs added
	 */
	public function add_urls_to_queue(): int {
		return $this->enqueue_directory_batch(
			'divi_crawler_state',
			array(
				array( 'basedir' => ABSPATH . 'wp-content/et-cache', 'baseurl' => site_url( '/wp-content/et-cache' ) ),
				array( 'basedir' => ABSPATH . 'wp-content/themes/Divi', 'baseurl' => site_url( '/wp-content/themes/Divi' ) ),
			),
			array( 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'ico', 'mp4', 'webm' ),
			(array) apply_filters( 'ss_skip_crawl_divi_directories', array( '.git', 'node_modules', 'vendor/bin', 'vendor/composer', 'tests' ) ),
			'simply_static_divi_crawler_max_entries_per_batch',
			'simply_static_divi_crawler_max_batch_seconds'
		);
	}

	/**
	 * Scan a directory for Divi asset files recursively (with filtering for asset file types).
	 *
	 * @param string $dir Directory path
	 * @param string $url_base Base URL for the directory
	 *
	 * @return array List of asset URLs
	 */
	private function scan_directory_for_assets( $dir, $url_base ): array {
		$urls = [];
		$max_entries = max( 1, min( 100000, (int) apply_filters( 'simply_static_divi_detection_max_entries', 5000 ) ) );
		$deadline    = microtime( true ) + max( 0.5, min( 15, (float) apply_filters( 'simply_static_divi_detection_max_seconds', 5 ) ) );
		$scanned     = 0;

		$asset_extensions = [
			'css','js','png','jpg','jpeg','gif','svg','webp','woff','woff2','ttf','eot','otf','ico','mp4','webm'
		];

		$skip_dirs = apply_filters( 'ss_skip_crawl_divi_directories', [ '.git','node_modules','vendor/bin','vendor/composer','tests' ] );

		if ( ! is_dir( $dir ) ) {
			\Simply_Static\Util::debug_log( "Directory does not exist: $dir" );
			return $urls;
		}

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ( $iterator as $file ) {
				$scanned++;
				if ( $scanned > $max_entries || microtime( true ) >= $deadline ) {
					break;
				}
				if ( $file->isDir() ) { continue; }
				$relative_path = \Simply_Static\Util::safe_relative_path( $dir, $file->getPathname() );
				$skip = false;
				foreach ( (array) $skip_dirs as $sd ) {
					if ( $sd && strpos( $relative_path, '/' . $sd . '/' ) !== false ) { $skip = true; break; }
				}
				if ( $skip ) { continue; }
				$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );
				if ( in_array( $extension, $asset_extensions, true ) ) {
					$urls[] = \Simply_Static\Util::safe_join_url( $url_base, $relative_path );
				}
			}
		} catch ( \Exception $e ) {
			\Simply_Static\Util::debug_log( "Error scanning directory $dir: " . $e->getMessage() );
		}

		return $urls;
	}
}
