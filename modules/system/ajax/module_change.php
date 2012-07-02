<?php
/* @var $core Core */
/**
 * Change module configuration, call this for every field change
 */
//Setup needed params
$params = new ParamStruct();
$params->add_required_param("modul", PDT_STRING);
$params->add_required_param("field", PDT_STRING);
$params->add_isset_param("value", PDT_STRING);

$params->fill();
//Invalid params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Check perms
if (!$core->get_right_manager()->has_perm("admin.system.modules")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

$field = $params->field;
$modul = $params->modul;

//Load the module configuration
$module_conf_obj = new ModulConfigObj($params->modul);
$old_enabled = 0;
if($module_conf_obj->load_success()) {
	$old_enabled = $module_conf_obj->enabled;
}
//Setup values
$module_conf_obj->modul = $params->modul;
$module_conf_obj->$field = $params->value;

//Save
if ($module_conf_obj->save_or_insert()) {

	//If we changed the enabled field, call the enable / disable function based up on the current value
	if($params->field == "enabled" && $old_enabled != $module_conf_obj->enabled)  {
		$core->generate_classlist();
		$action = ($module_conf_obj->enabled == 1) ? 'enable' : 'disable';
		$module_obj = new $modul();
		if (method_exists($module_obj, $action)) {
			$module_obj->$action();
		}
	}
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>