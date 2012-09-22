<?php

/**
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.field_groups
 */
abstract class AbstractFieldGroup extends Object
{

	/**
	 * Stores all elements for this group
	 * @var array
	 */
	protected $elements = array();

	/**
	 * This is a container for multi value groups
	 * @var array
	 */
	protected $multi_container = array();

	/**
	 * The label for this element
	 * @var string
	 */
	protected $label = "";

	/**
	 * The max value for this element
	 * @var int
	 */
	protected $max_value = 1;

	/**
	 * If the field group is required or not
	 * @var boolean
	 */
	protected $required = false;

	/**
	 * the id
	 *
	 * @var string
	 */
	private $id = "";

	/**
	 * This holds the prefilled values within load/save mode
	 * If posted, it will not used, the posted values will be used instead
	 *
	 * @var array
	 */
	private $values = array();

	/**
	 * Constructor
	 *
	 * @param array $values the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct();
		if(!empty($values)) {
			$this->values = $values;
		}
	}

	/**
	 * Set the id prefix for all elements
	 *
	 * @param string $prefix
	 *   the id prefix
	 * @param int|string $index
	 *   the element index for multi values (optional, default = "")
	 * @param boolean $pre_fill
	 *   whether we want to prefill data on a single element group or not (optional, default = false)
	 *
	 * @return void
	 */
	public function set_prefix($prefix, $index = "", $pre_fill = false) {
		$this->id = $prefix;

		if($pre_fill == true) {
			$group_values = array();
			if(isset($this->values[$this->id])) {
				$check_values = $this->values[$this->id];

				foreach($check_values AS $elements) {
					$key = key($elements);
					$group_values[$key] = current($elements);
				}
			}
		}
		/* @var $element AbstractHtmlInput */
		foreach ($this->elements AS &$element) {
			$original_id = $element->config("original_name");
			if(empty($original_id)) {
				$original_id = $element->config("name");
				$element->config("original_name", $original_id);
			}
			if($pre_fill == true && isset($group_values[$original_id])) {
				$element->config("value", $group_values[$original_id]);
			}
			$element->config("id", $prefix."_".$original_id."_".$index);
			$element->config("name", $prefix."[".$index."][".$original_id."]");
			$element->reinit();
		}
	}

	/**
	 * Set the label for this element
	 *
	 * @param string $label the label
	 */
	public function set_label($label) {
		$this->label = $label;
	}

	/**
	 * Returns the label for this group, if required is set to true it will also append the required star
	 * @return string
	 */
	public function get_label() {
		$label = $this->label;
		if($this->required == true) {
			$label .= '<span title="This field is required." class="form-required">*</span>';
		}
		return $label;
	}

	/**
	 * Set if the element is required or not
	 *
	 * @param string $value provide 'yes' or 'no' to set the group required or not.
	 */
	public function set_required($value) {
		$this->required = ($value == 'yes') ? true:false;
	}

	/**
	 * Set the max values for this element
	 * provide 0 to have unlimited values
	 * this will also setup all prefilled data and add posted elements on multi elements.
	 *
	 * @param int $value the max values
	 */
	public function set_max_value($value) {
		$this->max_value = (int)$value;
		if($value != 1 && empty($this->multi_container)) {
			$posted = $this->check_if_posted();

			if(!$posted) {
				$fill_values = $this->values;
			}
			else {
				$fill_values = $_POST;
			}
			if($value == 0) {
				$index = 0;
				if($posted || !empty($fill_values)) {

					reset($this->elements);
					$max_value = 0;
					if(isset($fill_values[$this->id])) {
						$max_value = count($fill_values[$this->id]);
					}

					/* @var $element AbstractHtmlInput */
					for($i = 0; $i < $max_value; $i++) {

						$elements = array();
						foreach($this->elements AS $k => $element_tmp) {
							$element = clone $element_tmp;
							$original_id = $element->config("original_name");
							$element->config("id", $this->id."_".$original_id."_".$index);
							$element->config("name", $this->id."[".$index."][".$original_id."]");
							$element->reinit();
							$element->config("value", $fill_values[$this->id][$i][$original_id]);
							$elements[$k] = $element;
						}
						$add = false;
						foreach($elements AS $element) {
							$element_value = $element->config("value");
							if(!empty($element_value)) {

								if($element instanceof Filefield) {
									$fobj = new MainFileObj($element_value);
									if(!$fobj->load_success()) {
										break;
									}
								}

								$add = true;
								break;
							}
						}
						if($add == true) {
							$this->multi_container[] = $elements;
							$index++;
						}
					}
				}

				$elements = array();
				foreach($this->elements AS $k => $element_tmp) {
					$element = clone $element_tmp;
					$original_id = $element->config("original_name");
					$element->config("id", $this->id."_".$original_id."_".$index);
					$element->config("name", $this->id."[".$index."][".$original_id."]");
					$element->reinit();
					$element->config("value", "");
					$elements[$k] = $element;
				}

				$this->multi_container[] = $elements;
			}
			else {

				/* @var $element AbstractHtmlInput */
				for($i = 0; $i < $value; $i++) {
					$elements = array();
					foreach($this->elements AS $k => &$element_tmp) {
						$element = clone $element_tmp;
						$original_id = $element->config("original_name");
						$element->config("id", $this->id."_".$original_id."_".$i);
						$element->config("name", $this->id."[".$i."][".$original_id."]");

						$element->reinit();
						$elements[$k] = $element;
					}

					$this->multi_container[] = $elements;
				}
			}
		}
	}

	public function is_valid() {

		if(!$this->required) {
			return true;
		}

		if(!$this->check_if_posted()) {
			return false;
		}


		if($this->max_value != 1) {
			$valid = false;
			foreach ($this->multi_container AS &$elements) {


				$group_is_valid = false;
				/* @var $element AbstractHtmlInput */
				foreach ($elements AS &$element) {
					$value = $element->config("value");
					if(!empty($value)) {
						$group_is_valid = true;
						break;
					}
				}
				if($group_is_valid) {
					$valid = true;
					break;
				}
			}
		}
		else {
			$not_valid = true;
			/* @var $element AbstractHtmlInput */
			foreach ($this->elements AS &$element) {
				$value = $element->config("value");
				if(!empty($value)) {
					$not_valid = false;
					break;
				}
			}
			$valid = !$not_valid;
		}

		if(!$valid) {
			$this->core->message(t("The field \"@field\" is required.", array("@field" => $this->label)), Core::MESSAGE_TYPE_ERROR);
		}
		return $valid;
	}

	/**
	 * Add all containing elements to the given form
	 *
	 * @param Form &$form the form
	 */
	public function add_element_to_form(Form &$form) {
		if($this->max_value != 1) {
			foreach ($this->multi_container AS &$elements) {
				foreach ($elements AS &$element) {
					$form->add($element);
				}
			}
		}
		else {
			foreach ($this->elements AS &$element) {
				$form->add($element);
			}
		}
	}

	/**
	 * Get the html string for this element.
	 * This function is needed because we must wrap the get_element method
	 * if we have more than one element
	 *
	 * @return string
	 */
	public function get_html() {

		if ($this->max_value == 1) {
			return $this->get_template();
		}
		else {
			$html = "<div class='form-element-label'>".$this->get_label()."</div>";
			$html .= "<table class='tablednd ui-widget-content' id='add_more_container_".$this->id."'>";
			$html .= "	<tbody>";
			foreach ($this->multi_container AS &$elements) {
				$html .= $this->get_another_item($elements);
			}
			$html .= "	</tbody>";
			if($this->max_value == 0) {
				$html .= "	<tfoot><tr><td></td><td><div class='form_button add_another_item' did='".$this->id."'>".t("add another item")."</div></td></tfoot>";
			}
			$html .= "</table>";
			return $html;
		}
	}

	/**
	 * Returns the string for another item in a multi valued field
	 * If $elements are not provided it will create default items, if provided it will use the provided ones
	 * @param array $elements the elements for this field group (optional, default = null)
	 * @return string
	 */
	public function get_another_item(Array &$elements = null) {
		$html = "	<tr>";
		$html .= '		<td class="handle_cell" style="width:20px;vertical-align:top;padding-top:5px;padding-left:5px;"><a class="tabledrag-handle" href="javascript:void(0);" title="'.t("drag and drop to move").'"><div class="handle">&nbsp;</div></a></td>';
		$html .= "		<td>".$this->get_template($elements)."</td>";
		$html .= "	</tr>";
		return $html;
	}

	/**
	 * Adds the given element to our element list
	 *
	 * @param AbstractHtmlInput $element the element to be added
	 */
	protected function add_field(AbstractHtmlInput $element) {
		$this->elements[$element->config('name')] = $element;
	}

	/**
	 * Returns the given AbstractHtmlInput
	 *
	 * @param string $fieldname the fieldname
	 * @return AbstractHtmlInput the element or if it is not found boolean false
	 */
	protected function &get_field($fieldname) {
		if (isset($this->elements[$fieldname])) {
			return $this->elements[$fieldname];
		}
		return false;
	}

	private function check_if_posted() {
		static $cache = null;
		if($cache != null) {
			return $cache;
		}

		if(!empty($_POST)) {
			if(isset($_POST[$this->id])) {
				$cache = true;
				return true;
			}
			/*foreach ($this->elements AS &$element) {
				if(isset($_POST[$this->id])) {
					$cache = true;
					return true;
				}
			}*/
		}
		$cache = false;
		return false;
	}

	/**
	 * Parses all provided values for this field group.
	 *
	 * @param array $values
	 *   The values for this field group id
	 */
	public static function parse_value(&$values) {

	}

	/**
	 * Returns the template for this group
	 */
	abstract function get_template();
}

