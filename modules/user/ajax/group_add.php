<?php
/**
 * Add a right group
 */
//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.group.add")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Setup params
$params = new ParamStruct();
$params->add_required_param("title", PDT_STRING);

$params->fill();

//Check if params valid
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Insert the group and return the created group id
$group_obj = new UserRightGroupObj();
$group_obj->set_fields($params->get_values());
if ($group_obj->insert()) {
	$return_array['group_id'] = $group_obj->get_last_inserted_id();
	AjaxModul::return_code(AjaxModul::SUCCESS, $return_array, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>
