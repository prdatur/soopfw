<?php

/**
 * Provide a validator which checks values if it is not empty
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => not used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.validators
 */
class RequiredValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the rules
	 *
	 * @return if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		$val = $this->get_value();
		if($val."" === "0") {
			return true;
		}
		return !empty($val);
	}

}

?>