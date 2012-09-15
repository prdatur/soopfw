<?php

/**
 * Provide a checkbox container
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Checkboxes extends AbstractHtmlInput
{

	/**
	 * The container which holds our Checkboxes
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Wether to include not checked elements within returning values or not
	 *
	 * @var boolean
	 */
	protected $include_empty_elements = false;

	/**
	 * Construct
	 *
	 * @param string $name
	 *   The main name for this checkbox container (the checkboxes will inhire the name)
	 * @param array $values
	 *   The values as an array in format ('value' => 'label') for every checkbox
	 * @param array $default_value
	 *   The default values to preselect checkboxes if needed, keys are not used,
	 *   provide just values with the key names from $values to be preselected
	 *   like array('yes') to preselect yes element (optional, default = array())
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param boolean $include_empty_values
	 *   Set to true if you want also empty 'not posted' keys within the returning output (optional, default = false)
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
	public function __construct($name, Array $values, Array $default_value = array(), $label = '', $description = '', $include_empty_values = false, $class = '', $id = '') {

		//We just need to load the core couse all needed parameters we will setup manually
		parent::load_core();

		//Set the label for the element, if $label not provided use $name instead
		$this->config("label", $label);

		//It can happen that an input will override config('label') so to have the original label store it
		$this->config("orig_label", (empty($label)) ? $name : $label);

		//Set the element description
		$this->config("description", $description);

		//Set if we want to include empty elements with the returning output
		$this->include_empty_elements($include_empty_values);

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
			if (in_array($value, $default_value)) {
				$default_element_value = $value;
			}

			$field = new Checkbox($name."[".$value."]", $value, $default_element_value, $label, '', $class);
			$field->config("id", $id.'_'.$i);
			$this->fields[] = $field;
		}
	}

	/**
	 * Set if we want to include empty elements within returning values or not
	 *
	 * @param boolean $include
	 *   if we want to include it or not, (optional, default = true)
	 */
	public function include_empty_elements($include = true) {
		$this->include_empty_elements = $include;
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config('type', 'checkboxes');
	}

	/**
	 * Returns the HTML-Code string for the element
	 * It will get all checkbox elements and concate the elements
	 *
	 * @return string the HTML code for the element
	 */
	public function fetch() {
		//first the the label string if not empty
		$output = $this->get_label();

		//Provide a suffix which we can configurate
		$suffix = $this->config("suffix");
		if(!empty($suffix)) {
			$output .= $suffix;
		}

		//Loop through all inputs and append the fetched element html string to our returning string
		foreach ($this->fields as &$field) {
			$this->init();
			$output .= "<div class='" . $this->config('type') . "'>".$field->fetch()."</div>";
		}

		//Append the main input template string and the followed description
		$output .= $this->get_description();
		return $output;
	}

	/**
	 * Get or set config values
	 *
	 * @param string $k
	 *   the key
	 * @param string $v
	 *   the value as a string, if not set, current value will be returned (optional, default = NS)
	 *
	 * @return mixed the value for the key as a string or if in set-mode return true, if a key is not set, return false
	 */
	public function config($key, $val = NS) {
		//If we want to return the value we need to override the normal function
		if ($key == "value" && $val === NS) {
			//If we have not inputs setup we can do nothing.
			if (empty($this->fields)) {
				return;
			}
			$return_array = array();

			//Loop through all elements and get their values
			foreach ($this->fields as &$field) {
				$value = $field->config('value');
				//Only add the value if it is not empty or we want also empty values
				if (!empty($value) || $this->include_empty_elements == true) {
					$return_array[$field->get_value()] = $value;
				}
			}
			return $return_array;
		}
		//All other actions will be normal
		return parent::config($key, $val);
	}

	/**
	 * Get or add config values to the config as an array, duplicate entries will be ignored by default
	 *
	 * @param string $key
	 *   the key
	 * @param string $val
	 *   the value as a string, if not set, current value will be returned (optional, default = NS)
	 * @param boolean $add_duplicates
	 *   if this setting is set to true, the value will be added twice also if it was in the array before (optional, default = false)
	 *
	 * @return mixed the values for the key as an array or if in set-mode return true, if you return a value which are not set, return false
	 */
	public function config_array($key, $val = NS, $add_duplicates = false) {
		if ($val !== NS) {
			if ($key == 'css_class') {
				foreach ($this->fields as &$field) {
					$field->config_array($key, $val, $add_duplicates);
				}
			}
		}

		parent::config_array($key, $val, $add_duplicates);
	}

}

