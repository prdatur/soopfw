<?php

/**
 * Provide an abstract class for an HTML-Input
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
abstract class AbstractHtmlInput extends Object
{

	/**
	 * The validators as an array
	 *
	 * @var array
	 */
	private $validators = array();

	/**
	 * the errors as an array
	 *
	 * @var array
	 */
	private $errors = null;

	/**
	 * the input config vars
	 *
	 * @var array
	 */
	private $config = "";

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

		parent::__construct();

		/*
		 * Set input configurations
		 */

		//Set the element id, if id is not provied use form_id_$name instead
		$this->config("id", (empty($id)) ? 'form_id_'.$name : $id);

		//Set the element name
		$this->config("name", $name);

		//Set the label for the element, if $label not provided use $name instead
		$this->config("label", $label);

		//It can happen that an input will override config('label') so to have the original label store it
		$this->config("orig_label", (empty($label)) ? $name : $label);

		//Set the element description
		$this->config("description", $description);

		//set the value
		$this->config("value", $value);

		//Add the $class to our css class array
		$this->config_array("css_class", $class);

		/*
		 * Parse the name string as parameter, so we can check the value_array against the correct values
		 * this is needed because we could have with check/radio-boxes name's like field[] or checkfield[key1][kery2]
		 */
		parse_str($this->config("name")."=1", $parse_str);

		//Check if we have posted this key
		$posted_var = $this->key_is_set($parse_str);

		if ($posted_var !== null) {
			//Set the value if the key was posted
			$this->config("value", $posted_var);

			//Also we provide other elements the possibility to check if the value was realy posted
			$this->config("key_is_set", true);
		}

		//initalize the input
		$this->init();
	}

	/**
	 * re-init the check if the element was posted.
	 */
	public function reinit() {
		/*
		 * Parse the name string as parameter, so we can check the value_array against the correct values
		 * this is needed because we could have with check/radio-boxes name's like field[] or checkfield[key1][kery2]
		 */
		parse_str($this->config("name")."=1", $parse_str);

		//Check if we have posted this key
		$posted_var = $this->key_is_set($parse_str);

		if ($posted_var !== null) {
			//Set the value if the key was posted
			$this->config("value", $posted_var);

			//Also we provide other elements the possibility to check if the value was realy posted
			$this->config("key_is_set", true);
		}

		//initalize the input
		$this->init();
	}

	/**
	 * Initialize the object, this is used if an input do not want to call this
	 * __construct (AbstractHtmlInput::__constrcut()) but want the object functionality
	 *
	 */
	public function load_core() {
		parent::__construct();
	}

	/**
	 * Disable an element
	 *
	 * @param boolean $boolean
	 *   wether this element is disabled or not
	 */
	public function disabled($boolean = true) {

		$this->config("disabled", $boolean);
	}

	/**
	 * Add a validator
	 *
	 * @param AbstractHtmlValidator &$validator
	 *   the validator
	 * @param boolean $is_valid
	 *   if this validator is always be valid (optional, default = false)
	 */
	public function add_validator(AbstractHtmlValidator &$validator, $is_valid = false) {

		//Set the current value to the validator
		$validator->set_value($this->config("value"));

		$this->set_validator_error_message($validator);

		//If we want that this validators is always valid set the validator to be always valid
		if ($is_valid == true) {
			$validator->set_valid();
		}

		/*
		 * Add the validator to our validator list
		 * There can be only one validator of the same type be set
		 */
		$this->validators[$validator->get_type()] = &$validator;
	}

	/**
	 * check if input has the given validator
	 *
	 * @param string $validator
	 *   validator name
	 *
	 * @return true if yes, else false
	 */
	public function has_validator($validator) {

		//If validator is set return true
		if (isset($this->validators[$validator])) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if all validators are valid
	 *
	 * @return boolean on success true, else false
	 */
	public function is_valid() {

		//Check if an validator exist, if not return true
		if (is_array($this->validators) && count($this->validators) > 0) {
			//Loop through all validators
			/* @var $validator AbstractHtmlValidator */
			foreach ($this->validators AS $validator) {

				//Get the validation status of that validator
				$tmp_valid = $validator->is_valid();
				//If the validator is not valid add the validator error message and return false
				if ($tmp_valid !== TRUE) {
					$this->errors[] = $validator->get_error();
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Get all errors
	 * The errors will be set after checking all validators
	 *
	 * @return array ther errors
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	function get_tpl_vars() {

		//Setup params
		$return_arr['name'] = $this->config("name");
		$return_arr['label'] = $this->config("label");
		$return_arr['value'] = $this->config("value");
		$return_arr['id'] = $this->config("id");
		$return_arr['style'] = $this->config("style");

		/*
		 * Get the CSS-Classes, check if it is an array if not we provide no css-classes,
		 * else we implode it with a whitespace
		 */
		$css_class = $this->config_array("css_class");
		if (!is_array($css_class)) {
			$css_class = array();
		}
		$return_arr['class'] = implode(" ", $css_class);
		$return_arr['other|clear'] = $this->config("other");

		//If this element has a required validator we provide the validate html property
		if ($this->has_validator("RequiredValidator")) {
			$return_arr['other|clear'] .= " validate=default require='".t("This field is required")."'";
		}

		//If element should be disabled provide the disabled property
		if ($this->config("disabled")) {
			$return_arr['other|clear'] .= " disabled='disabled'";
		}

		//Return the template variables
		return $return_arr;
	}

	/**
	 * Returns the HTML-Code string for the element
	 *
	 * @return string the HTML code for the element
	 */
	public function fetch() {

		//Get the main template for the element
		$tmp_tpl = $this->config("template");

		//Loop through all available template variables
		foreach ($this->get_tpl_vars() AS $k => $v) {

			//Init the replacement variables (the replaced value)
			$replacement = "";

			//Check if we have modifiers within our template variables
			if (strpos($k, "|") > -1) {
				//We HAVE modifiers so get all.
				$modifiers = explode("|", $k);

				//Replace the current key with the realy used one without the modifers
				$k = $modifiers[0];

				//Unset the first index because this is not an modifier, it is the original key
				unset($modifiers[0]);
			}

			//If the value is not empty
			if (!empty($v) || $v === 0 || $v === "0") {

				//Check if we have modifiers
				if (isset($modifiers) && is_array($modifiers)) {
					//Pre-init if we do not want htmlspecialchars
					$clear = false;
					foreach ($modifiers AS $modifier) {

						switch ($modifier) {

							//We do not want to call htmlspecialchars on the value
							case 'clear':
							case 'clean': $clear = true;
								break;

							//Want uppercase values
							case 'upper': $v = strtoupper($v);
								break; #
							//
							//Want lowercase values
							case 'lower': $v = strtolower($v);
								break;
						}
					}

					//Only do htmlspecialchars if we want it.
					if ($clear == false) {
						$v = " ".$k."=\"".htmlspecialchars($v)."\"";
					}
				}
				//If we have no modifiers we also want htmlspecialchars, because this is the normal behaviour.
				else {
					$v = " ".$k."=\"".htmlspecialchars($v)."\"";
				}

				//Setup our replacement variable
				$replacement = $v;
			}

			//Replace the current template key with the replacment.
			$tmp_tpl = str_replace("{".$k."}", $replacement, $tmp_tpl);
		}

		//Return the HTML-Input string.

		//first the the label string if not empty
		$return = $this->get_label();
		//Provide a suffix which we can configurate
		$suffix = $this->config("suffix");
		if(!empty($suffix)) {
			$return .= $suffix;
		}

		//Append the main input template string and the followed description
		$return .= $tmp_tpl.$this->get_description();
		return $return;
	}

	/**
	 * get or set config values
	 *
	 * @param string $k
	 *   the key
	 * @param string $v
	 *   the value as a string, if not set current value will be returned (optional, default = NS)
	 *
	 * @return mixed the value for the key as a string or if in set-mode return true, if a key is not set, return false
	 */
	public function config($k, $v = NS) {

		//Check if we want to return a value
		if ($v === NS) {
			//Check if we want to return the label for the element, if so and the element has an required validator
			//We return the label including a form-required star
			$required = "";
			if ($k == "label") {
				if ($this->has_validator("RequiredValidator")) {
					$required = '<span title="This field is required." class="form-required">*</span>';
				}
			}
			else if ($k == "label|pure") {
				$k = "label";
			}

			//If we want to return a value which is not set, return false
			if (!isset($this->config[$k])) {
				return false;
			}

			//Append the required-star if present
			if (!empty($required)) {
				return $this->config[$k].$required;
			}

			//Return the config key
			return $this->config[$k];
		}
		else {

			//We are in set mode


			if ($k == "id") {
				$v = str_replace("][", "_", $v);
				$v = str_replace("[", "_", $v);
				$v = str_replace("]", "", $v);
			}

			/**
			 * Because a browser can not handle form values where the input key has dots,
			 * We replace all dots with underscores
			 * It will only replace the "variable"-part, if an array (field[][]) was provided
			 * only the variable part will be replaced because array index keys can have dots
			 */
			if ($k == "name") {
				if (preg_match("/^([^\[]+)(.*)?$/is", $v, $matches)) {
					$v = str_replace(".", "_", $matches[1]).$matches[2];
				}
				$this->reinit_validators();
			}
			/**
			 * If we want to set the label of the element we need to add also the lable_error to this value
			 * This is needed because if the element has a required validator the returning label-string will have
			 * a required star HTML-Text which we do not want within an error message
			 */
			else if ($k == "label") {
				$this->config('label_error', $v);
			}


			//Set the value for the given key
			$this->config[$k] = $v;

			/**
			 * If we change the label we must also check if we maybe must change the required validator label error,
			 * We need this after we set the label error, else it will not have the new value and this reset would make no sense.
			 */
			if ($k == "label_error") {
				$this->reinit_validators();
			}
		}
		return;
	}

	/**
	 * Returns the given validator
	 *
	 * @param string $validator_name
	 *   the validator name
	 *
	 * @return AbstractHtmlValidator the validator or null if validator not exists
	 */
	public function &get_validator($validator_name) {
		if($this->has_validator($validator_name)) {
			return $this->validators[$validator_name];
		}
		return null;
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
		if ($val === NS) {

			//Return mode
			//If the given key is not set, return false
			if (!isset($this->config[$key])) {
				return false;
			}

			//Return the value
			return $this->config[$key];
		}
		else {

			//Set mode
			//If we have not set the key before, initialize with an empty array
			if (!isset($this->config[$key]) || !is_array($this->config[$key])) {
				$this->config[$key] = array();
			}

			/**
			 * This key is used as the array index, if we want to add_duplicates microtime will be added so values can
			 * be added twice
			 */
			$index_key = $val;
			if (isset($this->config[$key][$val])) {
				if ($add_duplicates) {
					$index_key .= microtime(true);
				}
				else {
					//We do not want to add duplicates but entry already exists, return true
					return true;
				}
			}

			//Add the value to the config array
			$this->config[$key][$index_key] = $val;
		}
		return;
	}

	/**
	 * Returns the configured or posted value for this element
	 *
	 * @return string the configured/posted value for the element if
	 */
	function __toString() {
		return $this->config("value");
	}


	/**
	 * Returns the label field if label configuration is not empty
	 *
	 * @return string
	 *   the label html code
	 */
	public function get_label() {
		if($this->config("type") == "fieldset") {
			return '';
		}
		$label = $this->config("label|pure");
		if(!empty($label)) {
			return '<div class="form-element-label"><label for="'.$this->config("id").'">'.$this->config("label").':</label></div>';
		}
		return '';
	}

	/**
	 * Returns the field description
	 *
	 * @return string
	 *   the description html code
	 */
	public function get_description() {
		$description = $this->config("description");
		if(!empty($description)) {
			return '<div class="form-element-description">'.$description.'</div>';
		}
		return '';
	}

	/**
	 * Checks a search_array recrusive is the specified end key is set within the value_array.
	 * This comes most time from parse_str function, so for example a element name is
	 * field[some][key] most time it will used as parse_str($name." = 1"); so an array will be build up like:
	 * 'field' => array(
	 * 		'some' => array(
	 * 			'key' => 1
	 * 		)
	 * )
	 *
	 * This array will be checked against the $value_array if this key is also set in $value_array
	 *
	 * @param mixed $search_array
	 *   can be an array or a key
	 * @param array $value_array
	 *   the value array , if left empty $_POST will be used (optional, default = array())
	 *
	 * @return mixed if key is set the value is returned if not it will return null
	 */
	protected function key_is_set($search_array, Array $value_array = array()) {

		//Check if value_array is empty, if use $_POST
		if (empty($value_array)) {
			if (!empty($_POST)) {
				$value_array = $_POST;
			}
			else {
				$value_array = $_GET;
			}
		}

		//If search value is not an array transform it to an array
		if (!is_array($search_array)) {
			$search_array = array($search_array => true);
		}

		//Reset the search array
		reset($search_array);

		//Get the current key
		$key = key($search_array);

		//Get the current value
		$v = current($search_array);

		//Check if the current key is set in the value array, if not we have no match, return false
		if (!isset($value_array[$key])) {
			return null;
		}

		//If current value is not an array it is the last check for that run, return if value array has the current key
		if (!is_array($v)) {
			return $value_array[$key];
		}

		//So we are not finished yet, because current value is an array, check that array with the representive value_array value for the current key
		return $this->key_is_set($v, $value_array[$key]);
	}

	/**
	 * Reinitialize all validators, set the current value and the error message txt's
	 */
	private function reinit_validators() {
		/* @var $validator AbstractHtmlValidator */
		foreach($this->validators AS &$validator) {
			$validator->set_value($this->config("value"));
			$this->set_validator_error_message($validator);
		}
	}

	/**
	 * Reset the validator error message
	 *
	 * @param AbstractHtmlValidator $validator
	 *   the validator
	 */
	private function set_validator_error_message(AbstractHtmlValidator &$validator) {
		//Add default invalid messages for specific validators
		if ($validator instanceof EmailValidator) {
			$validator->set_error(t("The field \"@field\" is not a valid email.", array("@field" => $this->config("label_error"))));
		}
		else if ($validator instanceof RequiredValidator) {
			$validator->set_error(t("The field \"@field\" is required.", array("@field" => $this->config("label_error"))));
		}
		else if ($validator instanceof LengthValidator) {
			//Get the minimum and maximum length option to provide it within the error message
			$options = $validator->get_options();
			if (!isset($options['min'])) {
				$options['min'] = 0;
			}
			if (!isset($options['max'])) {
				$options['max'] = strlen($validator->get_value());
			}
			$validator->set_error(t("The field \"@field\" must have a minimum of @min and a maximum of @max chars.", array("@field" => $this->config("label_error"), '@min' => $options['min'], '@max' => $options['max'])));
		}
		else if ($validator instanceof IsNumberValidator) {
			$validator->set_error(t("The field \"@field\" contains illegal characters, only numbers allowed.", array("@field" => $this->config("label_error"))));
		}
	}

	/**
	 * Initialize the input
	 */
	abstract function init();
}

?>