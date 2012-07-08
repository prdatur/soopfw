<?php

/**
 * This object holds a menu (just the menu, not the translation or menu entries)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 */
class MenuObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "menu_menu";

	/**
	 * Constructor
	 *
	 * @param string $menu_id the menu id (optional, default = "")
	 * @param boolean $force_db if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($menu_id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("menu_id");
		$this->db_struct->add_required_field("menu_id", t("menu id"), PDT_STRING);
		$this->db_struct->add_required_field("title", t("title"), PDT_STRING);

		if (!empty($menu_id)) {
			$this->load($menu_id, $force_db);
		}
	}

	/**
	 * Save the given Data
	 *
	 * @param boolean $save_if_unchanged Save this object even if no changes to it's values were made
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {
		$this->transaction_auto_begin();
		$old_menu_id = $this->get_original_value("menu_id");
		if(parent::save($save_if_unchanged)) {


			if($this->values['menu_id'] != $old_menu_id) {
				//We clear only the menu tree cache if we changed the menu id
				$this->clear_menu_tree_cache($this->values['menu_id'], $old_menu_id);

				//Replacing the old menu within menu entries.
				if($this->db->query_master("UPDATE `".MenuEntryObj::TABLE."` SET `menu_id` = @new_menu WHERE `menu_id` = @old_menu", array(
					'@new_menu' => $this->values['menu_id'],
					'@old_menu' => $old_menu_id
				))) {
					$this->transaction_auto_commit();
					return true;
				}
			}
			else {
				$this->transaction_auto_commit();
				return true;
			}
		}
		$this->transaction_auto_rollback();
		return false;
	}

	/**
	 * Insert the current data
	 *
	 * @param boolean $ignore Don't throw an error if data is already there (optional, default=false)
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {
		if(parent::insert($ignore)) {

			//Need to clear the cache key for this menu_id because we have changed the menu and it can be that we changed the menu id, so it must be rebuilded,
			$this->clear_menu_tree_cache($this->get_value("menu_id"));
			return true;
		}
		return false;
	}

	/**
	 * Delete the given menu, also deletes all menu entries for this menu
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$menu_id = $this->get_value("menu_id");
		if(parent::delete()) {
			$object_ids = array();

			//Get all menu entry ids which are linked with this menu
			foreach($this->db->query_slave_all("SELECT `entry_id` FROM `".MenuEntryObj::TABLE."` WHERE `menu_id` = @menu_id", array("@menu_id" => $menu_id)) AS $menu_entry) {
				$object_ids[] = $menu_entry['entry_id'];
			}

			//init menu entry object container which loads our entry objects
			$menu_entry_obj = new MenuEntryObj();

			//Load all linked menu entries and delete them
			foreach($menu_entry_obj->load_multiple($object_ids) AS $obj) {
				$obj->delete();
			}

			//Need to clear the cache key for this menu_id because we have changed the menu and it can be that we changed the menu id, so it must be rebuilded,
			$this->clear_menu_tree_cache($menu_id);

			return true;
		}
		return false;
	}

	/**
	 * Get the menu tree
	 * 
	 * @param boolean $just_active 
	 *   if set to true the active childs will be marked as active and only the #childs will be filled with the active ones (optional, default = false)
	 * @param array $alter_menu
	 *   if provided it will be merged to the original array, old existing will be overriden (optional, default = array())
	 * 
	 * @return array the menu array
	 */
	public function get_menu_tree($just_active = false, array $alter_menu = array()) {
		if(!$this->load_success()) {
			return array();
		}

		$array_2_tree = new Array2Tree();

		$skip_entries = array();

		$menu_entries = $this->core->mcache('MenuObj:get_menu_tree:'.$this->values['menu_id']);
		if(empty($menu_entries) || !is_array($menu_entries)) {
			$menu_entries = $this->db->query_slave_all("SELECT * FROM `".MenuEntryObj::TABLE."` WHERE `menu_id` = @menu_id ORDER BY `order` ASC", array("@menu_id" => $this->values['menu_id']));
			$this->core->mcache('MenuObj:get_menu_tree:'.$this->values['menu_id'], $menu_entries);
		}

		$menu_entries = array_merge_recursive($menu_entries, $alter_menu);
		
		foreach($menu_entries AS $menu_entry) {

			if(isset($skip_entries[$menu_entry['parent_id']])) {
				$skip_entries[$menu_entry['entry_id']] = true;
				continue;
			}

			if (!isset($menu_entry['#id'])) {
				$menu_translation = new MenuEntryTranslationObj($menu_entry['entry_id'], $this->core->current_language);
				$translations = $menu_translation->get_values();

				if(empty($translations) || ($translations['active'] == MenuEntryTranslationObj::ACTIVE_NO && !$this->right_manager->has_perm("admin.menu.view_inactive_entries", false)) || (!empty($translations['perm']) && !$this->right_manager->has_perm($translations['perm'], false))) {
					$skip_entries[$menu_entry['entry_id']] = true;
					continue;
				}

				$menu_entry['#id'] = 'soopfw_'.$this->values['menu_id']."_".$menu_entry['entry_id']; //A unique id which will be needed to generate the submenu
				$menu_entry['#title'] = $translations['title']; //The main title
				if($translations['active'] == MenuEntryTranslationObj::ACTIVE_NO) {
					$menu_entry['#inactive'] = true; //The main title
				}
				$menu_entry['#link'] = $translations['destination']; // The main link
			}
			$array_2_tree->add_item($menu_entry);
		}
		return $array_2_tree->get_tree(0, $just_active);
	}

	/**
	 * Tries to invalidate the get menu tree cache for the given menu id
	 * @param string $menu_id the menu id
	 * @param string $old_menu_id the original menu id (optional, default = '')
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

?>