<?php

/**
 * Provides a field group which contains text areas
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.field_groups
 */
class FieldGroupText extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);
		$this->add_field(new Textarea("text", '', t("text")));
	}

	public function get_template(Array &$elements = null) {

		if($elements == null) {
			$text_input = $this->get_field('text');
		}
		else {
			$text_input = &$elements['text'];
		}

		if($this->max_value == 1) {
			$text_input->config('label', $this->get_label());
		}

		return $text_input->fetch();
	}

}

