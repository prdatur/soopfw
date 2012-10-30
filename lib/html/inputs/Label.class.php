<?php
/**
 * This class provides a label
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Inputs
 */
class Label extends AbstractHtmlInput
{

	/**
	 * init the input
	 */
	public function init() {
		$this->config("label", "");
		$this->config("template",'<div {id}{name}{class}{style}{other}>{value}</div>');
	}

	/**
	 * Get Templates vars
	 *
	 * @return array Addidtional template vars as an array
	 */
	function getTplVars() {
		$this->config("label", "");
		$returnArr = parent::getTplVars();
		unset($returnArr['value']);
		$returnArr['value|clear'] = $this->config("value");
		return $returnArr;
	}
}

