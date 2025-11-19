<?php

namespace Simply_Static;

class AIO_SEO_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'aio-seo';

	public function __construct() {
		$this->name = __( 'All in One SEO', 'simply-static' );
		$this->description = __( 'Adds XML sitemaps to generated static files.', 'simply-static' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
        add_filter( 'aioseo_unrecognized_allowed_query_args', [ $this, 'allowed_query_args' ] );
		add_action( 'ss_after_setup_task', [ $this, 'register_sitemap_pages' ] );
		add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		add_filter( 'ss_additional_files', [ $this, 'maybe_add_text_files' ] );

		$this->include_file( 'handlers/class-ss-aio-seo-sitemap-handler.php' );
	}

    /**
     * Adding 'simply_static_page' as an allowed query argument.
     *
     * @param array $args Query Arguments that are allowed and won't be removed from urls.
     * @return array|mixed
     */
    public function allowed_query_args( $args ) {
        if ( ! is_array( $args ) ) {
            $args = [];
        }

        $args[] = 'simply_static_page';
        return $args;
    }

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_pages() {
		$url = home_url( 'sitemap.xml' );

		$this->register_sitemap_page( $url );

		if ( function_exists( 'aioseo' ) ) {
			aioseo()->sitemap->type = 'general';
			$post_types             = aioseo()->sitemap->helpers->includedPostTypes();
			foreach ( $post_types as $post_type ) {
				$post_type_url = home_url( $post_type . '-sitemap.xml' );
				$this->register_sitemap_page( $post_type_url );
			}

			$taxonomies = aioseo()->sitemap->helpers->includedTaxonomies();
			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_url = home_url( $taxonomy . '-sitemap.xml' );
				$this->register_sitemap_page( $taxonomy_url );
			}
		}

		// Extract and add individual sitemap URLs from sitemap.xml
		$this->extract_sitemap_urls_from_index();
	}

	/**
	 * Add sitemap URL to database for crawling.
	 *
	 * @param string $url given URL.
	 *
	 * @return void
	 */
	public function register_sitemap_page( $url ) {
		Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
		/** @var \Simply_Static\Page $static_page */
		$static_page = Page::query()->find_or_initialize_by( 'url', $url );
		$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
		$static_page->found_on_id = 0;
		$static_page->handler     = AIO_SEO_Sitemap_Handler::class;
		$static_page->save();
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		$urls[] = home_url( 'sitemap.xml' );
		$urls[] = home_url( 'main-sitemap.xsl' );

		if ( function_exists( 'aioseo' ) ) {
			aioseo()->sitemap->type = 'general';
			$post_types             = aioseo()->sitemap->helpers->includedPostTypes();
			foreach ( $post_types as $post_type ) {
				$post_type_url = home_url( $post_type . '-sitemap.xml' );
				$urls[] = $post_type_url;
			}

			$taxonomies = aioseo()->sitemap->helpers->includedTaxonomies();
			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_url = home_url( $taxonomy . '-sitemap.xml' );
				$urls[] = $taxonomy_url;
			}
		}

		// Extract individual sitemap URLs from sitemap.xml
		$sitemap_url = home_url( 'sitemap.xml' );
		$response = wp_remote_get( $sitemap_url, array( 'timeout' => 30 ) );

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
	 * Extract sitemap URLs from sitemap.xml and add them to the queue.
	 *
	 * @return void
	 */
	protected function extract_sitemap_urls_from_index() {
		$sitemap_url = home_url( 'sitemap.xml' );
		$response = wp_remote_get( $sitemap_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			Util::debug_log( 'Failed to fetch sitemap index: ' . $sitemap_url );
			return;
		}

		$xml_content = wp_remote_retrieve_body( $response );

		// Use SimpleXML to parse the XML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content );

		if ( $xml === false ) {
			Util::debug_log( 'Failed to parse sitemap index XML: ' . $sitemap_url );
			return;
		}

		// Extract sitemap URLs
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) ) {
					$sitemap_url = (string) $sitemap->loc;

					// Add the sitemap URL to the queue
					Util::debug_log( 'Adding individual sitemap URL to queue: ' . $sitemap_url );
					/** @var \Simply_Static\Page $static_page */
					$static_page = Page::query()->find_or_initialize_by( 'url', $sitemap_url );
					$static_page->set_status_message( __( 'Individual Sitemap URL', 'simply-static' ) );
					$static_page->found_on_id = 0;
					$static_page->handler     = AIO_SEO_Sitemap_Handler::class;
					$static_page->save();
				}
			}
		}
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'AIOSEO_FILE' );
	}

	/**
	 * Maybe add robots.txt and llms.txt to additional files when AIOSEO is active.
	 *
	 * We fetch public endpoints and write temporary files into the archive so Simply Static can export them.
	 *
	 * @param array $additional_files
	 * @return array
	 */
	public function maybe_add_text_files( $additional_files ) {
		$additional_files = is_array( $additional_files ) ? $additional_files : [];

		// Global flags to allow disabling robots.txt and llms.txt entirely.
		$include_robots = (bool) apply_filters( 'ss_include_robots_txt_in_export', true );
		$include_llms   = (bool) apply_filters( 'ss_include_llms_txt_in_export', true );

		$robots_physical = ABSPATH . 'robots.txt';
		$llms_physical   = ABSPATH . 'llms.txt';

		// If a physical llms.txt exists in the WP root, ensure it is exported with URL replacements.
		if ( $include_llms && file_exists( $llms_physical ) ) {
			$body = @file_get_contents( $llms_physical );
			if ( is_string( $body ) && $body !== '' ) {
				$body = $this->replace_urls_in_text( $body );
				$path = $this->write_archive_file( 'llms.txt', $body );
				if ( $path ) {
					$this->run_text_file_handler( 'llms.txt' );
				}
			}
		} elseif ( ! $include_llms ) {
			Util::debug_log( '[AIOSEO] llms.txt generation disabled via ss_include_llms_txt_in_export' );
		}

		$archive_dir = Options::instance()->get_archive_dir();
		if ( ! file_exists( $archive_dir ) ) {
			wp_mkdir_p( $archive_dir );
		}

		// robots.txt via public endpoint
		if ( $include_robots && ! file_exists( $robots_physical ) ) {
			$robots_url = home_url( '/robots.txt' );
			$response   = wp_remote_get( $robots_url, [ 'timeout' => 20 ] );
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$body = is_string( $body ) ? $body : '';
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'robots.txt', $body );
					if ( $path ) {
						$this->run_text_file_handler( 'robots.txt' );
					}
				}
			}
		} elseif ( ! $include_robots ) {
			Util::debug_log( '[AIOSEO] robots.txt generation disabled via ss_include_robots_txt_in_export' );
		}

		// llms.txt via public endpoint (if plugin provides it)
		if ( $include_llms && ! file_exists( $llms_physical ) ) {
			$llms_url = home_url( '/llms.txt' );
			$response = wp_remote_get( $llms_url, [ 'timeout' => 20 ] );
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$body = is_string( $body ) ? $body : '';
				if ( $body !== '' && stripos( $body, '<html' ) === false ) {
					$body = $this->replace_urls_in_text( $body );
					$path = $this->write_archive_file( 'llms.txt', $body );
					if ( $path ) {
						$this->run_text_file_handler( 'llms.txt' );
					}
				}
			}
		} elseif ( ! $include_llms ) {
			Util::debug_log( '[AIOSEO] llms.txt generation disabled via ss_include_llms_txt_in_export' );
		}

		return $additional_files;
	}

	/**
	 * Write a file directly into the current archive directory.
	 *
	 * @param string $filename
	 * @param string $content
	 * @return string|null
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
	 * Replace origin URLs with destination URL inside a plain-text string.
	 *
	 * @param string $content
	 * @return string
	 */
	private function replace_urls_in_text( $content ) {
		if ( ! is_string( $content ) || $content === '' ) {
			return $content;
		}
		$options         = Options::instance();
		$destination_url = rtrim( $options->get_destination_url(), '/' );
		if ( empty( $destination_url ) ) {
			return $content;
		}
		$origin_host  = Util::origin_host();
		$host_no_port = preg_replace( '/:\\d+$/', '', (string) $origin_host );
		$pattern      = '/(?:https?:)?\\/\\/' . preg_quote( $host_no_port, '/' ) . '(?::\\d+)?/i';
		$replaced     = preg_replace( $pattern, $destination_url, $content );

		$home_http  = set_url_scheme( home_url( '/' ), 'http' );
		$home_https = set_url_scheme( home_url( '/' ), 'https' );
		$home_proto = preg_replace( '#^https?:#i', '', $home_https );
		$search    = [ rtrim( $home_http, '/' ), rtrim( $home_https, '/' ), rtrim( $home_proto, '/' ) ];
		$replaced2 = str_replace( $search, rtrim( $destination_url, '/' ), $replaced );
		return $replaced2;
	}

	/**
	 * Run the Text_File_Handler on a relative file within the archive directory.
	 *
	 * @param string $relative_filename
	 * @return void
	 */
	private function run_text_file_handler( $relative_filename ) {
		if ( ! class_exists( __NAMESPACE__ . '\\Text_File_Handler', false ) ) {
			$this->include_file( 'handlers/class-ss-page-handler.php' );
			$this->include_file( 'handlers/class-ss-text-file-handler.php' );
		}
		if ( ! class_exists( __NAMESPACE__ . '\\Page', false ) ) {
			$this->include_file( 'models/class-ss-page.php' );
		}
		try {
			$archive_dir = Options::instance()->get_archive_dir();
			if ( empty( $archive_dir ) ) {
				return;
			}
			$page = new Page();
			$page->file_path = ltrim( $relative_filename, '/\\' );
			$handler = new Text_File_Handler( $page );
			$handler->after_file_fetch( $archive_dir );
		} catch ( \Throwable $e ) {
			// silent
		}
	}

}
