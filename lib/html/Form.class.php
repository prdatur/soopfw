<?php

/**
 * Provide a HTML-Form handler
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form
 */
class Form extends AbstractHtmlElement implements Iterator
{
	/**
	 * Define consts
	 */
	const ELEMENT_SCOPE_VISIBLE = 'visible';
	const ELEMENT_SCOPE_HIDDEN = 'hidden';
	const ELEMENT_SCOPE_BUTTON = 'button';

	/**
	 * If the form is valid or not
	 *
	 * @var boolean
	 */
	private $is_valid;

	/**
	 * The errors
	 *
	 * @var array
	 */
	private $errors;

	/**
	 * If the form was submitted or not
	 *
	 * @var boolean
	 */
	private $is_submit;

	/**
	 * Formular title
	 *
	 * @var string
	 */
	private $title = "";

	/**
	 * The ajax return type handler, default = ''
	 * @var string
	 */
	private $ajax_return_type_handler = "";

	/**
	 * The ajax return type, default = json
	 * @var string
	 */
	private $ajax_return_type = "json";

	/**
	 * The form method, default = POST
	 *
	 * @var string
	 */
	private $method = "POST";

	/**
	 * The form enctype, default = application/x-www-form-urlencoded
	 *
	 * @var string
	 */
	private $enctype = "application/x-www-form-urlencoded";

	/**
	 * The form action, default = PHP_SELF
	 *
	 * @var string
	 */
	private $action = "";

	/**
	 * Determine if form is ajax, if yes dont wrap <form>
	 *
	 * @var boolean
	 */
	private $is_ajax = false;

	/**
	 * the type where to walk through (can be visible, hidden or button)
	 *
	 * @var string
	 */
	private $get_type = "visible";

	/**
	 * the css class which will be appended to all elements
	 *
	 * @var string
	 */
	private $css_class = "inputs";

	/**
	 * Should this form handle the ajax submit?
	 *
	 * @var boolean
	 */
	public $handle_ajax_submit = true;

	/**
	 * The HTML Input elemets
	 *
	 * @var array
	 */
	public $elements;

	/**
	 * The form name
	 *
	 * @var string
	 */
	public $formname;

	/**
	 * Constructor
	 *
	 * @param string $form_name
	 *   The form name used for smarty
	 * @param string $title
	 *   The title for this form, this must be a translated string, it will not translate. (optional, default = '')
	 * @param string $submit
	 *   If a post element with this string exist the form is submitted,
	 *   can be set to "auto" as a special string that will setup
	 *   a unique hidden submit handler field (optional, default = 'auto')
	 * @param string $is_post
	 *   Set to false if you do not want to check the $submit value
	 *   against the $_POST value, only check if $submit is not empty (optional, default = true)
	 */
 	public function __construct($form_name, $title = '', $submit = 'auto', $is_post = true) {
		parent::__construct();

		//Set the form title
		$this->title = $title;

		//Pre init valid element groups
		$this->elements = array(
			self::ELEMENT_SCOPE_VISIBLE => array(),
			self::ELEMENT_SCOPE_HIDDEN => array(),
			self::ELEMENT_SCOPE_BUTTON => array(),
		);

		//Replace all spaces to underscores within the form_name
		$form_name = preg_replace("/\s/is", "_", $form_name);

		//Set the form name
		$this->formname = $form_name;

		//Prinit that this form is valid
		$this->is_valid = 1;

		//Preinit that this form was not submitted
		$this->is_submit = false;

		//Check if we want to validate the form submit through the unique hidden field ot just the $submit value
		if ($submit != 'auto') {

			//We want to check if $submit is not empty or, if the $is_post is set to true, if the $_POST variable has a not empty value for $submit key
			if ($is_post == true) {
				if (!empty($_POST[$submit])) {
					$this->is_submit = true;
				}
			}
			else {
				if (!empty($submit)) {
					$this->is_submit = true;
				}
			}
		}
		else {
			//Create our unique CSRF-Token hidden field which we use as to determine if the form was submitted or not.
			$this->add(new Hiddeninput($form_name.'_submit', $_SESSION['CSRFtoken']));
			if (isset($_POST[$form_name.'_submit']) && $_POST[$form_name.'_submit'] == $_SESSION['CSRFtoken']) {
				$this->is_submit = true;
			}
		}
	}

	/**
	 * Checks the form and assign the errors to smarty.
	 *
	 * @return boolean true if form is submitted and valid or not
	 */
	public function check_form() {

		if (!$this->assigned) {
			$this->assign_smarty();
		}

		$result = $this->is_submitted();
		if ($result == true) {
			//If form was submitted we remove all pre_value configs from all visible elements
			//So the "default" value will not be set again within the elements
			foreach ($this->elements[self::ELEMENT_SCOPE_VISIBLE] AS &$elm) {
				$elm->config("pre_value", "");
			}

			//If form is also not valid, get all errors from all fields and add the error message
			if (!$this->is_valid(false)) {
				foreach ($this->get_errors() AS $field_errors) {
					foreach ($field_errors AS $error) {
						$this->core->message($error, Core::MESSAGE_TYPE_ERROR, $this->is_ajax());
						$result = false;
					}
				}
			}
		}

		//Return if form was submitted and valid or not
		return $result;
	}

	/**
	 * Set wether to self handle ajax submit or not.
	 *
	 * @param boolean $self_handle
	 *   If set to true, no javascript will be added to submit the form via ajax request (optional, default = true)
	 */
	public function custom_ajax_handler($self_handle = true) {
		$this->handle_ajax_submit = !$self_handle;
	}

	/**
	 * Set or get the form css class
	 * The CSS-Class will be added to all current elements
	 *
	 * @param string $class
	 *   the css class (optional, default = NS)
	 *
	 * @return mixed if we are in return mode ($class = NS) return the current css class, else return nothing
	 */
	public function css_class($class = NS) {
		if ($class !== NS) {
			$this->css_class = $class;
			foreach ($this->elements AS &$array) {
				foreach ($array AS &$elm) {
					$elm->config_array("css_class", $class);
				}
			}
			return;
		}
		return $this->css_class;
	}

	/**
	 * Assign the form to smarty.
	 *
	 * @param string $name
	 *   The smarty variable (optional, default = 'form')
	 */
	public function assign_smarty($name = "form") {

		if (empty($this->elements[self::ELEMENT_SCOPE_BUTTON])) {
			$this->add(new Submitbutton('submit', t('Submit')));
		}

		if ($this->is_ajax) {
			//If we are on ajax mode we try to find a submit button to add it as the ajax submit handler
			foreach ($this->elements[self::ELEMENT_SCOPE_BUTTON] AS &$elem) {
				if ($elem->config("type") == "submit") {
					$elem->config_array("css_class", "ajax_submit_handler");
				}
			}
			//We need to find all filefields to set it also to ajax mode if the form is ajax.
			foreach ($this->elements[self::ELEMENT_SCOPE_VISIBLE] AS &$elem) {
				if ($elem->config("type") == "Filefield") {
					$elem->set_ajax(true);
				}
			}
		}
		parent::assign_smarty($name);
	}

	/**
	 * Append the form to smarty.
	 *
	 * @param string $key
	 *   The key for the array
	 * @param string $name
	 *   The smarty variable (optional, default = 'form')
	 */
	public function append_smarty($key, $name = "form") {

		if (empty($this->elements[self::ELEMENT_SCOPE_BUTTON])) {
			$this->add(new Submitbutton('submit', t('Submit')));
		}

		if ($this->is_ajax) {
			//If we are on ajax mode we try to find a submit button to add it as the ajax submit handler
			foreach ($this->elements[self::ELEMENT_SCOPE_BUTTON] AS &$elem) {
				if ($elem->config("type") == "submit") {
					$elem->config_array("css_class", "ajax_submit_handler");
				}
			}
			//We need to find all filefields to set it also to ajax mode if the form is ajax.
			foreach ($this->elements[self::ELEMENT_SCOPE_VISIBLE] AS &$elem) {
				if ($elem->config("type") == "Filefield") {
					$elem->set_ajax(true);
				}
			}
		}
		parent::append_smarty($name, $key);
	}

	/**
	 * Return if the form is an ajax
	 *
	 * @return boolean
	 *   Returns true if form is handled as ajax, else false
	 */
	public function is_ajax() {
		return $this->is_ajax;
	}

	/**
	 * Set if the form should be ajax form or not
	 *
	 * @param boolean $bool
	 *   Wether to determine that this form is ajax or not (optional, default = true)
	 */
	public function set_ajax($bool = true) {
		$this->is_ajax = $bool;
	}

	/**
	 * set or get the ajax return type.
	 *
	 * @param string $type
	 *   the type, if provided should be json or html (optional, default = NS)
	 *
	 * @return mixed if we are in return mode (type = NS) return the current type, else return nothing
	 */
	public function ajax_return_type ($type = NS) {
		if($type != NS) {
			$this->ajax_return_type = $type;
			return;
		}

		if(empty($this->ajax_return_type)) {
			return 'json';
		}
		return $this->ajax_return_type;
	}

	/**
	 * Set or get the ajax return type handler.
	 *
	 * @param string $ajax_return_type_handler
	 *   the handler (js-function name) (optional, default = NS)
	 *
	 * @return mixed if we are in return mode (type = NS) return the current handler, else return nothing
	 */
	public function ajax_return_type_handler ($ajax_return_type_handler = NS) {
		if($ajax_return_type_handler != NS) {
			$this->ajax_return_type_handler = $ajax_return_type_handler;
			return;
		}

		return $this->ajax_return_type_handler;
	}
	/**
	 * Set or get the form action
	 *
	 * @param string $action
	 *   the form action (optional, default = NS)
	 *
	 * @return mixed if we are in return mode (action = NS) return the current action, else return nothing
	 */
	public function action($action = NS) {
		if ($action !== NS) {
			$this->action = $action;
			return;
		}

		if (empty($this->action)) {
			return $_SERVER['REQUEST_URI'];
		}
		return $this->action;
	}

	/**
	 * Set or get the form method
	 *
	 * @param string $method
	 *   the form method (POST, GET) (optional, default = NS)
	 *
	 * @return mixed if we are in return mode (method = NS) return the current method, else return nothing
	 */
	public function method($method = NS) {
		if ($method !== NS) {
			$this->method = $method;
			return;
		}
		return $this->method;
	}

	/**
	 * Set or get the form enctype
	 *
	 * @param string $enctype
	 *   the form enctype (optional, default = NS)
	 *
	 * @return mixed if we are in return mode (enctype = NS) return the current enctype, else return nothing
	 */
	public function enctype($enctype = NS) {
		if ($enctype !== NS) {
			$this->enctype = $enctype;
			return;
		}
		return $this->enctype;
	}

	/**
	 * Returns the form title
	 *
	 * @return string the form title
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set the form title
	 *
	 * @param string $title
	 *   the title
	 */
	public function set_title($title) {
		$this->title = $title;
	}

	/**
	 * Check if form is valid
	 *
	 * @return boolean
	 *   true if valid, else false
	 */
	public function form_valid() {
		if ($this->is_submit && $this->is_valid) {
			return true;
		}
		return false;
	}

	/**
	 * Check if form is submitted
	 * if $submit_element_key is provided it will check if this submit button was clicked.
	 *
	 * @param string $submit_element_key
	 *   the element key to be checked (optional, default = "")
	 * @return boolean true if submitted, else false
	 */
	public function is_submitted($submit_element_key = "") {
		if(empty($submit_element_key)) {
			return $this->is_submit;
		}

		$element = $this->get($submit_element_key, "button");
		if($element !== false && $element instanceof Submitbutton) {
			return $element->is_submitted();
		}
		return false;
	}

	/**
	 * Returns the errors
	 *
	 * @return array
	 *   all errors
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Adds an error
	 * The $element variable is used to mark the element as invalid (add error classes to the element)
	 *
	 * @param string $element
	 *   the element
	 * @param string $msg
	 *   the error message
	 */
	public function add_error($element, $msg) {
		$this->errors[$element] = $msg;
	}

	/**
	 * Remove an input
	 *
	 * @param string $name
	 *   input name
	 * @param string $element_scope
	 *   the input scope, use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 */
	public function remove($name, $element_scope = self::ELEMENT_SCOPE_VISIBLE) {
		unset($this->elements[$element_scope][$name]);
	}

	/**
	 * Add a single input
	 *
	 * @param AbstractHtmlInput &$input
	 *   the AbstractHtmlInput
	 * @param array &$validators
	 *   an array with validators (optional, default = array())
	 */
	public function add(AbstractHtmlInput &$input, $validators = array()) {

		if(!empty($validators)) {
			if(!is_array($validators)) {
				$validators = array($validators);
			}

			foreach($validators AS &$validator) {
				if(!($validator instanceof AbstractHtmlValidator)) {
					continue;
				}
				$input->add_validator($validator);
			}
		}

		//Add the current css_class to the newly added one
		$input->config_array("css_class", $this->css_class());

		//Provide a css class which can be used within JQuery to select all elements for this form
		$input->config_array("css_class", "inputs_".$this->formname);

		//Set a unique id
		$input->config("id", "form_id_".$this->formname."_".$input->config("name"));

		//Check which scope the input should be
		$type = "visible";
		if ($input->config("type") == "hidden") {
			$type = "hidden";
		}
		else if ($input->config("type") == "submit" || $input->config("type") == "button") {
			$input->config_array("css_class", "form_button");
			$type = "button";
		}

		//Set the enctype to the needed multipart/form-data if we have add a Filefield
		if ($input->config("type") == "Filefield") {
			$this->enctype("multipart/form-data");
		}

		//Add the input
		$this->elements[$type][$input->config("name")] = $input;
	}

	/**
	 * Check all input validators.
	 *
	 * @param boolean $assign_errors
	 *   if set to true, the errors will be added directory into core messages (optional, default = false)
	 *
	 * @return boolean true if valid, else false
	 */
	public function is_valid($assign_errors = false) {

		//Set the form to be valid
		$this->is_valid = true;

		//Loop through all available inputs and check if the element is valid
		foreach ($this->elements AS &$elements) {
			foreach ($elements AS $name => &$input) {

				//Check if the elemen is valid, if not we add the errors to our internal error array
				if ($input->is_valid() !== TRUE) {
					$errors = $input->get_errors();
					$this->add_error($name, $errors);

					//If we also want to assign the errors into core messages, do it
					if ($assign_errors == true) {
						foreach ($errors AS $error) {
							$this->core->message($error, Core::MESSAGE_TYPE_ERROR, $this->is_ajax);
						}
					}

					//Add an invalid_input class to the invalid element
					$input->config_array("css_class", 'invalid_input');

					//Set the form validation status to false
					$this->is_valid = false;
				}
			}
		}

		//Return the validation status
		return $this->is_valid;
	}

	/**
	 * Get all wanted elements
	 * If $scope is empty it will return the pure elements array (like array(Form::ELEMENT_SCOPE_VISIBLE = array(visible_elements), Form::ELEMENT_SCOPE_HIDDEN => array(hidden_elements),...)
	 * and if scope is provided it will return all elements within the provided scope
	 *
	 * @param string $scope
	 *   the input scope , use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 *
	 * @return array the element array format [key] = AbstractHtmlInput
	 */
	public function get_array($scope = self::ELEMENT_SCOPE_VISIBLE) {
		if (empty($scope)) {
			return $this->elements;
		}
		return $this->elements[$this->get_type];
	}

	/**
	 * Get all elements with their values
	 *
	 * @param boolean $include_hidden
	 *   Wether to include hidden and submit values within return values (optional, default=false)
	 *
	 * @return array the element array format [key] = value
	 */
	public function &get_values($include_hidden = false) {
		$return = array();

		if ($include_hidden == true) {
			$return_array = $this->elements;
		}
		else {
			$return_array = array($this->elements[self::ELEMENT_SCOPE_VISIBLE]);
		}

		foreach ($return_array as $arr) {
			foreach ($arr as $key => &$obj) {
				$value = $obj->config("value");

				//If the element is an datefield we transform the value to a well formed date string
				if ($obj instanceof Datefield && !empty($value)) {
					$return[$key] = date("Y-m-d H:i:s", strtotime($value));
				}
				else {
					$return[$key] = $value;
				}
			}
		}

		return $return;
	}

	/**
	 * Get the form values as an multidimensional array if name fields like field[key1]
	 *
	 * @param boolean $include_hidden
	 *   set this to true if you want hidden element values (optional, default = false)
	 *
	 * @return array the parsed value array
	 */
	public function &get_array_values($include_hidden = false) {
		$v = $this->get_values($include_hidden);
		array_walk($v, function(&$val, $key) {
			//IMPORTANT to use urlencode function because parse_str expects the value as url encoded if not we have a security issue to provide some bad chars
			$val = $key.'='.urlencode($val);
		});
		parse_str(implode('&', $v), $values);
		return $values;
	}

	/**
	 * This provides the ability to configurate an input element
	 * You can provide any AbstractHtmlInput->config() key for the element
	 *
	 * @param string $name
	 *   the element name
	 * @param array $config
	 *   The configuration array in format array('option' => 'value')
	 * @param string $scope
	 *   the input scope , use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 */
	public function config_element($name, Array $config, $scope = self::ELEMENT_SCOPE_VISIBLE) {
		if (!isset($this->elements[$scope][$name])) {
			return;
		}
		foreach ($config AS $k => $v) {
			$this->elements[$scope][$name]->config($k, $v);
		}
	}

	/**
	 * Set given values
	 *
	 * @param array &$values
	 *   the values as elm_name => value
	 * @param string $scope
	 *   the input scope , use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 */
	public function set_values(&$values, $scope = self::ELEMENT_SCOPE_VISIBLE) {
		foreach ($values as $key => &$value) {
			if (!isset($this->elements[$scope][$key])) {
				continue;
			}
			$obj = &$this->elements[$scope][$key];
			if ($obj->config("type") == 'submit') {
				continue;
			}
			$obj->config("value", $value);
		}
	}

	/**
	 * Set the javascript function to be called if the form is successfully submitted
	 *
	 * @param string $function_name
	 *   the javascript function
	 */
	public function add_js_success_callback($function_name) {
		$this->core->js_config("js_function_callback", $function_name, true);
	}

	/**
	 * Get an element within the scope
	 *
	 * @param string $val
	 *   the element name
	 * @param string $scope
	 *   the input scope , use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 *
	 * @return AbstractHtmlInput the element as an AbstractHtmlInput or false if element not found
	 */
	public function get($val, $scope = self::ELEMENT_SCOPE_VISIBLE) {
		if (array_key_exists($val, $this->elements[$scope]) && is_object($this->elements[$scope][$val])) {
			return $this->elements[$scope][$val];
		}
		return false;
	}

	/**
	 * Get a value for the given element name within the given scope
	 *
	 * @param string $name
	 *   the input name
	 * @param string $scope
	 *   the input scope , use one of Form::ELEMENT_SCOPE_* (optional, default = Form::ELEMENT_SCOPE_VISIBLE)
	 *
	 * @return mixed the value for this value or false if element not exists
	 */
	public function get_value($name, $scope = self::ELEMENT_SCOPE_VISIBLE) {
		$element = $this->get($name, $scope);
		if ($element != false) {
			return $element->config('value');
		}
		return false;
	}

	/**
	 * Set the return elements type for array walk (foreach)
	 *
	 * @param string $type
	 *   the type use one of Form::ELEMENT_SCOPE_*
	 */
	public function get_type($type) {
		$this->get_type = $type;
	}

	/**
	 * Get an element overriding __get
	 *
	 * @param string $name
	 *   the element name
	 *
	 * @return the element as an AbstractHtmlInput or false if element not exists
	 */
	public function __get($name) {
		return $this->get($name, $this->get_type);
	}

	/**
	 * Rewind the elements
	 */
	public function rewind() {
		reset($this->elements[$this->get_type]);
	}

	/**
	 * Returns the current element
	 *
	 * @return AbstractHtmlInput The element
	 */
	public function current() {
		return current($this->elements[$this->get_type]);
	}

	/**
	 * Returns the label for the current element
	 *
	 * @return string the Label
	 */
	public function key() {
		$label = current($this->elements[$this->get_type])->config("label");
		$required = "";
		if (current($this->elements[$this->get_type])->has_validator("RequiredValidator")) {
			$required = '<span title="This field is required." class="form-required">*</span>';
		}
		return $label.$required;
	}

	/**
	 * Advance the internal array pointer of an array
	 *
	 * @return mixed the array value in the next place that's pointed to by the internal array pointer, or false if there are no more elements.
	 */
	public function next() {
		return next($this->elements[$this->get_type]);
	}

	/**
	 * Returns wether the current entry is valid or not
	 *
	 * @return boolean
	 *   Wether the current key is valid or not
	 */
	public function valid() {
		$fields = $this->current() !== false;
		return $fields;
	}

}


