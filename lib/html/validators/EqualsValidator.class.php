<?php

/**
 * Provide a validator which checks if the value equals the options
 *
 * Possible parameters:
 * 		value => the first value as a single value (no array)
 * 		options => the second value as a single value (no array)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class EqualsValidator extends AbstractHtmlValidator
{

	/**
	 * Return if the first value equals the second value
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		return ($this->get_value() == $this->get_options());
	}

}