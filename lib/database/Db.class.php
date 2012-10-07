<?php

/**
 * Provides Database connection to an MySQL-Database
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.database
 * @category Database
 */
class Db
{
	/**
	 * The mysql table object
	 *
	 * @var MysqlTable
	 */
	public $mysql_table = null;

	/**
	 * the link id
	 *
	 * @var int
	 */
	protected $link_id = 0;

	/**
	 * the databases
	 *
	 * @var array
	 */
	protected $dbserver = array();

	/**
	 * the query id
	 *
	 * @var int
	 */
	protected $query_id = 0;

	/**
	 * record
	 *
	 * @var array
	 */
	protected $record = array();

	/**
	 * error description
	 *
	 * @var string
	 */
	protected $errdesc = '';

	/**
	 * error no
	 *
	 * @var int
	 */
	protected $errno = 0;

	/**
	 * version
	 *
	 * @var string
	 */
	protected $version = '';

	/**
	 * show error
	 *
	 * @var boolean
	 */
	protected $show_error = 1;

	/**
	 * debug mode
	 *
	 * @var boolean
	 */
	protected $_debug = false;

	/**
	 * the database server
	 *
	 * @var string
	 */
	protected $server = '';

	/**
	 * Is there an active (open) transaction?
	 *
	 * @var boolean
	 */
	protected $transaction_active = false;

	/**
	 * Memcached keys that were saved during the current transaction
	 * These keys will be deleted when rolling back a transaction
	 *
	 * @var array
	 */
	public $changed_memcached_keys = array();

	/**
	 *
	 * @var array
	 */
	private $altertable = array();

	/**
	 * The tablename prefix.
	 *
	 * @var string
	 */
	private $table_prefix = "";

	/**
	 * Constructor from class DB,
	 * configurating mysql server,
	 * and connecting to database
	 *
	 * @param string $server
	 *   Mysql Server
	 * @param string $user
	 *   Mysql Username
	 * @param string $password
	 *   Mysql Password
	 * @param string $database
	 *   Mysql Database
	 * @param int $debug
	 *   Debug mod (optional, default = false)
	 * @param string $servername
	 *   Servername (optional, default = 'default')
	 */
	public function __construct($server, $user, $password, $database, $debug = false, $servername = "default") {
		$this->add_server($servername, $server, $user, $password, $database);
		$this->set_server();

		$this->_debug = $debug;
	}

	/**
	 * Set or Get the table prefix.
	 *
	 * @param string $table_prefix
	 *   the table prefix, if not provided we return the current one. (optional, default = NS)
	 *
	 * @return mixed returns the table_prefix as a string if provided $table_prefix is not provided.
	 *   If $table_prefix is provided it will set the new value and returns nothing.
	 */
	public function table_prefix($table_prefix = NS) {
		if ($table_prefix === NS) {
			return $this->table_prefix;
		}

		$this->table_prefix = $table_prefix;
	}

	/**
	 * Destructor: Rollback open transactions
	 */
	public function __destruct() {
		if ($this->transaction_is_active()) {
			$this->transaction_rollback();
		}
	}

	/**
	 * Adds a DB server and connect directly to this server
	 *
	 * @param string $servername
	 *   Servername
	 * @param string $server
	 *   Mysql Server
	 * @param string $username
	 *   Mysql Username
	 * @param string $password
	 *   Mysql Password
	 * @param string $database
	 *   Mysql Database
	 */
	public function add_server($servername, $server, $username, $password, $database) {
		$this->dbserver[$servername] = @mysql_connect($server, $username, $password);
		$this->link_id = $this->dbserver[$servername];
		@mysql_select_db($database, $this->dbserver[$servername]);


		$this->query("SET NAMES 'utf8'");
		$this->query("SET time_zone = '+0:00';");
	}

	/**
	 * Sets the server.
	 *
	 * @param string $var
	 *   the unique servername (optional, default = 'default')
	 */
	public function set_server($var = "default") {
		if ($this->transaction_is_active()) {
			$this->transaction_rollback();
		}
		$this->link_id = $this->get_server($var);
	}

	/**
	 * Get the server.
	 *
	 * @param string $var
	 *   the unique servername
	 *
	 * @return resource the mysql resource
	 */
	public function get_server($var) {
		return $this->dbserver[$var];
	}

	/**
	 * Sets the default server
	 *
	 * @param string $var
	 *   the unique servername
	 */
	public function set_default_server($var) {
		$this->dbserver["default"] = $this->dbserver[$var];
		$this->link_id = $this->dbserver["default"];
	}

	/**
	 * Initialize the mysql table object.
	 */
	public function init_mysql_table() {
		$this->mysql_table = new MysqlTable();
	}

	/**
	 * setter for _debug
	 *
	 * @param boolean $var
	 *   True or false, debug or not
	 */
	public function set_debug($var) {
		$this->_debug = $var;
	}

	/**
	 * Get Link-ID
	 *
	 * @return resource mysql link id
	 */
	public function get_link_id() {
		return $this->link_id;
	}

	/**
	 * Escape substrings, exploded by *<br>
	 * and replace all * with %
	 * You can provide a default search with $default
	 * for example if someone searches for "hello*" it will find * at the end and will use this.
	 * if someone searches for "hell" no * provided so the default value will be used.
	 * The provided value will replaced within . (dot)
	 * Let say we have default = "%.%" and someone searches the above example "hell".
	 * . (dot) will be replaced with escaped "hell" and there for the search will be %hell%
	 *
	 * @param string $val
	 *   The string
	 * @param string $default
	 *   the default search pattern if $val has no * (optional, default = '')
	 * @param boolean $escape
	 *   if set to false the value will not be escaped.
	 *   BE CAREFULL WITH THIS. USE THIS ONLY IF YOU USE DatabaseFilter OR YOU HAVE SELF ESCAPED
	 *   THE VALUE. (optional, default = true)
	 *
	 * @return string The escaped and replaced string
	 */
	public function get_sql_string_search($val, $default = "", $escape = true) {
		$return_arr = array();
		foreach (explode("*", $val) AS $sub_string) {
			if ($escape) {
				$sub_string = Db::sql_escape($sub_string);
			}
			else {
				$sub_string = str_replace("%", "\\%", $sub_string);
			}
			$return_arr[] = $sub_string;
		}
		if (count($return_arr) <= 1) {
			if ($escape) {
				$val = Db::sql_escape($val);
			}
			else {
				$val = str_replace("%", "\\%", $val);
			}
			return str_replace(".", $val, str_replace('*', '%', $default));
		}
		return implode("%", $return_arr);
	}

	/**
	 * Sending a Mysql query to the MASTER server.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 * @param int $limit
	 *   The limit (optional, default = 0)
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 *
	 * @return resource Mysql queryid
	 */
	public function query_master($query_string, $args = array(), $limit = 0, $offset = 0) {
		return $this->query($query_string, $args, $limit, $offset);
	}

	/**
	 * Sending a Mysql query and get the results
	 * as an string back, display first result and first field.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 *
	 * @return array The Results
	 */
	public function query_field($query_string, $args = array()) {
		$this->query($query_string, $args, 1);
		$return_array = $this->fetch_array($this->query_id, MYSQL_NUM);
		if (empty($return_array)) {
			return null;
		}
		return current($return_array);
	}

	/**
	 * Sending a Mysql query to a SLAVE server.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 * @param int $limit
	 *   The limit (optional, default = 0)
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 *
	 * @return resource Mysql queryid
	 */
	public function query_slave($query_string, $args = array(), $limit = 0, $offset = 0) {
		if (preg_match('/^(INSERT|UPDATE)/iUs', $query_string)) {
			trigger_error('Insert or Update on slave server!', E_USER_WARNING);
		}
		return $this->query($query_string, $args, $limit, $offset);
	}

	/**
	 * Sending a Mysql query to a SLAVE and get the num_rows count.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 * @param int $limit
	 *   The limit (optional, default = 0)
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 *
	 * @return int The result count
	 */
	public function query_slave_count($query_string, $args = array(), $limit = 0, $offset = 0) {
		$this->query_slave($query_string, $args, $limit, $offset);
		return $this->num_rows();
	}

	/**
	 * Sending a Mysql query to a SLAVE and get the results
	 * as an array back, but only the first row.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 * @param int $type
	 *   Type (optional ,default = MYSQL_ASSOC)
	 *
	 * @return mixed the first result row
	 */
	public function query_slave_first($query_string, $args = array(), $offset = 0, $type = MYSQL_ASSOC) {
		return $this->fetch_array($this->query_slave($query_string, $args, 1, $offset), $type);
	}

	/**
	 * Sending a Mysql query to a SLAVE and return if a row exists for this query.
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 *
	 * @return boolean returns true if the query is not empty, else false
	 */
	public function query_slave_exists($query_string, $args = array()) {
		$data = $this->fetch_array($this->query_slave($query_string, $args, 1));
		return !empty($data);
	}

	/**
	 * Sends a mysql-query and get ALL the results from a SLAVE server as an array back
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (Optional, default = array())
	 * @param int $limit
	 *   The limit (optional, default = 0)
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 * @param int $array_key
	 *   returned array key as a table field (optional, default=0)
	 * @param int $type
	 *   The result type (use one of MYSQL_*)
	 * 	 (optional, default = MYSQL_ASSOC)
	 * @return array The Results
	 */
	public function query_slave_all($query_string, $args = array(), $limit = 0, $offset = 0, $array_key = 0, $type = MYSQL_ASSOC) {
		$return = array();

		$this->query($query_string, $args, $limit, $offset);
		while ($row = $this->fetch_array($this->query_id, $type)) {

			if (!empty($array_key) && isset($row[$array_key])) {
				$return[$row[$array_key]] = $row;
				continue;
			}
			$return[] = $row;
		}
		return $return;
	}

	/**
	 * Sending a Mysql query
	 *
	 * @param string $query_string
	 *   Querystring
	 * @param array $args
	 *   an array which replace inline strings array('search_key' => 'replace_val') (optional, default = array())
	 * @param int $limit
	 *   The limit (optional, default = 0)
	 * @param int $offset
	 *   The offset (optional, default = 0)
	 *
	 * @return resource Mysql queryid
	 */
	protected function query($query_string, $args = array(), $limit = 0, $offset = 0) {

		$this->final_transform_query($query_string);

		// Sort the arg array descending by strlen.
		if (!empty($args)) {
			uksort($args, function($a, $b) {
				$str_len_a = strlen($a)-1;
				if (!isset($b{$str_len_a})) {
					return 1;
				}

				if (!isset($b{$str_len_a+1})) {
					return -1;
				}

				return 0;
			});
		}

		//Escape all arguments with the prefix
		foreach ($args AS $key => $value) {
			switch (substr($key, 0, 1)) {
				case 'i':
					$value = (int) $value;
					break;
				case 'f':
					$value = (float) $value;
					break;
				case '@':
					$value = "'" . Db::safe($value) . "'";
					break;
				default:
					$value = Db::safe($value);
					break;
			}

			$query_string = str_replace($key, $value, $query_string);
		}
		$limit = (int) $limit;
		if ($limit > 0) {
			$query_string .= " LIMIT " . $limit;
		}

		$offset = (int) $offset;
		if ($offset > 0) {
			$query_string .= " OFFSET " . $offset;
		}

		if ($this->_debug) {
			if (!defined('is_shell')) {
				echo "<div style=\"width:100%;background-color:white;color:black;\"><div style=\"width:100%;background-color:white;color:blue;\">" . $query_string . "</div>\n";
			}
			else {
				CliHelper::console_log($query_string);
			}
			if (preg_match("/^\s*SELECT\s/iUs", $query_string)) {
				$sql = @mysql_query("EXPLAIN " . $query_string, $this->link_id);
				$res = @mysql_fetch_assoc($sql);
				if (!empty($res)) {
					foreach ($res AS $key => $val) {
						if (is_numeric($key)) {
							unset($res[$key]);
						}
					}
				}
				if (!defined('is_shell')) {
					echo "<div>";
					print_r($res);
					echo "</div>";
				}
				else {
					CliHelper::console_log($res);
				}
			}
		}
		$this->query_id = mysql_query($query_string, $this->link_id);
		return $this->query_id;
	}

	/**
	 * Get the Mysql result from the Queryid
	 * If Queryid was not set, it will get the
	 * results of the last query
	 *
	 * @param string $query_id
	 *   Queryid (optional ,default=-1)
	 * @param int $type
	 *   type (optional, default = MYSQL_ASSOC)
	 *
	 * @return array Mysql result as an array only with numeric index
	 */
	public function &fetch_array($query_id = -1, $type = MYSQL_ASSOC) {
		if ($query_id != -1) {
			$this->query_id = $query_id;
		}
		if (empty($this->query_id)) {
			$return = array();
			return $return;
		}
		$this->record = mysql_fetch_array($this->query_id, $type);
		return $this->record;
	}

	/**
	 * Return the count of rows from
	 * the last query
	 * If the Queryid is not set, it will
	 * get the results from the last query
	 *
	 * @param int $query_id
	 *   Queryid (optional) Default=-1
	 *
	 * @return int the count
	 */
	public function num_rows($query_id = -1) {
		if ($query_id != -1) {
			$this->query_id = $query_id;
		}
		return @mysql_num_rows($this->query_id);
	}

	/**
	 * Return the last inserted id from autoincrement
	 *
	 * @return int the inserted id
	 */
	public function insert_id() {
		return mysql_insert_id($this->link_id);
	}

	/**
	 * Sets AutoCommit
	 *
	 * @param boolean $mode
	 *   AutoCommit
	 */
	public function set_autocommit($mode) {
		if ($mode != TRUE) {
			$mode = "0";
		}

		mysql_query("SET AUTOCOMMIT=" . $mode, $this->link_id);
	}

	/**
	 * Add the provided value/s to the changed memcache keys container if a transaction is active
	 *
	 * @param mixed $values
	 *   a single value or an array with values
	 */
	public function add_changed_memcached_key($values) {
		if (!$this->transaction_is_active()) {
			return;
		}
		if (!is_array($values)) {
			$values = array($values);
		}

		foreach ($values AS $value) {
			$this->changed_memcached_keys[$value] = $value;
		}
	}

	/**
	 * Returns whether we currently have an open transaction
	 *
	 * @return boolean true if there is a tranaction active, false otherwise
	 */
	public function transaction_is_active() {
		return $this->transaction_active;
	}

	/**
	 * Begin a transaction
	 *
	 * @return resource the mysql resource or false on error
	 */
	public function transaction_begin() {
		if ($qry_result = $this->query_master('BEGIN')) {
			$this->transaction_active = true;
			$this->changed_memcached_keys = array();
		}
		return $qry_result;
	}

	/**
	 * Commit a transaction
	 *
	 * @return resource the mysql resource or false on error
	 */
	public function transaction_commit() {
		if ($qry_result = $this->query_master('COMMIT')) {
			$this->transaction_active = false;
			$this->changed_memcached_keys = array();
		}
		return $qry_result;
	}

	/**
	 * Rollback a transaction
	 *
	 * @return resource the mysql resource or false on error
	 */
	public function transaction_rollback() {
		$this->transaction_active = false;
		//Check if the previous operations changed/added some memcached keys
		if ($this->changed_memcached_keys) {

			//We can only do something if we have the core object
			if (isset($GLOBALS['core'])) {
				//Get the core object
				$core = &$GLOBALS['core'];

				//Loop through all changed memcache keys and delete it
				foreach ($this->changed_memcached_keys as $memcached_key) {
					$core->memcache_obj->delete($memcached_key);
				}
			}
			$this->changed_memcached_keys = array();
		}

		//Rollback the mysql queries
		return $this->query_master('ROLLBACK');
	}

	/**
	 * Inizialize the alter table queue if we have it not and return the alter
	 * table SQL-String, if already initialized it will return an empty string
	 *
	 * @param string $table
	 *   The table
	 *
	 * @return string The sql
	 */
	private function init_alter_table_queue($table) {
		$query = "";
		//Check if table was initialized
		if (!isset($this->altertable[$table])) {
			//It was not so we setup the table and return the alter table SQL-String
			//So the first query for this table queue will get the ALTER TABLE prefix.
			$this->altertable[$table] = array();
			$query = "ALTER TABLE `" . $table . "` ";
		}
		return $query;
	}

	/**
	 * Removes a field from a table.
	 *
	 * @param string $table
	 *   The table
	 * @param string $field
	 *   The field
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function remove_table_field($table, $field, $queue = false) {
		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= "DROP COLUMN `" . $field . "`";
			$this->altertable[$table][] = $query;
			return;
		}

		$this->query("ALTER TABLE `" . $table . "` DROP COLUMN `" . $field . "`");
	}

	/**
	 * Adds a new field to the given table.
	 *
	 * @param string $table
	 *   the table
	 * @param string $field
	 *   the field
	 * @param string $after
	 *   The new field will be added after this, if set to empty it will be created in at first
	 * @param array $data
	 *   the data array which contains typ, default, additional keys
	 * @param boolean $autoincrement
	 *   should this field set autoincrement? (optional, default = false)
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function add_table_field($table, $field, $after, $data, $autoincrement = false, $queue = false) {
		$line = $this->mysql_table->create_database_get_line($field, $data, $autoincrement);
		if (empty($after)) {
			$after = " FIRST";
		}
		else {
			$after = " AFTER `" . $after . "`";
		}
		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= "ADD " . $line . $after;
			$this->altertable[$table][] = $query;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` ADD " . $line . $after);
	}

	/**
	 * Remove an table index.
	 *
	 * @param string $table
	 *   the table
	 * @param string $index_type
	 *   the index type
	 * @param array $fields
	 *   all fields for this index.
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function add_index($table, $index_type, $fields, $queue = false) {
		foreach ($fields AS &$field) {
			$field = "`" . Db::safe($field) . "`";
		}
		$fields = implode(", ", $fields);

		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= " ADD ". $index_type . " (" . $fields . ")";
			$this->altertable[$table][] = $query;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` ADD ". $index_type . " (" . $fields . ")");
	}

	/**
	 * Remove an table index.
	 *
	 * @param string $table
	 *   the table
	 * @param string $index_name
	 *   the index name
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function remove_index($table, $index_name, $queue = false) {
		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= " DROP INDEX " . Db::safe($index_name);
			$this->altertable[$table][] = $query;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` DROP INDEX " . Db::safe($index_name));
	}

	/**
	 * Change a field from a given table.
	 *
	 * To rename a field you need to provide $data the array key 'new_field' with the new field name
	 * and within $field parameter the old value.
	 *
	 * @param string $table
	 *   the table
	 * @param string $field
	 *   the field
	 * @param array $data
	 *   the data array which contains typ, default, additional keys
	 *   if 'new_field' exists within the data array it will try to rename the old field $field into $data['new_field']
	 * @param boolean $autoincrement
	 *   should this field set autoincrement? (optional, default = false)
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function change_table_field($table, $field, $data, $autoincrement = false, $queue = false) {
		$line = $this->mysql_table->create_database_get_line($field, $data, $autoincrement, true);
		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= "CHANGE " . $line;
			$this->altertable[$table][] = $query;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` CHANGE " . $line);
	}

	/**
	 * Set the primary key.
	 *
	 * @param string $table
	 *   the table
	 * @param string $field
	 *   the field
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function set_primary_key($table, $field, $queue = false) {
		if (!is_array($field)) {
			$field = array($field);
		}
		foreach ($field AS &$val) {
			$val = "`" . Db::safe($val) . "`";
		}
		if ($queue === true) {
			$query = $this->init_alter_table_queue($table);
			$query .= "DROP PRIMARY KEY, ADD PRIMARY KEY (" . implode(", ", $field) . ")";
			$this->altertable[$table][] = $query;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` DROP PRIMARY KEY, ADD PRIMARY KEY (" . implode(", ", $field) . ")");
	}

	/**
	 * Move a table field after the given $after field.
	 *
	 * @param string $table
	 *   the table
	 * @param string $field
	 *   the field
	 * @param array $data
	 *   the data array which contains typ, default, additional keys
	 * @param boolean $autoincrement
	 *   should this field set autoincrement?
	 * @param string $after
	 *   The new field will be added after this, if set to empty it will be created in at first
	 * @param boolean $queue
	 *   if the sql query will be queued or not (optional, default = false)
	 */
	public function move_table_field($table, $field, $data, $autoincrement, $after, $queue = false) {
		$field_data = $this->mysql_table->create_database_get_line($field, $data, $autoincrement, true);
		$alter_data = "CHANGE " . $field_data . " AFTER `" . $after . "`";
		if ($queue === true) {
			$this->altertable[$table][] = $this->init_alter_table_queue($table) . $alter_data;
			return;
		}
		$this->query("ALTER TABLE `" . $table . "` " . $alter_data);
	}

	/**
	 * Execute all queries which was queued for the given $table.
	 *
	 * @param string $table
	 *   The table queue to run
	 */
	public function alter_table_queue($table) {
		//Return if table was not queued
		if (!isset($this->altertable[$table])) {
			return;
		}
		//Run all commands
		$this->query(implode(",\n ", $this->altertable[$table]));

		//Clear the queue
		unset($this->altertable[$table]);
	}

	/**
	 * Returns an array with all fields for given table.
	 *
	 * @param string $table
	 *   the table
	 *
	 * @return array with all fields array('fieldname' => 'fieldname')
	 */
	public function get_table_fields($table) {
		$fields = array();
		foreach ($this->query_slave_all("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . Db::sql_escape($table) . "' ORDER BY ORDINAL_POSITION") AS $row) {
			$fields[$row['COLUMN_NAME']] = $row;
		}
		return $fields;
	}

	/**
	 * Get the Primarykey of a table.
	 *
	 * @param string $table
	 *   the table
	 * @param boolean $return_as_array
	 *   Wether we want to return the primary key as an array or as a comma seperated string (optional, default = false)
	 *
	 * @return mixed the primary key as an array or a string comma seperated
	 */
	public function get_primary_key($table, $return_as_array = false) {
		$sql = "SHOW COLUMNS FROM `" . Db::safe($this->table_prefix) . $table . "`";
		$this->query_id = mysql_query($sql, $this->link_id);
		$primarykeys = array();
		if (mysql_num_rows($this->query_id) > 0) {
			while ($row = mysql_fetch_assoc($this->query_id)) {
				if ($row['Key'] == "PRI") {
					$primarykeys[] = $row['Field'];
				}
			}
		}
		if ($return_as_array == true) {
			return $primarykeys;
		}
		return implode(",", $primarykeys);
	}

	/**
	 * Get the indexe of a table.
	 *
	 * @param string $table
	 *   the table
	 * @param string $type
	 *   the index type
	 *   if provided only the specific type of index will be returned, else all available (without primary)
	 *   (optional, default = NS)
	 *
	 * @return mixed the primary key as an array or a string comma seperated
	 */
	public function get_table_indexes($table, $type = NS) {
		$sql = "SELECT `INDEX_NAME`, `COLUMN_NAME`, `NON_UNIQUE` FROM `information_schema`.`statistics` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = '" . Db::safe($table) . "' AND `INDEX_NAME` != 'PRIMARY'";
		if ($type !== NS) {
			$sql .= " AND `INDEX_NAME` = '" . Db::safe($type) . "'";
		}
		$sql .= '  ORDER BY `SEQ_IN_INDEX`';

		$this->query_id = mysql_query($sql, $this->link_id);
		$indexe = array();
		if (mysql_num_rows($this->query_id) > 0) {
			while ($row = mysql_fetch_assoc($this->query_id)) {

				$type = ($row['NON_UNIQUE'] == '1') ? 'INDEX' : 'UNIQUE';
				if (!isset($indexe[$type][$row['INDEX_NAME']])) {
					$indexe[$type][$row['INDEX_NAME']] = array();
				}
				$indexe[$type][$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
			}
		}

		return $indexe;
	}

	/**
	 * Insert data to the database
	 *
	 * @param string $table
	 *   the database table
	 * @param array $values
	 *   The Values
	 * @param array $db_struct
	 *   The Database Struct
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional,default = false)
	 *
	 * @return true on success, else false
	 */
	public function insert($table, Array $values, Array $db_struct, $ignore = false) {

		if (count($db_struct) <= 0) {
			return false;
		}

		$sqlfields = $insertvalues = array();

		foreach ($db_struct AS $key => $structval) {
			if (isset($values[$key])) {
				$val = $values[$key];
			}
			else {
				$val = null;
			}

			$sqlfields[] = "`" . $key . "`";

			if ((is_null($val) || $val === NS) && !empty($structval['default'])) {
				$val = $structval['default'];
			}
			$insertvalues[] = $this->parse_value_type($val, $structval['typ']);
		}

		$sql = "INSERT " . ($ignore ? 'IGNORE ' : '') . " INTO `" . $table . "` (" . implode(" , ", $sqlfields) . ") VALUES (" . implode(" , ", $insertvalues) . ")";
		if ($this->query_master($sql)) {
			return true;
		}
		return false;
	}

	/**
	 * Edit data
	 *
	 * @param string $table
	 *   the database table
	 * @param array $values
	 *   The values
	 * @param array $db_struct
	 *   The database struct (not the object)
	 * @param DatabaseFilter $filter
	 *   an object of DatabaseFilter
	 *
	 * @return boolean true on success, else false
	 */
	public function edit($table, Array $values, Array $db_struct, DatabaseFilter &$filter) {
		//return true if we have not values to edit
		if (count($values) <= 0) {
			return true;
		}


		$update_arr = array();

		//loop through all change values
		foreach ($values AS $key => $val) {
			//Continue if the key does not exist for the given database structure
			if (!isset($db_struct[$key])) {
				continue;
			}

			//If the value is null or NS but the database struct has a default value, set this to the value
			//NOTICE: we can not check with empty function because 0 which could be a valid value returns true on empty check
			if ((is_null($val) || $val === NS) && !empty($db_struct[$key]['default'])) {
				$val = $db_struct[$key]['default'];
			}

			//Fill up the parsed sql safe edit values
			$update_arr[] = $this->parse_value($val, $key, $db_struct);
		}

		//Update the data
		if ($this->query_master("UPDATE `" . $table . "` SET " . implode(" , ", $update_arr) . $filter->get_where())) {
			return true;
		}
		return false;
	}

	/**
	 * Returns a parsed string for the update query.
	 *
	 * @param mixed $val
	 *   the value
	 * @param string $key
	 *   the table field
	 * @param array &$db_struct
	 *   db_struct will be passed by reference,do not provide a & couse function body do this
	 *
	 * @return string the parsed update string
	 */
	private function parse_value($val, $key, &$db_struct) {
		return "`" . $key . "` = " . $this->parse_value_type($val, $db_struct[$key]['typ']) . "";
	}

	/**
	 * Parse the values based up on the given type.
	 *
	 * @param mixed $val
	 *   the unparsed value
	 * @param int $type
	 *   a type const, choose one of PDT_*
	 *
	 * @return mixed the parsed value
	 */
	private function parse_value_type($val, $type) {
		switch ($type) {
			case PDT_SERIALIZED:
				return "'" . Db::safe(@json_encode($val)) . "'";
			case PDT_FILE:
			case PDT_INT:
			case PDT_TINYINT:
			case PDT_MEDIUMINT:
			case PDT_BIGINT:
				return (int) $val;
			case PDT_FLOAT:
			case PDT_DECIMAL:
				return (float) $val;
			case PDT_INET:
				return ip2long($val);
			case PDT_DATE:
				if (preg_match("/^1970-01-01/iUs", $val)) {
					$val = "0000-00-00";
				}
			case PDT_DATETIME:
				if (preg_match("/^1970-01-01/iUs", $val)) {
					$val = "0000-00-00 00:00:00";
				}
			default:
				return "'" . Db::safe($val) . "'";
		}
	}

	/**
	 * This transform finally the query to the needed one.
	 * For now it will only setup the table prefix if present.
	 *
	 * @param string &$query_string
	 *   the querystring.
	 */
	public function final_transform_query(&$query_string) {
		if (empty($this->table_prefix)) {
			return;
		}
		$prefix = Db::safe($this->table_prefix);

		$table_statements = "(((LEFT\s+)?JOIN|(INSERT|REPLACE)\s+(IGNORE\s+)?INTO|FROM|(?<!KEY )UPDATE|CREATE\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE)\s+)";
		$look_behind = "(?<!JOIN )(?<!INTO )(?<!CREATE TABLE )(?<!ALTER TABLE )(?<!DROP TABLE )(?<!(?<!KEY )UPDATE )";

		$pattern = "/(" . $table_statements . "[^\s]*(`[^`]`\.)?`)([^`]+)`(\s|$)/";
		$query_string = trim(preg_replace($pattern, "\${1}" . $prefix . "\${8}`\${9}", $query_string));
		$query_string = preg_replace("/" . $look_behind . "(`[^`]+`\.)?`([^`]+)`\./", "\${1}`" . $prefix . "\${2}`.", $query_string);
	}

	/**
	 * Strip a text to be a "save" text for not allowing SQL-Injections
	 * and other bad things.
	 *
	 * @param string $text
	 *   the text to escape
	 * @param int $type
	 *   the type value type
	 *   if you provide PDT_INT it will be parsed as an integer
	 *   provide PDT_FLOAT to be parsed as a float
	 *   else it will be escaped through mysql_real_escape_string
	 *   (optional, default = PDT_STRING)
	 *
	 * @return float|int|string the escaped value
	 */
	public static function safe($text, $type = PDT_STRING) {
		switch ($type) {
			case PDT_INT:
				return (int)$text;
				break;
			case PDT_FLOAT:
				return (float)$text;
				break;
		}

		return mysql_real_escape_string($text);
	}

	/**
	 * Escape the give string
	 * with mysql_real_escape_string
	 *
	 * @param string $string
	 *  The sql string
	 *
	 * @return string The escaped string.
	 */
	public static function sql_escape($string) {
		return str_replace("%", "\\%", mysql_real_escape_string($string));
	}

}

