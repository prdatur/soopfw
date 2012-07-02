<?php
	/* @var $core Core */
	//Define needed params
	$params = new ParamStruct();
	$params->add_required_param("entry_id", PDT_INT);

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

	$menu_entry_translation_obj = new MenuEntryTranslationObj($params->entry_id, $core->current_language);

	//If the translation entry does not exist, return
	if(!$menu_entry_translation_obj->load_success()) {
		AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such menu entry');
	}

	//Delete the application
	if($menu_entry_translation_obj->delete()) {
		AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
	}
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>