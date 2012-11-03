<?
/**
 * Provides an ajax request to print out hello world
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Example
 */
class AjaxSystemSaveLoginHandlerPriority extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		$params = new ParamStruct();
		$params->add_param('enabled', PDT_ARR);
		$params->fill();

		if (!$params->is_valid()) {
			throw new SoopfwWrongParameterException();
		}

		$values = $params->get_values();
		$new_values = array();
		foreach ($values['enabled'] AS $handler_name) {
			$new_values[] = $handler_name;
		}
		if ($this->core->dbconfig("system", System::CONFIG_LOGIN_HANDLER, $new_values)) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}

		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}

