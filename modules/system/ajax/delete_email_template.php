<?php
/**
 * Deletes the given email template
 */

//Initalize param struct
$params = new ParamStruct();
$params->add_required_param("id", PDT_STRING);

$params->fill();

//Parameters are missing
if (!$params->is_valid()) {
	AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
}

//Right missing
if (!$core->get_right_manager()->has_perm("admin.system.config")) {
	AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
}

//Try to delete the entry
$deleted = DatabaseFilter::create(MailTemplateObj::TABLE)
	->add_where('id', $params->id)
	->delete();

if ($deleted) {
	AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
}
AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);
?>