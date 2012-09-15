<?php

/**
 * Provide an email validator
 * Possible parameters:
 * 		value => the email which will be checked
 * 		options => no options used
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.validators
 * @category Form.Validators
 */
class EmailValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the email
	 * Be aware, an empty value validates it to true so a maybe attached
	 * RequiredValidator can handle it if the EmailValidator was provided first

	 * @return boolean true if valid, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		$val = $this->get_value();
		if (empty($val)) {
			return true;
		}
		return NetTools::check_mail($this->get_value());
	}

}

?>