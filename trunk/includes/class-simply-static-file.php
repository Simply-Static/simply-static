<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static file class, for tracking the state of static files
 *
 * @package Simply_Static
 */
class Simply_Static_File {

    /**
	 * Returns the name of the URLs table
	 *
	 * @return void
	 */
	static private function table_name() {
		global $wpdb;

		return $wpdb->prefix . Simply_Static::SLUG . '_files';
	}

    public static function find_by( $field_name, $field_value ) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE ' . $field_name . ' = %s', $field_value )
        );
        error_log( $wpdb->last_query );
        return $row;
    }

    public static function find_or_create_by( $field_name, $field_value, $data = array() ) {
        global $wpdb;

        $row = self::find_by( $field_name, $field_value );
        if ( $row ) {
            $wpdb->update( self::table_name(), $data, array( $field_name => $field_value ) );
            error_log( $wpdb->last_query );
            return $row;
        } else {
            // automatically add $field_name/value to $data if it's not there
            if ( ! isset( $data[ $field_name ] ) ) {
                $data[ $field_name ] = $field_value;
            }
            $wpdb->insert( self::table_name(), $data );
            error_log( $wpdb->last_query );
            return (object) $data;
        }
    }

}
