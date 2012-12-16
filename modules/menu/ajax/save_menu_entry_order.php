<?php

/**
 * Provides an ajax request to save the new menu order.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Ajax
 */
class AjaxMenuSaveMenuEntryOrder extends AjaxModul
{
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
		// Define needed params.
		$params = new ParamStruct();
		$params->add_required_param("menu_id", PDT_STRING);
		$params->add_required_param("new_order", PDT_ARR);

		// Fill the params.
		$params->fill();

		// Display error if params are not valid.
		if (!$params->is_valid()) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER);
		}

		// Check perms.
		if (!$this->core->get_right_manager()->has_perm("admin.menu.manage")) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
		}

		$this->db->transaction_begin();

		// We need to use a copy because recrusive_save_new_order accepts the $order only by reference.
		$new_order = $params->new_order;

		// Make sure that we start from 0.
		$this->order_counter = 0;
		// Save new order.
		if ($this->recrusive_save_new_order($new_order, $params->menu_id)) {
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
	 *   the menu id.
	 * @param int $parent
	 *   the parent id. (optional, default = 0)
	 *
	 * @return boolean true if menus are saved, else false.
	 */
	private function recrusive_save_new_order(&$order, $menu_id, $parent = 0) {

		// Process all values.
		foreach ($order AS $menu_entry_id => $child_entries) {

			if (empty($child_entries)) {
				continue;
			}

			$menu_entry_id = (int)str_replace("+", "", $menu_entry_id);
			// Increment the order counter.
			$this->order_counter++;

			// Load the menu entry, if not found we skip this entry.
			$menu_obj = new MenuEntryObj($menu_entry_id);
			if (!$menu_obj->load_success()) {
				continue;
			}

			// The the new order.
			$menu_obj->order = $this->order_counter;

			// Set the parent, because maybe we changed it through the table sorter.
			$menu_obj->parent_id = $parent;

			// If entry could not be saved return false.
			if (!$menu_obj->save()) {
				return false;
			};

			// If we have childs, process them recrusive.
			if (is_array($child_entries)) {
				if (!$this->recrusive_save_new_order($child_entries, $menu_id, $menu_entry_id)) {
					// Return false if a child order could not be saved.
					return false;
				}
			}
		}

		// Orders could be saved.
		return true;
	}

}
