<?php

/**
 * Provides a field group which contains 2 textfields one for link text and one for the link destination
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Field groups
 */
class FieldGroupLink extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values
	 *   the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);

		// Define fields.
		$this->add_field(new Textfield("text", '', t("text")));
		$this->add_field(new Textfield("link", '', t("url"), t('please provide for external urls the full url including http(s)://')));
	}

	/**
	 * Returns the template for this group.
	 *
	 * @param array &$elements
	 *   If null provided fresh new input fields will be used, else the provided one will be used. (optional, default = null)
	 */
	public function get_template(Array &$elements = null) {

		// Get the elements.
		if ($elements == null) {
			$text_input = $this->get_field('text');
			$link_input = $this->get_field('link');
		}
		else {
			$text_input = &$elements['text'];
			$link_input = &$elements['link'];
		}

		// We can only set the label and the required star char if we have only 1 element, else the calling class will
		// do this for us.
		if ($this->max_value == 1) {

			// Setup required char.
			$required_string = '';
			if ($this->required == true) {
				$required_string = '<span title="This field is required." class="form-required">*</span>';
			}

			// Setup label for link text.
			$text_input->config("label", $this->label . " " . $text_input->config("label|pure") . $required_string);

			// Setup label for link link.
			$link_input->config("label", $this->label . " " . $link_input->config("label|pure") . $required_string);
		}

		// Build up our input form html.
		$text = "";
		$text .= '<div>';
		$text .= '	<div style="float:left; width:50%;">' . $text_input->fetch() . '</div>';
		$text .= '	<div style="float:left; width:50%;">' . $link_input->fetch() . '</div>';
		$text .='	<div style="clear:left;"></div>';
		$text .= '</div>';
		return $text;
	}

}

