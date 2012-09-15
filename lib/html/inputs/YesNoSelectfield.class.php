<?php

/**
 * Provides a HTML-Selectfield which includes directly 2 options, yes and no
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class YesNoSelectfield extends Selectfield
{

	/**
	 * Construct
	 *
	 * @param string $name 
	 *   the input name
	 * @param string $selected 
	 *   the selected key (optional, default = '')
	 * @param string $label 
	 *   the input label (optional, default='')
	 * @param string $description 
	 *   the input description (optional, default = '')
	 * @param string $class 
	 *   the input css class (optional, default = '')
	 * @param string $id 
	 *   the input id (optional, default = '')
	 */
 	public function __construct($name, $selected = "", $label = "", $description = "", $class = "", $id = "") {

		//Call the select field construct with predefined values
		parent::__construct($name, array(
			'yes' => t('Yes'),
			'no' => t('No')
		), $selected, $label, $description, $class, $id);
	}
}

