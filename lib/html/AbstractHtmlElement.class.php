<?php

/**
 * Provide an abstract class for an HTML-Element (Like a Form which containes AbstractHtmlInput's)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form
 */
abstract class AbstractHtmlElement extends Object
{

	/**
	 * Assign the form to smarty
	 *
	 * @param string $name 
	 *   The smarty variable
	 */
	public function assign_smarty($name) {
		$this->smarty->assign_by_ref($name, $this);
	}

}

?>