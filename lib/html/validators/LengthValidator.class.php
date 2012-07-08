<?php

/**
 * Provide an validator which validates the given value against a min and a max length
 *
 * Possible parameters:
 * 		value => the value to be checked
 * 		options => an array with ('min' => int, 'max' => '0')
 * 					if min is not provided 0 will be used
 * 					if max is not provided the strlen of given value will be used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.validators
 * @category Form.Validators
 */
class LengthValidator extends AbstractHtmlValidator
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
		$options = $this->get_options();
		if (!isset($options['min'])) {
			$options['min'] = 0;
		}
		if (!isset($options['max'])) {
			$options['max'] = strlen($this->get_value());
		}
		if (strlen($this->get_value()) >= $options['min'] && strlen($this->get_value()) <= $options['max']) {
			return true;
		}
		return false;
	}

}

?>