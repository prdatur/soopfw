<?php

/**
 * Provides a database filter which can be used to generate a mysql
 * string to filter with a where statement or getting limit / offset.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.database.filter
 */
class DatabaseFilter
{

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
	 * construct
	 */
	function __construct() {
		$this->where = new DatabaseWhereGroup();
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
	 * @param string $field the table field
	 * @param string $table the table name (optional, default = NS)
	 */
	public function add_column($field, $table = NS) {
		$field = "`".$field."`";
		if ($table !== NS) {
			"`".$table."`.".$field;
		}
		$this->fields[] = $field;
	}

	/**
	 * Get the pure column array
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Return the columns as a string sperated by a comma
	 *
	 * @return string
	 */
	public function get_columns() {
		if (empty($this->fields)) {
			$this->fields[] = "*";
		}
		return implode(", ", $this->fields);
	}

	/**
	 * add a where condition
	 * @param mixed $field the field or the database where group
	 * @param string $value can be optional, but only if $field is an DatabaseWhereGroup (optional, default = "")
	 * @param string $condition_type (=, !=, LIKE)
	 */
	public function add_where($field, $value = "", $condition_type = "=") {
		$this->where->add_where($field, $value, $condition_type);
	}

	/**
	 * Set the where condition
	 * @param DatabaseWhereGroup $where
	 */
	public function set_where(DatabaseWhereGroup $where) {
		$this->where = $where;
	}

	/**
	 * Returns wether the filter has a where statement or not
	 *
	 * @return boolean true if not empty, else false
	 */
	public function has_where() {
		return!$this->where->is_empty();
	}

	/**
	 * Returns the sql where statement. if the where filter was setup with
	 * some filter it returns WHERE in front of the prefix with all filters
	 *
	 * @return string
	 */
	public function get_where() {
		return $this->where->get_sql();
	}

	/**
	 * Set or get the database limit
	 *
	 * @param int $limit if provided it will set the limit (optional, default = NS)
	 * @return int returns the limit while in get mode, null in set mode
	 */
	public function limit($limit = NS) {
		if ($limit !== NS) {
			$this->limit = (int)$limit;
			return null;
		}
		return $this->limit;
	}

	/**
	 * Set or get the database offset
	 *
	 * @param int $offset if provided it will set the offset (optional, default = NS)
	 * @return int returns the offset while in get mode, null in set mode
	 */
	public function offset($offset = NS) {
		if ($offset !== NS) {
			$this->offset = (int)$offset;
			return;
		}
		return $this->offset;
	}

}

?>