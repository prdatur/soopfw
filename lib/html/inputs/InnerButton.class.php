<?php
/**
 * This class provides a Button input which only is a div (not a input type button or submit)
 * 
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class InnerButton extends Button {

	/**
	 * constructor
	 *
	 * @param string $name 
	 *   the input name
	 * @param string $value 
	 *   the value for this input (optional, default = "")
	 * @param string $label 
	 *   the label (optional, default = "")
	 * @param string $class 
	 *   the css class (optional, default = "")
	 * @param string $id 
	 *   the input id (optional, default = "")
	 */
 	public function __construct($name, $value = "", $label = "", $class = "", $id = "") {
		parent::__construct($name, $value, $class, $id);
		$this->config("label", $label);
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config_array("css_class", "form_button");
		$this->config("template", "<div {value}{id}{class}{style}{other}>{value_button}</div>");
		$this->config("type", "inner_button");
	}

}

?>