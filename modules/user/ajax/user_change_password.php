<?php
/**
 * Provides an ajax request to changes the user password.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxUserUserChangePassword  extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("user_id", PDT_INT);
		$params->add_required_param("password", PDT_STRING);
		$params->add_param("inform", PDT_BOOL, false);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check perms (perm admin.user.change is allowed and the current user can change his own password)
		if ($this->session->current_user()->user_id != $params->user_id && !$this->core->get_right_manager()->has_perm("admin.user.change")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Load the user, set the new password and save it
		$user_obj = new UserObj($params->user_id);
		$user_obj->password = $params->password;

		if ($user_obj->save(true, true)) {
			if ($this->session->current_user()->user_id != $params->user_id) {
				// Inform the user if we wanted it.
				if ($params->inform == true && $this->core->get_right_manager()->has_perm("admin.user.change")) {
					$tpl_vals['password'] = $params->password;
					$tpl_vals['username'] = $user_obj->username;
					$mail = new Email();
					$mail->send_tpl( User::CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD, $user_obj->language, $user_obj->get_address_by_group(UserAddressObj::USER_ADDRESS_GROUP_DEFAULT, "email"), $tpl_vals);
				}

				// Audit only if we have not changed our own password.
				SystemHelper::audit(t('Changed password for user "@username"', array('@username' => $user_obj->username)), 'user');
			}
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
