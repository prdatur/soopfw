<?php

class Array2Tree {

	/**
	 * Our entries to be processed
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Determines if we already have found a selected menu, we only want the first matched entry
	 * @var boolean
	 */
	private $menu_selected = false;

	/**
	 * Add an element to the entry.
	 * Required array keys = parent_id, entry_id, #link and order
	 *
	 * @param array $entry  the entry array
	 */
	public function add_item(Array $entry) {
		if(!isset($this->items[$entry['parent_id']])) {
			$this->items[$entry['parent_id']] = array();
		}
		$this->items[$entry['parent_id']][] = $entry;
	}

	/**
	 * Returns the tree
	 *
	 * @param int $parent_id the starting parent id
	 * @param boolean $just_active if we just want the active tree in depth provide true, if you want the hole tree provide false  (optional, default = false)
	 * @return array the menu tree
	 */
	public function get_tree($parent_id = 0, $just_active = false) {
		if(!isset($this->items[$parent_id])) {
			return array();
		}
		$result = array();
		$this->items[$parent_id] = $this->sort_menu($this->items[$parent_id]);
		foreach($this->items[$parent_id] AS &$entry) {
			if($just_active == true) {
				list($url) = explode('?', $_SERVER['REQUEST_URI'],2);

				//If a language prefix is within an url, remove it for active check
				$url = preg_replace('/\/[a-z][a-z]\//i', '/', $url);
				$check_active_link = preg_replace('/\/[a-z][a-z]\//i', '/', $entry['#link']);

				if($this->menu_selected == false && strtolower($url) == strtolower($check_active_link)) {
					$entry['#active'] = true;
					$entry['#active_direct'] = true;
					$this->menu_selected = true;
				}
			}


			$entry['#childs'] = $this->sort_menu($this->get_tree($entry['entry_id'], $just_active));

			if($just_active == true && $this->check_if_a_child_is_active($entry['#childs'])) {
				$entry['#active'] = true;
			}

			$result[] = $entry;

		}

		if($just_active == true && $parent_id."" === "0") {
			$this->get_only_active($result);
		}
		return $result;
	}

	/**
	 * Removes all entries which are inactive
	 *
	 * @param array &$array the array which will be processed
	 * @param boolean $onetime_add_all if set to true it will pass the unset behaviour (optional, default = false)
	 */
	private function get_only_active(&$array, $onetime_add_all = false) {

		foreach($array AS $k => &$childs) {

			if($onetime_add_all == false && $childs['parent_id']."" !== "0" && empty($childs['#active']) && !$this->check_if_a_child_is_direct_selected($array)) {
				unset($array[$k]);
			}
			if(!empty($childs['#childs'])) {
				$this->get_only_active($childs['#childs'], !empty($childs['#active']));
			}
		}
	}

	/**
	 * Removes all entries which are active
	 *
	 * @param array &$array the array which will be processed
	 */
	public function get_only_inactive(&$array, $skip_remove = false) {

		foreach($array AS $k => &$childs) {

			if($skip_remove == false && !empty($childs['#active'])) {
				unset($array[$k]);
			}
			if(!empty($childs['#childs'])) {
				$this->get_only_active($childs['#childs'], empty($childs['#active']));
			}
		}
	}

	/**
	 * Checks wether the child array has direct selected
	 *
	 * @param array $array the child array
	 * @return boolean true if selected, else false
	 */
	private function check_if_a_child_is_direct_selected(&$array) {
		list($url) = explode('?', $_SERVER['REQUEST_URI'],2);
		foreach($array AS &$child) {
			if(strtolower($url) == strtolower($child['#link'])) {
				return true;
			}
			if(!empty($child['#childs'])) {
				return $this->check_if_a_child_is_direct_selected($child['#childs']);
			}
		}
		return false;
	}

	/**
	 * Checks wether the child array is active or not
	 *
	 * @param array $array the child array
	 * @return boolean true if active, else false
	 */
	private function check_if_a_child_is_active(&$array) {
		foreach($array AS &$child) {
			if(!empty($child['#active'])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sorts the provided array based up on the value[order] key
	 *
	 * @param array &$menu the menu to sort
	 * @return int, 0 if equals, -1 if prev, 1 if next
	 */
	private function sort_menu($menu) {
		usort($menu, function($a, $b) {
			if($a['order'] == $b['order']) {
				return 0;
			}
			return ($a['order'] < $b['order']) ? -1 : 1;
		});
		return $menu;
	}
}
?>
