<?php

/**
 * Provides a HTML-Hidden field
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 */
class Hiddeninput extends AbstractHtmlInput
{

	public $label = "";

	/**
	 * constructor
	 *
	 * @param String $name the input name
	 * @param String $value the value for this input (optional, default = "")
	 * @param String $label the label (optional, default = "")
	 * @param String $description the description (optional, default = "")
	 * @param String $class the css class (optional, default = "")
	 * @param String $id the input id (optional, default = "")
	 */
	function __construct($name, $value = "", $label = "", $description = "", $class = "", $id = "") {
		parent::__construct($name, $value, $label, $description, $class, $id);
		$this->label = $label;
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<input type=\"hidden\" {class} {name} {value} {id} {other}/>");
		$this->config("type", "hidden");
	}

}

?>