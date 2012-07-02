<?php
	//Define needed params
	$params = new ParamStruct();
	$params->add_required_param("menu_id", PDT_STRING);

	//Fill the params
	$params->fill();

	//Display error if params are not valid
	if(!$params->is_valid()) {
		AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
	}

	//Check perms
	if(!$core->get_right_manager()->has_perm("admin.menu.manage")) {
		AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
	}

	$menu_obj = new MenuObj($params->menu_id);
	if(!$menu_obj->load_success()) {
		AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such menu');
	}

	//Delete the application
	if($menu_obj->delete()) {
		AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
	}
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>