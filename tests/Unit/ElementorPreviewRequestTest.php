<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Elementor_Integration;
use Simply_Static\Tests\Support\ElementorPreviewFrontend;
use Simply_Static\Tests\Support\ElementorPreviewInlineCss;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ElementorPreviewRequestTest extends UnitTestCase {

	/** @var ElementorPreviewFrontend */
	private $frontend;

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/integrations/class-ss-integration.php' );
		$this->requireSource( 'src/integrations/class-ss-elementor-integration.php' );
		require_once dirname( __DIR__ ) . '/Support/ElementorPreviewFrontend.php';

		$this->frontend = new ElementorPreviewFrontend();
		\Elementor\Plugin::$instance = (object) array( 'frontend' => $this->frontend );
		WpEnv::$options['simply_static_elementor_defaults_set'] = 1;
		WpEnv::$options['elementor_css_print_method'] = 'internal';
	}

	/** @dataProvider apiRequestTypes */
	public function test_api_preview_response_remains_valid_json_with_inline_css( string $request_type, string $print_method ): void {
		$this->setRequestType( $request_type );
		WpEnv::$options['elementor_css_print_method'] = $print_method;

		// Confirm the fixture exposes the original failure before compatibility
		// hooks are registered: CSS is printed before the JSON body.
		$broken_response = $this->renderApiResponse();
		self::assertStringStartsWith( ElementorPreviewInlineCss::STYLE, $broken_response );
		self::assertNull( json_decode( $broken_response, true ) );
		self::assertSame( JSON_ERROR_SYNTAX, json_last_error() );

		( new Elementor_Integration() )->run();
		$response = $this->renderApiResponse();

		$data = json_decode( $response, true );
		self::assertSame( JSON_ERROR_NONE, json_last_error() );
		self::assertTrue( $data['success'] );
		self::assertStringContainsString( 'Unpublished preview changes', $data['data']['content'] );
		if ( 'ajax' === $request_type ) {
			self::assertStringContainsString( ElementorPreviewInlineCss::STYLE, $data['data']['content'] );
		}
		self::assertSame( 1, $this->frontend->style_registration_calls );
		self::assertSame( $print_method, get_option( 'elementor_css_print_method' ) );
	}

	public static function apiRequestTypes(): array {
		return array(
			'Elementor AJAX autosave' => array( 'ajax', 'internal' ),
			'WordPress REST endpoint' => array( 'rest_endpoint', 'internal' ),
			'Cached inline CSS after changing print mode' => array( 'rest_endpoint', 'external' ),
		);
	}

	public function test_rest_request_constant_protects_json_without_relying_on_endpoint_detection(): void {
		define( 'REST_REQUEST', true );
		( new Elementor_Integration() )->run();

		$response = $this->renderApiResponse();

		self::assertIsArray( json_decode( $response, true ) );
		self::assertSame( JSON_ERROR_NONE, json_last_error() );
		self::assertSame( 1, $this->frontend->style_registration_calls );
	}

	public function test_regular_frontend_does_not_register_styles_early(): void {
		WpEnv::$is_admin = false;
		( new Elementor_Integration() )->run();

		do_action( 'elementor/css-file/before_enqueue', new ElementorPreviewInlineCss() );

		self::assertSame( 0, $this->frontend->style_registration_calls );
		self::assertFalse( wp_style_is( 'elementor-frontend', 'registered' ) );
	}

	public function test_existing_style_registration_and_css_preference_are_preserved(): void {
		WpEnv::$doing_ajax = true;
		WpEnv::$options['elementor_css_print_method'] = 'external';
		wp_register_style( 'elementor-frontend', '/custom/frontend.css', array( 'theme-style' ), 'custom' );
		$registered_styles = WpEnv::$registered_styles;
		( new Elementor_Integration() )->run();

		do_action( 'elementor/css-file/before_enqueue', new ElementorPreviewInlineCss() );

		self::assertSame( 0, $this->frontend->style_registration_calls );
		self::assertSame( $registered_styles, WpEnv::$registered_styles );
		self::assertSame( 'external', get_option( 'elementor_css_print_method' ) );
	}

	public function test_repeated_css_files_register_frontend_styles_once(): void {
		WpEnv::$doing_ajax = true;
		( new Elementor_Integration() )->run();

		do_action( 'elementor/css-file/before_enqueue', new ElementorPreviewInlineCss() );
		do_action( 'elementor/css-file/before_enqueue', new ElementorPreviewInlineCss() );

		self::assertSame( 1, $this->frontend->style_registration_calls );
	}

	private function setRequestType( string $request_type ): void {
		WpEnv::$doing_ajax = 'ajax' === $request_type;
		WpEnv::$rest_endpoint = 'rest_endpoint' === $request_type;
	}

	private function renderApiResponse(): string {
		ob_start();
		try {
			$content = $this->frontend->get_builder_content();
			echo wp_json_encode( array( 'success' => true, 'data' => array( 'content' => $content ) ) );

			return (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}
}
