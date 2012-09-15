<?php

/**
 * Provides a database filter which can be used to generate a mysql
 * string to filter with a where statement or getting limit / offset.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.database.filter
 * @category Database
 */
class DatabaseFilter extends Object
{
	const ASC = 'asc';
	const DESC = 'desc';

	/**
	 * the table name
	 *
	 * @var string
	 */
	private $table = "";

	/**
	 * the table alias
	 *
	 * @var string
	 */
	private $alias = "";

	/**
	 * The where filter object
	 *
	 * @var DatabaseWhereGroup
	 */
	private $where = null;

	/**
	 * The database columns which we want to return
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * the limit
	 *
	 * @var int
	 */
	private $limit = 0;

	/**
	 * the offset
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * The group by elements
	 *
	 * @var array
	 */
	private $group_by = array();

	/**
	 * The order by elements
	 *
	 * @var array
	 */
	private $order_by = array();

	/**
	 * The joins
	 *
	 * @var array
	 */
	private $joins = array();

	/**
	 * the fields which will be changed on insert or update.
	 *
	 * @var array
	 */
	private $change_fields = array();

	/**
	 * construct
	 *
	 * @param string $table
	 *   the table (optional, default = "")
	 * @param string $alias
	 *   the alias for this table (optional, default = "")
	 * @param Db &$db
	 *   if provided it will set the db for our databasefilter.
	 *   this is needed if we use this filter within the construct
	 *   of the Core, normaly this is not needed because it will get it from
	 *   the Object class
	 *   (optional, default = null)
	 */
	public function __construct($table = "", $alias = "", Db &$db = null) {
		$this->table = $table;
		$this->alias = $alias;
		parent::__construct();
		if ($db !== null) {
			$this->db = $db;
		}
		$this->where = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_AND, $this->db);
	}

	/**
	 * Set the table
	 *
	 * @param string $table
	 *   the table name
	 * @param string $alias
	 *   the alias for the table (optional, default = '')
	 */
	public function set_table($table, $alias = '') {
		$this->table = $table;
		$this->alias = $alias;
	}

	/**
	 * Creates a databaseFilter instance which is the same as using
	 * new DatabaseFilter but this will let us have a better code style
	 *
	 * @param string $table
	 *   the table (optional, default = "")
	 * @param string $alias
	 *   the alias for this table (optional, default = "")
	 * @param Db $db
	 *   if provided it will set the db for our databasefilter.
	 *   this is needed if we use this filter within the construct
	 *   of the Core, normaly this is not needed because it will get it from
	 *   the Object class
	 *   (optional, default = null)
	 *
	 * @return DatabaseFilter returns the newly created object
	 */
	public static function create($table = "", $alias = "", Db &$db = null) {
		return new DatabaseFilter($table, $alias, $db);
	}

	/**
	 * Reset our filter settings
	 */
	public function clear() {
		$this->limit = 0;
		$this->offset = 0;
		$this->fields = array();
		$this->where = new DatabaseWhereGroup();
	}

	/**
	 * Add a column to our wanted columns. if table is specified the table name
	 * will be placed in front of the field
	 *
	 * @param string $field
	 *   the table field
	 * @param string $table
	 *   the table name (optional, default = NS)
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &add_column($field, $table = NS) {

		if ($table === NS) {
			$table = $this->get_table_or_alias();
		}
		else {
			$table = "`" . Db::safe($table) . "`";
		}

		$field = Db::safe($field);
		if (preg_match('/^[a-z_]+$/', $field)) {
			$field = "`" . $field . "`";
			if ($table !== NS) {
				$field = $table . "." . $field;
			}
		}


		$this->fields[] = $field;
		return $this;
	}

	/**
	 * Add a column to our wanted columns. if table is specified the table name
	 * will be placed in front of the field
	 *
	 * @param string $field
	 *   the table field
	 * @param string $value
	 *   the new value for this field
	 * @param string $table
	 *   the table name (optional, default = NS)
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &change_fields($field, $value, $table = NS) {
		$field = "`" . Db::safe($field) . "`";
		if ($table === NS) {
			$table = $this->get_table_or_alias();
		}
		else {
			$table = "`" . Db::safe($table) . "`";
		}
		if ($table !== NS) {
			$field = $table . "." . $field;
		}
		$this->change_fields[] = array('field' => $field, 'value' => Db::safe($value));
		return $this;
	}

	/**
	 * Get the pure column array
	 *
	 * @return array the field array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Return the columns as a string sperated by a comma
	 *
	 * @return string the fields imploded by , (comma)
	 */
	public function get_columns() {
		if (empty($this->fields)) {
			$table = $this->get_table_or_alias();
			if (!empty($table)) {
				$table .= '.';
			}
			$this->fields[] = $table . "*";
		}
		return implode(", ", $this->fields);
	}

	/**
	 * add a where condition
	 *
	 * @param mixed $field
	 *   the field as a string or an DatabaseWhereGroup object
	 * @param string $value
	 *   can be optional, but only if $field is an DatabaseWhereGroup (optional, default = "")
	 * @param string $condition_type
	 *   the condition type (=, !=, LIKE) (optional, default = "=")
	 * @param boolean $escape
	 *   if set to false the value will not be escaped
	 * 	 USE THIS WITH CAUTION, not escaping value can be a security issue and
	 *   can open SQL-Injections. (optional, default = true)
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &add_where($field, $value = "", $condition_type = "=", $escape = true) {
		$this->where->add_where($field, $value, $condition_type, $escape);
		return $this;
	}

	/**
	 * Set the where condition
	 *
	 * @param DatabaseWhereGroup $where
	 *   the database where group
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &set_where(DatabaseWhereGroup $where) {
		$this->where = $where;
		return $this;
	}

	/**
	 * Returns wether the filter has a where statement or not
	 *
	 * @return boolean true if not empty, else false
	 */
	public function has_where() {
		return !$this->where->is_empty();
	}

	/**
	 * Returns the sql where statement. if the where filter was setup with
	 * some filter it returns WHERE in front of the prefix with all filters
	 *
	 * @return string the where sql
	 */
	public function get_where() {
		return $this->where->get_sql();
	}

	/**
	 * Set or get the database limit
	 *
	 * @param int $limit
	 *   if provided it will set the limit (optional, default = NS)
	 *
	 * @return mixed returns the limit while in get mode, DatabaseFilter self returning in set mode
	 */
	public function limit($limit = NS) {
		if ($limit !== NS) {
			$this->limit = (int) $limit;
			return $this;
		}
		return (int) $this->limit;
	}

	/**
	 * Set or get the database offset
	 *
	 * @param int $offset
	 *   if provided it will set the offset (optional, default = NS)
	 *
	 * @return mixed returns the offset while in get mode, DatabaseFilter self returning in set mode
	 */
	public function offset($offset = NS) {
		if ($offset !== NS) {
			$this->offset = (int) $offset;
			return $this;
		}
		return (int) $this->offset;
	}

	/**
	 * Group by the given field.
	 *
	 * @param string $field
	 *   the field to group by
	 * @param string $table
	 *   the table or alias prefix to use for this field (optional, default = NS)
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &group_by($field, $table = NS) {
		if ($table === NS) {
			$table = $this->get_table_or_alias();
		}
		else {
			$table = "`" . Db::safe($table) . "`";
		}

		$this->group_by[] = $table . '.`' . Db::safe($field) . '`';
		return $this;
	}

	/**
	 * Order by the given field.
	 *
	 * @param string $field
	 *   the field to group by
	 * @param string $direction
	 *   the order direction use DatabaseFilter::ASC or DatabaseFilter::DESC (optional, default = NS)
	 * @param string $table
	 *   the table or alias prefix to use for this field
	 *   if you provide null, no table will be prepended (optional, default = NS)
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &order_by($field, $direction = self::ASC, $table = NS) {
		if ($table === NS) {
			$table = $this->get_table_or_alias() . '.';
		}
		elseif ($table !== null) {
			$table = "`" . Db::safe($table) . "`.";
		}
		else {
			$table = '';
		}

		if ($direction != self::ASC && $direction != self::DESC) {
			$direction = self::ASC;
		}

		$field = Db::safe($field);
		if (preg_match('/^[a-z_]+$/', $field)) {
			$field = "`" . $field . "`";
		}
		$this->order_by[] = $table . $field . ' ' . $direction;
		return $this;
	}

	/**
	 * Joins a table
	 *
	 * @param string $table
	 *   the table
	 * @param string $on
	 *   the on statement
	 * @param string $alias
	 *   the alias (optional, default = '')
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &join($table, $on, $alias = '') {
		$table = "`" . Db::safe($table) . "`";
		if (!empty($alias)) {
			$table .= ' AS ' . Db::safe($alias);
		}
		$this->joins[] = 'JOIN ' . $table . ' ON (' . $on . ')';
		return $this;
	}

	/**
	 * Joins a table
	 *
	 * @param string $table
	 *   the table
	 * @param string $on
	 *   the on statement
	 * @param string $alias
	 *   the alias (optional, default = '')
	 *
	 * @return DatabaseFilter Self returning
	 */
	public function &left_join($table, $on, $alias = '') {
		$table = "`" . Db::safe($table) . "`";
		if (!empty($alias)) {
			$table .= ' AS ' . Db::safe($alias);
		}
		$this->joins[] = 'LEFT JOIN ' . $table . ' ON (' . $on . ')';
		return $this;
	}

	/**
	 * Execute the filter to get all rows.
	 *
	 * @param int $array_key
	 *   returned array key as a table field (optional, default=0)
	 * @param boolean $single_row_field
	 *  if set to true and the returning array will just have one field
	 *  the value array will transformed into a single value string
	 *  for example if the returning array would be:
	 *  array('row1' => array('field1'), 'row2' => array('field1'))
	 *  the transformed one would be:
	 *  array('row1' => 'field1', 'row1' => 'field1')
	 *
	 * @return array the rows
	 */
	public function select_all($array_key = 0, $single_row_field = false) {
		$values = $this->db->query_slave_all($this->get_select_sql(), array(), $this->limit(), $this->offset(), $array_key);
		if ($single_row_field == true && is_array($values)) {
			$current_first_row = current($values);
			if (is_array($current_first_row)) {
				foreach ($values AS &$value) {
					if (!empty($array_key) && count($value) > 1) {
						unset($value[$array_key]);
					}
					$value = current($value);
				}
			}
		}
		return $values;
	}

	/**
	 * Execute the filter to get the first row.
	 *
	 * @return array the row
	 */
	public function select_first() {
		return $this->db->query_slave_first($this->get_select_sql(), array(), $this->offset());
	}

	/**
	 * Execute the filter to get the count of results.
	 *
	 * @return int the count
	 */
	public function select_count() {
		return $this->db->query_slave_count($this->get_select_sql(), array(), $this->limit(), $this->offset());
	}

	/**
	 * Execute the filter to check if a value exists.
	 *
	 * @return boolean returns true if a row exists, else false
	 */
	public function select_exists() {
		return $this->db->query_slave_exists($this->get_select_sql());
	}

	/**
	 * Execute the filter which can be further processed with Db->fetch_assoc();
	 *
	 * @return resource the mysql resource id
	 */
	public function select() {
		return $this->db->query_slave($this->get_select_sql(), array(), $this->limit(), $this->offset());
	}

	/**
	 * Execute the filter with an update statement.
	 *
	 * @return boolean returns true if the update succeeds, else false
	 */
	public function update() {
		if (empty($this->change_fields)) {
			return true;
		}
		return $this->db->query_master($this->get_update_sql());
	}

	/**
	 * Execute the filter with an delete statement.
	 *
	 * @return boolean returns true if the delete succeeds, else false
	 */
	public function delete() {
		return $this->db->query_master($this->get_delete_sql(), array(), $this->limit(), $this->offset());
	}

	/**
	 * Execute the filter with an insert statement.
	 *
	 * @return mixed returns the last inserted id if available, if not it will return boolean if the insert was successfully.
	 */
	public function insert() {

		if ($this->db->query_master($this->get_insert_sql())) {
			$inserted_id = $this->db->insert_id();
			if ($inserted_id !== false) {
				return $inserted_id;
			}
			return true;
		}
		return false;
	}

	/**
	 * Generates a insert sql statement.
	 *
	 * @return string the sql string
	 */
	public function get_insert_sql() {
		$table = '`' . Db::safe($this->table) . '`';
		if (!empty($this->alias)) {
			$table .= ' AS ' . Db::safe($this->alias);
		}

		$fields = $values = array();
		foreach ($this->change_fields AS $field) {
			$fields[] = $field['field'];
			$values[] = "'" . $field['value'] . "'";
		}
		return "INSERT INTO " . $table . " (" . implode(" , ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
	}

	/**
	 * Generates a delete sql statement.
	 *
	 * @return string the sql string
	 */
	public function get_delete_sql() {
		$table = '`' . Db::safe($this->table) . '`';
		if (!empty($this->alias)) {
			$table .= ' AS ' . Db::safe($this->alias);
		}
		return "DELETE FROM " . $table . $this->get_where();
	}

	/**
	 * Generates a update sql statement.
	 *
	 * @return string the sql string
	 */
	public function get_update_sql() {
		$table = '`' . Db::safe($this->table) . '`';
		if (!empty($this->alias)) {
			$table .= ' AS ' . Db::safe($this->alias);
		}
		$update_fields = array();
		foreach ($this->change_fields AS $field) {
			$update_fields[] = $field['field'] . " = '" . $field['value'] . "'";
		}
		return "UPDATE " . $table . " SET " . implode(", ", $update_fields) . $this->get_where();
	}

	/**
	 * Generates a select sql statement.
	 *
	 * @return string the sql string
	 */
	public function get_select_sql() {
		$table = '`' . Db::safe($this->table) . '`';
		if (!empty($this->alias)) {
			$table .= ' AS ' . Db::safe($this->alias);
		}
		$table .= ' ';
		$group_by = $order_by = "";
		if (!empty($this->group_by)) {
			$group_by .= ' GROUP BY ' . implode(', ', $this->group_by);
		}

		if (!empty($this->order_by)) {
			$order_by .= ' ORDER BY ' . implode(', ', $this->order_by);
		}

		return "SELECT " . $this->get_columns() . " FROM " . $table . implode(' ', $this->joins) . $this->get_where() . $group_by . $order_by;
	}

	/**
	 * Returns the usable table name, if an alias exists for the table it will
	 * return the alias.
	 *
	 * @return string the table or the alias
	 */
	private function get_table_or_alias() {
		$table = "";
		if (!empty($this->table)) {
			$table = "`" . Db::safe($this->table) . "`";
		}
		if (!empty($this->alias)) {
			$table = Db::safe($this->alias);
		}
		return $table;
	}

}

?>