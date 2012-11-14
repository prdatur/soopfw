<?php

/**
 * Provides a class build up the menu tree.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Menu
 * @category Module
 */
class Array2Tree
{
	/**
	 * Our entries to be processed
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Determines if we already have found a selected menu, we only want the first matched entry.
	 *
	 * @var boolean
	 */
	private $menu_selected = false;

	/**
	 * Holds the current parsed request uri.
	 *
	 * @var string
	 */
	private $request_uri = "";

	/**
	 * Constructor
	 */
	public function __construct() {

		// Save performance and only get the request uri once.
		$this->request_uri = preg_replace('/^\/[a-z][a-z]\//i', '/', strtolower(current(explode('?', $_SERVER['REQUEST_URI'], 2))));
	}

	/**
	 * Add an element to the entry.
	 * Possible keys:
	 *  - entry_id: the unique entry id for this element.
	 * 	- parent_id: The parent id which is based up on entry_id
	 *  - #link: the link for the entry.
	 *  - #id: the html unique id
	 *  - #always_open: true|false whether to have this element and the childs always be open.
	 *  - #active: true|false whether to direct set this element to be active.
	 *  - #inactive: true|false whether to disable this entry.
	 *  - #childs: an array where thge values holds other arrays which are the same as this one (recrusive)
	 *  - order: lower numbers are placed before higher.
	 *
	 * @param array $entry
	 *   the entry array
	 */
	public function add_item(Array $entry) {
		if (!isset($this->items[$entry['parent_id']])) {
			$this->items[$entry['parent_id']] = array();
		}
		$this->items[$entry['parent_id']][] = $entry;
	}

	/**
	 * Returns the tree
	 *
	 * @param int $parent_id
	 *   the starting parent id. (optional, default = 0)
	 * @param boolean $just_active
	 *   if we just want the active tree in depth provide true, if you want the hole tree provide false. (optional, default = false)
	 *
	 * @return array the menu tree
	 */
	public function get_tree($parent_id = 0, $just_active = false) {
		// If we have no elements with this parent id, return an empty one.
		if (!isset($this->items[$parent_id])) {
			return array();
		}

		// Pre-init result array.
		$result = array();

		// Get all elements sorted which are within the parent_id.
		$this->items[$parent_id] = $this->sort_menu($this->items[$parent_id]);

		// Loop through all entries for this parent id.
		foreach ($this->items[$parent_id] AS &$entry) {

			// Check if the current entry is active or maybe direct active.
			// Active means that this or a child entry is direct active.
			// Direct active means that the link (#link) is the link we actual requested.
			$regexp = "/^(\/[a-z][a-z]\/)?" . preg_quote($entry['#link'], '/') . "(\/?\?.*)?$/i";

			// Only the first menu entry which is found for the request uri will be direct active.
			// Further matches will be skipped.
			if ($this->menu_selected === false && preg_match($regexp, $this->request_uri)) {
				// We will set this entry to an active and direct active one.
				$entry['#active'] = true;
				$entry['#active_direct'] = true;

				// Prevent further matches.
				$this->menu_selected = true;
			}

			// Recrusive call to process all childs if some exist..
			$entry['#childs'] = $this->sort_menu($this->get_tree($entry['entry_id'], $just_active));

			// If we have currently not an active entry, check if a child is active, if so this entry will also be active.
			if ($entry['#active'] !== false && $this->check_if_a_child_is_active($entry['#childs'])) {
				$entry['#active'] = true;
			}

			// Add the entry to our returning result.
			$result[] = $entry;
		}

		// If we only want active entries and we call this for the first time (parent_id = 0), get only the active ones.
		// Will not return just a branch instead an example:
		// Tree
		// -----------------------------------
		// 1
		//   1.1
		//     1.1.1
		//   1.2
		// 2 -> active
		//   2.1 -> active
		//     2.1.1 -> active + direct active
		//   2.2
		// 3
		//  3.1
		//    3.1.1
		//  3.2
		//
		// The returning tree will be
		// -----------------------------------
		// 1
		// 2
		//   2.1
		//     2.1.1
		//   2.2
		// 3
		if ($just_active == true && $parent_id . "" === "0") {
			$this->get_only_active($result);
		}
		return $result;
	}

	/**
	 * Removes all entries which are inactive
	 *
	 * @param array &$array
	 *   the array which will be processed
	 * @param boolean $onetime_add_all
	 *   if set to true it will pass the unset behaviour (optional, default = false)
	 */
	private function get_only_active(&$array, $onetime_add_all = false) {

		// Process all entries.
		foreach ($array AS $k => &$childs) {

			// Only remove the entry if
			// - onetime_add_all is not set to true
			// - it is not the root (parent = 0)
			// - always_open is false
			// - active is false
			// - no child is active
			//
			if ($onetime_add_all === false && $childs['parent_id'] . "" !== "0" && empty($childs['#active']) && (!isset($childs['#always_open']) || $childs['#always_open'] !== MenuEntryTranslationObj::ALWAYS_OPEN_YES) && !$this->check_if_a_child_is_direct_selected($array)) {
				unset($array[$k]);
			}

			// If we have childs, process the childs.
			if (!empty($childs['#childs'])) {
				// If the current entry has the "active" flag we need to add all direct childs.
				$this->get_only_active($childs['#childs'], !empty($childs['#active']));
			}
		}
	}

	/**
	 * Removes all entries which are active.
	 *
	 * @param array &$array
	 *   the array which will be processed.
	 * @param boolean $skip_remove
	 *   If set to true, active entries will not be removed from the array. (optional, default = false)
	 */
	public function get_only_inactive(&$array, $skip_remove = false) {

		// Process all entries.
		foreach ($array AS $k => &$childs) {

			// Only remove the entry if
			// - skip_remove is not set to false AND
			// (
			//   - always_open is true OR
			//   - active is true OR
			// )
			//
			if ($skip_remove === false && (!empty($childs['#active']) || (isset($childs['#always_open']) && $childs['#always_open'] === MenuEntryTranslationObj::ALWAYS_OPEN_YES))) {
				unset($array[$k]);
			}

			// If we have childs, process the childs.
			if (!empty($childs['#childs'])) {

				// If the current entry is not "active" we can not remove any child.
				$this->get_only_inactive($childs['#childs'], empty($childs['#active']));
			}
		}
	}

	/**
	 * Checks wether the child array has direct selected links.
	 *
	 * @param array $array
	 *   the child array.
	 *
	 * @return boolean true if selected, else false.
	 */
	private function check_if_a_child_is_direct_selected(&$array) {
		static $cache = array();

		// Process all entries.
		foreach ($array AS &$child) {
			// Only make the link to lower case one time.
			if (!isset($cache[$child['#link']])) {
				$cache[$child['#link']] = strtolower($child['#link']);
			}

			// If the current entry link equals the request uri, this entry is direct selected.
			if ($this->request_uri === $cache[$child['#link']]) {
				return true;
			}

			// Check if maybe a child is direct selected (recrusive).
			if (!empty($child['#childs'])) {
				return $this->check_if_a_child_is_direct_selected($child['#childs']);
			}
		}
		return false;
	}

	/**
	 * Checks wether the child array is active or not.
	 *
	 * @param array $array
	 *   the child array.
	 *
	 * @return boolean true if active, else false.
	 */
	private function check_if_a_child_is_active(&$array) {

		// Process all entries.
		foreach ($array AS &$child) {
			// If the entry is active or always open return true, else false.
			if (!empty($child['#active']) || (isset($child['#always_open']) && $child['#always_open'] == MenuEntryTranslationObj::ALWAYS_OPEN_YES)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sorts the provided array based up on the value[order] key.
	 *
	 * @param array &$menu
	 *   the menu to sort.
	 *
	 * @return int, 0 if equals, -1 if prev, 1 if next.
	 */
	private function sort_menu($menu) {
		usort($menu, function($a, $b) {
			if ($a['order'] === $b['order']) {
				return 0;
			}
			return ($a['order'] < $b['order']) ? -1 : 1;
		});
		return $menu;
	}

}

