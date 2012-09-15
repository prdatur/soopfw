<?php

/**
 * Provides a field group which contains textfields which should displayed within ul li's
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.field_groups
 */
class FieldGroupList extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values the prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);
		$this->add_field(new Textfield("list", '', t("item")));
	}

	public function get_template(Array &$elements = null) {

		if($elements == null) {
			$list_input = $this->get_field('list');
		}
		else {
			$list_input = &$elements['list'];
		}

		if($this->max_value == 1) {
			$list_input->config('label', $this->get_label());
		}
		return $list_input->fetch();
	}

}

