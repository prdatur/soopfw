<?php
/**
 * Provides an ajax request to delete the given email template.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxSystemDeleteEmailTemplate extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing.
	 */
	public function run() {

		// Initalize param struct.
		$params = new ParamStruct();
		$params->add_required_param("id", PDT_STRING);

		$params->fill();

		// Parameters are missing.
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		// Right missing.
		if (!$this->core->get_right_manager()->has_perm("admin.system.config")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		// Try to delete the entry.
		$deleted = DatabaseFilter::create(MailTemplateObj::TABLE)
			->add_where('id', $params->id)
			->delete();

		if ($deleted) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
