<?php
/**
 * Provides an ajax request to return all rights which the given group owns.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxUserGroupReadRights extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Check params
		if (!$this->core->get_right_manager()->has_perm("admin.user.group.view")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		//Setup needed params
		$params = new ParamStruct();
		$params->add_required_param("group_id", PDT_INT);
		$params->fill();

		//Check params
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Load the data and return it
		$return_array['rights'] = $this->core->get_right_manager()->get_group_rights($params->group_id);
		AjaxModul::return_code(AjaxModul::SUCCESS, $return_array);
	}
}

