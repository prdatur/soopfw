<?php
/**
 * Provides an ajax request to change the right for the given user.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxUserUserPermissionChange extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.user.rights.change")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("right", PDT_STRING);
		$params->add_required_param("user_id", PDT_INT);
		$params->add_required_param("value", PDT_STRING);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Try to load the user right object, if not exist fill the user_id
		$user_right_obj = new UserRightObj($params->user_id);
		if (!$user_right_obj->load_success()) {
			$user_right_obj->user_id = $params->user_id;
		}
		$log_string = "";
		//grant, revoke or remove the given right
		switch ($params->value) {
			case 'y':
				$user_right_obj->grant_permission($params->right);
				$log_string = t("granted");
				break;
			case 'n':
				$user_right_obj->revoke_permission($params->right);
				$log_string = t("revoked");
				break;
			case 'notowned':
			case 'g':
				$user_right_obj->remove_permission($params->right);
				$log_string = t("removed");
				break;
		}

		if ($user_right_obj->flush_permissions()) {

			$user_obj = new UserObj($params->user_id);
			SystemHelper::audit(t('Permissions "@permission" was "@type" for user "@username"', array(
				'@username' => $user_obj->username,
				'@permission' => $params->right,
				'@type' => $log_string,
			)), 'user');


			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
