<?php

/**
 * Provide a validator which checks values if it is a valid phone number
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => not used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class PhoneValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the rules
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		$val = $this->get_value();
		if (empty($val)) {
			return true;
		}
		return preg_match("/^((\+|00)[1-9]+\s)?[0-9\-., ]+\s[0-9\-., ]+$/iUs", $this->get_value());
	}

}

