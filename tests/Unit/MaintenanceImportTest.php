<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionClass;
use ReflectionProperty;
use Simply_Static\Admin_Rest;
use Simply_Static\Plugin;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/admin/inc/class-ss-admin-rest.php';

final class MaintenanceImportJob {

	/** @var bool */
	public $active = false;

	public function is_active(): bool {
		return $this->active;
	}

	public function is_running(): bool {
		return $this->active;
	}

	public function is_paused(): bool {
		return false;
	}
}

final class MaintenanceImportTest extends UnitTestCase {

	/** @var Admin_Rest */
	private $rest;

	/** @var MaintenanceImportJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		$this->job = new MaintenanceImportJob();
		$plugin    = ( new ReflectionClass( Plugin::class ) )->newInstanceWithoutConstructor();

		$job_property = new ReflectionProperty( Plugin::class, 'archive_creation_job' );
		$job_property->setAccessible( true );
		$job_property->setValue( $plugin, $this->job );

		$instance_property = new ReflectionProperty( Plugin::class, 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, $plugin );

		$this->rest = new Admin_Rest();
	}

	protected function tearDown(): void {
		$instance_property = new ReflectionProperty( Plugin::class, 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );

		parent::tearDown();
	}

	public function test_portable_import_preserves_destination_secrets_paths_and_runtime_state(): void {
		WpEnv::$options['simply-static'] = array(
			'delivery_method'             => 'sftp',
			'origin_url'                  => 'https://destination.example.test',
			'local_dir'                   => '/srv/destination',
			'sftp_pass'                   => 'destination-password',
			'archive_deploy_id'           => 'active-deploy-id',
			'version'                     => '3.4.5',
			'provider'                    => array(
				'label'      => 'Old label',
				'api_secret' => 'nested-secret',
			),
		);

		$response = json_decode(
			(string) $this->rest->save_settings(
				new \WP_REST_Request(
					array(
						'__simply_static_import' => true,
						'delivery_method'        => 'zip',
						'origin_url'             => 'https://attacker.example.test',
						'local_dir'              => '/attacker/replacement',
						'sftp_pass'              => 'attacker-password',
						'archive_deploy_id'      => 'attacker-runtime',
						'version'                => '0.0.0',
						'provider'               => array(
							'label'      => 'Imported label',
							'api_secret' => 'attacker-nested-secret',
						),
					)
				)
			),
			true
		);

		self::assertSame( 200, $response['status'] ?? null );
		$saved = WpEnv::$options['simply-static'];
		self::assertSame( 'zip', $saved['delivery_method'] );
		self::assertSame( 'https://destination.example.test', $saved['origin_url'] );
		self::assertSame( '/srv/destination', $saved['local_dir'] );
		self::assertSame( 'destination-password', $saved['sftp_pass'] );
		self::assertSame( 'active-deploy-id', $saved['archive_deploy_id'] );
		self::assertSame( '3.4.5', $saved['version'] );
		self::assertSame(
			array( 'label' => 'Imported label', 'api_secret' => 'nested-secret' ),
			$saved['provider']
		);
		self::assertArrayNotHasKey( '__simply_static_import', $saved );
	}

	public function test_destructive_maintenance_and_import_are_blocked_while_job_is_active(): void {
		$this->job->active = true;
		WpEnv::$options['simply-static'] = array( 'delivery_method' => 'zip' );

		$import = json_decode(
			(string) $this->rest->save_settings(
				new \WP_REST_Request(
					array(
						'__simply_static_import' => true,
						'delivery_method'        => 'local',
					)
				)
			),
			true
		);
		$settings_reset = json_decode( (string) $this->rest->reset_settings( new \WP_REST_Request() ), true );
		$database_reset = json_decode( (string) $this->rest->reset_database(), true );
		$migrate        = json_decode( (string) $this->rest->migrate( new \WP_REST_Request() ), true );
		$clear_temp     = json_decode( (string) $this->rest->clear_temp_files(), true );

		self::assertSame( 409, $import['status'] ?? null );
		self::assertSame( 409, $settings_reset['status'] ?? null );
		self::assertSame( 409, $database_reset['status'] ?? null );
		self::assertSame( 409, $migrate['status'] ?? null );
		self::assertSame( 409, $clear_temp['status'] ?? null );
		self::assertFalse( $clear_temp['cleared'] ?? true );
		self::assertSame( array( 'delivery_method' => 'zip' ), WpEnv::$options['simply-static'] );
	}

	public function test_network_import_keeps_destination_credentials_and_excluded_values(): void {
		WpEnv::$multisite                           = true;
		WpEnv::$capabilities['manage_network_options'] = true;
		WpEnv::$sites[2]                           = (object) array(
			'blog_id'  => 2,
			'deleted'  => 0,
			'spam'     => 0,
			'archived' => 0,
		);
		WpEnv::$options['simply-static']           = array(
			'delivery_method' => 'sftp',
			'origin_url'      => 'https://destination.example.test',
			'local_dir'       => '/srv/destination',
			'sftp_pass'       => 'destination-password',
		);
		WpEnv::$site_options['simply-static-2']    = array(
			'delivery_method' => 'zip',
			'origin_url'      => 'https://attacker.example.test',
			'local_dir'       => '/srv/source',
			'sftp_pass'       => 'source-password',
		);

		$response = json_decode(
			(string) $this->rest->update_from_network( new \WP_REST_Request( array( 'blog_id' => 2 ) ) ),
			true
		);

		self::assertSame( 200, $response['status'] ?? null );
		self::assertSame( 'zip', WpEnv::$options['simply-static']['delivery_method'] );
		self::assertSame( 'https://destination.example.test', WpEnv::$options['simply-static']['origin_url'] );
		self::assertSame( '/srv/destination', WpEnv::$options['simply-static']['local_dir'] );
		self::assertSame( 'destination-password', WpEnv::$options['simply-static']['sftp_pass'] );
	}
}
