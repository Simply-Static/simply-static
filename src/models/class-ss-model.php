<?php
namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Model class
 *
 * Represents a single database table with accessors for finding, creating, and
 * updating records.
 */
class Model {

	/**
	 * The name of the table (prefixed with the name of the plugin)
	 *
	 * @var string
	 */
	protected static $table_name = null;

	/**
	 * A list of the columns for the model
	 *
	 * In the format of 'col_name' => 'col_definition', e.g.
	 *     'id' => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'
	 * @var array
	 */
	protected static $columns = array();

	/**
	 * A list of the indexes for the model
	 *
	 * In the format of 'index_name' => 'index_def', e.g.
	 *     'url' => 'url'
	 *
	 * @var array
	 */
	protected static $indexes = array();

	/**
	 * The name of the primary key for the model
	 *
	 * @var string
	 */
	protected static $primary_key = null;

	/**
	 * The stored data for this instance of the model.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * The last successfully persisted data for this instance.
	 *
	 * @var array
	 */
	private $original_data = array();

	/**
	 * Track the fields that have changed since the last successful save.
	 *
	 * @var array
	 */
	private $dirty_fields = array();

	/**
	 * Retrieve the value of a field for the model
	 *
	 * Returns an exception if you try to retrieve a field that isn't set.
	 *
	 * @param  string $field_name The name of the field to retrieve.
	 * @return mixed              The value for the field.
	 */
	public function __get( $field_name ) {
		if ( ! array_key_exists( $field_name, $this->data ) ) {
			throw new \Exception( 'Undefined variable for ' . get_called_class() );
		} else {
			return $this->data[ $field_name ];
		}
	}

	/**
	 * Check whether a field has a non-null value.
	 *
	 * @param string $field_name The name of the field to check.
	 * @return bool Whether the field is set.
	 */
	public function __isset( $field_name ) {
		return array_key_exists( $field_name, $this->data ) && null !== $this->data[ $field_name ];
	}

	/**
	 * Set the value of a field for the model
	 *
	 * Returns an exception if you try to set a field that isn't one of the
	 * model's columns.
	 *
	 * @param string $field_name  The name of the field to set.
	 * @param mixed  $field_value The value for the field.
	 * @return mixed              The value of the field that was set.
	 */
	public function __set( $field_name, $field_value ) {
		if ( ! array_key_exists( $field_name, static::$columns ) ) {
            return;
			throw new \Exception( 'Column doesn\'t exist for ' . get_called_class() );
		} else {
			$this->data[ $field_name ] = $field_value;

			if ( ! array_key_exists( $field_name, $this->original_data ) || $this->original_data[ $field_name ] !== $field_value ) {
				$this->dirty_fields[ $field_name ] = true;
			} else {
				unset( $this->dirty_fields[ $field_name ] );
			}

			return $field_value;
		}
	}

	/**
	 * Returns the name of the table
	 *
	 * Note that MySQL doesn't allow anything other than alphanumerics,
	 * underscores, and $, so dashes in the slug are replaced with underscores.
	 *
	 * @return string The name of the table
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . 'simply_static_' . static::$table_name;
	}

	/**
	 * Used for finding models matching certain criteria
	 *
	 * @return Simply_Static\Query string of query to execute.
	 */
	public static function query() {
		$query = new Query( get_called_class() );
		return $query;
	}

	/**
	 * Initialize an instance of the class and set its attributes
	 *
	 * @param  array $attributes Array of attributes to set for the class.
	 * @return static            An instance of the class.
	 */
	public static function initialize( $attributes ) {
		$obj = new static();
		foreach ( array_keys( static::$columns ) as $column ) {
			$obj->data[ $column ] = null;
		}
		$obj->attributes( $attributes );
		$obj->original_data = $obj->data;
		$obj->dirty_fields  = array();
		return $obj;
	}

	/**
	 * Set the attributes of the model
	 *
	 * @param  array $attributes Array of attributes to set.
	 * @return static            An instance of the class.
	 */
	public function attributes( $attributes ) {
		foreach ( $attributes as $name => $value ) {
			$this->$name = $value;
		}
		return $this;
	}

	/**
	 * Save the model to the database
	 *
	 * If the model is new a record gets created in the database, otherwise the
	 * existing record gets updated.
	 *
	 * @param  array $attributes Array of attributes to set.
	 * @return boolean           An instance of the class.
	 */
	public function save() {
		global $wpdb;

		$is_existing = $this->exists();

		// Do not update an existing record unless one of its fields changed.
		if ( $is_existing && empty( $this->dirty_fields ) ) {
			return true;
		}

		// Autoset created_at/updated_at when a write is required.
		if ( ! $is_existing && $this->created_at === null ) {
			$this->created_at = Util::formatted_datetime();
		}
		$this->updated_at = Util::formatted_datetime();

		if ( $is_existing ) {
			$fields = array_intersect_key( $this->data, $this->dirty_fields );
		} else {
			// New records need all initialized values, even though initialize()
			// deliberately leaves the object clean for hydrated records.
			$fields = array();
			foreach ( $this->data as $field_name => $field_value ) {
				if ( null !== $field_value ) {
					$fields[ $field_name ] = $field_value;
				}
			}
		}

		if ( $is_existing ) {
			$primary_key = static::$primary_key;
			$rows_updated = $wpdb->update( self::table_name(), $fields, array( $primary_key => $this->$primary_key ) );

			if ( false === $rows_updated ) {
				return false;
			}

			$this->mark_fields_clean( $fields );
			return true;
		} else {
			$rows_updated = $wpdb->insert( self::table_name(), $fields );
			if ( $rows_updated === false ) {
				return false;
			} else {
				$primary_key                = static::$primary_key;
				$this->data[ $primary_key ] = $wpdb->insert_id;
				$this->original_data        = $this->data;
				$this->dirty_fields         = array();
				return true;
			}
		}
	}

	/**
	 * Mark successfully persisted fields as clean.
	 *
	 * @param array $fields Fields and values sent to the database.
	 * @return void
	 */
	private function mark_fields_clean( $fields ) {
		foreach ( $fields as $field_name => $field_value ) {
			$this->original_data[ $field_name ] = $field_value;

			if ( $this->data[ $field_name ] === $field_value ) {
				unset( $this->dirty_fields[ $field_name ] );
			}
		}
	}

	/**
	 * Check if the model exists in the database
	 *
	 * Technically this is checking whether the model has its primary key set.
	 * If it is set, we assume the record exists in the database.
	 *
	 * @return boolean Does this model exist in the db?
	 */
	public function exists() {
		$primary_key = static::$primary_key;
		return $this->$primary_key !== null;
	}

	/**
	 * Create or update the table for the model
	 *
	 * Uses the static::$table_name and loops through all of the columns in
	 * static::$columns and the indexes in static::$indexes to create a SQL
	 * query for creating the table.
	 *
	 * http://wordpress.stackexchange.com/questions/78667/dbdelta-alter-table-syntax
	 * @return void
	 */
	public static function create_or_update_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = 'CREATE TABLE ' . self::table_name() . ' (' . "\n";

		foreach ( static::$columns as $column_name => $column_definition ) {
			$sql .= '`' . $column_name . '` ' . $column_definition . ', ' . "\n";
		}
		foreach ( static::$indexes as $index ) {
			$sql .= $index . ', ' . "\n";
		}

		// remove trailing newline
		$sql = rtrim( $sql, "\n" );
		// remove trailing comma
		$sql = rtrim( $sql, ', ' );
		$sql .= "\n" . ') ' . "\n" . $charset_collate;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Drop the table for the model
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table_name() );
	}
}
