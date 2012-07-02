<?php
/**
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.
 */
//Define needed params
$params = new ParamStruct();
$params->add_required_param("id", PDT_STRING);
$params->add_required_param("index", PDT_INT);

//Fill the params
$params->fill();

//Display error if params are not valid
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}


//Check perms
if (!$core->get_right_manager()->has_perm("admin.content.create")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

$field_group = new ContentTypeFieldGroupObj($params->id);

$field_object = new $field_group->field_group();
$field_object->set_max_value($field_group->max_value);
$field_object->set_prefix($field_group->id, $params->index);
$field_object->set_label($field_group->name);
$field_object->set_required($field_group->required);
echo $field_object->get_another_item();
die();
?>