<?php
/**
 * Plugin Name:       Simply Static
 * Plugin URI:        http://simplystatic.com
 * Description:       Produces a static HTML version of your Wordpress install and adjusts URLs accordingly.
 * Version:           1.0.0
 * Author:            Code of Conduct
 * Author URI:        http://codeofconduct.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require plugin_dir_path( __FILE__ ) . 'includes/class-simply-static.php';

Simply_Static::init(__FILE__);
