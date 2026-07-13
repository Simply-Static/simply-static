<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

final class OptionsTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireSource( 'src/class-ss-plugin.php' );
		$this->requireSource( 'src/class-ss-options.php' );
		$this->requireSource( 'src/class-ss-util.php' );
	}

	public function test_it_reads_mutates_and_persists_options(): void {
		WpEnv::$options['simply-static'] = array( 'delivery_method' => 'zip' );
		$options = Options::reinstance();

		self::assertSame( 'zip', $options->get( 'delivery_method' ) );
		self::assertNull( $options->get( 'missing' ) );
		self::assertTrue( $options->set( 'delivery_method', 'local' )->save() );
		self::assertSame( 'local', WpEnv::$options['simply-static']['delivery_method'] );
		self::assertTrue( $options->destroy( 'delivery_method' ) );
		self::assertFalse( $options->destroy( 'delivery_method' ) );
	}

	public function test_constant_overrides_do_not_require_a_persisted_option_key(): void {
		if ( ! defined( 'SIMPLY_STATIC_RUNTIME_ONLY_SETTING' ) ) {
			define( 'SIMPLY_STATIC_RUNTIME_ONLY_SETTING', 'from-constant' );
		}

		WpEnv::$options['simply-static'] = array( 'delivery_method' => 'zip' );
		$options = Options::reinstance();

		self::assertSame( 'from-constant', $options->get( 'runtime_only_setting' ) );
		self::assertTrue( $options->set( 'archive_start_time', '2026-07-13 18:00:00' )->save() );
		self::assertSame( 'from-constant', $options->get( 'runtime_only_setting' ) );
		self::assertArrayNotHasKey( 'runtime_only_setting', WpEnv::$options['simply-static'] );
	}

	public function test_runtime_filtered_options_remain_available_after_save(): void {
		add_filter(
			'ss_get_options',
			static function ( $options ) {
				$options = is_array( $options ) ? $options : array();
				$options['filtered_runtime_setting'] = 'from-filter';

				return $options;
			}
		);

		WpEnv::$options['simply-static'] = array( 'delivery_method' => 'zip' );
		$options = Options::reinstance();

		self::assertSame( 'from-filter', $options->get( 'filtered_runtime_setting' ) );
		self::assertTrue( $options->set( 'archive_start_time', '2026-07-13 18:00:00' )->save() );
		self::assertSame( 'from-filter', $options->get( 'filtered_runtime_setting' ) );
		self::assertArrayNotHasKey( 'filtered_runtime_setting', WpEnv::$options['simply-static'] );
	}

	public function test_save_merges_only_dirty_keys_into_fresh_database_state(): void {
		WpEnv::$options['simply-static'] = array(
			'delivery_method'    => 'zip',
			'archive_start_time' => null,
		);
		$options = Options::reinstance();
		$options->set( 'archive_start_time', '2026-07-12 12:00:00' );

		// Simulate an administrator saving a different key while a long-lived
		// worker still holds its original request-local Options instance.
		WpEnv::$options['simply-static']['delivery_method'] = 'local';
		WpEnv::$options['simply-static']['new_admin_value'] = 'keep-me';

		self::assertTrue( $options->save() );
		self::assertSame(
			array(
				'delivery_method'    => 'local',
				'archive_start_time' => '2026-07-12 12:00:00',
				'new_admin_value'    => 'keep-me',
			),
			WpEnv::$options['simply-static']
		);
		self::assertSame( 'local', $options->get( 'delivery_method' ) );
	}

	public function test_destroy_removes_only_the_requested_key_from_fresh_state(): void {
		WpEnv::$options['simply-static'] = array( 'remove_me' => 'old', 'keep' => 'old' );
		$options = Options::reinstance();
		self::assertTrue( $options->destroy( 'remove_me' ) );
		WpEnv::$options['simply-static']['keep'] = 'new';

		self::assertTrue( $options->save() );
		self::assertSame( array( 'keep' => 'new' ), WpEnv::$options['simply-static'] );
	}

	public function test_invalid_options_filter_output_cannot_corrupt_the_container(): void {
		add_filter(
			'ss_get_options',
			static function () {
				return 'invalid';
			}
		);

		$options = Options::reinstance();
		self::assertSame( array(), $options->get_as_array() );
		self::assertTrue( $options->set( 'delivery_method', 'zip' )->save() );
		self::assertSame( 'zip', WpEnv::$options['simply-static']['delivery_method'] );
	}

	public function test_save_merges_against_a_fresh_database_row_instead_of_stale_option_cache(): void {
		WpEnv::$options['simply-static'] = array( 'admin_value' => 'stale-cache', 'runtime' => 'old' );
		$options = Options::reinstance();
		$options->set( 'runtime', 'new' );

		$previous_wpdb = isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb'] : null;
		$GLOBALS['wpdb'] = new class() {
			/** @var string */
			public $options = 'wp_options';

			public function prepare( $query, ...$arguments ) {
				return str_replace( '%s', "'simply-static'", $query );
			}

			public function get_var( $query ) {
				return serialize( array( 'admin_value' => 'fresh-database', 'runtime' => 'old' ) );
			}
		};

		try {
			self::assertTrue( $options->save() );
		} finally {
			if ( null === $previous_wpdb ) {
				unset( $GLOBALS['wpdb'] );
			} else {
				$GLOBALS['wpdb'] = $previous_wpdb;
			}
		}

		self::assertSame( 'fresh-database', WpEnv::$options['simply-static']['admin_value'] );
		self::assertSame( 'new', WpEnv::$options['simply-static']['runtime'] );
	}

	/**
	 * @dataProvider destinationProvider
	 * @param array<string,mixed> $settings
	 */
	public function test_it_builds_destination_urls( array $settings, string $expected ): void {
		WpEnv::$options['simply-static'] = $settings;
		self::assertSame( $expected, Options::reinstance()->get_destination_url() );
	}

	/** @return array<string,array{array<string,mixed>,string}> */
	public function destinationProvider(): array {
		return array(
			'absolute' => array(
				array(
					'destination_url_type' => 'absolute',
					'destination_scheme'   => 'https://',
					'destination_host'     => 'static.example/',
				),
				'https://static.example',
			),
			'relative' => array(
				array( 'destination_url_type' => 'relative', 'relative_path' => '/docs' ),
				'/docs',
			),
			'offline' => array( array( 'destination_url_type' => 'offline' ), './' ),
		);
	}
}
