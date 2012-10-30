<?php
/**
 * Provides an ajax request to change module configuration, call this for every field change
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.System
 */
class AjaxSystemDisableModule extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("module", PDT_STRING);

		$params->fill();
		//Invalid params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.system.modules")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}


		//Load the module configuration
		$module_conf_obj = new ModulConfigObj($params->module);
		if(!$module_conf_obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER);
		}
		$old_enabled = $module_conf_obj->enabled;

		$module_conf_obj->enabled = 0;

		//Save
		if ($module_conf_obj->save_or_insert()) {
			//If we the enabled field changed, call the disable function based up on the current value
			if($old_enabled != $module_conf_obj->enabled)  {
				$obj = new cli_generate_classlist();
				$obj->generate_classlist();

				$module_obj = new $params->module();
				if (method_exists($module_obj, 'disable')) {
					$module_obj->disable();
				}

				$permissions = SystemHelper::get_module_permissions($params->module, true);
				if (!empty($permissions)) {
					$this->core->message(t("The following rights were removed:\n!rights", array("!rights" => implode("\n", $permissions))), Core::MESSAGE_TYPE_SUCCESS);
				}
			}
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
