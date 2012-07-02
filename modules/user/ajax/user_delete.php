<?php
/**
 * Deletes the given user
 */
//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.delete")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Setup needed params
$params = new ParamStruct();
$params->add_required_param("user_id", PDT_INT);
$params->fill();

//Check params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Load the user and delete it
$user_obj = new UserObj($params->user_id);
if ($user_obj->delete()) {
	$core->hook('user_delete', array($params->user_id));
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>