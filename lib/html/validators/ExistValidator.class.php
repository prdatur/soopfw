<?php

/**
 * Provide a validator which checks values against a database
 *
 * Possible parameters:
 * 		value => the value which will be searched within the table => field
 * 		options => an array with (table => field)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class ExistValidator extends AbstractHtmlValidator
{

	/**
	 * Returns true if the value exists
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}

		$val = $this->get_value();
		$this->options = $this->get_options();
		if (empty($val)) {
			return true;
		}

		if (!is_array($this->options)) {
			return true;
		}

		$exists_tbl = key($this->options);
		$exists_field = $this->options[$exists_tbl];
		$checkval = $this->get_value();


		$sql = "SELECT * FROM `:exists_tbl` WHERE `" . Db::safe($exists_field) . "` = :checkval";
		return ($this->db->query_slave_count($sql, array(
			":exists_tbl" => $exists_tbl, 
			":checkval" => $checkval
		)) > 0);
	}

}

