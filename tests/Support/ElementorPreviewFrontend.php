<?php

declare(strict_types=1);

namespace Elementor {

	/** Minimal Elementor container, loaded only in isolated compatibility tests. */
	final class Plugin {
		/** @var object|null */
		public static $instance;
	}
}

namespace Simply_Static\Tests\Support {

	final class ElementorPreviewFrontend {
		/** @var int */
		public $style_registration_calls = 0;

		public function register_styles(): void {
			++$this->style_registration_calls;
			wp_register_style( 'elementor-frontend', '/elementor/frontend.css', array(), 'test' );
		}

		public function get_builder_content(): string {
			( new ElementorPreviewInlineCss() )->enqueue();

			// Elementor starts buffering only AFTER enqueue(), then includes CSS
			// inside the returned builder markup for AJAX requests.
			ob_start();
			if ( wp_doing_ajax() ) {
				echo ElementorPreviewInlineCss::STYLE;
			}
			echo '<div>Unpublished preview changes</div>';

			return (string) ob_get_clean();
		}
	}

	/**
	 * Reproduces Elementor Core\Files\CSS\Base::enqueue()'s inline branch:
	 * an unregistered frontend dependency makes it print outside the builder
	 * content buffer, before an API controller can serialize its JSON response.
	 */
	final class ElementorPreviewInlineCss {
		public const STYLE = '<style id="elementor-preview-123">.preview{color:red}</style>';

		public function enqueue(): void {
			do_action( 'elementor/css-file/before_enqueue', $this );

			if ( ! wp_style_is( 'elementor-frontend', 'registered' ) ) {
				echo self::STYLE;
			}
		}
	}
}
