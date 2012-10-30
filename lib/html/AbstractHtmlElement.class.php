<?php

/**
 * Provide an abstract class for an HTML-Element (Like a Form which containes AbstractHtmlInput's)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form
 */
abstract class AbstractHtmlElement extends Object
{

	/**
	 * Determines if the form is assigned to smarty or not.
	 *
	 * @var boolean
	 */
	protected $assigned = false;

	/**
	 * Assign the form to smarty
	 *
	 * @param string $name
	 *   The smarty variable
	 */
	public function assign_smarty($name) {
		$this->smarty->assign_by_ref($name, $this);
		$this->assigned = true;
	}

	/**
	 * Assign the form to smarty
	 *
	 * @param string $name
	 *   The smarty variable
	 */
	public function append_smarty($name, $key) {
		$this->smarty->append(array($name => array($key => &$this)), '', true);
		$this->assigned = true;
	}

}

