<?php

/**
 * This object holds a menu entry
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class MenuEntryObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "menu_entry";

	/**
	 * Constructor
	 *
	 * @param int $entry_id 
	 *   the menu id (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($entry_id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("entry_id"));
		$this->db_struct->set_auto_increment("entry_id");
		$this->db_struct->add_field("entry_id", t("entry id"), PDT_INT);
		$this->db_struct->add_field("menu_id", t("menu id"), PDT_STRING);
		$this->db_struct->add_field("parent_id", t("parent id"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("order", t("order"), PDT_INT, 0, 'UNSIGNED');

		if (!empty($entry_id)) {
			if (!$this->load(array($entry_id), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Delete the given menu entry, also deletes all menu entry translations for this menu entry
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$entry_id = $this->get_value("entry_id");
		$menu_id = $this->get_value("menu_id");
		$old_menu_id = $this->get_original_value('menu_id');
		if(parent::delete()) {
			$object_ids = array();

			//Get all translated language menu entry which are linked with this menu entry
			foreach($this->db->query_slave_all("SELECT `language` FROM `".MenuEntryTranslationObj::TABLE."` WHERE `entry_id` = @entry_id", array("@entry_id" => $entry_id)) AS $menu_entry_translation) {
				$object_ids[] = array($entry_id, $menu_entry_translation['language']);
			}

			//init object container which loads our entry translation objects
			$menu_entry_translation_obj = new MenuEntryTranslationObj();

			//Load all linked menu entry translations and delete them
			foreach($menu_entry_translation_obj->load_multiple($object_ids) AS $obj) {
				$obj->delete();
			}

			foreach($this->db->query_slave_all("SELECT `entry_id` FROM `".MenuEntryObj::TABLE."` WHERE `parent_id` = ientry_id", array('ientry_id' => $entry_id)) AS $child_menu_entry) {
				$obj = new MenuEntryObj($child_menu_entry['entry_id']);
				$obj->delete();
			}


			//Need to clear the cache key for this menu_id because we have changed a menu entry and must rebuild the menu if $MenuObj::get_menu_tree is called
			$this->clear_menu_tree_cache($menu_id, $old_menu_id);
			return true;
		}
		return false;
	}

	/**
	 * Save the given Data
	 *
	 * @param boolean $save_if_unchanged 
	 *   Save this object even if no changes to it's values were made (optional, default = false)
	 * 
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {
		$old_menu_id = $this->get_original_value('menu_id');
		if(parent::save($save_if_unchanged)) {
			//Need to clear the cache key for this menu_id because we have changed a menu entry and must rebuild the menu if $MenuObj::get_menu_tree is called
			$this->clear_menu_tree_cache($this->get_value("menu_id"), $old_menu_id);
			return true;
		}
		return false;
	}

	/**
	 * Insert the current data
	 * After we inserted the data, we also clear the menu cache for this menu id
	 *
	 * @param boolean $ignore 
	 *   Don't throw an error if data is already there (optional, default=false)
	 * 
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {
		if(parent::insert($ignore)) {
			//Need to clear the cache key for this menu_id because we have changed a menu entry and must rebuild the menu if $MenuObj::get_menu_tree is called
			$this->clear_menu_tree_cache($this->get_value("menu_id"));
			return true;
		}
		return false;
	}

	/**
	 * Returns a list of all deactivated menu entries.
	 * 
	 * @return array the menu entries
	 */
	public function get_all_deactivated_menu_entries() {

		$tree = new Array2Tree();
		foreach($this->db->query_slave_all("
			SELECT *
			FROM `".MenuEntryObj::TABLE."` me
			JOIN `".MenuEntryTranslationObj::TABLE."` met ON (me.`entry_id` = met.`entry_id`)
			WHERE `language` = @language
			", array('@language' => $this->core->current_language)) as $entry) {
			$entry['#link'] = "";
			$entry['#active'] = ($entry['active'] === "yes") ? true: false;
			$tree->add_item($entry);
		}
		$treelist = $tree->get_tree(0);
		$tree->get_only_inactive($treelist);

		return $this->get_flat_entry_id_list($treelist);
	}

	/**
	 * Converts a tree into a flat list array.
	 * 
	 * @param array $treelist
	 *   the entry tree list
	 * 
	 * @return array a flatten list where the tree will be restored.
	 */
	private function get_flat_entry_id_list($treelist) {
		$result = array();
		foreach($treelist AS $entry) {
			$result[$entry['entry_id']] = (int)$entry['entry_id'];
			if(!empty($entry['#childs'])) {
				$result = array_merge($result, $this->get_flat_entry_id_list($entry['#childs']));

			}
		}
		return $result;
	}

	/**
	 * Tries to invalidate the get menu tree cache for the given menu id
	 * 
	 * @param string $menu_id 
	 *   the menu id
	 * @param string $old_menu_id 
	 *   the original menu id (optional, default = '')
	 */
	private function clear_menu_tree_cache($menu_id, $old_menu_id = '') {
		if(!empty($old_menu_id)) {
			$this->core->mcache('MenuObj:get_menu_tree:'.$old_menu_id, "",1);
		}
		if($old_menu_id != $menu_id && !empty($menu_id)) {
			$this->core->mcache('MenuObj:get_menu_tree:'.$menu_id, "",1);
		}
	}

}

