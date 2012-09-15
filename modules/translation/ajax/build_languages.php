<?php
/**
 * Provides an ajax request to build the language files for a given module.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.ajax
 * @category Module.System
 */
class AjaxSystemBuildLanguages extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Setup needed params
		$params = new ParamStruct();
		$params->add_param("module", PDT_STRING, "");
		$params->fill();

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.translate")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Build the languages for this module and stores the errors within $error
		$this->core->lng->build_language($params->module, array(), false, true, $errors);
		if (empty($errors)) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		else {
			AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, implode("\n", $errors));
		}
	}
}
