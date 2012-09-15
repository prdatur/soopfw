<?php
/**
 * Provides an ajax request to delete a content type
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.ajax
 * @category Module.Content
 */
class AjaxContentDeleteContentType extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("content_type", PDT_STRING);

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

		$content_type_obj = new ContentTypeObj($params->content_type);
		if (!$content_type_obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such content type');
		}

		//Delete the application
		if ($content_type_obj->delete()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
