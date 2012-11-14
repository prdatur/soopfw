<?php
/**
 * Provides an ajax request to save the new order for the content field groups.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Content
 * @category Ajax
 */
class AjaxContentSaveFieldGroupOrder extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("new_order", PDT_ARR);

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

		// Setup new order for each field group and save it.
		foreach ($params->new_order AS $index => $field_type) {
			$obj = new ContentTypeFieldGroupObj($field_type);
			$obj->order = $index;
			$obj->save();
		}

		AjaxModul::return_code(AjaxModul::SUCCESS);
	}
}
