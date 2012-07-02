<?php
// ************************************************************************************//
// * Air Content Managment System v1
// ************************************************************************************//
// * Copyright (c) 2008-2009 Air-Unlimited
// * Web           http://www.air-unlimited.de/
// ************************************************************************************//
// * Air Content Managment System v1 is NOT free software.
// * You may not redistribute this package or any of it's files.
// ************************************************************************************//
// * $Date: 2008-03-31 20:32:15 +0100 (Mo, 31 Mrz 2008) $
// * $Author: Christian Ackermann $
// * $Rev: 1 $
// ************************************************************************************//

/**
 * This class provides a Textfield input<br>
 * @package    libs.form.inputs
 * @author     Christian Ackermann <webmaster@air-unlimited.de>
 * @copyright  2008-2009 Air-Unlimited
 * @version    Release: 1.1
 * @class Textfield
 */
class Label extends AbstractHtmlInput
{

	/**
	* init the input
	*/
	public function init() {
		$this->config("label", "");
		$this->config("template",'<div {id}{name}{class}{style}{other}>{value}</div>');
		$this->config("type",'label');
	}

	/**
	 * Get Templates vars
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

?>