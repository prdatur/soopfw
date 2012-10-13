<?php

/**
 * Provide a HTML-Form handler which stores the posted values within the session.
 * A reload will not clear the values.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.inputs
 * @category Form
 */
class SessionForm extends Form
{
	/**
	 * Checks the form and assign the errors to smarty.
	 *
	 * @return boolean true if form is submitted and valid or not
	 */
	public function check_form() {

		// Check the form for errors.
		$return = parent::check_form();

		// Check if form was submited.
		if ($this->is_submitted()) {
			//Set session key for search values so a reload of a page will use the session values
			$this->session->set($this->formname, parent::get_values());
		}
		else {
			//Form was not submited so try to load session values
			$this->set_values($this->session->get($this->formname, array()));
		}

		return $return;
	}
}

