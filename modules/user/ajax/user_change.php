<?php
/**
 * Provides an ajax request to change a specific field of the given user.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.User
 */
class AjaxUserUserChange extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.user.change")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("user_id", PDT_INT);
		$params->add_required_param("field", PDT_STRING);
		$params->add_isset_param("value", PDT_STRING);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Load the user, set the new value for the field and save it
		$field = $params->field;
		$user_obj = new UserObj($params->user_id);
		$user_obj->$field = $params->value;
		if ($user_obj->save()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
