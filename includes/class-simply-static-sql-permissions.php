<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static SQL Privilege Checker
 *
 * Checks to ensure that the MySQL has permissions needed for Simply Static.
 * @package Simply_Static
 */
class Simply_Static_Sql_Permissions {

	/**
	 * Singleton instance
	 * @var Simply_Static_Sql_Permissions
	 */
	protected static $instance = null;

	/**
	 * SQL permissions that a user could have
	 * @var array
	 */
	private $permissions = array(
		'select' => false,
		'update' => false,
		'insert' => false,
		'delete' => false,
		'alter'  => false,
		'create' => false,
		'drop' 	 => false
	);

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Return an instance of the Simply Static plugin
	 * @return Simply_Static_Sql_Permissions
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();

			global $wpdb;
			$rows = $wpdb->get_results( 'SHOW GRANTS FOR current_user()', ARRAY_N );

			foreach ( $rows as $row ) {
				preg_match( '/GRANT (.+) ON (.+) TO/', $row[0], $matches );
				foreach ( explode( ',', $matches[1] ) as $permission ) {
					$permission = str_replace( ' ', '_', trim( strtolower( $permission ) ) );
					if ( $permission === 'all_privileges' ) {
						foreach ( self::$instance->permissions as $key => $value ) {
							self::$instance->permissions[$key] = true;
						}
						break 2;
					}
					self::$instance->permissions[$permission] = true;
				}
			}
		}

		return self::$instance;
	}

	/**
	 * Check if the MySQL user is able to perform the provided permission
	 */
	public function can( $permission ) {
		return ( isset( $this->permissions[ $permission ] ) && $this->permissions[ $permission ] === true );
	}
}
