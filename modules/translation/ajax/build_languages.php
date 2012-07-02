<?php
/**
 * Build the language files for a given module
 */
//Setup needed params
$params = new ParamStruct();
$params->add_param("module", PDT_STRING, "");
$params->fill();

//Check perms
if (!$core->get_right_manager()->has_perm("admin.translate")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Build the languages for this module and stores the errors within $error
$core->lng->build_language($params->module, array(), false, true, $errors);
if (empty($errors)) {
	AjaxModul::return_code(AjaxModul::SUCCESS);
}
else {
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, implode("\n", $errors));
}
?>