<?php

/**
 * Provides a HTML-Textfield
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Textfield extends AbstractHtmlInput
{

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", '<input type="text" {name}{value}{id}{class}{style}{other}/>');
		$this->config("type", 'textfield');
	}

}

