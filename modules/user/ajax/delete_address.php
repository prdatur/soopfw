<?php
/**
 * Provides an ajax request to delete the the given entry,
 * only if the user owns the right admin.user.delete or wants to delete his own address can delete it.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.User
 */
class AjaxUserDeleteAddress extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("address_id", PDT_INT);

		$params->fill();

		//Params invalid
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check if provided address id is valid
		$address_obj = new UserAddressObj($params->address_id);
		if (!$address_obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, t("address not found"));
		}

		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.user.delete") && $address_obj->user_id != $this->core->get_session()->current_user()->user_id) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		if ($address_obj->delete()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
