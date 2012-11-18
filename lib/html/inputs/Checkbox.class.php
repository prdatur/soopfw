<?php

/**
 * Provides a HTML-Checkbox
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Checkbox extends AbstractHtmlInput
{

	/**
	 * The Checkbox value
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The Checkbox label
	 *
	 * @var string
	 */
	public $label;

	/**
	 * constructor
	 *
	 * @param string $name
	 *   the input name
	 * @param string $value
	 *   the input value
	 * @param string $checkvalue
	 *   the check value whether to preselect this element or not
	 * @param string $label
	 *   the input label (optional, default='')
	 * @param string $description
	 *   the input description (optional, default = '')
	 * @param string $class
	 *   the input css class (optional, default = '')
	 * @param string $id
	 *   the input id (optional, default = '')
	 */
 	public function __construct($name, $value, $checkvalue, $label = "", $description = "", $class = "", $id = "") {

		/**
		 * Set the label before parent construct is called, because the construct will call the init function
		 * which needs the label information
		 */
		if (!empty($label)) {
			$this->label = $label;
		}

		//Setup default element parameters
		parent::__construct($name, $value, $label, $description, $class, $id);

		//Reset config key because we have our own label and value managment
		$this->config("label", "");

		//Set our own value and label values
		$this->value = $value;

		//If we have not really posted, we must clear the value which was set by parent constructor and add the pre_value
		if ($this->config("key_is_set") != true) {
			$this->config("value", '');
			$this->config("pre_value", $checkvalue);
		}
	}

	/**
	 * Set the label text (inner checkbox, not the hole input top label).
	 * To change the input top label use ...->config('label', '...')
	 *
	 * @param string $label
	 *   The new label text.
	 */
	public function set_label($label) {
		$this->label = $label;
		$this->init();
	}

	/**
	 * Returns the own element value
	 *
	 * @return string the value
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Init the input
	 */
	public function init() {
		//Setup the label for our checkbox if it is not empty
		$label = "";
		if (!empty($this->label)) {
			$label = "<label for=\"{clearId}\">{label_own}</label>";
		}
		//The hidden input is used as a work around to have also the key within the post array if checkbox was not submitted
		//This "hack" works within FF (win/linux), IE 9 and Chrome (win/linux) older version of IE are NOT tested.
		$this->config("template", '<input type="hidden" {name} value="" />'."<input type=\"checkbox\" {name}{value}{id}{class}{style}{other}{checked}/>".$label);
	}

	/**
	 * Get templates vars
	 *
	 * @return array all template variables
	 */
	public function get_tpl_vars() {

		//Pre init the template variables with the default values
		$conf = parent::get_tpl_vars();

		//Set our own value
		$conf['value'] = $this->value;

		//We should check if we have posted, if not we use the pre_value to determine if we must precheck the checkbox
		$checkval = $this->config("value");
		if (empty($checkval) && !$this->config("key_is_set")) {
			$checkval = $this->config("pre_value");
		}

		//Set the checked value
		$conf['checked'] = ($checkval == $this->value || $this->config("checked")) ? ' checked="checked"' : '';

		//If label is not empty we provide the label information
		if (!empty($this->label)) {
			$conf['label_own|clear'] = $this->label;
			$conf['clearId|clear'] = $this->config("id");
		}
		return $conf;
	}

}

