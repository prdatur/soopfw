<?php

/**
 * Provides an ajax request to change the language if it is enabled or not
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Translation
 * @category Ajax
 */
class AjaxTranslationLanguageChange extends AjaxModul
{

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		// Setup needed params.
		$params = new ParamStruct();
		$params->add_required_param("lang", PDT_STRING);
		$params->add_isset_param("value", PDT_STRING);

		$params->fill();

		// Check valid params.
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		// Check perms.
		if (!$this->core->get_right_manager()->has_perm("admin.translate")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		// Load the language object, set the new values and save or insert it.
		$language_obj = new LanguagesObj($params->lang);
		$language_obj->lang = $params->lang;
		$language_obj->enabled = $params->value;
		if ($language_obj->save_or_insert()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}

}
