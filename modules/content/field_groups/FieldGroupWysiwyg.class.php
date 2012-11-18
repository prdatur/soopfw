<?php

/**
 * Provides a field group which contains text areas with a wysiwyg editor
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Field groups
 */
class FieldGroupWysiwyg extends AbstractFieldGroup
{

	/**
	 * Constructor
	 *
	 * @param array $values
	 *   The prefilled values should be same as _POST after submitting (optional, default = array())
	 */
	function __construct(Array $values = array()) {
		parent::__construct($values);

		// Define fields.
		$this->add_field(new Textarea("text", '', t("text"), '', 'wysiwyg_bbcode'));
	}
}