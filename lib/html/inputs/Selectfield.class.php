<?php

/**
 * Provides a HTML-Selectfield
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Selectfield extends AbstractHtmlInput
{

	/**
	 * The selectfield options (not configuration optione, these are the selectable element options)
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Construct
	 *
	 * @param string $name
	 *   the input name
	 * @param array $options
	 *   the options as array(key => value, ...)
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
 	public function __construct($name, $options, $selected = "", $label = "", $description = "", $class = "", $id = "") {

		//Call normal construct but provide an empty value
		parent::__construct($name, null, $label, $description, $class, $id);
		//Add our select options
		$this->add_options($options, $selected);
	}

	/**
	 * init the input
	 */
	public function init() {
		$this->config("template", "<select {name} {id} {class} {style} {other}>{options}</select>");
	}

	/**
	 * Adds an options to the selectfield
	 *
	 * @param string $value
	 *   the input value
	 * @param string $content
	 *   the content
	 * @param boolean $checked_param
	 *   true or false if input is checked
	 */
	public function add_option($value, $content, $checked_param) {
		$checked = false;

		/**
		 * Check if we have submitted this select element, if true and the value is the current added select option value
		 * We set this option to true and also provide a config variable that we have now already checked this element
		 * This is needed to prevent further checked true if a default value is after the posted one
		 * if the default value was before the posted on it doesn't matter, because the the last checked option will be used
		 */
		if (isset($_POST[$this->config("name")]) && (string)$_POST[$this->config("name")] == (string)$value) {
			$this->config("checked", true);
			$checked = true;
		}
		else if ($this->config("checked") == false) {
			$checked = $checked_param;
			if ($checked_param) {
				$this->config("checked", true);
			}
		}
		$this->options[$value]['value'] = $value;
		$this->options[$value]['content'] = $content;
		$this->options[$value]['selected'] = $checked;
	}

	/**
	 * Adds an options to the Selectfield and preselect if wanted the $selected option value
	 *
	 * @param array $options
	 *   The option data array.
	 * @param string $selected
	 *   The preselcted option, this would be the key from the options array. (optional, default = '')
	 */
	public function add_options(Array $options, $selected = "") {
		//Loop through all provided options
		foreach ($options AS $key => $val) {
			//Check if we want to preselect this value
			$checked = false;
			if ((string)$selected == (string)$key) {
				$checked = true;
			}
			//Add the option
			$this->add_option($key, $val, $checked);
		}
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	function get_tpl_vars() {
		$conf = parent::get_tpl_vars();
		$conf['options|clear'] = "";

		$value_checked = null;
		$check_value = $this->config('value');
		if ($check_value !== false) {
			foreach ($this->options AS $input) {
				if ($check_value == $input['value']) {
					$value_checked = $input['value'];
					break;
				}
			}
		}
		foreach ($this->options AS $input) {
			if (is_null($value_checked)) {
				$input['selected'] = (($input['selected'] == true)) ? ' selected="selected"' : '';
			}
			else {
				$input['selected'] = (($this->config('value') == $input['value'])) ? ' selected="selected"' : '';
			}
			$conf['options|clear'] .= '<option value="'.$input['value'].'"'.$input['selected'].'>'.$input['content']."</option>\n";
		}

		return $conf;
	}
	
	/**
	 * Checks if all validators are valid.
	 * Overrides previous one to verify that the value is one of the added options.
	 *
	 * @return boolean 
	 *   on success true, else false
	 */
	public function is_valid() {
		if (!isset($this->options[$this->get_value()])) {
			$this->add_error(t('Invalid option provided'));
			return false;
		}
		return parent::is_valid();
	}

}

