<?php
namespace Simply_Static;

class Test extends \WP_UnitTestCase {

	/**
	 * @var Simply_Static
	 */
	private static $class_instance;

	public static function setUpBeforeClass() {
		self::$class_instance = Plugin::instance();
	}

	/**
	 * Placeholder test to prevent PHPUnit from throwing errors
	 */
	public function test_class_is_tested() {
		$this->assertTrue( true );
	}

}
