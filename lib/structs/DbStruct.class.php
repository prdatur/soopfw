<?php

/**
 * Provides a Database structure (define fields, primary keys, auto increment, ...)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib/structs
 */
class DbStruct extends Object
{

	/**
	 * The table name
	 * @var string
	 */
	private $table_name = "";

	/**
	 * The reference key, can be an array or a string
	 * @var mixed
	 */
	private $reference_key = "";

	/**
	 * The struct, will hold all table fields
	 * @var array
	 */
	public $struct = array();

	/**
	 * An array where the hidden fields are setup.
	 * It will just be filled with field=>true so this determines that the
	 * given field is hidden
	 *
	 * @var array
	 */
	private $hidden_values = array();

	/**
	 * Holds the field which will be auto increment
	 * @var string
	 */
	private $auto_increment = "";

	/**
	 * Determines if this struct should be cached or not (memcached)
	 * @var boolean
	 */
	private $is_cacheable = false;

	/**
	 * The default cache time for memcached before it will expire
	 * @var int
	 */
	private $default_cache_time = 600;

	/**
	 * Determines if this struct should be runtime cached through static var
	 * within AbstractDataManager
	 *
	 * @var boolean
	 */
	public $runtime_cache = false;

	/**
	 * holds the struct count to not always use the count function on struct
	 * @var int
	 */
	private $struct_count = 0;
	/**
	 * constructor
	 * @param string $table_name The sql table
	 */
	function __construct($table_name) {
		parent::__construct();
		$this->table_name = $table_name;
	}

	/**
	 * Set or get the default cache time
	 *
	 * @params mixed $default_cache_time The cache time, provide NS to get the current value (optional, default = NS)
	 * @return mixed returns the default cache time as an integer in get mode or null in set mode
	 */
	public function default_cache_time($default_cache_time = NS) {
		if ($default_cache_time !== NS) {
			$this->default_cache_time = (int)$default_cache_time;
			return null;
		}
		return $this->default_cache_time;
	}

	/**
	 * Getter for cacheable
	 * @return boolean return true if object is cacheable, else false
	 */
	public function is_cacheable() {
		if(is_null($this->core->memcache_obj)) {
			return false;
		}
		return $this->is_cacheable;
	}

	/**
	 * Setter for cacheable
	 *
	 * @param boolean $val if struct is cacheable
	 */
	public function set_cache($val) {
		$this->is_cacheable = $val;
	}

	/**
	 * Getter for auto_increment
	 *
	 * @return return the key as a string
	 */
	public function get_auto_increment() {
		return $this->auto_increment;
	}

	/**
	 * Return if this struct has a autoincrement field
	 *
	 * @return boolean
	 */
	public function has_auto_increment() {
		return!empty($this->auto_increment);
	}

	/**
	 * Setter for auto_increment
	 *
	 * @param string $val the db field which has auto increment
	 */
	public function set_auto_increment($val) {
		$this->auto_increment = $val;
	}

	/**
	 * Get the Database struct
	 *
	 * @return array The Database struct
	 */
	public function get_struct() {
		return $this->struct;
	}

	/**
	 * Get the Database struct count (how many fields this struct has)
	 *
	 * @return int the field count of this struct
	 */
	public function get_struct_count() {
		if($this->struct_count == 0) {
			$this->struct_count = count($this->struct);
		}
		return $this->struct_count;
	}

	/**
	 * Add a hidden field, so dont return it by default
	 *
	 * @param string $field The DB Field
	 */
	public function add_hidden_field($name, $title, $typ, $default_value = "", $additional = "") {
		$this->hidden_values[$name] = true;
		$this->add_field($name, $title, $typ, $default_value, $additional);
	}

	/**
	 * Returns if the field is a hidden field, normaly a hidden field
	 * should not be returnd with get_values or something similar
	 *
	 * @param string $field The DB Field
	 * @return boolean return true if field is hidden, else false
	 */
	public function is_hidden_field($field) {
		return isset($this->hidden_values[$field]);
	}

	/**
	 * Returns if struct has hidden values
	 *
	 * @return boolean return true if it has hidden values, else false
	 */
	public function has_hidden_fields() {
		return!empty($this->hidden_values);
	}

	/**
	 * Check if struct has db field
	 *
	 * @param string $field The DB Field
	 * @return boolean if exist return true, else false
	 */
	public function has_field($field) {
		return isset($this->struct[$field]);
	}

	/**
	 * Returns the db struct field
	 *
	 * @param string $field The DB Field
	 * @return mixed if exist return the field, else false
	 */
	public function get_field($field) {
		if (isset($this->struct[$field])) {
			return $this->struct[$field];
		}
		return false;
	}

	/**
	 * Adds a reference_key to the struct
	 *
	 * @param string $key The reference key
	 */
	public function add_reference_key($key) {
		if (!is_array($key)) {
			$key = array($key);
		}
		$this->reference_key = $key;
	}

	/**
	 * Return the SQL Table reference_key where to update or delete
	 *
	 * @return array The SQL Table reference_key
	 */
	public function get_reference_key() {
		return $this->reference_key;
	}

	/**
	 * returns wether the $field is a reference key or not
	 * @param string $field
	 * @return boolean
	 */
	public function is_reference_key($field) {
		return in_array($field, $this->reference_key);
	}

	/**
	 *   returns the database type for that field
	 *   @return the type as an integer, based on constants PDT_*
	 */
	public function get_field_type($key) {
		if (!isset($this->struct[$key])) {
			return -1;
		}
		return $this->struct[$key]['typ'];
	}

	/**
	 * Add a description to an element (for form handling)
	 *
	 * @param mixed $field the struct field name or an array with fieldname => description
	 * @param string $description if field is an array description is optional, else it is mandatory (optional, default = '')
	 */
	public function add_description($field, $description = '') {
		//If $field is not an array we transform it to one
		if (!is_array($field)) {
			$field = array($field => $description);
		}

		//Loop through all fields and provide the field the given description, continue if field does not exists
		foreach ($field as $tmp_field => $desc) {
			if (!$this->has_field($tmp_field)) {
				continue;
			}
			$this->struct[$tmp_field]['description'] = $desc;
		}
	}

	/**
	 * Add a struct field
	 *
	 * @param string $name The database field
	 * @param string $title The title which will be used for an objectForm for example
	 * @param int $typ The database field typ ( use Constant for PDT_*)
	 * @param mixed $default_value Default value for this field (optional, default = '')
	 * @param mixed $additional additional params like for PDT_INT 'UNSIGNED' (optional, default = '')
	 */
	public function add_field($name, $title, $typ, $default_value = "", $additional = '') {
		$this->struct[$name]['typ'] = $typ;
		$this->struct[$name]['title'] = $title;
		if (isset($default_value) && $default_value != null) {
			$this->struct[$name]['default'] = $default_value;
		}

		if (isset($additional)) {
			$this->struct[$name]['additional'] = $additional;
		}
		$this->struct[$name]['required'] = false;
	}

	/**
	 * Add a required struct field
	 *
	 * @param string $name The Database field
	 * @param string $title The title which will be used for an objectForm for example
	 * @param int $typ The fatabase field typ ( use Constant for STRING, INT, FLOAT....)
	 * @param mixed $default_value Default value for this field (optional, default = '')
	 * @param mixed $additionaladditional params like for PDT_INT 'UNSIGNED' (optional, default = '')
	 */
	public function add_required_field($name, $title, $typ, $default_value = "", $additional = '') {
		$this->add_field($name, $title, $typ, $default_value, $additional);
		$this->struct[$name]['required'] = true;
	}

	/**
	 * Set the given field to a hidden one
	 *
	 * @param string $name the field name
	 */
	public function set_field_hidden($name) {
		if(!isset($this->struct[$name])) {
			return;
		}

		$this->hidden_values[$name] = true;
	}

	/**
	 * Get the default value for the given param
	 *
	 * @param string $name The Database field
	 * @return mixed The default value, if value doesnt exist or no default value exist return FALSE
	 */
	public function get_default_value($name) {
		if (isset($this->struct[$name]) && isset($this->struct[$name]['default'])) {
			return $this->struct[$name]['default'];
		}
		else {
			return false;
		}
	}

	/**
	 * Get the Database table
	 *
	 * @return string The Database table as a String
	 */
	public function get_table() {
		return $this->table_name;
	}

}

?>