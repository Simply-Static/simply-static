<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Simply_Static
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/simply-static.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Include Faker
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php'; // 3rd-party libs

// Setting some initial options for testing
$options = Simply_Static\Options::instance();
$options
	->set( 'destination_url_type', 'absolute' )
	->set( 'destination_scheme',   'http://' )
	->set( 'destination_host',     'example.org' )
	->set( 'temp_files_dir',       get_temp_dir() )
	->save();

// Include helpers
require_once 'helpers/class-ss-url-extractor-factory.php';
require_once 'helpers/class-ss-page-factory.php';
