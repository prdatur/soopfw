<?php
/**
 * Provide an abstract data managment.
 * Everytime we decide to create a new table we should create a Model object
 * which extends the AbstractDataManagement class.
 * The data manager will handle all save / load / change processes within the database.
 * It works also with memcached to have a better performance
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Model
 */
abstract class AbstractDataManagement extends Object
{

	/**
	 * Define constances
	 */
	const PARAM_TYPE_ADD = "add";
	const PARAM_TYPE_CHANGE = "change";
	const PARAM_TYPE_VIEW = "view";
	const PARAM_TYPE_DELETE = "delete";

	/**
	 * the db filter for the where statement
	 *
	 * @var DatabaseFilter
	 */
	public $db_filter;

	/**
	 * only the changed values
	 *
	 * @var array
	 */
	public $values_changed = array();

	/**
	 * The db_struct Object
	 *
	 * @var DbStruct
	 */
	protected $db_struct = null;

	/**
	 * Whether or not the transaction_auto_begin() method
	 * began a transaction
	 *
	 * @var boolean
	 */
	protected $transaction_auto_begun = false;

	/**
	 * successfull loaded?
	 *
	 * @var boolean
	 */
	protected $load_success = false;

	/**
	 * The current values
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * The old values for reverting
	 *
	 * @var array
	 */
	protected $old_values = array();

	/**
	 * The last inserted auto_increment id
	 *
	 * @var int
	 */
	private $last_inserted_id = null;

	/**
	 * Constructor.
	 *
	 * @param Core $core
	 *   The Core Object (optional, default = null)
	 */
	public function __construct(Core &$core = null) {
		parent::__construct($core);
		$this->db_filter = new DatabaseFilter();
	}

	/**
	 * Returns the original key for this object, the values will be filled after loading and will not change until a save operation occurs
	 *
	 * @param string $key
	 *   the key
	 *
	 * @return mixed the value for the key, if key not exists returns null
	 */
	public function get_original_value($key) {
		if(!isset($this->old_values[$key])) {
			return null;
		}
		return $this->old_values[$key];
	}

	/**
	 * Get the object db struct
	 *
	 * @return DbStruct returns the database struct
	 */
	public function &get_dbstruct() {
		return $this->db_struct;
	}

	/**
	 * Returns an object form for the current AbstractDataManagement object
	 *
	 * @return ObjForm the object_form
	 */
	public function get_form() {
		return new ObjForm($this);
	}

	/**
	 * Set the value of given name
	 *
	 * @param string $name
	 *   The name
	 * @param mixed $value
	 *   The value
	 */
	public function __set($name, $value) {
		if (!$this->db_struct->has_field($name)) {
			return false;
		}

		$value = $this->parse_value($value, $this->db_struct->get_field_type($name));

		if (!is_scalar($value)) {
			trigger_error($this->db_struct->get_table().': Value ['.$name.'] is expected to be a scalar but '.gettype($value).' provided.', E_USER_ERROR);
		}

		$this->set_values_changed($name, $value);
		$this->values[$name] = $value;
		return true;
	}

	/**
	 * Set values
	 *
	 * @param array $field_arr
	 *   The Data
	 */
	public function set_fields(Array $field_arr) {
		foreach ($field_arr as $k => $v) {
			$this->__set($k, $v);
		}
	}

	/**
	 * Get the value of given name
	 *
	 * @return mixed The value
	 */
	public function __get($name) {
		if (!isset($this->values[$name])) {
			if ($this->db_struct->has_field($name)) {
				return $this->db_struct->get_default_value($name);
			}
			else {
				trigger_error("Undefined Objectvalue ".$name);
				return null;
			}
		}
		return $this->values[$name];
	}

	/**
	 * Get the value from given key
	 *
	 * @param string $key
	 *   The key
	 *
	 * @return mixed the value or if not exists returns null
	 */
	public function &get_value($key) {
		if(!isset($this->values[$key])) {
			$null = null;
			return $null;
		}
		return $this->values[$key];
	}

	/**
	 * Get all values
	 *
	 * @param boolean $force_all_fields
	 *   Whether we want to force all fields (including hidden ones) (optional, default = false)
	 * @param array $fields
	 *   If provided we do only get back the provided fields
	 *   fields are the values within this array (optional, default = array())
	 *
	 * @return array The values
	 */
	public function &get_values($force_all_fields = false, $fields = array()) {

		//Setup the return data
		$return_values = array();

		//Check if we provided specific fields to be returned
		if (empty($fields)) {
			//Check if the we force all fields or if the struct has no hidden fields, if so we can just return the hole current values
			if ($force_all_fields || !$this->db_struct->has_hidden_fields()) {
				return $this->values;
			}

			//We must check on valid return values, loop through all current values
			foreach ($this->values AS $k => $v) {
				//Only add the value to return array if the field is not a hidden field
				if (!$this->db_struct->is_hidden_field($k)) {
					$return_values[$k] = $v;
				}
			}
		}
		else {
			//We provided specific fields, so loop through the wanted fields
			foreach ($fields AS $k) {
				//Only add the value to the returning one if the field exist within current values, and the field is not hidden or we want to get also hidden fields (force all fields)
				if (isset($this->values[$k]) && ($force_all_fields || !$this->db_struct->is_hidden_field($k))) {
					$return_values[$k] = $this->values[$k];
				}
			}
		}
		//Return the values
		return $return_values;
	}

	/**
	 * Checks if value is set
	 *
	 * @return boolean true if isset, else false
	 */
	public function __isset($name) {
		return isset($this->values[$name]);
	}

	/**
	 * Whether or not the field exist within the struct or not
	 *
	 * @param string $name
	 *   The name of the field
	 *
	 * @return boolean true if the field exist, else false
	 */
	public function has_field($name) {
		return $this->db_struct->has_field($name);
	}

	/**
	 * Whether or not the field has changed since this object was loaded
	 *
	 * @param string $name
	 *   The name of the field
	 *
	 * @return boolean true if changed, else false
	 */
	public function has_changed($name) {
		return isset($this->values_changed[$name]);
	}

	/**
	 * Get the last inserted id
	 *
	 * @return int the last inserted id
	 */
	public function get_last_inserted_id() {
		return $this->last_inserted_id;
	}

	/**
	 * clear all stored data
	 */
	public function clear() {
		$this->values = array();
		$this->values_changed = array();
	}


	/**
	 * Set all values to there given default values
	 */
	public function set_default_fields() {
		//Loop through all struct fields
		foreach ($this->db_struct->get_struct() as $name => $struct) {
			//If struct defines a default value, set it
			if (isset($struct['default'])) {
				$this->values[$name] = $struct['default'];
				continue;
			}
			//Get the parsed value for an empty value for the current field type
			$this->values[$name] = $this->parse_value('', $struct['typ']);
		}
	}

	/**
	 * Set values (bulk).
	 *
	 * Use this only for fast setting (loading) all values.
	 * This method will not parse the provided values, but will only allow field names which are set within the struct.
	 *
	 * This will also force the load_success to true.
	 *
	 * @param array $field_array
	 *   The data array.
	 */
	public function set_fields_bulk(Array $field_array) {
		$this->values = array_intersect_key($field_array, $this->db_struct->struct);
		$this->values_changed = array();
		$this->load_success = true;
	}

	/**
	 * load the given data
	 *
	 * @param mixed $val
	 *   The reference key value to be selected
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function load($val = "", $force_db = false) {

		// Check if we provided a reference key, if so we will transform it to an array if it isn't already one.
		// After that we check each entry if it is empty, if so we do not proceed further.
		// But we want this only if we have configurated no db filter, because then we need to pass thru.
		if (!empty($val) && !$this->db_filter->has_where()) {
			if (!is_array($val)) {
				$val = array($val);
			}

			foreach ($val as $check) {
				if (empty($check)) {
					return;
				}
			}
		}
		//Object not loaded
		$this->load_success = false;

		//Clean all previous values
		$this->values = array();

		//initialize the values_changed array, because after a load, we have no changed values
		$this->values_changed = array();

		//We can stop right here if we have no val and the object database filter has no where statements
		if (empty($val) && !$this->db_filter->has_where()) {
			return false;
		}
		//Check if we should try the object from cache
		if (!$force_db && !empty($val) && $this->db_struct->is_cacheable()) {

			//Get the memcached key
			$memcached_key = $this->get_memcached_key($val);

			//Load from cache
			$values = $this->core->memcache_obj->get($memcached_key);

			//Check if cache loaded successfully
			if (!empty($values) && $this->core->memcache_obj->get_result_code() == CacheProvider::RES_SUCCESS) {

				// Check if the field count from cache is the same as the structure, if not we need to load it fresh from database, because the object could have changed
				if (count($values) == $this->db_struct->get_struct_count()) {

					//Direct set the fields because all values within memcached should be escaped and parsed as needed
					$this->set_fields_bulk($values);

					//Set that we have loaded the object successfully
					$this->load_success = true;

					//copy the current values to the old values, this is needed if we want to revert it.
					$this->old_values = $this->values;
					return true;
				}
			}
		}

		//We can only setup our db_filter new if we have provided primary key values and did not setup previous the db_filter object
		if (!$this->db_filter->has_where() && !empty($val)) {

			//Transform a single value into an array
			if (!is_array($val)) {
				$val = array($val);
			}

			$this->db_filter->clear();
			$ref_key = $this->db_struct->get_reference_key();
			foreach ($ref_key as $index => $key) {
				if(!isset($val[$index])) {
					$this->fill_values_from_ref_keys($val);
					return false;
				}
				$this->db_filter->add_where($key, $val[$index]);
			}
		}
		$this->db_filter->set_table($this->db_struct->get_table());

		//We must load the data from database
		$db_values = $this->db_filter->select_first();

		//Clear the database filter
		$this->db_filter->clear();

		//Check if the database had such an entry
		if (!empty($db_values)) {

			//Set our loaded values
			$this->set_fields($db_values, true);

			//Reset the values changed field because we have just loaded the data from the database
			$this->values_changed = array();

			//Save the cache if struct is cacheable
			$this->save_cache();

			//Set that the object was loaded successfully
			$this->load_success = true;

			//copy the current values to the old values, this is needed if we want to revert it.
			$this->old_values = $this->values;

			return true;
		}

		//Could not load from cache or from database, return false
		$this->fill_values_from_ref_keys($val);
		return false;
	}

	/**
	 * Load multiple records into an array
	 *
	 * @param mixed $keys
	 *   Array with primary keys to be loaded or a database filter which is used for the query
	 * @param int $return_as
	 *   determines the return value type  use PDT_OBJ or PDT_ARR (optional, default = PDT_OBJ)
	 * @param string $array_key
	 *   If provided it MUST be an existing table field and it needs to be a unique value.
	 *   This value will be used for the row keys within. (optional, default = NS)
	 *
	 * @return array with objects/array as values
	 */
	public function &load_multiple($keys, $return_as = PDT_OBJ, $array_key = NS) {

		//initialize the arrayindex counter for return values
		$return_index_counter = 0;

		if ($keys instanceof DatabaseFilter) {
			$this->db_filter = $keys;
		}
		else {
			$return = array();
			//If we did not provide any keys, we can do nothing.
			if(empty($keys)) {
				return $return;
			}

			//Get the database structure
			$struct = $this->db_struct->get_struct();
			$count_struct = count($struct);


			$memcached_keys = array();
			//Setup all wanted memcached keys to load
			foreach ($keys as $value) {
				$memcached_key = $this->get_memcached_key($value);
				$memcached_keys[$memcached_key] = $memcached_key;
			}

			/**
			 * If the structure is cacheable, try to get the cached data,
			 * we also check if some wanted data is missed, if so we try to load it from the database
			 */
			if ($this->db_struct->is_cacheable()) {
				$memcached_return = $this->core->memcache_obj->get_multi($memcached_keys);

				if ($memcached_return) {
					//Add the found data to our return array
					foreach ($memcached_return as $key => $values) {
						// cached 'item not available' or outdated
						if (is_null($values) || count($values) != $count_struct) {
							continue;
						}

						//Setup the return data
						switch($return_as) {
							case PDT_ARR:
								$key = ($array_key === NS || !isset($values[$array_key])) ? $return_index_counter++ : $values[$array_key];
								$return[$key] = $values;
								break;
							case PDT_OBJ:
								if ($array_key !== NS && isset($values[$array_key])) {
									$return[$values[$array_key]] = new $this();
									$return[$values[$array_key]]->set_fields_bulk($values);
								}
								else {
									$return[$return_index_counter] = new $this();
									$return[$return_index_counter++]->set_fields_bulk($values);
								}
								break;
						}
						//Remove current key from memcached_keys so if we finished the loop the values which are left within the array we have missed and must be loaded from database
						unset($memcached_keys[$key]);
					}
				}
				//Clean memory
				unset($memcached_return);
			}

			//Everything was cached, we're done :-)
			if (empty($memcached_keys)) {
				return $return;
			}

			//Setup the database filter for the missed element
			$this->db_filter->clear();

			//Main database where group
			$main_where_group = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);

			//Loop all missed values from cache and build up a database filter to catch all missed one together with one sql query
			foreach($memcached_keys AS $key) {

				//Get the memcached key as an array
				$value_array = explode(":", $key);

				//Remove the table from the memcached key
				array_shift($value_array);

				$where_group = new DatabaseWhereGroup();

				//Loop through all reference keys and setup the database filter
				foreach($this->db_struct->get_reference_key() AS $index => $key_ref) {
					$where_group->add_where($key_ref, $value_array[$index]);
				}

				$main_where_group->add_where($where_group);
			}

			$this->db_filter->set_table($this->db_struct->get_table());
			$this->db_filter->set_where($main_where_group);
		}
		$return = array();
		//Get all database values
		foreach($this->db_filter->select_all() AS $values) {
			//Setup the return data
			switch($return_as) {
				case PDT_ARR:
					$key = ($array_key === NS || !isset($values[$array_key])) ? $return_index_counter++ : $values[$array_key];
					$return[$key] = $values;
					break;
				case PDT_OBJ:
					if ($array_key !== NS && isset($values[$array_key])) {
						$return[$values[$array_key]] = new $this();
						$return[$values[$array_key]]->set_fields_bulk($values);
					}
					else {
						$return[$return_index_counter] = new $this();
						$return[$return_index_counter++]->set_fields_bulk($values);
					}
					break;
			}
			$memcached_key = $this->get_memcached_key($values);

			//If this structure is cacheable we need to cache the new loaded database values
			if ($this->db_struct->is_cacheable()) {
				$this->core->memcache_obj->set($memcached_key, $values, $this->db_struct->default_cache_time());
			}
			if (!empty($memcached_keys)) {
				unset($memcached_keys[$memcached_key]);
			}
		}
		return $return;
	}

	/**
	 * Returns whether the object was loaded successfully or not
	 *
	 * @return boolean true if loaded successfully, else fals
	 */
	public function load_success() {
		return $this->load_success;
	}

	/**
	 * Save the given Data
	 *
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made (optional, default = false)
	 *
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {

		//If we have no values changed and we do not want to force saving, return true
		if (empty($this->values_changed) && !$save_if_unchanged) {
			return true;
		}

		//Set the values which will be updated
		$update_values = $this->values_changed;
		//Check if the values is empty (this can only be true if $save_if_unchanged = true) and fill the current complete values
		if (empty($update_values)) {
			$update_values = $this->values;
		}

		/**
		 * Setup the database filter, we need to use not the current values couse the primary keys could be changed, we need to use
		 * the old values which we setup right after loading. also we need not to check if have loaded because saveing only occurs
		 * if we have loaded something.
		 */
		$filter = new DatabaseFilter();
		foreach ($this->db_struct->get_reference_key() as $key) {
			$search_v = (isset($this->old_values[$key])) ? $this->old_values[$key] : $this->values[$key];
			$filter->add_where($key, $search_v);
		}

		//Save the data
		if ($this->db->edit($this->db_struct->get_table(), $update_values, $this->db_struct->get_struct(), $filter)) {
			$this->save_cache();

			//If saving succeed we now need to setup the current saved values to the old one.
			$this->old_values = $this->values;
			$this->values_changed = array();
			return true;
		}

		return false;
	}

	/**
	 * Insert the current data
	 *
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {

		//If we have inserted the data successfully, we set our refence keys to the current one
		if ($this->db->insert($this->db_struct->get_table(), $this->values, $this->db_struct->get_struct(), $ignore)) {

			//Only set reference keys and last inserted id, if the object has an auto increment field and only set this field
			if($this->db_struct->has_auto_increment()) {
				//Set the last inserted id for this object
				$this->last_inserted_id = $this->db->insert_id();

				$ref_key = $this->db_struct->get_reference_key();
				if (count($ref_key) == 1) {
					$this->values[$ref_key[0]] = $this->last_inserted_id;
				}
				else {
					$this->values[$this->db_struct->get_auto_increment()] = $this->last_inserted_id;
				}
			}
			//Save cache
			if ($this->db_struct->is_cacheable()) {
				$this->save_cache();
			}
			$this->load_success = true;

			$this->old_values = $this->values;
			$this->values_changed = array();
			return true;
		}
		return false;
	}

	/**
	 * Save if data is already there, else insert current data
	 *
	 * @return boolean true on success, else false
	 */
	public function save_or_insert() {
		if ($this->load_success()) {
			return $this->save();
		}
		else {
			return $this->insert();
		}
	}


	/**
	 * Delete the given data
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		//Do nothing if we did not load anything
		if (!$this->load_success()) {
			return true;
		}

		//Build up our database filter
		$this->db_filter->clear();
		foreach ( $this->db_struct->get_reference_key() as $key) {
			$this->db_filter->add_where($key, $this->values[$key]);
		}

		//Delete the database entry and if struct is cacheable delete the cached object from memcached
		$res = $this->db->query_master("DELETE FROM `".$this->db_struct->get_table()."` ".$this->db_filter->get_where());
		$this->db_filter->clear();
		if ($res && $this->db_struct->is_cacheable()) {
			$this->core->memcache_obj->delete($this->get_memcached_key());
		}
		return $res;
	}

	/**
	 * Reverts the object to the state where it was loaded.
	 * 
	 * @param boolean $save
	 *   if set to true, the object will be saved after reverting, else just the
	 *   old values for the current object will be restored without saving to database/cache.
	 */
	public function revert($save = true) {
		$this->values = $this->old_values;
		
		if ($save === true) {
			$this->save(true);
		}
	}

	/**
	 * Save current data to cache
	 *
	 * @param int $expire
	 *   The time when cache item expired (optional, default=null)
	 *
	 * @return boolean true on success , else false
	 */
	public function save_cache($expire = null) {

		//Do nothing if struct is not cacheable
		if (!$this->db_struct->is_cacheable()) {
			return true;
		}
		//Get the expiration time
		if (is_null($expire)) {
			$expire = $this->db_struct->default_cache_time();
		}

		//Get the memcached key
		$memcached_key = $this->get_memcached_key();

		//Store the data to the memcache
		$this->core->memcache_obj->set($memcached_key, $this->values, $expire);

		/**
		 * If the operation succeeds, add the memcache key to the changed memcached keys, because this must be deleted
		 * if a transaction is active and a rollback is called.
		 */
		if ($this->core->memcache_obj->get_result_code() == CacheProvider::RES_SUCCESS) {
			$this->db->add_changed_memcached_key($memcached_key);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Delete the current item from the cache
	 *
	 * @return boolean true on success, else false
	 */
	public function delete_cache() {
		//Do nothing if struct is not cacheable
		if ($this->db_struct->is_cacheable()) {
			return true;
		}

		//Delete from cache and return the result
		return $this->core->memcache_obj->delete($this->get_memcached_key());
	}

	/**
	 * Get the next auto increment value for the given table
	 *
	 * @return mixed
	 *   if table does not exist within the status report, return 1,
	 *   if exist but auto increment field does not exist return false,
	 *   else return the auto increment value as an int
	 */
	public function get_next_id() {
		$row = $this->db->query_slave_first("SHOW TABLE STATUS WHERE `Name` = :table", array(":table" => $this->db_struct->get_table()));
		if (empty($row)) {
			return 1;
		}
		if (empty($row['Auto_increment'])) {
			return false;
		}
		return $row['Auto_increment'];
	}

	/**
	 * Returns if values changed
	 *
	 * @return boolean returns true if values changed, else false
	 */
	public function has_values_changed() {
		return !empty($this->values_changed);
	}

	/**
	 * Check whether there is a transaction active and begin one if not
	 */
	public function transaction_auto_begin() {
		$this->transaction_auto_begun = false;
		if (!$this->db->transaction_is_active()) {
			$this->db->transaction_begin();
			$this->transaction_auto_begun = true;
		}
	}

	/**
	 * If transaction_auto_begin() began a transaction, commit it
	 * If the transaction was not opened by us, don't do anything
	 */
	public function transaction_auto_commit() {
		if ($this->transaction_auto_begun) {
			$this->db->transaction_commit();
			$this->transaction_auto_begun = false;
		}
	}

	/**
	 * If transaction_auto_begin() began a transaction, rollback it
	 * If the transaction was not opened by us, don't do anything
	 */
	public function transaction_auto_rollback() {
		if ($this->transaction_auto_begun) {
			$this->db->transaction_rollback();
			$this->transaction_auto_begun = false;
		}
	}

	/**
	 * Set an element as "changed" to our struct
	 *
	 * @param string $key
	 *   the key (database field)
	 * @param mixed $value
	 *   the value
	 * @param boolean $ignore_values_changed
	 *   whether if we want to ignore that this value is changed (optional, default = false)
	 */
	protected function set_values_changed($key, &$value, $ignore_values_changed = false) {
		if (!$ignore_values_changed && (!isset($this->values[$key]) || $this->values[$key] != $value)) {
			$this->values_changed[$key] = $value;
			if($this->load_success) {
				$this->db->add_changed_memcached_key($this->get_memcached_key());
			}
		}
	}

	/**
	 * Get a memached key
	 *
	 * @param mixed $values
	 *   the values as an array or NS for current values (optional, default = NS)
	 * @param string $table
	 *   the table name or NS for current table name (optional, default = NS)
	 *
	 * @return string the memcached key
	 */
	protected function get_memcached_key($values = NS, $table = NS) {

		$ref_key = $this->db_struct->get_reference_key();

		if ($table === NS || $table === null) {
			$table = $this->db_struct->get_table();
		}

		if ($values === NS || $values === null) {
			$values = array();
			foreach ($this->values AS $field => &$v) {
				if ($this->db_struct->is_reference_key($field)) {
					$values[] = $v;
				}
			}
		}
		//If provided values are not an array, transform it to one
		else if (!is_array($values)) {
			$values = array($values);
		}

		$refkey_array = array();

		foreach($ref_key AS $index => $key) {
			if(isset($values[$key])) {
				$refkey_array[] = $values[$key];
			}
			else if(isset($values[$index])) {
				$refkey_array[] = $values[$index];
			}
		}

		if (count($ref_key) != count($refkey_array)) {
			trigger_error($table . ' has ' . count($ref_key) . ' refkeys - but you supplied ' . count($refkey_array), E_USER_ERROR);
		}
		//Prepend the table name to the array
		array_unshift($refkey_array, $table);

		//Return the key
		return implode(":", $refkey_array);
	}

	/**
	 * Parse a given value to a given type, this can also be used to determine the default value, if you provide '' within $value
	 *
	 * @param mixed $value
	 *   the value which will be parsed
	 * @param int $type
	 *   const use one of PDT_*
	 *
	 * @return mixed the parsed value
	 */
	protected function parse_value($value, $type) {
		switch ((int)$type) {
			case PDT_BLOB: return $value;
			case PDT_SERIALIZED:
				if(is_array($value) || is_object($value)) {
					return @json_encode($value);
				}
				else {
					return json_decode($value);
				}
			case PDT_ARR:
				if (!is_array($value)) {
					return array();
				}
				return $value;
			case PDT_LANGUAGE :
			case PDT_LANGUAGE_ENABLED : $value = strtolower($value);
			case PDT_ENUM :
			case PDT_TEXT :
			case PDT_PASSWORD :
			case PDT_STRING : return ''.$value;
			case PDT_TINYINT :
			case PDT_BIGINT :
			case PDT_MEDIUMINT:
			case PDT_SMALLINT :
			case PDT_FILE :
			case PDT_INT : return (int)$value;
			case PDT_DECIMAL :
			case PDT_FLOAT : return (float)str_replace(",", ".", $value);
			case PDT_DATE :
				if (empty($value)) {
					return "0000-00-00";
				}
				if ($value == "0000-00-00") {
					return $value;
				}
				if ((int)$value."" !== $value."") {
					$value = strtotime($value);
				}
				return date(DB_DATE, (int)$value);
			case PDT_DATETIME :
				if (empty($value)) {
					return "0000-00-00 00:00:00";
				}
				if ($value == "0000-00-00 00:00:00") {
					return $value;
				}
				if ((int)$value."" !== $value."") {
					$value = strtotime($value);
				}
				return date(DB_DATETIME, (int)$value);
			case PDT_TIME :
				if (empty($value)) {
					return "00:00:00";
				}
				if ($value == "00:00:00") {
					return $value;
				}
				if ((int)$value !== $value) {
					$value = strtotime($value);
				}
				return date(DB_TIME, $value);
			default : return $value;
		}
	}

	/**
	 * Fill up our reference key values and values_changed with the provided values.
	 *
	 * @param mixed $value_array
	 *   The values for the reference keys, if a single value is provided it will be transformed into an array.
	 */
	private function fill_values_from_ref_keys($value_array) {
		//Transform a single value into an array
		if (!is_array($value_array)) {
			$value_array = array($value_array);
		}

		$ref_keys = $this->db_struct->get_reference_key();
		if (count($ref_keys) === count($value_array)) {
			foreach ($ref_keys AS $index => $key) {
				$this->values[$key] = $this->values_changed[$key] = $value_array[$index];
			}
		}
	}
}