<?php

/**
 * Provides a HTML-Button
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Button extends Submitbutton
{

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<button {value}{name}{id}{class}{style}{other}>{value_button}</button>");
	}

}

