<?php
/**
 * Change the right for the given user
 */
//Check perms
if (!$core->get_right_manager()->has_perm("admin.user.rights.change")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Setup needed params
$params = new ParamStruct();
$params->add_required_param("right", PDT_STRING);
$params->add_required_param("user_id", PDT_INT);
$params->add_required_param("value", PDT_STRING);
$params->fill();

//Check params
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Try to load the user right object, if not exist fill the user_id
$user_right_obj = new UserRightObj($params->user_id);
if (!$user_right_obj->load_success()) {
	$user_right_obj->user_id = $params->user_id;
}

//Get the current rights because we do not want to lose the current rights the user owns
$current_rights = $user_right_obj->permissions;

//Add all current rights to an temporary array but without the given param right couse this will be handled below
$tmp_arr = array();
$regexp = "/^-?".quote($params->right)."$/is";
foreach (explode("\n", $current_rights) AS $right) {
	$right = str_replace("\r", "", $right);
	$right = str_replace("\n", "", $right);
	if (!preg_match($regexp, $right, $matches)) {
		$tmp_arr[] = $right;
	}
}

//Add, remove or disallow the given right
switch ($params->value) {
	case 'y':
		$tmp_arr[] = $params->right;
		break;
	case 'n':
		$tmp_arr[] = "-".$params->right;
		break;
	case 'notowned':
	case 'g':
		break;
}

//Get the new right string, set it as the permissions attribute and save or insert it.
$current_rights = implode("\n", $tmp_arr);
$user_right_obj->permissions = $current_rights;
if ($user_right_obj->save_or_insert()) {
	
	/**
	 * Provides hook: user_permission_change
	 *  
	 * Allow other modules to do tasks if the rights changed for the specific user
	 * 
	 * @param int $user_id
	 *   The user id
	 * @param array $permissions
	 *   the current permissions for the user (includes the changes) 
	 */
	$core->hook('user_permission_change', array($params->user_id, $user_right_obj->permissions));
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>
