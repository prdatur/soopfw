<?php

/**
 * Provides an ajax request to delete a menu entry translation.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxMenuDeleteMenuEntryTranslation extends AjaxModul
{

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("entry_id", PDT_INT);

		//Fill the params
		$params->fill();

		//Display error if params are not valid
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.menu.manage")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		$menu_entry_translation_obj = new MenuEntryTranslationObj($params->entry_id, $this->core->current_language);

		//If the translation entry does not exist, return
		if (!$menu_entry_translation_obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such menu entry');
		}

		//Delete the application
		if ($menu_entry_translation_obj->delete()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}

}
