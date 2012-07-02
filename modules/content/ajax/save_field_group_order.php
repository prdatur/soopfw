<?php
//Define needed params
$params = new ParamStruct();
$params->add_required_param("new_order", PDT_ARR);

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


foreach ($params->new_order AS $index => $field_type) {
	$obj = new ContentTypeFieldGroupObj($field_type);
	$obj->order = $index;
	$obj->save();
}

AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
?>