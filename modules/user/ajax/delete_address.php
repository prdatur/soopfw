<?php
/**
 * Delete the the given entry, only if the user owns the right admin.user.delete or wants to delete his own address can delete it.
 */
//Setup needed params
$params = new ParamStruct();
$params->add_required_param("address_id", PDT_INT);

$params->fill();

//Params invalid
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Check if provided address id is valid
$address_obj = new UserAddressObj($params->address_id);
if (!$address_obj->load_success()) {
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, t("address not found"));
}

//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.delete") && $address_obj->user_id != $core->get_session()->current_user()->user_id) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

if ($address_obj->delete()) {
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>