<?php

/**
 * Provides a HTML-Textarea
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Textarea extends AbstractHtmlInput
{
	/*
	 * init the input
	 */

	public function init() {
		$this->config("template", "<textarea {name}{id}{class}{style}{other}>{value}</textarea>");
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	function get_tpl_vars() {
		$return_arr = parent::get_tpl_vars();
		unset($return_arr['value']);
		//We want the value to be cleared so we have not the "value=" tag within the textarea value
		$return_arr['value|clear'] = htmlspecialchars($this->config("value"));
		return $return_arr;
	}

}

