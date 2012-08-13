<?php
/**
 * Changes the user password
 */
//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.change")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Setup needed params
$params = new ParamStruct();
$params->add_required_param("user_id", PDT_INT);
$params->add_required_param("password", PDT_STRING);
/**
 * DISABLED UNTIL EMAIL TEMPLATE CONFIGURATION IS IMPLEMENTED
 */
// $params->add_isset_param("inform", PDT_BOOL, false);
$params->fill();

//Check params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Load the user, set the new password and save it
$field = $params->field;
$user_obj = new UserObj($params->user_id);
$user_obj->password = $params->password;

if ($user_obj->save(true, true)) {

	//If we wanted to inform the user about the new password, send an email

	/**
	 * DISABLED UNTIL EMAIL TEMPLATE CONFIGURATION IS IMPLEMENTED
	 */
	if (false && $params->inform == true) {
		$tpl_vals['password'] = $params->password;
		$tpl_vals['username'] = $user_obj->username;
		$mail = new Email();
		$mail->send_tpl("admin_edit_user_password", $user_obj->language, $user_obj->get_address_by_group(UserAddressObj::USER_ADDRESS_GROUP_DEFAULT, "email"), $tpl_vals);
	}
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>