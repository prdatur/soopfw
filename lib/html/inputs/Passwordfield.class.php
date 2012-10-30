<?php

/**
 * Provides a HTML-Password field
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Passwordfield extends AbstractHtmlInput {

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<input type=\"password\" {name}{value}{id}{class}{style}{other}/>");
	}

}

