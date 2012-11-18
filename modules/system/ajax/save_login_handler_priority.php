<?php
/**
 * Provides an ajax request to save the login handler priority order.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxSystemSaveLoginHandlerPriority extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {

		// Setup needed params.
		$params = new ParamStruct();
		$params->add_param('enabled', PDT_ARR);
		$params->fill();

		// Invalid params.
		if (!$params->is_valid()) {
			throw new SoopfwWrongParameterException();
		}

		// Get the provides values.
		$values = $params->get_values();
		$new_values = array();

		// Setup new order.
		foreach ($values['enabled'] AS $handler_name) {
			$new_values[] = $handler_name;
		}

		// Try save the new order.
		if ($this->core->dbconfig("system", System::CONFIG_LOGIN_HANDLER, $new_values)) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}

		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}

