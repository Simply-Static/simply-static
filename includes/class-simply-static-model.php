<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static Model class
 *
 * Represents a single database table with accessors for finding, creating, and
 * updating records.
 * @package Simply_Static
 */
class Simply_Static_Model {

	/**
	 * The name of the table (prefixed with the name of the plugin)
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
	 * @var array
	 */
	protected static $indexes = array();

	/**
	 * The name of the primary key for the model
	 * @var string
	 */
	protected static $primary_key = null;

	/**************************************************************************/

	/**
	 * The stored data for this instance of the model.
	 * @var array
	 */
	private $data = array();

	/**
	 * Track if this record has had changed made to it
	 * @var boolean
	 */
	private $changed = false;

	/**
	 * Retrieve the value of a field for the model
	 *
	 * Returns an exception if you try to retrieve a field that isn't set.
	 * @param  string $field_name The name of the field to retrieve
	 * @return mixed              The value for the field
	 */
	public function __get( $field_name ) {
		if ( ! array_key_exists( $field_name, $this->data ) ) {
			throw new Exception( 'Undefined variable for ' . get_called_class() );
		} else {
			return $this->data[ $field_name ];
		}
	}

	/**
	 * Set the value of a field for the model
	 *
	 * Returns an exception if you try to set a field that isn't one of the
	 * model's columns.
	 * @param string $field_name  The name of the field to set
	 * @param mixed  $field_value The value for the field
	 * @return mixed              The value of the field that was set
	 */
	public function __set( $field_name, $field_value ) {
		if ( ! array_key_exists( $field_name, static::$columns ) ) {
			throw new Exception( 'Column doesn\'t exist for ' . get_called_class() );
		} else {
			if ( ! array_key_exists( $field_name, $this->data ) || $this->data[ $field_name ] !== $field_value ) {
				$this->changed = true;
			}
			return $this->data[ $field_name ] = $field_value;
		}
	}

	/**
	 * Returns the name of the table
	 * @return string The name of the table
	 */
	static private function table_name() {
		global $wpdb;

		return $wpdb->prefix . Simply_Static::SLUG . '_' . static::$table_name;
	}

	/**
	 * Returns an array of all records in the table
	 * @return array|null Array of all records, or null if query failure
	 */
	public static function all() {
		global $wpdb;

		$rows = $wpdb->get_results(
			'SELECT * FROM ' . self::table_name(),
			ARRAY_A
		);

		if ( $rows === null ) {
			return null;
		} else {
			$records = array();

			foreach ( $rows as $row ) {
				$records[] = self::initialize( $row );
			}

			return $records;
		}
	}

	/**
	 * Returns the number of pages in the table
	 * @return int Count of the number of pages in the table
	 */
	public static function count() {
		global $wpdb;

		return $wpdb->get_var( 'SELECT COUNT(*) FROM ' .self::table_name() );
	}

	/**
	 * Returns the number of pages in the table
	 * @param  string     $query The SQL query to perform
	 * @return int Count of the number of pages in the table
	 */
	public static function count_where( $query ) {
		global $wpdb;

		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' .self::table_name() . ' WHERE ' . $query );

		return $count;
	}

	/**
	 * Delete records matching a where query, replacing ? with $args
	 * @param  string     $query The SQL query to perform
	 * @param  array      $args  Arguments to replace ?'s with
	 * @return array|null        An array of records, or null if failure
	 */
	public static function delete_where( $query, $args ) {
		global $wpdb;

		$where_values = func_get_args();
		array_shift( $where_values );

		foreach ( $where_values as $value ) {
			$query = preg_replace( '/\?/', self::value_placeholder( $value ), $query, 1 );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'DELETE FROM ' . self::table_name() . ' WHERE ' . $query, $where_values ),
			ARRAY_A
		);

		if ( $rows === null ) {
			return null;
		} else {
			$records = array();

			foreach ( $rows as $row ) {
				$records[] = self::initialize( $row );
			}

			return $records;
		}
	}

	/**
	 * Find records matching a where query, replacing ? with $args
	 * @param  string     $query The SQL query to perform
	 * @param  array      $args  Arguments to replace ?'s with
	 * @return array|null        An array of records, or null if failure
	 */
	public static function where( $query, $args ) {
		global $wpdb;

		$where_values = func_get_args();
		array_shift( $where_values );

		foreach ( $where_values as $value ) {
			$query = preg_replace( '/\?/', self::value_placeholder( $value ), $query, 1 );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE ' . $query, $where_values ),
			ARRAY_A
		);

		if ( $rows === null ) {
			return null;
		} else {
			$records = array();

			foreach ( $rows as $row ) {
				$records[] = self::initialize( $row );
			}

			return $records;
		}
	}

	/**
	 * Find and return an the first record matching the column name/value
	 *
	 * Example: find_by( 'id', 123 )
	 * @param  string $column_name The name of the column to search on
	 * @param  string $value       The value that the column should contain
	 * @return static|null         An instance of the class, or null
	 */
	public static function find_by( $column_name, $value ) {
		global $wpdb;

		$attributes = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE ' . self::where_sql( $column_name, $value ), $value ),
			ARRAY_A
		);

		if ( $attributes === null ) {
			return null;
		} else {
			return self::initialize( $attributes );
		}
	}

	/**
	 * Find or initialize the first record with the given column name/value
	 *
	 * Finds the first record with the given column name/value, or initializes
	 * an instance of the model if one is not found.
	 * @param  string $column_name The name of the column to search on
	 * @param  string $value       The value that the column should contain
	 * @return static              An instance of the class (might not exist in db yet)
	 */
	public static function find_or_initialize_by( $column_name, $value ) {
		global $wpdb;

		$obj = self::find_by( $column_name, $value );
		if ( ! $obj ) {
			$obj = self::initialize( array( $column_name => $value ) );
		}
		return $obj;
	}

	/**
	 * Find the first record with the given column name/value, or create it
	 * @param  string $column_name The name of the column to search on
	 * @param  string $value       The value that the column should contain
	 * @return static              An instance of the class (might not exist in db yet)
	 */
	public static function find_or_create_by( $column_name, $value ) {
		$obj = self::find_or_initialize_by( $column_name, $value );
		if ( ! $obj->exists() ) {
			$obj->save();
		}
		return $obj;
	}

	/**
	 * Initialize an instance of the class and set its attributes
	 * @param  array $attributes Array of attributes to set for the class
	 * @return static            An instance of the class
	 */
	public static function initialize( $attributes ) {
		$obj = new static();
		foreach ( array_keys( static::$columns ) as $column ) {
			$obj->$column = null;
		}
		$obj->attributes( $attributes );
		return $obj;
	}

	/**
	 * Update all records to set the column name equal to the value
	 * @param  string $column_name The name of the column to search on
	 * @param  string $value       The value that the column should contain
	 * @return int|null            The number of rows updated, or null if failure
	 */
	public static function update_all( $column_name, $value ) {
		global $wpdb;

		if ( $value === null ) {
			$sql = 'UPDATE ' . self::table_name() . ' SET ' . self::update_set_sql( $column_name, $value );
		} else {
			$sql = $wpdb->prepare( 'UPDATE ' . self::table_name() . ' SET ' . self::update_set_sql( $column_name, $value ), $value );
		}

		$rows_updated = $wpdb->query( $sql );
		return $rows_updated;
	}

	/**
	 * Set the attributes of the model
	 * @param  array $attributes Array of attributes to set
	 * @return static            An instance of the class
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
	 * @param  array $attributes Array of attributes to set
	 * @return boolean           An instance of the class
	 */
	public function save() {
		global $wpdb;

		// autoset created_at/updated_at upon save
		if ( $this->created_at === null ) {
			$this->created_at = sist_formatted_datetime();
		}
		$this->updated_at = sist_formatted_datetime();

		// remove null data
		$fields = array_filter( $this->data, function($v) { return $v !== null; } );

		// If we haven't changed anything, don't bother updating the DB, and
		// return that saving was successful.
		if ( $this->changed === false ) {
			return true;
		} else {
			// otherwise, we're going to save this record, so mark that we're
			// not changed anymore.
			$this->changed = false;
		}

		if ( $this->exists() ) {
			$primary_key = static::$primary_key;
			$rows_updated = $wpdb->update( self::table_name(), $fields, array( $primary_key => $this->$primary_key ) );
			return $rows_updated !== false;
		} else {
			$rows_updated = $wpdb->insert( self::table_name(), $fields );
			if ( $rows_updated === false ) {
				return false;
			} else {
				$this->id = $wpdb->insert_id;
				return true;
			}
		}
	}

	/**
	 * Check if the model exists in the database
	 *
	 * Technically this is checking whether the model has its primary key set.
	 * If it is set, we assume the record exists in the database.
	 * @return boolean Does this model exist in the db?
	 */
	public function exists() {
		$primary_key = static::$primary_key;
		return $this->$primary_key !== null;
	}

	/**
	 * Generate a SQL fragment for use in WHERE x=y
	 * @param  string $column_name The name of the column
	 * @param  mixed  $value       The value for the column
	 * @return string              The SQL fragment to be used in WHERE x=y
	 */
	public static function where_sql( $column_name, $value ) {
		$where_sql = ' ' . $column_name . ' ';
		$where_sql .= ( $value === null ) ? 'IS ' : '= ';
		$where_sql .= self::value_placeholder( $value );
		return $where_sql;
	}

	/**
	 * Generate a SQL fragment for use in UPDATE queries to SET x=y
	 * @param  string $column_name The name of the column
	 * @param  mixed  $value       The value for the column
	 * @return string              The SQL fragment to be used in SET x=y
	 */
	public static function update_set_sql( $column_name, $value ) {
		$where_sql = ' ' . $column_name . ' = ' . self::value_placeholder( $value );
		return $where_sql;
	}

	/**
	 * Return a placeholder (or NULL) based on the value for use in SQL queries
	 * @param  mixed $value The value for the SQL query
	 * @return string       The placeholder (or NULL) to be used in the SQL query
	 */
	public static function value_placeholder( $value ) {
		if ( $value === null ) {
			return 'NULL';
		} elseif ( is_float( $value ) ) {
			return '%f';
		} elseif ( is_integer( $value ) ) {
			return '%d';
		} else {
			return '%s';
		}
	}

	/**
	 * Create the table for the model
	 *
	 * Uses the static::$table_name and loops through all of the columns in
	 * static::$columns and the indexes in static::$indexes to create a SQL
	 * query for creating the table.
	 *
	 * http://wordpress.stackexchange.com/questions/78667/dbdelta-alter-table-syntax
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = 'CREATE TABLE ' . self::table_name() . ' (' . "\n";

		foreach ( static::$columns as $column_name => $column_definition ) {
			$sql .= $column_name . ' ' . $column_definition . ', ' . "\n";
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
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table_name() );
	}
}
