<?php
/**
 * Provides an ajax request to handle the "Add more button" for content creation
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module.Content
 */
class AjaxContentCreateContentAddAnotherItem extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("id", PDT_STRING);
		$params->add_required_param("index", PDT_INT);

		//Fill the params
		$params->fill();

		//Display error if params are not valid
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}


		//Check perms
		if (!$this->core->get_right_manager()->has_perm("admin.content.create")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		$field_group = new ContentTypeFieldGroupObj($params->id);

		$field_object = new $field_group->field_group();
		$field_object->set_max_value($field_group->max_value);
		$field_object->set_prefix($field_group->id, $params->index);
		$field_object->set_label($field_group->name);
		$field_object->set_required($field_group->required);
		echo $field_object->get_another_item();
		die();
	}
}
