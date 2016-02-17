<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Simply Static Model class
 *
 * Represents a single database table with accessors for finding, creating, and
 * updating records.
 *
 * @package Simply_Static
 */
class Simply_Static_Model {

	/**
	 * The name of the table (prefixed with the name of the plugin)
	 * @var string
	 */
	protected static $table_name = '';

	/**
	 * A list of the columns for the model
	 *
	 * In the format of 'col_name' => 'col_definition', e.g.
	 *     'id' => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'
	 *
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
	 * @var string
	 */
	protected static $primary_key = '';

	/**************************************************************************/

	/**
	 * The stored data for this instance of the model.
	 * @var array
	 */
	private $data = array();

	/**
	 * Retrieve the value of a field for the model
	 *
	 * Returns an exception if you try to retrieve a field that isn't set.
	 *
	 * @param  string $field_name The name of the field to retrieve
	 * @return mixed              The value for the field
	 */
	public function __get( $field_name ) {
		if ( ! array_key_exists( $field_name, $this->data ) ) {
			throw new Exception('Undefined variable for ' . static::class);
		} else {
			return $this->data[ $field_name ];
		}
	}

	/**
	 * Set the value of a field for the model
	 *
	 * Returns an exception if you try to set a field that isn't one of the
	 * model's columns.
	 *
	 * @param string $field_name  The name of the field to set
	 * @param mixed  $field_value The value for the field
	 * @return mixed              The value of the field that was set
	 */
	public function __set( $field_name, $field_value ) {
		if ( ! array_key_exists( $field_name, static::$columns ) ) {
			throw new Exception('Column doesn\'t exist for ' . static::class);
		} else {
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
	 * Find and return an the first record matching the column name/value
	 *
	 * Example: find_by( 'id', 123 )
	 *
	 * @param  string $column_name The name of the column to search on
	 * @param  string $value       The value that the column should contain
	 * @return static|null         An instance of the class, or null
	 */
	public static function find_by( $column_name, $value ) {
		global $wpdb;

		$attributes = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE ' . $column_name . ' = %s', $value ),
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
	 *
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
	 *
	 * @param  array $attributes Array of attributes to set
	 * @return boolean           An instance of the class
	 */
	public function save() {
		global $wpdb;

		// autoset created_at/updated_at upon save
		if ( ! $this->exists() ) {
			$this->created_at = sist_formatted_datetime();
		}
		$this->updated_at = sist_formatted_datetime();

		// remove null data
		$fields = array_filter( $this->data );

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
	 * 
	 * @return boolean Does this model exist in the db?
	 */
	public function exists() {
		$primary_key = static::$primary_key;
		return $this->$primary_key !== null;
	}
}
