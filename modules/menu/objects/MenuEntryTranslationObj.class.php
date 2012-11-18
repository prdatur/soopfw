<?php

/**
 * This object holds a menu entry translation which also provides the destination
 * for the given entry in the given language.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class MenuEntryTranslationObj extends AbstractDataManagement
{

	/**
	 * Define constances
	 */
	const TABLE = "menu_entry_translation";
	const ACTIVE_YES = 'yes';
	const ACTIVE_NO = 'no';
	const ALWAYS_OPEN_YES = 'yes';
	const ALWAYS_OPEN_NO = 'no';

	/**
	 * Constructor
	 *
	 * @param int $entry_id
	 *   the menu id (optional, default = "")
	 * @param string $language
	 *   the language (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($entry_id = "", $language = "", $force_db = false) {
		parent::__construct();

		if(empty($language)) {
			$language = $this->core->current_language;
		}
		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("entry_id", "language"));
		$this->db_struct->add_hidden_field("entry_id", t("entry id"), PDT_INT);
		$this->db_struct->add_required_field("language", t("language"), PDT_LANGUAGE_ENABLED, $language, 4);
		$this->db_struct->add_required_field("title", t("title"), PDT_STRING);
		$this->db_struct->add_field("perm", t("permission"), PDT_STRING, '');
		$this->db_struct->add_field("always_open", t("always open?"), PDT_ENUM, self::ALWAYS_OPEN_NO, array(self::ALWAYS_OPEN_YES => t('Yes'), self::ALWAYS_OPEN_NO => t('No')));
		$this->db_struct->add_required_field("destination", t("destination"), PDT_STRING);
		$this->db_struct->add_required_field("active", t("active"), PDT_ENUM, self::ACTIVE_YES, array(self::ACTIVE_YES => t('Yes'), self::ACTIVE_NO => t('No')));

		if (!empty($entry_id) && !empty($language)) {
			if (!$this->load(array($entry_id, $language), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Insert the current menu entry translation.
	 *
	 * If $menu_id is not provided it will check if we have setup an menu id within current core static cache
	 * module: menu_entry, key: insert_menu_id
	 * If this is also empty it will be not created and return false.
	 *
	 * @param string $menu_id
	 *   the menu id. (optional, default='')
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there. (optional, default=false)
	 *
	 * @return boolean true on success, else false.
	 */
	public function insert($menu_id = "", $ignore = false) {
		$entry_id = $this->get_value("entry_id");

		// If we have no entry id we need to add a new menu entry too.
		if(empty($entry_id)) {

			// Try to get the static cached insert menu id key if we did not provided the menu id.
			if(empty($menu_id)) {
				$menu_id = $this->core->cache("menu_entry", "insert_menu_id");
			}

			// If it's still empty return false.
			if(empty($menu_id)) {
				return false;
			}

			// If the menu does not exist return also false.
			$menu_obj = new MenuObj($menu_id);
			if(!$menu_obj->load_success()) {
				return false;
			}

			$this->transaction_auto_begin();

			// Create the new menu entry
			$menu_entry_obj = new MenuEntryObj();
			$menu_entry_obj->menu_id = $menu_id;

			// Set the entry_id for the translation to the created menu entry if it succeeds.
			if($menu_entry_obj->insert()) {
				$this->__set("entry_id", $menu_entry_obj->entry_id);
			}
		}

		// Try to insert the menu entry translation.
		$result = parent::insert($ignore);

		// If we had no entry id we maybe need to rollback or commit based up on if the menu entry translation could be
		// inserted.
		if(empty($entry_id)) {
			if($result == true) {
				$this->transaction_auto_commit();
			}
			else {
				$this->transaction_auto_rollback();
			}
		}

		return $result;
	}

	/**
	 * Returns whether this menu entry has childs or not.
	 *
	 * @return boolean true if menu entry has childs or not
	 */
	public function has_childs() {
		$entry_id = $this->get_value("entry_id");
		$language = $this->get_value("language");
		$has_childs = $this->db->query_slave_first("
			SELECT 1
			FROM `".MenuEntryObj::TABLE."` me
			JOIN `".MenuEntryTranslationObj::TABLE."` met ON (me.`entry_id` = met.`entry_id`)
			WHERE `parent_id` = ientry_id AND `language` = @language", array('ientry_id' => $entry_id, '@language' => $language));
		return !empty($has_childs);
	}

	/**
	 * Delete the given menu entry translation, if the menu entry has childs for the current language, delete it recrusive
	 *
	 * @return boolean true on success, else false
	 */
	public function save_delete() {
		if (!$this->load_success()) {
			return true;
		}
		if($this->has_childs()) {
			$this->active = MenuEntryTranslationObj::ACTIVE_NO;
			$this->destination = '';
			return $this->save();
		}
		else {
			return $this->delete();
		}
	}

	/**
	 * Delete the given menu entry translation, if the menu entry has childs for the current language, delete it recrusive
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		if (!$this->load_success()) {
			return true;
		}
		$entry_id = $this->get_value("entry_id");
		if ($entry_id <= 0) {
			return true;
		}
		if ($this->delete_childs() && parent::delete()) {
			if($this->core->db->query_slave_count("SELECT 1 FROM `".MenuEntryTranslationObj::TABLE."` WHERE `entry_id` = ientry_id", array('ientry_id' => $entry_id), 1) <= 0) {
				$menu_entry_obj = new MenuEntryObj($entry_id);
				$menu_entry_obj->delete();
			}
			return true;
		}
		return false;
	}

	/**
	 * Deletes all child translation entries
	 *
	 * @return boolean true on sucess, else false
	 */
	public function delete_childs() {
		if (!$this->load_success()) {
			return true;
		}
		$entry_id = $this->get_value("entry_id");
		if ($entry_id <= 0) {
			return true;
		}
		$language = $this->get_value("language");
		$menu_entry_obj = new MenuEntryObj($entry_id);

		// Only delete childs if the parent menu entry exist.
		if($menu_entry_obj->load_success()) {
			foreach($this->db->query_slave_all("
				SELECT `".MenuEntryObj::TABLE."`.`entry_id`
				FROM `".MenuEntryObj::TABLE."`
				JOIN `".MenuEntryTranslationObj::TABLE."` ON (`".MenuEntryObj::TABLE."`.`entry_id` = `".MenuEntryTranslationObj::TABLE."`.`entry_id`)
				WHERE `parent_id` = ientry_id AND `language` = @language", array('ientry_id' => $entry_id, '@language' => $language)) AS $child_menu_entry) {

				// Delete Menu entry translation.
				$obj = new MenuEntryTranslationObj($child_menu_entry['entry_id'], $language);
				$obj->delete();

				// If we have no menu translation left over delete the menu entry also.
				if($this->core->db->query_slave_count("SELECT 1 FROM `".MenuEntryTranslationObj::TABLE."` WHERE `entry_id` = ientry_id", array('ientry_id' => $child_menu_entry['entry_id']), 1) <= 0) {
					$menu_entry_obj = new MenuEntryObj($child_menu_entry['entry_id']);
					$menu_entry_obj->delete();
				}
			}

		}
		return true;
	}

}

