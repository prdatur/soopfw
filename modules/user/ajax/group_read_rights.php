<?php
/**
 * Return all rights which the given group owns
 */
/* @var $core Core */

//Check params
if (!$core->get_right_manager()->has_perm("admin.user.group.view")) {
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

//Load the data and return it
$return_array['rights'] = $core->get_right_manager()->get_group_rights($params->group_id);
AjaxModul::return_code(AjaxModul::SUCCESS, $return_array, true);
?>
