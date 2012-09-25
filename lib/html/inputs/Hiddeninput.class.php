<?php

/**
 * Provides a HTML-Hidden field
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Hiddeninput extends AbstractHtmlInput
{

	public $label = "";

	/**
	 * constructor
	 *
	 * @param string $name
	 *   the input name
	 * @param string $value
	 *   the value for this input (optional, default = "")
	 * @param string $label
	 *   the label (optional, default = "")
	 * @param string $description
	 *   the description (optional, default = "")
	 * @param string $class
	 *   the css class (optional, default = "")
	 * @param string $id
	 *   the input id (optional, default = "")
	 */
	public function __construct($name, $value = "", $label = "", $description = "", $class = "", $id = "") {
		parent::__construct($name, $value, $label, $description, $class, $id);
		$this->label = $label;
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<input type=\"hidden\" {class} {name} {value} {id} {other}/>");
	}

}

