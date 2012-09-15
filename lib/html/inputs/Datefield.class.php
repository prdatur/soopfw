<?php

/**
 * Provides a HTML-Textfield with a date picker
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Datefield extends Textfield
{

	/**
	 * constructor
	 *
	 * @param string $name 
	 *   the input name
	 * @param string $value 
	 *   the value for this input (optional, default='')
	 * @param string $label 
	 *   the input label (optional, default='')
	 * @param string $description 
	 *   the input description (optional, default = '')
	 * @param string $class 
	 *   the input css class (optional, default = '')
	 * @param string $id 
	 *   the input id (optional, default = '')
	 */
	public function __construct($name, $value = '', $label = '', $description = '', $class = "", $id = '') {
		parent::__construct($name, $value, $label, $description, $class, $id);
		$this->config_array('css_class', 'datepicker');
	}

}

