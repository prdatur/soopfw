<?php

/**
 * Provides a database where group, every group has his own link type (AND/OR)
 * for the elements within the container. you can add a database where group
 * also to the add_where function to build up complex where statements
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.database.filter
 * @category Database
 */
class DatabaseWhereGroup
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
	 * The grouped conditions
	 * @var Array
	 */
	private $conditions = array();

	/**
	 * Construct.
	 *
	 * @param string $type
	 *   the link type use one of DatabaseWhereGroup::TYPE_* (optional, default = DatabaseWhereGroup::TYPE_AND)
	 */
 	public function __construct($type = DatabaseWhereGroup::TYPE_AND) {
		$this->type = $type;
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
	 *
	 * @return DatabaseWhereGroup
	 *   The where group
	 */
	public function add_where($key, $value = NS, $condition_type = '=') {
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
				'condition_type' => $condition_type
			);
		}
		return $this;
	}

	/**
	 * Returns wether the database where group is empty or not
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
			
			/**
			 * the dot is a seperator for direct table selection so we need to escape it with `.` to get a field string
			 * for example, original = my.db.field1, so we get this: `my`.`db`.`field1`
			 */
			$k = "`".safe(str_replace(".", "`.`",  $k))."`";

			//Escape the value
			$val = safe($v['value']);
			// This removal of escape char is required if a % value was provided which was already escaped.
			$val = preg_replace("/[\\\]+\%/", "\\%", $val);
			//Check if we have not a number, if so we need to add ''
			if (((int)$v['value'] !== $v['value']) && ((float)$v['value'] !== $v['value'])) {
				$val = "'".$val."'";
			}

			//Add the condition string
			$tmp_array[] = $k." ".$v['condition_type']." ".$val;
		}

		//get all conditions to a string linked with the link type
		$where_str = implode(" ".$this->type." ", $tmp_array);
		$where = "";
		if (!empty($where_str)) {
			$where = " (".$where_str.") ";
		}

		//place the WHERE prefix statement
		if ($first && !empty($where)) {
			$where = " WHERE".$where;
		}
		return $where;
	}

}

?>