<?php

/**
 * Provides an abstract class which are used to represent a content type field group.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Field groups
 */
abstract class AbstractFieldGroup extends Object
{
	/**
	 * Stores all elements for this group.
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * This is a container for multi value groups.
	 *
	 * @var array
	 */
	protected $multi_container = array();

	/**
	 * The label for this element.
	 *
	 * @var string
	 */
	protected $label = "";

	/**
	 * The max value for this element.
	 *
	 * @var int
	 */
	protected $max_value = 1;

	/**
	 * If the field group is required or not.
	 *
	 * @var boolean
	 */
	protected $required = false;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id = "";

	/**
	 * This holds the prefilled values within load/save mode.
	 * If posted, it will not used, the posted values will be used instead.
	 *
	 * @var array
	 */
	private $values = array();

	/**
	 * Holds all group specific config values.
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * The field name for default processing "get_template" please see set_field_name() for more information.
	 *
	 * @var string
	 */
	private $field_name = '';

	/**
	 * Constructor
	 *
	 * @param array $values
	 *   The prefilled values should be same as _POST after submitting. (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct();
		if (!empty($values)) {
			$this->values = $values;
		}
	}

	/**
	 * Set the id and name prefix for all elements.
	 *
	 * If $pre_fill is set to true it will also set the element value.
	 *
	 * @param string $prefix
	 *   the id prefix.
	 * @param int|string $index
	 *   the element index for multi values. (optional, default = "")
	 * @param boolean $pre_fill
	 *   whether we want to prefill data on a single element group or not. (optional, default = false)
	 */
	public function set_prefix($prefix, $index = "", $pre_fill = false) {
		// Setip our prefix
		$this->id = $prefix;

		// Checl if we want to prefill, if so check if we have values for the provided prefix.
		if ($pre_fill == true) {
			$group_values = array();
			if (isset($this->values[$this->id])) {
				$group_values = current($this->values[$this->id]);
			}
		}
		/* @var $element AbstractHtmlInput */
		foreach ($this->elements AS &$element) {
			// Check if we can get the original name for the element. (before we changed it)
			$original_id = $element->config("original_name");

			// If not, get the name and set this name also as the current original_name
			if (empty($original_id)) {
				$original_id = $element->config("name");
				$element->config("original_name", $original_id);
			}

			// Set the prefilled value if we have a value and want to pre fill.
			if ($pre_fill == true && isset($group_values[$original_id])) {
				$element->config("value", $group_values[$original_id]);
			}

			// Change the id and the name.
			$element->config("id", $prefix . "_" . $original_id . "_" . $index);
			$element->config("name", $prefix . "[" . $index . "][" . $original_id . "]");

			// Because we changed the name, we maybe have posted the value but with the initial name it could not be
			// recognized as the value for "this" element, after re initializing we have the correct values.
			$element->reinit();
		}
	}

	/**
	 * Set the label for this element.
	 *
	 * @param string $label
	 *   the label.
	 */
	public function set_label($label) {
		$this->label = $label;
	}

	/**
	 * Returns the label for this group, if required is set to true it will also append the required star.
	 *
	 * @return string the label.
	 */
	public function get_label() {
		$label = $this->label;
		if ($this->required == true) {
			$label .= '<span title="This field is required." class="form-required">*</span>';
		}
		return $label;
	}

	/**
	 * Set if the element is required or not.
	 *
	 * @param string|boolean|int $value
	 *   provide 'yes' or 'no' as a string, 1 as an int or true as a boolean value to set the group required, all other
	 *   provided values will set it to false.
	 */
	public function set_required($value) {
		$this->required = ($value === 'yes' || $value === 1 || $value === true) ? true : false;
	}

	/**
	 * Set the max values for this element.
	 *
	 * Provide 0 to have unlimited values this will also setup all prefilled data and
	 * add posted elements on multi elements.
	 *
	 * @param int $value
	 *   the max values.
	 */
	public function set_max_value($value) {

		// First get how much values we want (0 = unlimited)
		$this->max_value = (int) $value;

		// We need only check the very complex things if we have multi fields and we did not set them up before.
		if ($value != 1 && empty($this->multi_container)) {

			// Check if we have posted this group to determine which prefill values we need to use.
			// (Default or the posted ones)
			$posted = $this->check_if_posted();

			// If not posted use default ones, else the posted.
			if (!$posted) {
				$fill_values = $this->values;
			}
			else {
				$fill_values = $_POST;
			}

			// The most complex things are with unlimited fields.
			if ($value == 0) {

				// First setup an index counter which will be used to get an orderd list.
				$index = 0;

				// If we have posted it and have values which wants to be filled, do it.
				if ($posted || !empty($fill_values)) {

					// First reset the internal array counter to the first position.
					reset($this->elements);

					// This variable to determine how much "groups" we need to add.
					$max_value = 0;

					// We will determine the count by our prefill values.
					if (isset($fill_values[$this->id])) {
						$max_value = count($fill_values[$this->id]);
					}

					/* @var $element AbstractHtmlInput */

					// Now we want to re-add all posted groups
					//
					for ($i = 0; $i < $max_value; $i++) {

						// Setup the group inputs.
						$elements = array();
						foreach ($this->elements AS $k => $element_tmp) {
							$element = clone $element_tmp;
							$original_id = $element->config("original_name");
							$element->config("id", $this->id . "_" . $original_id . "_" . $index);
							$element->config("name", $this->id . "[" . $index . "][" . $original_id . "]");
							$element->reinit();
							$element->config("value", $fill_values[$this->id][$i][$original_id]);
							$elements[$k] = $element;
						}

						// Then we check if we have a value posted (maybe we posted the keys but left the values empty.
						// If we have left empty the values we do NOT want to add it to our container.
						$add = false;
						foreach ($elements AS $element) {

							// Check if we have a value.
							$element_value = $element->config("value");
							if (!empty($element_value)) {

								// On Filefields we have a special behaviour because we also want to check
								// if the current value which is the file id for a fileObj exist within our files
								// If it does not exist anymore we also want the element not within the container.
								if ($element instanceof Filefield) {
									$fobj = new MainFileObj($element_value);
									if (!$fobj->load_success()) {
										break;
									}
								}

								// All fine, we want this group so set the $add to true, because we have found a
								// positive element we further do not need to process this group.
								$add = true;
								break;
							}
						}

						// Add it if we want it.
						if ($add == true) {
							$this->multi_container[] = $elements;
							$index++;
						}
					}
				}

				// We want directly one empty group, so add it.
				$elements = array();
				foreach ($this->elements AS $k => $element_tmp) {
					$element = clone $element_tmp;
					$original_id = $element->config("original_name");
					$element->config("id", $this->id . "_" . $original_id . "_" . $index);
					$element->config("name", $this->id . "[" . $index . "][" . $original_id . "]");
					$element->reinit();
					$element->config("value", "");
					$elements[$k] = $element;
				}

				$this->multi_container[] = $elements;
			}
			else {
				// If we have a fixed size of groups, add all of them at once.

				/* @var $element AbstractHtmlInput */
				for ($i = 0; $i < $value; $i++) {
					$elements = array();
					foreach ($this->elements AS $k => &$element_tmp) {
						$element = clone $element_tmp;
						$original_id = $element->config("original_name");
						$element->config("id", $this->id . "_" . $original_id . "_" . $i);
						$element->config("name", $this->id . "[" . $i . "][" . $original_id . "]");

						$element->reinit();
						$elements[$k] = $element;
					}

					$this->multi_container[] = $elements;
				}
			}
		}
	}

	/**
	 * Returns whether the field group has valid values or not.
	 * Currently this will only check for required validator.
	 *
	 * @return boolean true if field group is valid, else false.
	 */
	public function is_valid() {

		// Return true if it is not required.
		if (!$this->required) {
			return true;
		}

		// If the group was not posted, return false.
		if (!$this->check_if_posted()) {
			return false;
		}

		// Check if we have a multi field group.
		if ($this->max_value != 1) {
			$valid = false;

			// Loop through all container elements and within this container all inputs.
			// If one of them is not empty, the group is valid
			foreach ($this->multi_container AS &$elements) {


				$group_is_valid = false;
				/* @var $element AbstractHtmlInput */
				foreach ($elements AS &$element) {
					$value = $element->config("value");
					if (!empty($value)) {
						$group_is_valid = true;
						break;
					}
				}
				if ($group_is_valid) {
					$valid = true;
					break;
				}
			}
		}
		// If we have not a multi field group, just loop through all inputs and if one of them is not empty
		// the group is valid.
		else {
			$valid = false;
			/* @var $element AbstractHtmlInput */
			foreach ($this->elements AS &$element) {
				$value = $element->config("value");
				if (!empty($value)) {
					$valid = true;
					break;
				}
			}
		}

		// Setup required message if the group is invalid.
		if (!$valid) {
			$this->core->message(t("The field \"@field\" is required.", array(
				"@field" => $this->label,
			)), Core::MESSAGE_TYPE_ERROR);
		}
		return $valid;
	}

	/**
	 * Add all inputs for this field group to the given form.
	 *
	 * @param Form &$form
	 *   the form.
	 */
	public function add_element_to_form(Form &$form) {
		// If we have more than 1 element we need to loop through the multi_container,
		if ($this->max_value != 1) {
			foreach ($this->multi_container AS &$elements) {
				foreach ($elements AS &$element) {
					$form->add($element);
				}
			}
		}
		// else we just add the elements.
		else {
			foreach ($this->elements AS &$element) {
				$form->add($element);
			}
		}
	}

	/**
	 * Get the html string for this element.
	 *
	 * This function is needed because we must wrap the get_element method
	 * if we have more than one element
	 *
	 * @return string The parsed html for this field.
	 */
	public function get_html() {

		// If we have just one element, direct return the input html template.
		if ($this->max_value == 1) {
			return $this->get_template();
		}
		// We need to do little things more on multi fields.
		else {
			// Because we have more than one value, we want to sort it through table dnd.
			$this->core->add_js("/js/jquery_plugins/jquery.tablednd.js");

			// Build up the wrapping HTML to be able to sort the elements.
			$html = "<div class='form-element-label'>" . $this->get_label() . "</div>";
			$html .= "<table class='tablednd ui-widget-content' id='add_more_container_" . $this->id . "'>";
			$html .= "	<tbody>";
			foreach ($this->multi_container AS &$elements) {
				$html .= $this->get_another_item($elements);
			}
			$html .= "	</tbody>";

			// And if we have unlimited values, provide the add more button.
			if ($this->max_value == 0) {
				$html .= "	<tfoot>
								<tr>
									<td></td>
									<td><div class='form_button add_another_item' did='" . $this->id . "'>" . t("add another item") . "</div></td>
								</tr>
							</tfoot>";
			}
			$html .= "</table>";
			return $html;
		}
	}

	/**
	 * Returns the string for another item in a multi valued field.
	 * If $elements are not provided it will create default items, if provided it will use the provided ones
	 *
	 * @param array $elements
	 *   the elements for this field group. (optional, default = null)
	 *
	 * @return string The element html code which will be added to our multi field table.
	 */
	public function get_another_item(Array &$elements = null) {
		$html = "	<tr>";
		$html .= '		<td class="handle_cell" style="width:20px;vertical-align:top;padding-top:5px;padding-left:5px;"><a class="tabledrag-handle" href="javascript:void(0);" title="' . t("drag and drop to move") . '"><div class="handle">&nbsp;</div></a></td>';
		$html .= "		<td>" . $this->get_template($elements) . "</td>";
		$html .= "	</tr>";
		return $html;
	}

	/**
	 * Adds the given element to our element list
	 *
	 * @param AbstractHtmlInput $element
	 *   the element to be added
	 */
	protected function add_field(AbstractHtmlInput &$element) {

		// Set the field name for "get_template()" default processing.
		$this->field_name = $element->config('name');

		// Add the element.
		$this->elements[$this->field_name] = $element;
	}

	/**
	 * Returns the given AbstractHtmlInput
	 *
	 * @param string $fieldname
	 *   the fieldname.
	 *
	 * @return AbstractHtmlInput the element or if it is not found boolean false.
	 */
	protected function &get_field($fieldname) {
		if (isset($this->elements[$fieldname])) {
			return $this->elements[$fieldname];
		}
		$false = false;
		return $false;
	}

	/**
	 * Returns whether this field group was posted or not.
	 *
	 * @return boolean Returns true if field group was posted, else false.
	 */
	private function check_if_posted() {
		return isset($_POST[$this->id]);
	}

	/**
	 * Set the group specific config values.
	 *
	 * @param array $config
	 *   The config array.
	 */
	public function set_config(Array $config) {
		$this->config = $config;
	}

	/**
	 * Parses all provided values for this field group.
	 *
	 * @param array $values
	 *   The values for this field group id.
	 */
	public static function parse_value(&$values) {

	}

	/**
	 * This method will be called to get additional field type configs.
	 *
	 * You have to use the provided $form by reference to add field type specific config values.
	 * This configurated values can be accessed through the database field "config" within the field group table entry.
	 *
	 * @param Form $form
	 *   The form where we need to add our config parameters.
	 */
	public function config(Form &$form) {

	}

	/**
	 * Returns the template for this group.
	 *
	 * @param array &$elements
	 *   If null provided fresh new input fields will be used, else the provided one will be used.
	 *   (optional, default = null)
	 *
	 * @return string The parsed html, will return an empty string if input field was not found.
	 */
	public function get_template(Array &$elements = null) {
		// Get the elements.
		if ($elements == null) {
			$input = $this->get_field($this->field_name);
		}
		else {
			$input = &$elements[$this->field_name];
		}

		// Return an empty string because we could not find a valid input field.
		if (empty($input)) {
			return '';
		}
		// If we have just one value, we need to set the label on the element, else it would be inserted within the
		// wrapper html.
		if ($this->max_value == 1) {
			$input->config('label', $this->get_label());
		}

		return $input->fetch();
	}

	/**
	 * If we have just one element and no special behaviours which we need to to within get_template we just can
	 * set the name of the defined field.
	 *
	 * This will be used to process default behaviour on get_template
	 *
	 * Normally you do not need to call this, because the "add" method will read out the name and stores it.
	 *
	 * @param string $name
	 *   The name of the element.
	 */
	protected function set_field_name($name) {
		$this->field_name = $name;
	}

}