<?php

/**
 * Provide a validator which checks values against a database, it will return true
 * if the value NOT exists
 *
 * Possible parameters:
 * 		value => the value which will be searched within the table => field
 * 		options => an array with (table => field)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.validators
 */
class NotExistValidator extends ExistValidator
{

	/**
	 * Returns true if the value NOT exists
	 *
	 * @return if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		return!(parent::is_valid());
	}

}

?>