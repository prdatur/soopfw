<?php

/**
 * Provide a radiobutton container
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Radiobuttons extends Checkboxes
{

	/**
	 * Construct
	 *
	 * @param string $name 
	 *   The main name for this radiobutton container (the radiobuttons will inhire the name)
	 * @param array $values 
	 *   The values as an array in format ('value' => 'label') for every radiobutton
	 * @param array $default_value 
	 *   The default value to preselect radiobutton if needed, its just the key within the $values (optional, default = '0')
	 * @param string $class 
	 *   the input css class (optional, default = '')
	 * @param string $id 
	 *   the input id (optional, default = '')
	 */
	public function __construct($name, $values, $default_value = "0", $class = '', $id = '') {

		//We just need to load the core couse all needed parameters we will setup manually
		parent::load_core();

		//Set include empty elements with the returning output to always be false
		$this->include_empty_elements(false);

		//Configurate the original name so we can use it within the returning values
		$this->config('name', $name);

		//Build up the id for our elements
		$id = (empty($id)) ? "form_id_".$name : $id;

		//Init loop counter
		$i = 0;
		//Loop through all provided elements
		foreach ($values as $value => $label) {
			$i++;

			//Check if we want a default value for that element,
			$default_element_value = "";

			if ($value == $default_value) {
				$default_element_value = $value;
			}

			$field = new Radiobutton($name."[]", $value, $default_element_value, $label, '', $class);
			$field->config("id", $id.'_'.$i);
			$this->fields[] = $field;
		}
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config('type', 'radiobuttons');
	}

	/**
	 * Get or set config values
	 *
	 * @param string $k 
	 *   the key
	 * @param string $v 
	 *   the value as a string, if not set, current value will be returned (optional, default = NS)
	 * @return mixed 
	 *   the value for the key as a string or if in set-mode return true, if a key is not set, return false
	 */
	public function config($key, $val = NS) {
		//If we want to return the value we need to override the normal function
		if ($key == "value" && $val == NS) {
			//If we have not inputs setup we can do nothing.
			if (empty($this->fields)) {
				return;
			}

			/**
			 * Loop through all elements and check if we got a value, if so return it.
			 * Radiotbuttons allow only one value per group so the element name of the group elements
			 * will have the same name and with that we got the realy posted value in every radio element within the group
			 */
			foreach ($this->fields as &$field) {
				$value = $field->config('value');
				if (!empty($value)) {
					return $value;
				}
			}
			return "";
		}
		//All other actions will be normal
		return parent::config($key, $val);
	}

}

?>