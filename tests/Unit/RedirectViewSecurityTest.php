<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Tests\Support\UnitTestCase;

final class RedirectViewSecurityTest extends UnitTestCase {

	public function test_script_keeps_raw_query_separators_while_html_is_escaped(): void {
		$html = $this->render( 'https://example.test/next?one=1&two=2' );

		self::assertStringContainsString(
			'window.location.assign("https:\/\/example.test\/next?one=1&two=2")',
			$html
		);
		self::assertStringNotContainsString( 'one=1&#038;two=2")', $html );
		self::assertStringContainsString( 'one=1&amp;two=2', $html );
	}

	/** @dataProvider relativeTargetProvider */
	public function test_relative_redirect_targets_emit_navigation( string $url ): void {
		$html = $this->render( $url );

		self::assertStringContainsString(
			'window.location.assign(' . wp_json_encode( $url ) . ')',
			$html
		);
		self::assertStringContainsString( 'content="0;url=' . esc_attr( $url ) . '"', $html );
		self::assertStringContainsString( 'href="' . esc_attr( $url ) . '"', $html );
		self::assertStringNotContainsString( 'The redirect target is invalid.', $html );
	}

	/** @return array<string,array{string}> */
	public function relativeTargetProvider(): array {
		return array(
			'root relative'     => array( '/en/' ),
			'configured prefix' => array( '/docs/en/?preview=1#top' ),
			'offline'           => array( './en/' ),
			'parent directory'  => array( '../en/' ),
			'path relative'     => array( 'en/' ),
		);
	}

	/** @dataProvider unsafeTargetProvider */
	public function test_unsafe_redirect_targets_do_not_emit_navigation( string $url ): void {
		$html = $this->render( $url );

		self::assertStringNotContainsString( 'window.location.assign', $html );
		self::assertStringContainsString( 'The redirect target is invalid.', $html );
	}

	/** @return array<string,array{string}> */
	public function unsafeTargetProvider(): array {
		return array(
			'javascript'        => array( 'javascript:alert(1)' ),
			'userinfo'          => array( 'https://attacker@example.test/private' ),
			'zero user'         => array( 'https://0@example.test/private' ),
			'file'              => array( 'file:///etc/passwd' ),
			'protocol relative' => array( '//attacker.example/private' ),
			'backslash host'    => array( '\\\\attacker.example/private' ),
			'mixed slash host'  => array( '/\\attacker.example/private' ),
			'control byte'      => array( "/en/\nnext" ),
		);
	}

	private function render( string $url ): string {
		$renderer = new class( $url ) {
			/** @var string */
			public $redirect_url;

			public function __construct( string $redirect_url ) {
				$this->redirect_url = $redirect_url;
			}

			public function output(): string {
				ob_start();
				include dirname( __DIR__, 2 ) . '/views/redirect.php';
				return (string) ob_get_clean();
			}
		};

		return $renderer->output();
	}
}
