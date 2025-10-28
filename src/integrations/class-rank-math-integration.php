<?php

namespace Simply_Static;

use RankMath\Sitemap\Router;

class Rank_Math_Integration extends Integration {
	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'rank-math';

	public function __construct() {
		$this->name        = __( 'Rank Math', 'simply-static' );
		$this->description = __( 'Automatically includes your XML sitemaps, handles URL replacements in schema.org markup, and creates redirects on your static site for you.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_page' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_redirections' ] );
		add_action( 'ss_dom_before_save', [ $this, 'replace_json_schema' ], 10, 2 );

		// Ensure robots.txt and llms.txt are generated for static export when RankMath is active.
		add_filter( 'ss_additional_files', [ $this, 'maybe_add_text_files' ] );
		// Also ensure these URLs are added so they get transferred to Local Directory deployments.
		add_filter( 'ss_setup_task_additional_urls', [ $this, 'add_text_file_urls' ] );

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-rank-math-sitemap-handler.php' );
	}

	/**
	 * Maybe add robots.txt and llms.txt to additional files when RankMath is active.
	 *
	 * RankMath stores robots.txt content in settings and serves llms.txt dynamically.
	 * We generate temporary files so Simply Static can export them.
	 *
	 * @param array $additional_files
	 * @return array
	 */
	public function maybe_add_text_files( $additional_files ) {
		// Ensure we have an array to work with.
		$additional_files = is_array( $additional_files ) ? $additional_files : [];

		// If physical files exist, Setup_Task will already include them.
		$robots_physical = ABSPATH . 'robots.txt';
		$llms_physical   = ABSPATH . 'llms.txt';

		$archive_dir = Options::instance()->get_archive_dir();
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}

		// robots.txt via RankMath setting.
		if ( ! file_exists( $robots_physical ) ) {
			$robots = $this->get_rankmath_robots_content();
			if ( is_string( $robots ) && $robots !== '' ) {
				$path = $this->write_archive_file( 'robots.txt', $robots );
				if ( $path ) {
					Util::debug_log( 'RankMath: wrote robots.txt into archive dir: ' . $path );
				}
			}
		}

		// llms.txt via public endpoint served by RankMath.
		if ( ! file_exists( $llms_physical ) ) {
			$llms_url = home_url( '/llms.txt' );
			$response = wp_remote_get( $llms_url, [ 'timeout' => 20 ] );
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$body = is_string( $body ) ? $body : '';
				// Basic sanity check: ensure it looks like plain text and not an HTML 404.
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					$path = $this->write_archive_file( 'llms.txt', $body );
					if ( $path ) {
						Util::debug_log( 'RankMath: wrote llms.txt into archive dir: ' . $path );
					}
				}
			}
		}

		// We return the list unchanged; files are placed directly in the archive directory.
		return $additional_files;
	}

	/**
	 * Get robots.txt content from RankMath settings or WordPress defaults.
	 *
	 * @return string robots.txt content or empty string if unavailable.
	 */
	private function get_rankmath_robots_content() {
		$public  = (int) get_option( 'blog_public' );
		$default = "# This file is automatically added by Simply Static (Rank Math integration)\n";
		$default .= "User-Agent: *\n";
		if ( 0 === $public ) {
			$default .= "Disallow: /\n";
		} else {
			$default .= "Disallow: /wp-admin/\n";
			$default .= "Allow: /wp-admin/admin-ajax.php\n";
		}

		// Prefer RankMath custom content when available.
		if ( class_exists( '\\RankMath\\Helper' ) ) {
			$custom = \RankMath\Helper::get_settings( 'general.robots_txt_content' );
			if ( is_string( $custom ) && $custom !== '' ) {
				return $custom;
			}
		}

		// Fallback to WP filtered default (may include other SEO filters when applicable).
		return apply_filters( 'robots_txt', $default, $public );
	}


	/**
	 * Write a file directly into the current archive directory (no prefix).
	 * This ensures the file becomes part of the static export output and any local transfer.
	 *
	 * @param string $filename Filename such as 'robots.txt' or 'llms.txt'.
	 * @param string $content  File contents.
	 * @return string|null Full path on success, null on failure.
	 */
	private function write_archive_file( $filename, $content ) {
		$archive_dir = Options::instance()->get_archive_dir();
		if ( empty( $archive_dir ) ) {
			return null;
		}
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}
		$path = trailingslashit( $archive_dir ) . ltrim( $filename, '/\\' );
		$result = @file_put_contents( $path, $content );
		return $result !== false ? $path : null;
	}

	/**
	 * Ensure robots.txt and llms.txt URLs are queued so they get transferred in Local Directory deployments.
	 *
	 * @param string $additional_urls Raw textarea string of additional URLs.
	 * @return string Modified string including robots.txt and llms.txt when needed.
	 */
	public function add_text_file_urls( $additional_urls ) {
		$robots_physical = ABSPATH . 'robots.txt';
		$llms_physical   = ABSPATH . 'llms.txt';

		$to_add = [];
		if ( ! file_exists( $robots_physical ) ) {
			$to_add[] = home_url( '/robots.txt' );
		}
		if ( ! file_exists( $llms_physical ) ) {
			$to_add[] = home_url( '/llms.txt' );
		}

		if ( empty( $to_add ) ) {
			return $additional_urls;
		}

		// Normalize to string.
		if ( ! is_string( $additional_urls ) ) {
			if ( is_array( $additional_urls ) ) {
				$additional_urls = implode( "\n", $additional_urls );
			} else {
				$additional_urls = '';
			}
		}

		$prefix = '';
		if ( $additional_urls !== '' && substr( $additional_urls, -1 ) !== "\n" ) {
			$prefix = "\n";
		}

		return $additional_urls . $prefix . implode( "\n", $to_add );
	}

	/**
	 * Get Redirections.
	 * @return array|object|\stdClass[]|null
	 */
	protected function get_redirects() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rank_math_redirections", ARRAY_A );


		if ( empty( $results ) ) {
			return null;
		}

		$results = array_map( function ( $item ) {
			$item["sources"] = maybe_unserialize( $item["sources"] );

			return $item;
		}, $results );

		return $results;
	}

	/**
	 * Register all redirections.
	 *
	 * @return void
	 */
	public function register_redirections() {
		// Only on full or update exports.
		$use_single = get_option( 'simply-static-use-single' );
		$use_build  = get_option( 'simply-static-use-build' );

		if ( ! empty( $use_build ) || ! empty( $use_single ) ) {
			return;
		}

		$redirections = $this->get_redirects();

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {

			if ( empty( $redirection['sources'] ) ) {
				continue;
			}

			foreach ( $redirection['sources'] as $source ) {

				if ( $source['comparison'] !== 'exact' ) {
					continue;
				}

				$url = home_url( $source['pattern'] );
				Util::debug_log( 'Adding RankMath redirection URL to queue: ' . $url );
				/** @var \Simply_Static\Page $static_page */
				$static_page = Page::query()->find_or_initialize_by( 'url', $url );
				$static_page->set_status_message( __( 'RankMath Redirection URL', 'simply-static' ) );
				$static_page->found_on_id = 0;
				$static_page->save();
			}

		}

	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_page() {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return;
		}

		$urls = array(
			Router::get_base_url( 'sitemap_index.xml' ),
			Router::get_base_url( 'main-sitemap.xsl' )
	);

		foreach ( $urls as $url ) {
			Util::debug_log( 'Adding sitemap URL to queue: ' . $url );

			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->handler     = Rank_Math_Sitemap_Handler::class;
			$static_page->save();
		}

		// Extract and add individual sitemap URLs from sitemap_index.xml
		$this->extract_sitemap_urls_from_index();
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return $urls;
		}

		$urls[] = Router::get_base_url( 'sitemap_index.xml' );
		$urls[] = Router::get_base_url( 'main-sitemap.xsl' );

		// Extract individual sitemap URLs from sitemap_index.xml
		$sitemap_index_url = Router::get_base_url( 'sitemap_index.xml' );
		$response = wp_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$xml_content = wp_remote_retrieve_body( $response );

			// Use SimpleXML to parse the XML
			libxml_use_internal_errors( true );
			$xml = simplexml_load_string( $xml_content );

			if ( $xml !== false && isset( $xml->sitemap ) ) {
				foreach ( $xml->sitemap as $sitemap ) {
					if ( isset( $sitemap->loc ) ) {
						$sitemap_url = (string) $sitemap->loc;
						$urls[] = $sitemap_url;
						Util::debug_log( 'Adding individual sitemap URL to single export: ' . $sitemap_url );
					}
				}
			}
		}

		return $urls;
	}

	/**
	 * Replace JSON schema for schema.org
	 *
	 * @param mixed  $dom DOMDocument or HTML string.
	 * @param string $url given URL.
	 *
	 * @return mixed DOMDocument or HTML string (same type as input)
	 */
	public function replace_json_schema( $dom, $url ) {
		$options = Options::instance();

		// Normalize input to DOMDocument while keeping track of original type.
		$original_was_string = is_string( $dom );

		if ( $original_was_string ) {
			$doc = new \DOMDocument();
			libxml_use_internal_errors( true );
			// Suppress implied html/body to better preserve fragments if possible.
			$load_options = defined('LIBXML_HTML_NOIMPLIED') ? (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD) : 0;
			$doc->loadHTML( $dom, $load_options );
			libxml_clear_errors();
		} elseif ( $dom instanceof \DOMDocument ) {
			$doc = $dom;
		} else {
			// Unknown type; nothing to do.
			return $dom;
		}

		// Use DOMXPath to find script elements with class 'rank-math-schema'
		$xpath = new \DOMXPath( $doc );
		$scripts = $xpath->query( '//script[contains(@class, "rank-math-schema")]' );

		if ( $scripts ) {
			foreach ( $scripts as $script ) {
				$decoded_text = html_entity_decode( $script->nodeValue, ENT_NOQUOTES );
				$text = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $options->get_destination_url(), $decoded_text );
				$script->nodeValue = $text;
			}
		}

		// Return the same type that was provided.
		if ( $original_was_string ) {
			return $doc->saveHTML();
		}

		return $doc;
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		// Rank Math exposes various identifiers; use multiple checks for reliability.
		return ( defined( 'RANK_MATH_VERSION' )
			|| class_exists( '\\RankMath\\Helper' )
			|| class_exists( '\\RankMath\\Sitemap\\Router' ) );
	}

	/**
	 * Extract sitemap URLs from sitemap_index.xml and add them to the queue.
	 *
	 * @return void
	 */
	protected function extract_sitemap_urls_from_index() {
		if ( ! class_exists( '\RankMath\Sitemap\Router' ) ) {
			return;
		}

		$sitemap_index_url = Router::get_base_url( 'sitemap_index.xml' );
		$response = wp_remote_get( $sitemap_index_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			Util::debug_log( 'Failed to fetch sitemap index: ' . $sitemap_index_url );
			return;
		}

		$xml_content = wp_remote_retrieve_body( $response );

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content );

		if ( $xml === false ) {
			Util::debug_log( 'Failed to parse sitemap index XML: ' . $sitemap_index_url );
			return;
		}

		// Extract sitemap URLs
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) ) {
					$sitemap_url = (string) $sitemap->loc;

					// Add the sitemap URL to the queue
					Util::debug_log( 'Adding sitemap URL to queue: ' . $sitemap_url );
					/** @var \Simply_Static\Page $static_page */
					$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
					$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$static_page->handler     = Rank_Math_Sitemap_Handler::class;
					$static_page->save();
				}
			}
		}
	}
}
