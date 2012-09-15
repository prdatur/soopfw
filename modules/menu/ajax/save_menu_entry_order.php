<?php
/**
 * Provides an ajax request to save the new menu order.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.menu.ajax
 * @category Module.Menu
 */
class AjaxMenuSaveMenuEntryOrder extends AjaxModul {

	/**
	 * The order counter for the new order.
	 *
	 * @var int
	 */
	private $order_counter = 0;

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		//Define needed params
		$params = new ParamStruct();
		$params->add_required_param("menu_id", PDT_STRING);
		$params->add_required_param("new_order", PDT_ARR);

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

		$this->db->transaction_begin();
		$menu_id = $params->menu_id;
		$new_order = $params->new_order;
		$this->order_counter = 0;
		if($this->recrusive_save_new_order($new_order, $menu_id)) {
			$this->db->transaction_commit();
			AjaxModul::return_code(AjaxModul::SUCCESS);
		}

		$this->db->transaction_rollback();
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT);
	}

	/**
	 * Save the menu order for the given menu recrusive.
	 *
	 * @param int $order
	 *   the new order.
	 * @param int $menu_id
	 *   the menu id
	 * @param int $parent
	 *   the parent id (optional, default = 0)
	 * @return boolean true if menus are saved, else false
	 */
	private function recrusive_save_new_order(&$order, $menu_id, $parent = 0) {
		foreach($order AS $k => $v) {
			if(empty($v)) {
				continue;
			}
			$this->order_counter++;
			$menu_obj = new MenuEntryObj($k);
			if(!$menu_obj->load_success()) {
				continue;
			}

			$menu_obj->order = $this->order_counter;
			$menu_obj->parent_id = $parent;
			if(!$menu_obj->save()) {
				return false;
			};

			if(is_array($v)) {
				if(!$this->recrusive_save_new_order($v, $menu_id, $k)) {
					return false;
				}
			}
		}
		return true;
	}
}
?>