<?php

/**
 * Provides a field group which contains 2 textfields one for link text and one for the link destination
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.field_groups
 */
class FieldGroupLink extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);
		$this->add_field(new Textfield("text", '', t("text")));
		$this->add_field(new Textfield("link", '', t("url"), t('please provide for external urls the full url including http(s)://')));
	}

	public function get_template(Array &$elements = null) {


		if($elements == null) {
			$text_input = $this->get_field('text');
			$link_input = $this->get_field('link');
		}
		else {
			$text_input = &$elements['text'];
			$link_input = &$elements['link'];
		}

		if($this->max_value == 1) {

			$required_string = '';
			if($this->required == true) {
				$required_string = '<span title="This field is required." class="form-required">*</span>';
			}

			//Setup label for link text
			$text_input->config("label", $this->label." ".$text_input->config("label|pure").$required_string);

			//Setup label for link link
			$link_input->config("label", $this->label." ".$link_input->config("label|pure").$required_string);
		}

		//Build up our input forms
		$text = "";
		$text .= '<div>';
		$text .= '	<div style="float:left; width:50%;">'.$text_input->fetch().'</div>';
		$text .= '	<div style="float:left; width:50%;">'.$link_input->fetch().'</div>';
		$text .='	<div style="clear:left;"></div>';
		$text .= '</div>';
		$text .= '</fieldset>';
		return $text;
	}

}

?>