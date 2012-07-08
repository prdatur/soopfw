<?php

/**
 * Provides a HTML-Fieldset
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form.Inputs
 */
class Fieldset extends AbstractHtmlInput
{

	/**
	 * Construct
	 *
	 * Special notes in "inner" parameter
	 * If we want to place this fieldset in a maybe previous created fieldset we MUST
	 * provide the parameter as a boolean true, else please also not if your are
	 * for example in a depth of 4 inner fieldsets and the next fieldset within the
	 * second depth fieldset you need to count how often you need to go "back"
	 * so for current 4 and want to 2, provide 2, current 6 and want to 1 provide 5.
	 * 0 will just place it within the current depth after the last one.
	 *
	 * @param string $id 
	 *   the fieldset id
	 * @param string $label 
	 *   the label  (optional, default = '')
	 * @param string $description 
	 *   the description for this fieldset (optional, default = '')
	 * @param mixed $inner 
	 *   int for depth change and true to place it within the previous one (optional, default = 0)
	 */
 	public function __construct($id, $label = "", $description = "", $inner = 0) {
		parent::__construct($id);
		$this->config("id", $id);
		$this->config("label", $label);
		$this->config("description", $description);
		$this->config("inner", ($inner === true) ? 'yes' : $inner);
	}

	/**
	 * init the input
	 * Please not a closing fieldset tag is not provided within the template
	 * it will be placed within the form.tpl, this is needed to provide a simple form
	 * of writing the html form code
	 */
	public function init() {
		$this->config("template", '<fieldset {id}{class}>');
		$this->config("type", 'fieldset');
	}

}

?>