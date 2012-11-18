<?php

/**
 * Provides a database where group, every group has his own link type (AND/OR)
 * for the elements within the container. you can add a database where group
 * also to the add_where function to build up complex where statements
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Database
 */
class DatabaseWhereGroup extends Object
{
	/**
	 * Define constances
	 */
	const TYPE_AND = 'AND';
	const TYPE_OR = 'OR';

	/**
	 * The link type
	 *
	 * @var string
	 */
	private $type = "AND";

	/**
	 * The grouped conditions.
	 * 
	 * @var array
	 */
	private $conditions = array();

	/**
	 * Construct.
	 *
	 * @param string $type
	 *   the link type use one of DatabaseWhereGroup::TYPE_* (optional, default = DatabaseWhereGroup::TYPE_AND)
	 * @param Db $db
	 *   if provided it will set the db for our database where group.
	 *   this is needed if we use this filter within the construct
	 *   of the Core, normaly this is not needed because it will get it from
	 *   the Object class
	 *   (optional, default = null)
	 */
	public function __construct($type = DatabaseWhereGroup::TYPE_AND, Db &$db = null) {
		$this->type = $type;
		parent::__construct();

		if ($db !== null) {
			$this->db = $db;
		}
	}

	/**
	 * Adds a field or a complete DatabaseWhereGroup object to this group
	 *
	 * @param mixed $key
	 *   the table field as a string, could also be a DatabaseWhereGroup object
	 * @param string $value
	 *   the condition value, it is only optional if key is a DatabaseWhereGroup object (optional, default = NS)
	 * @param string $condition_type
	 *   like =, !=, LIKE, <, >, <=, >= (optional, default = '=')
	 * @param string $table
	 *   the table where we find the field
	 *   (optional, default = NS)
	 * @param boolean $escape
	 *   if set to false the value will not be escaped
	 * 	 USE THIS WITH CAUTION, not escaping value can be a security issue and
	 *   can open SQL-Injections. (optional, default = true)
	 *
	 * @return DatabaseWhereGroup
	 *   The where group
	 */
	public function add_where($key, $value = NS, $condition_type = '=', $table = NS, $escape = true) {
		//If we just provided a database where group object, add this
		if ($value === NS && $key instanceof DatabaseWhereGroup) {
			$this->conditions[] = $key;
		}
		//Else we need to have setup a value
		else if ($value !== NS) {
			//Add the condition
			$this->conditions[] = array(
				'key' => $key,
				'value' => $value,
				'condition_type' => $condition_type,
				'escape' => $escape,
				'table' => $table,
			);
		}
		return $this;
	}

	/**
	 * Returns whether the database where group is empty or not
	 *
	 * @return boolean
	 *   true if empty, else false
	 */
	public function is_empty() {
		return empty($this->conditions);
	}

	/**
	 * Return the sql where statement, normaly we should just call without parameter, the $first will be called within the method self
	 * if the condition is a database where group object to get not more than one WHERE prefix within the returning string
	 *
	 * @param boolean $first
	 *   if set to true it will place in front of the string the WHERE prefix
	 *   this parameter should never provided because it is used for recrusion internal (optional, default = true)
	 *
	 * @return string
	 *   the sql statement string
	 */
	public function get_sql($first = true) {
		$tmp_array = array();
		//Loop through all available conditions
		foreach ($this->conditions AS $v) {
			//If we have a database where group, get the statement from this object
			if ($v instanceof DatabaseWhereGroup) {
				$tmp_array[] = $v->get_sql(false);
				continue;
			}

			$k = $v['key'];
			if ($k instanceof DatabaseWhereGroup) {
				$tmp_array[] = $k->get_sql(false);
				continue;
			}

			/**
			 * the dot is a seperator for direct table selection so we need to escape it with `.` to get a field string
			 * for example, original = my.db.field1, so we get this: `my`.`db`.`field1`
			 * this will only be executed if the k is not a numeric integer because maybe we want just select 1
			 */
			if ((int) $k !== $k) {
				$k = "`" . Db::safe(str_replace(".", "`.`", $k)) . "`";
				if (!empty($v['table']) && $v['table'] !== NS) {
					$k = Db::safe($v['table']) . '.' . $k;
				}
			}
			else {
				$k = Db::safe($k);
			}

			if ($v['value'] instanceof DatabaseFilter) {
				$limit_conditions = array(
					'=' => true,
					'!=' => true,
					'>=' => true,
					'<=' => true,
					'<' => true,
					'>' => true,
					'LIKE' => true,
				);

				if (isset($limit_conditions[$v['condition_type']])) {
					$v['value']->limit(1);
				}
				$val = '(' . $v['value']->get_select_sql();
				$limit = (int) $v['value']->limit();

				if ($limit > 0) {
					$val .= " LIMIT " . $limit;
				}

				$offset = (int) $v['value']->offset();
				if ($offset > 0) {
					$val .= " OFFSET " . $offset;
				}

				$val .= ')';
			}
			else if ($v['escape'] == true) {
				//Escape the value
				$val = Db::safe($v['value']);
				// This removal of escape char is required if a % value was provided which was already escaped.
				$val = preg_replace("/[\\\]+\%/", "\\%", $val);
				//Check if we have not a number, if so we need to add ''
				if (((int) $v['value'] !== $v['value']) && ((float) $v['value'] !== $v['value'])) {
					$val = "'" . $val . "'";
				}
			}
			else {
				$val = $v['value'];
			}
			//Add the condition string
			$tmp_array[] = $k . " " . $v['condition_type'] . " " . $val;
		}

		//get all conditions to a string linked with the link type
		$where_str = implode(" " . $this->type . " ", $tmp_array);
		$where = "";
		if (!empty($where_str)) {
			$where = " (" . $where_str . ") ";
		}

		//place the WHERE prefix statement
		if ($first && !empty($where)) {
			$where = " WHERE" . $where;
		}
		return $where;
	}

}

