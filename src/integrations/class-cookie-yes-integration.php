<?php

namespace Simply_Static;

class CookieYes_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'cookieyes';

	public function __construct() {
		$this->name = __( 'CookieYes | GDPR Cookie Consent', 'simply-static' );
		$this->description = __( 'Fixes scripts given by CookieYes to work on exported pages.', 'simply-static' );
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return defined( 'CKY_APP_URL' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'ss_after_extract_and_replace_urls_in_html', [ $this, 'fix_cookieyes_template' ] );
	}

	/**
	 * @param string|object $html_content HTML content or DOM object.
	 *
	 * @return string|object
	 */
	public function fix_cookieyes_template( $html_content ) {
		// Check if WP_HTML_Tag_Processor class exists (WordPress 6.2+)
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			// Log a notice that we're using a fallback
			error_log( 'Simply Static: WP_HTML_Tag_Processor not available in CookieYes integration. Using fallback method.' );

			// For WordPress versions before 6.2, we'll use a simple regex-based approach
			return $this->fix_cookieyes_template_fallback( $html_content );
		}

		// Create a new processor for the HTML content
		$processor = new \WP_HTML_Tag_Processor( $html_content );

		// Find all script tags
		while ( $processor->next_tag( 'script' ) ) {
			// Check if it has the id we're looking for
			if ( 'ckyBannerTemplate' === $processor->get_attribute( 'id' ) ) {
				// Extract the script content
				$script_content = $this->extract_tag_content( $html_content, 'script', $processor );

				if ( $script_content ) {
					// Apply the regex replacement
					$updated_content = preg_replace( '/<\\\/i', '<', $script_content );

					// Replace the content
					$html_content = $this->replace_tag_content( $html_content, 'script', $script_content, $updated_content );
				}
			}
		}

		return $html_content;
	}

	/**
	 * Fallback method for fixing cookieyes template using regex
	 * 
	 * @param string $html_content HTML content
	 * @return string Updated HTML content
	 */
	private function fix_cookieyes_template_fallback( $html_content ) {
		// Pattern to match script tags with id ckyBannerTemplate
		$pattern = '/<script[^>]*id=[\'"]ckyBannerTemplate[\'"][^>]*>(.*?)<\/script>/is';

		return preg_replace_callback(
			$pattern,
			function( $matches ) {
				$script_content = $matches[1];
				$updated_content = preg_replace( '/<\\\/i', '<', $script_content );

				return str_replace( $script_content, $updated_content, $matches[0] );
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
}
