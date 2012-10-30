<?php
/**
 * Provides an ajax request to add or remove a user from the specified group.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.User
 */
class AjaxUserUserPermissionGroupChange extends AjaxModul {

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
		$params->add_required_param("group_id", PDT_INT);
		$params->add_required_param("user_id", PDT_INT);
		$params->add_required_param("value", PDT_STRING);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//initialize the user 2 right group object
		$user2rightGroupObj = new User2RightGroupObj();

		$return = false;
		//Check if we want to remove the user or add it
		if ($params->value == "remove") {
			$user2rightGroupObj->db_filter->add_where("group_id", $params->group_id);
			$user2rightGroupObj->db_filter->add_where("user_id", $params->user_id);
			$user2rightGroupObj->load();
			$return = $user2rightGroupObj->delete();
		}
		else if ($params->value == "add") {
			$user2rightGroupObj->set_fields($params->get_values());
			$return = $user2rightGroupObj->insert();
		}

		if ($return) {

			/**
			 * Provides hook: user_permission_group_change
			 *
			 * Allow other modules to do tasks if a user is removed or added from a specific group
			 *
			 * @param int $group_id
			 *   The group id
			 * @param int $user_id
			 *   the user id
			 * @param string $value
			 *   'remove' if we remove the user or 'add' if we add him to the specific group
			 *
			 */
			$this->core->hook('user_permission_group_change', $params->get_values());
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}

