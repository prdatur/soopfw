<?php
/**
 * Chang the language if it is enabled or not
 */
//Setup needed params
$params = new ParamStruct();
$params->add_required_param("lang", PDT_STRING);
$params->add_isset_param("value", PDT_STRING);

$params->fill();

//Check valid params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Check perms
if (!$core->get_right_manager()->has_perm("admin.translate")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Load the language object, set the new values and save or insert it
$language_obj = new LanguagesObj($params->lang);
$language_obj->lang = $params->lang;
$language_obj->enabled = $params->value;
if ($language_obj->save_or_insert()) {
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>