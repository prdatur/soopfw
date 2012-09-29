<?php

/**
 * Provides a container which holds just static html
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class HtmlContainerInput extends AbstractNoValueInput
{
	/**
	 * The value to display.
	 *
	 * @var string
	 */
	public $value = "";

	/**
	 * constructor
	 *
	 * @param string $value
	 *   the value for this input (optional, default = "")
	 * @param string $class
	 *   the css class (optional, default = "")
	 * @param string $id
	 *   the input id (optional, default = "")
	 */
	public function __construct($value = "", $class = "") {
		parent::__construct(md5(uniqid()));

		// Add the $class to our css class array.
		$this->config_array("css_class", $class);

		// Setup our value.
		$this->value = $value;
	}

	/**
	 * Init the input
	 */
	public function init() {
		$this->config("template", "<div {class} {name} {id} {other}>{value}</div>");
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	function get_tpl_vars() {
		$return_arr = parent::get_tpl_vars();
		unset($return_arr['value']);
		//We want the value to be cleared so we have not the "value=" tag within the html container value
		$return_arr['value|clear'] = $this->value;
		return $return_arr;
	}

}