<?php
//Define needed params
$params = new ParamStruct();
$params->add_required_param("id", PDT_STRING);

//Fill the params
$params->fill();

//Display error if params are not valid
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Check perms
if (!$core->get_right_manager()->has_perm("admin.content.manage")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

$obj = new ContentTypeFieldGroupObj($params->id);
if (!$obj->load_success()) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such field');
}

//Delete the application
if ($obj->delete()) {
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>