<?php
/**
 * @package Simply_Static\Unit_tests
 */
class Simply_Static_Test extends WP_UnitTestCase {

    /**
	 * @var Simply_Static
	 */
	private static $class_instance;

	public static function setUpBeforeClass() {
		self::$class_instance = Simply_Static::instance();
	}

	/**
	 * Placeholder test to prevent PHPUnit from throwing errors
	 */
	public function test_class_is_tested() {
		$this->assertTrue( true );
	}

}
