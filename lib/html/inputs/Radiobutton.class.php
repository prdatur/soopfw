<?php

/**
 * Provides a HTML-Radiobutton
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 */
class Radiobutton extends Checkbox
{

	/**
	 * init the input
	 */
	public function init() {
		//Setup the label for our checkbox if it is not empty
		$label = "";
		if (!empty($this->label)) {
			$label = "<label for=\"{clearId}\">{label_own}</label>";
		}
		$this->config("template", "<input type=\"radio\" {name}{value}{id}{class}{style}{other}{checked}/>".$label);
		$this->config("type", "radio");
	}
}

?>