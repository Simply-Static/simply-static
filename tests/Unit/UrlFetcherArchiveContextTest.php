<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use ReflectionProperty;
use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Tests\Support\UnitTestCase;
use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;
use Simply_Static\Url_Fetcher;

require_once dirname( __DIR__, 2 ) . '/src/class-ss-plugin.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-options.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-util.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-model.php';
require_once dirname( __DIR__, 2 ) . '/src/models/class-ss-page.php';
require_once dirname( __DIR__, 2 ) . '/src/handlers/class-ss-page-handler.php';
require_once dirname( __DIR__, 2 ) . '/src/class-ss-url-fetcher.php';

final class ArchiveContextPage extends Page {

	public function save() {
		return true;
	}
}

final class UrlFetcherArchiveContextTest extends UnitTestCase {

	/** @var string */
	private $temporary_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->temporary_dir = sys_get_temp_dir() . '/' . uniqid( 'ss-archive-context-', true );
		WpEnv::$options['simply-static'] = array(
			'archive_name'   => 'first-export',
			'temp_files_dir' => $this->temporary_dir . '/first-site',
		);
		WpEnv::$remote_response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '<html>Homepage</html>',
		);
		Options::reinstance();
		$this->resetFetcher();
	}

	protected function tearDown(): void {
		$this->resetFetcher();
		$this->removeTree( $this->temporary_dir );
		parent::tearDown();
	}

	public function test_next_export_writes_into_its_own_archive_in_the_same_process(): void {
		$fetcher = Url_Fetcher::instance();
		$first_archive = Options::instance()->get_archive_dir();
		self::assertTrue( $fetcher->fetch( $this->homepage(), false ) );

		Options::instance()->set( 'archive_name', 'second-export' )->save();
		$second_archive = Options::instance()->get_archive_dir();

		self::assertSame( $fetcher, Url_Fetcher::instance() );
		self::assertTrue( Url_Fetcher::instance()->fetch( $this->homepage(), false ) );
		self::assertFileExists( $first_archive . 'index.html' );
		self::assertSame( '<html>Homepage</html>', file_get_contents( $second_archive . 'index.html' ) );
	}

	public function test_switching_sites_and_restoring_options_refreshes_the_archive(): void {
		$fetcher = Url_Fetcher::instance();
		$first_options = WpEnv::$options['simply-static'];
		$first_archive = Options::instance()->get_archive_dir();
		self::assertTrue( $fetcher->fetch( $this->homepage(), false ) );

		// WordPress changes the active site's options and uploads during a switch.
		switch_to_blog( 2 );
		WpEnv::$options['simply-static'] = array(
			'archive_name'   => 'second-site-export',
			'temp_files_dir' => $this->temporary_dir . '/second-site',
		);
		Options::reinstance();
		$second_archive = Options::instance()->get_archive_dir();

		self::assertSame( $fetcher, Url_Fetcher::instance() );
		self::assertTrue( Url_Fetcher::instance()->fetch( $this->homepage(), false ) );
		self::assertFileExists( $second_archive . 'index.html' );

		restore_current_blog();
		WpEnv::$options['simply-static'] = $first_options;
		Options::reinstance();
		WpEnv::$remote_response['body'] = '<html>Restored site</html>';

		self::assertSame( $fetcher, Url_Fetcher::instance() );
		self::assertTrue( Url_Fetcher::instance()->fetch( $this->homepage(), false ) );
		self::assertSame( '<html>Restored site</html>', file_get_contents( $first_archive . 'index.html' ) );
		self::assertSame( '<html>Homepage</html>', file_get_contents( $second_archive . 'index.html' ) );
	}

	private function homepage(): ArchiveContextPage {
		return ArchiveContextPage::initialize(
			array(
				'id'  => 1,
				'url' => 'https://example.test/',
			)
		);
	}

	private function resetFetcher(): void {
		$property = new ReflectionProperty( Url_Fetcher::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	private function removeTree( string $path ): void {
		if ( ! is_dir( $path ) ) {
			return;
		}

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getPathname() );
			} else {
				unlink( $file->getPathname() );
			}
		}
		rmdir( $path );
	}
}
