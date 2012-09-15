<?php
/**
 * Provides an ajax request to delete a menu.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.menu.ajax
 * @category Module.Menu
 */
class AjaxMenuDeleteMenu extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("menu_id", PDT_STRING);

		//Fill the params
		$params->fill();

		//Display error if params are not valid
		if(!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		//Check perms
		if(!$this->core->get_right_manager()->has_perm("admin.menu.manage")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		$menu_obj = new MenuObj($params->menu_id);
		if(!$menu_obj->load_success()) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, 'no such menu');
		}

		//Delete the application
		if($menu_obj->delete()) {
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}
}
?>