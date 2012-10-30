<?php

/**
 * Provides a HTML-Submitbutton
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Submitbutton extends AbstractHtmlInput
{

	/**
	 * constructor
	 *
	 * @param string $name
	 *   the input name
	 * @param string $value
	 *   the value for this input (optional, default='')
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
 	public function __construct($name, $value = "", $class = "", $id = "") {
		parent::__construct($name, $value, '', '', $class, $id);
	}

	/**
	 * Check if this submitbutton is submitted
	 *
	 * @return boolean true if submitted yes, else false
	 */
	public function is_submitted() {
		return isset($_POST[$this->config("name")]);
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<button type=\"submit\" {value}{name}{id}{class}{style}{other}>{value_button}</button>");
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	public function get_tpl_vars() {
		$conf = parent::get_tpl_vars();
		$conf['value_button|clear'] = $this->config("value");
		return $conf;
	}

}

