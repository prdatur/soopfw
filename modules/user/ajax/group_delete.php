<?php
/**
 * Deletes a right group
 */
//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.group.delete")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Setup needed params
$params = new ParamStruct();
$params->add_required_param("group_id", PDT_INT);
$params->fill();

//Check params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Delete the group
$group_obj = new UserRightGroupObj($params->group_id);
if(!$group_obj->load_success()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, 'invalid group');
}

if ($group_obj->delete()) {
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>