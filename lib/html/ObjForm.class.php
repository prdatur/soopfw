<?php

/**
 * Provide a HTML-ObjectForm
 * This is similar to normal HTML-Form but provide easy access
 * to provide a fast simple form for an AbstractDataManagement object
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form
 */
class ObjForm extends Form
{

	/**
	 * The object to parse
	 * @var AbstractDataManagment
	 */
	private $object = null;

	/**
	 * An array with validators which will be applied after building form
	 *
	 * @var array
	 */
	public $apply_default_validators = array();

	/**
	 * Construct
	 *
	 * @param AbstractDataManager $obj
	 *   the object from which we build up the form
	 * @param string $title
	 *   The title for this form, this must be a translated string, it will not translate. (optional, default = '')
	 * @param array $element_config
	 *   A configuration array in form array('field_name' => array('option'  => 'value')) (optional, default = array())
	 * @param boolean $force_load_success
	 *   Wether we see the provided object as loaded or use the object load_success state (optional, default = false)
	 */
 	public function __construct(AbstractDataManagment &$obj, $title = "", $element_config = array(), $force_load_success = false) {

		//Init parent construct and provde the form_{table} as the form name
		parent::__construct("form_".$obj->get_dbstruct()->get_table(), $title);
		$this->object = $obj;

		//If we did not provide a loaded object (maybe just new Object()) or we want to forced it to be loaded we set the default values
		if (!$force_load_success && $this->object->load_success() == false) {
			$this->object->set_default_fields();
		}

		//Build up our form based up on the provided object
		$this->get_form();

		//If we provided a configuration for a specific element we config this
		foreach ($element_config AS $name => $config) {
			$this->config_element($name, $config);
		}

		//apply default validators which are setup by get_form
		$this->add_default_validators();
	}

	/**
	 * returns the object
	 *
	 * @return AbstractDataManagment the object
	 */
	public function &get_object() {
		return $this->object;
	}

	/**
	 * Sets the Object
	 *
	 * @param AbstractDataManagment $obj
	 *   the object
	 */
	public function set_object(AbstractDataManagment &$obj) {
		$this->object = $obj;
	}

	/**
	 * Build up our elements based up on the provided object
	 */
	public function get_form() {

		//Get the database struct
		$struct = $this->object->get_dbstruct();

		//loop through all fields
		foreach ($struct->get_struct() AS $field => $field_options) {
			$obj = null;
			/**
			 * If this field is a hidden field, add a hidden input but only if the title is empty
			 * The fact that only empty title are added as hidden can be used to create an hidden field with a required label (* stared)
			 */
			if ($struct->is_hidden_field($field) || empty($field_options['title'])) {
				$obj = new Hiddeninput($field, $this->object->$field, '', "form_id_".$struct->get_table()."_".$field);
				continue;
			}

			//Switch the database field type, and choose an input element for that type
			switch ($field_options['typ']) {
				case PDT_INT:
				case PDT_BIGINT:
				case PDT_SMALLINT:
				case PDT_MEDIUMINT:
				case PDT_TINYINT:
				case PDT_FLOAT:
				case PDT_DECIMAL:
				case PDT_STRING:
					//We have only numbers or strings, add a textfield
					$obj = new Textfield($field, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_PASSWORD:
					$obj = new Passwordfield($field, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_FILE:
					$this->enctype("multipart/form-data");
					$obj = new Filefield($field, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_TEXT:
					$obj = new Textarea($field, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_DATE:
					$obj = new Datefield($field, date("d.m.Y", strtotime($this->object->$field)), '', '', 'datepicker', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_DATETIME:
					$obj = new Datefield($field, date("d.m.Y H:i", strtotime($this->object->$field)), '', '', 'datetimepicker', "form_id_".$struct->get_table()."_".$field);
					break;
				case PDT_ENUM:
					$options = array();
					//The enum options are stored within the additional field config
					foreach ($field_options['additional'] AS $val => $title) {
						$options[$val] = $title;
					}
					$select = new Selectfield($field, $options, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					$obj = $select;

					break;
				case PDT_LANGUAGE_ENABLED:
					$only_enabled = true;
				case PDT_LANGUAGE:
					if(empty($only_enabled)) {
						$only_enabled = false;
					}

					$this->lng->load_language_list('', array('de','fr','it','en'), $only_enabled);
					$obj = new Selectfield($field, $this->lng->languages, $this->object->$field, '', '', '', "form_id_".$struct->get_table()."_".$field);
					break;
			}

			if ($obj != null) {
				//If the required option is set, add the field to the apply default validators array, this will add a required validator to that element
				if ($field_options['required'] == true) {
					$this->apply_default_validators[] = $field;
				}

				//If title is present, set it as the input label
				if (isset($field_options['title'])) {
					$obj->config("label", $field_options['title']);
				}

				//If description is present, set it as the input description
				if (isset($field_options['description'])) {
					$obj->config("description", $field_options['description']);
				}

				//Add the input
				$this->add($obj);
			}
		}
	}

	/**
	 * Insert the object if form is valid
	 *
	 * @return boolean true on success, else false
	 */
	public function insert() {
		if (!$this->is_valid()) {
			return false;
		}
		if (is_object($this->object)) {
			$this->object->set_fields($this->get_values());
			if ($this->object->insert()) {
				if ($this->object->get_dbstruct()->has_auto_increment()) {
					return $this->object->get_last_inserted_id();
				}
				else {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Save or insert the object if form is valid.
	 *
	 * @return boolean true on success, else false
	 */
	public function save_or_insert() {
		if (!$this->is_valid()) {
			return false;
		}
		if (is_object($this->object)) {
			$this->object->set_fields($this->get_values());
			return $this->object->save_or_insert();
		}
		return false;
	}

	/**
	 * Save the object if form is valid.
	 *
	 * @return boolean true on success, else false
	 */
	public function save() {
		if (!$this->is_valid()) {
			return false;
		}
		if (is_object($this->object)) {
			$this->object->set_fields($this->get_values());
			return $this->object->save();
		}
		return false;
	}

	/**
	 * Apply the default validators to the needed fields
	 */
	private function add_default_validators() {
		foreach ($this->apply_default_validators AS $field) {
			$this->elements['visible'][$field]->add_validator(new RequiredValidator());
		}
	}

}

