<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Crawlers class
 *
 * This class manages all the crawler implementations.
 */
class Crawlers {

	/**
	 * Singleton instance
	 * @var Simply_Static\Crawlers
	 */
	protected static $instance = null;

	/**
	 * Array of crawler instances
	 * @var array
	 */
	protected $crawlers = [];

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {
		$this->load_crawlers();
	}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {
	}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {
	}

	/**
	 * Return an instance of Simply_Static\Crawlers
	 * @return Simply_Static\Crawlers
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load all crawler implementations
	 * @return void
	 */
	protected function load_crawlers() {
		// Load the base crawler class
		require_once SIMPLY_STATIC_PATH . 'src/crawler/class-ss-crawler.php';

		// Load all crawler implementations (with ss- prefix in filenames)
		$crawler_files = glob( SIMPLY_STATIC_PATH . 'src/crawler/class-ss-*-crawler.php' );

		Util::debug_log( "Found " . count( $crawler_files ) . " crawler files" );

		foreach ( $crawler_files as $file ) {
			require_once $file;

			// Get the class name from the file name
			$class_name = str_replace( 'class-', '', basename( $file, '.php' ) );
			// Strip optional ss- prefix from filenames (class-ss-archive-crawler.php -> archive-crawler)
			if ( strpos( $class_name, 'ss-' ) === 0 ) {
				$class_name = substr( $class_name, 3 );
			}
			$class_name = str_replace( '-', '_', $class_name );
			$class_name = ucwords( $class_name, '_' );

			// Create the fully qualified class name
			$fq_class_name = 'Simply_Static\\Crawler\\' . $class_name;

			// Create an instance of the crawler
			if ( class_exists( $fq_class_name ) ) {
				$crawler = new $fq_class_name();
				$this->crawlers[] = $crawler;
			} else {
				Util::debug_log( "Class does not exist: " . $fq_class_name );
			}
		}

		// Allow plugins to add their own crawlers
		$this->crawlers = apply_filters( 'simply_static_crawlers', $this->crawlers );
	}

	/**
	 * Get all crawler instances
	 * @return array
	 */
	public function get_crawlers() {
		// Check if we have any crawlers loaded
		if (empty($this->crawlers)) {
			$this->load_crawlers();
		}

		return $this->crawlers;
	}

	/**
	 * Get active crawler instances
	 * @return array
	 */
	public function get_active_crawlers() {
		return array_filter( $this->crawlers, function( $crawler ) {
			return $crawler->is_active();
		} );
	}

	/**
	 * Get crawler information for JS part
	 * @return array
	 */
	public function get_crawlers_for_js() {
		$crawlers_for_js = [];

		foreach ( $this->crawlers as $crawler ) {
			$js_object = $crawler->js_object();
			$crawlers_for_js[] = $js_object;
		}

		return $crawlers_for_js;
	}
}
