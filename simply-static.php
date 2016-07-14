<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        http://codeofconduct.co/simply-static
 * Description:       Produces a static HTML version of your WordPress install and adjusts URLs accordingly.
 * Version:           1.7.0
 * Author:            Code of Conduct
 * Author URI:        http://codeofconduct.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

if ( ! defined( 'SIST_FILE' ) ) {
	define( 'SIST_FILE', __FILE__ );
}

if ( ! defined( 'SIST_PATH' ) ) {
	define( 'SIST_PATH', plugin_dir_path( SIST_FILE ) );
}

if ( ! defined( 'SIST_BASENAME' ) ) {
	define( 'SIST_BASENAME', plugin_basename( SIST_FILE ) );
}

require plugin_dir_path( __FILE__ ) . 'includes/class-simply-static.php';

Simply_Static::init( __FILE__ );
