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

		foreach ( $this->get_scan_directories() as $directory ) {
			if ( is_dir( $directory['basedir'] ) ) {
				$directory_urls = $this->scan_directory_for_assets( $directory['basedir'], $directory['baseurl'] );
				$asset_urls     = array_merge( $asset_urls, $directory_urls );
			} else {
				\Simply_Static\Util::debug_log( 'Directory does not exist: ' . $directory['basedir'] );
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
			$this->get_scan_directories(),
			array( 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'ico', 'mp4', 'webm' ),
			(array) apply_filters( 'ss_skip_crawl_divi_directories', array( '.git', 'node_modules', 'vendor/bin', 'vendor/composer', 'tests' ) ),
			'simply_static_divi_crawler_max_entries_per_batch',
			'simply_static_divi_crawler_max_batch_seconds'
		);
	}

	/**
	 * Return Divi asset roots appropriate for the active major version.
	 *
	 * Divi 5 exposes the assets used by a rendered page through its Dynamic
	 * Assets system. Simply Static's normal URL extraction follows those assets,
	 * while this crawler only needs to preserve Divi's generated cache. Divi 4
	 * and unknown versions retain the complete-theme crawl as a compatibility
	 * fallback.
	 *
	 * @return array<int,array{basedir:string,baseurl:string}>
	 */
	protected function get_scan_directories(): array {
		$content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$content_url = defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : site_url( '/wp-content' );
		$version     = $this->get_divi_theme_version();
		$legacy      = $this->should_use_legacy_asset_crawl( $version );
		$directories = array(
			array(
				'basedir' => rtrim( $content_dir, "/\\" ) . DIRECTORY_SEPARATOR . 'et-cache',
				'baseurl' => rtrim( $content_url, '/' ) . '/et-cache',
			),
		);

		if ( $legacy ) {
			$theme_dir = function_exists( 'get_template_directory' )
				? get_template_directory()
				: $content_dir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'Divi';
			$theme_url = function_exists( 'get_template_directory_uri' )
				? get_template_directory_uri()
				: rtrim( $content_url, '/' ) . '/themes/Divi';
			$directories[] = array( 'basedir' => $theme_dir, 'baseurl' => $theme_url );
		}

		/**
		 * Filter the filesystem roots scanned by the Divi crawler.
		 *
		 * @param array $directories Divi asset roots.
		 * @param bool  $legacy       Whether the legacy full-theme fallback is active.
		 * @param string $version     Detected Divi parent-theme version.
		 */
		return (array) apply_filters(
			'ss_divi_crawler_directories',
			$directories,
			$legacy,
			$version
		);
	}

	/**
	 * Whether to retain the Divi 4 full-theme compatibility crawl.
	 *
	 * @param string|null $version Detected version, when already available.
	 *
	 * @return bool
	 */
	protected function should_use_legacy_asset_crawl( $version = null ): bool {
		$version = is_string( $version ) ? $version : $this->get_divi_theme_version();
		$legacy  = true;

		if ( preg_match( '/^(\d+)/', $version, $matches ) ) {
			$legacy = (int) $matches[1] < 5;
		}

		return (bool) apply_filters( 'ss_divi_use_legacy_asset_crawl', $legacy, $version );
	}

	/**
	 * Return the active Divi parent-theme version.
	 *
	 * @return string
	 */
	protected function get_divi_theme_version(): string {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return '';
		}

		$theme = wp_get_theme();
		if ( ! $theme ) {
			return '';
		}

		if ( method_exists( $theme, 'parent' ) ) {
			$parent = $theme->parent();
			if ( $parent ) {
				$parent_name       = method_exists( $parent, 'get' ) ? $parent->get( 'Name' ) : '';
				$parent_stylesheet = method_exists( $parent, 'get_stylesheet' ) ? $parent->get_stylesheet() : '';
				if ( $this->is_divi_identifier( $parent_name ) || $this->is_divi_identifier( $parent_stylesheet ) ) {
					return method_exists( $parent, 'get' ) ? trim( (string) $parent->get( 'Version' ) ) : '';
				}
			}
		}

		$template = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';
		$name     = method_exists( $theme, 'get' ) ? $theme->get( 'Name' ) : '';
		if ( $this->is_divi_identifier( $template ) || $this->is_divi_identifier( $name ) ) {
			return method_exists( $theme, 'get' ) ? trim( (string) $theme->get( 'Version' ) ) : '';
		}

		return '';
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
