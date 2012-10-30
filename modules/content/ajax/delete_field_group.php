<?php
/**
 * Provides an ajax request to delete a content type field group
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.Content
 */
class AjaxContentDeleteFieldGroup extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("id", PDT_STRING);

		//Fill the params
		$params->fill();

		//Display error if params are not valid
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.content.manage")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		$obj = new ContentTypeFieldGroupObj($params->id);
		if (!$obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_INVALID_PARAMETER, null, true, 'no such field');
		}

		//Delete the application
		if ($obj->delete()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
