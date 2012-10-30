<?php
/**
 * Provides an ajax request to save the new rights for the group.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.User
 */
class AjaxUserGroupSaveRights extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Check params
		if (!$this->core->get_right_manager()->has_perm("admin.user.group.change")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("group_id", PDT_INT);
		$params->add_isset_param("rights", PDT_ARR);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Get the new rights string
		$new_rights = implode("\n", $params->rights);

		//Load the group, set the value and save it
		$right_obj = new UserRightGroupObj($params->group_id);
		$right_obj->permissions = $new_rights;
		if ($right_obj->save()) {

			/**
			 * Provides hook: group_save_rights
			 *
			 * Allow other modules to do tasks if the rights changed for the specific group
			 *
			 * @param int $group_id
			 *   The group id
			 * @param array $permissions
			 *   the current permissions for the group (includes the changes)
			 */
			$this->core->hook('group_save_rights', array($params->group_id, $params->rights));
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}

