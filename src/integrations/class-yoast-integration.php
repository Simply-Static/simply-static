<?php

namespace Simply_Static;

class Yoast_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'yoast';

	public function __construct() {
		$this->name = __( 'Yoast', 'simply-static' );
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

		// Maybe update sitemap on single export.
		$add_sitemap_single_export = apply_filters( 'ssp_single_export_add_xml_sitemap', false );

		if ( $add_sitemap_single_export ) {
			add_filter( 'ssp_single_export_additional_urls', [ $this, 'add_sitemap_url' ] );
		}

		$this->include_file( 'handlers/class-ss-yoast-sitemap-handler.php' );
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

		$redirections = get_option("wpseo-premium-redirects-base");

		if ( ! $redirections ) {
			return;
		}

		foreach ( $redirections as $redirection ) {

			if ( strpos( $redirection['origin'], 'http' ) === 0 ) {
				continue;
			}

			$url = home_url( $redirection['origin'] );
			Util::debug_log( 'Adding Yoast redirection URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Yoast Redirection URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->save();
		}

	}

	/**
	 * Register sitemap maps for static export.
	 *
	 * @return void
	 */
	public function register_sitemap_page() {
		if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
			return;
		}

		$urls = array(
			\WPSEO_Sitemaps_Router::get_base_url( 'sitemap.xml' ),
			\WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' ),
			\WPSEO_Sitemaps_Router::get_base_url( 'main-sitemap.xsl' ),
		);

		foreach ( $urls as $url ) {
			Util::debug_log( 'Adding sitemap URL to queue: ' . $url );
			/** @var \Simply_Static\Page $static_page */
			$static_page = Page::query()->find_or_initialize_by( 'url', $url );
			$static_page->set_status_message( __( 'Sitemap URL', 'simply-static' ) );
			$static_page->found_on_id = 0;
			$static_page->handler     = Yoast_Sitemap_Handler::class;
			$static_page->save();
		}
	}

	/**
	 * Add XML sitemap to single exports.
	 *
	 * @param $urls
	 *
	 * @return mixed
	 */
	public function add_sitemap_url( $urls ) {
		if ( ! class_exists( 'WPSEO_Sitemaps_Router' ) ) {
			return $urls;
		}

		$urls[] = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap.xml' );
		$urls[] = \WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' );

		return $urls;
	}

	/**
	 * Replace JSON schema for schema.org
	 *
	 * @param string|object $html_content HTML content or DOM object.
	 * @param string $url given URL.
	 *
	 * @return string|object
	 */
	public function replace_json_schema( $html_content, $url ) {
		$options = Options::instance();

		// Check if WP_HTML_Tag_Processor class exists (WordPress 6.2+)
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			// Log a notice that we're using a fallback
			error_log( 'Simply Static: WP_HTML_Tag_Processor not available in Yoast integration. Using fallback method.' );

			// For WordPress versions before 6.2, we'll use a simple regex-based approach
			return $this->replace_json_schema_fallback( $html_content );
		}

		// Create a new processor for the HTML content
		$processor = new \WP_HTML_Tag_Processor( $html_content );

		// Find all script tags with class yoast-schema-graph
		while ( $processor->next_tag( array( 'tag_name' => 'script', 'class_name' => 'yoast-schema-graph' ) ) ) {
			// Extract the script content
			$script_content = $this->extract_tag_content( $html_content, 'script', $processor );

			if ( $script_content ) {
				$decoded_text = html_entity_decode( $script_content, ENT_NOQUOTES );
				$updated_text = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $options->get_destination_url(), $decoded_text );

				// Replace the content
				$html_content = $this->replace_tag_content( $html_content, 'script', $script_content, $updated_text );
			}
		}

		return $html_content;
	}

	/**
	 * Fallback method for replacing JSON schema using regex
	 * 
	 * @param string $html_content HTML content
	 * @return string Updated HTML content
	 */
	private function replace_json_schema_fallback( $html_content ) {
		$options = Options::instance();

		// Pattern to match script tags with class yoast-schema-graph
		$pattern = '/<script[^>]*class=[\'"]yoast-schema-graph[\'"][^>]*>(.*?)<\/script>/is';

		return preg_replace_callback(
			$pattern,
			function( $matches ) use ( $options ) {
				$script_content = $matches[1];
				$decoded_text = html_entity_decode( $script_content, ENT_NOQUOTES );
				$updated_text = preg_replace( '/(https?:)?\/\/' . addcslashes( Util::origin_host(), '/' ) . '/i', $options->get_destination_url(), $decoded_text );

				return str_replace( $script_content, $updated_text, $matches[0] );
			},
			$html_content
		);
	}

	/**
	 * Extract content between opening and closing tags
	 *
	 * @param string $html The HTML content
	 * @param string $tag_name The tag name
	 * @param \WP_HTML_Tag_Processor $processor The processor at the position of the tag
	 * @return string|null The content between tags or null if not found
	 */
	private function extract_tag_content( $html, $tag_name, $processor ) {
		// Get the position of the current tag
		$tag_pos = $processor->get_tag();

		if ( $tag_pos === null ) {
			return null;
		}

		// Use regex to extract the content between the opening and closing tags
		$pattern = "/<{$tag_name}[^>]*>(.*?)<\/{$tag_name}>/is";
		if ( preg_match_all( $pattern, $html, $matches ) ) {
			// Return the content of the current tag
			// This is a simplification and might not work perfectly for nested tags
			return $matches[1][0] ?? null;
		}

		return null;
	}

	/**
	 * Replace content between opening and closing tags
	 *
	 * @param string $html The HTML content
	 * @param string $tag_name The tag name
	 * @param string $old_content The old content to replace
	 * @param string $new_content The new content
	 * @return string The updated HTML
	 */
	private function replace_tag_content( $html, $tag_name, $old_content, $new_content ) {
		// Escape special characters for regex
		$old_content_escaped = preg_quote( $old_content, '/' );

		// Replace the content between the tags
		$pattern = "/(<{$tag_name}[^>]*>)$old_content_escaped(<\/{$tag_name}>)/is";
		return preg_replace( $pattern, "$1$new_content$2", $html );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'WPSEO_FILE' );
	}
}
