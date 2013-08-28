<?php

/**
 * Stores the page
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class PageObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "content_pages";

	const DELETED_YES = 'yes';
	const DELETED_NO = 'no';

	/**
	 * Construct
	 *
	 * @param int $page_id
	 *   the page id (optional, default = "")
	 * @param string $language
	 *   the language, if not provided current language will be used (optional, default = '')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($page_id = "", $language = '', $force_db = false) {
		parent::__construct();

		if(empty($language)) {
			$language = $this->core->current_language;
		}

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("page_id", "language"));
		$this->db_struct->add_field("page_id", t("page id"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("language", t("language"), PDT_STRING, '', 4);
		$this->db_struct->add_field("content_type", t("content type"), PDT_STRING, '', 70);
		$this->db_struct->add_field("created", t("Created"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		$this->db_struct->add_field("created_by", t("Created by"), PDT_INT, $this->session->current_user()->user_id, 'UNSIGNED');
		$this->db_struct->add_field("last_revision", t("last revision"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("last_access", t("last access"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		$this->db_struct->add_field("last_modified", t("last modified"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		$this->db_struct->add_field("last_modified_by", t("last modified by"), PDT_INT, $this->session->current_user()->user_id, 'UNSIGNED');
		$this->db_struct->add_hidden_field("current_menu_entry_id", t("current menu"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("view_count", t("view count"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("edit_count", t("edit count"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("deleted", t("deleted"), PDT_ENUM, self::DELETED_NO, array(self::DELETED_YES => t('Yes'), self::DELETED_NO => t('No')));

		if (!empty($page_id) && !empty($language)) {
			$this->load(array($page_id, $language), $force_db);
		}
	}

	/**
	 * Save the given Data, also de/re-activate the menu entry if we changed the publish status.
	 *
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made
	 *
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {

		if(!empty($this->values_changed) && isset($this->values_changed['last_revision'])) {
			$menu_activiation = (empty($this->values_changed['last_revision']) ? MenuEntryTranslationObj::ACTIVE_NO : MenuEntryTranslationObj::ACTIVE_YES);
		}
		if(parent::save($save_if_unchanged)) {
			if(!empty($menu_activiation) && !empty($this->values['current_menu_entry_id'])) {

				$menu_entry_translation_obj = new MenuEntryTranslationObj($this->values['current_menu_entry_id'], $this->values['language']);
				if ($menu_entry_translation_obj->load_success()) {
					$menu_entry_translation_obj->active = $menu_activiation;
					$menu_entry_translation_obj->save();
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Delete the given data.
	 *
	 * @param boolean $really_delete
	 *   if we want to really delete the page or just set the delete flag. (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function delete($really_delete = false) {
		$page_id = $this->values['page_id'];
		$language = $this->values['language'];
		$current_menu_entry_id = $this->values['current_menu_entry_id'];
		if($really_delete == false || parent::delete()) {
			$alias = $this->db->query_slave_first("SELECT `alias` FROM `".UrlAliasObj::TABLE."` WHERE `module` = 'content' AND `action` = 'view' AND `params` = :params", array(
				":params" => intval($page_id) . '|' . $language,
			));
			if(!empty($alias)) {
				$this->core->mcache('url_alias_match_'.md5($alias['alias']), "", 1);
			}
			$this->db->query_master("DELETE FROM `".UrlAliasObj::TABLE."` WHERE `module` = 'content' AND `action` = 'view' AND `params` = :params", array(
				":params" => intval($page_id) . '|' . $language,
			));

			$menu_entry_obj = new MenuEntryTranslationObj($current_menu_entry_id, $language);
			$menu_entry_obj->save_delete();

			if($really_delete == false) {
				$this->deleted = self::DELETED_YES;
				$this->last_revision = '';
				$return = $this->save();

				if ($return === true) {
					$this->delete_solr($page_id, $language);
				}

				return $return;
			}


			$this->db->query_master("DELETE FROM `".ContentTypeFieldGroupFieldValueObj::TABLE."` WHERE `page_id` = ipage_id AND `language` = :language", array(
				'ipage_id' => $this->values['page_id'],
				':language' => $this->values['language']
			));

			$return = $this->db->query_master("DELETE FROM `".PageRevisionObj::TABLE."` WHERE `page_id` = ipage_id AND `language` = :language", array(
				'ipage_id' => $this->values['page_id'],
				':language' => $this->values['language']
			));

			if ($return !== false) {
				$this->delete_solr($page_id, $language);
			}

			return $return;
		}
		return false;
	}

	/**
	 * Returns the next available page id
	 *
	 * @return int the page id
	 */
	public function get_free_id() {

		$row = $this->db->query_slave_first("SELECT `page_id` FROM `".self::TABLE."` ORDER BY `page_id` DESC");

		//No pages created yet, return 1
		if(empty($row)) {
			return 1;
		}
		return (int)(++$row['page_id']);
	}

	/**
	 * Returns the pure content for this content.
	 *
	 * @return string the content
	 */
	public function get_content() {
		$content = "";
		$page_id = $this->page_id;
		$page_data_array = explode("|", $page_id, 2);
		if(!isset($page_data_array[1])) {
			$page_data_array[1] = '';
		}

		$page = new PageObj($page_data_array[0], $page_data_array[1]);
		if(!$page->load_success()) {
			
			return $content;
		}


		if($page->deleted == 'yes'  && !$this->right_manager->has_perm("admin.content.delete", false)) {
			return $content;
		}

		if(empty($page->last_revision) && !$this->right_manager->has_perm("admin.content.create", false)) {
			return $content;
		}

		$page_revision = new PageRevisionObj($page_data_array[0], $page_data_array[1], $page->last_revision);
		if(!$page_revision->load_success()) {
			return $content;
		}

		$values = array_merge($page->get_values(), $page_revision->get_values());

		$data_array = json_decode($values['serialized_data'], true);

		$module_tpl_dir = SITEPATH.'/modules/content/templates/';

		$content_type_tpl = $module_tpl_dir.'/field_groups/'.$values['content_type'].".tpl";

		$content_smarty = new Smarty();
		$content_smarty->enableSecurity(); //Can not be transformed into underscore couse this comes from original smarty class
		$content_smarty->init();
		$content_smarty->set_tpl($module_tpl_dir.'/field_groups/');

		if(file_exists($content_type_tpl)) {
			$content_smarty->assign_by_ref("data", $data_array);
			$content = $content_smarty->fetch($content_type_tpl);
		}
		else {
			foreach($data_array AS $field_group_id => $field_group_values) {
				$field_group_tpl = $module_tpl_dir.'/field_groups/'.$field_group_id.".tpl";
				if(!file_exists($field_group_tpl)) {
					$group_obj = new ContentTypeFieldGroupObj($field_group_id);
					$field_group_tpl = $module_tpl_dir.'/field_groups/'.$group_obj->field_group.".tpl";
				}
				$content_smarty->clearAllAssign();
				$content_smarty->assign("data", array('elements' => $field_group_values));
				$content .= $content_smarty->fetch($field_group_tpl);
			}
		}
		return $content;
	}

	/**
	 * Deletes the given entry from solr.
	 *
	 * @param int $id
	 *   the page id
	 *   if not provided it will try to get the current one. (optional, default = "")
	 * @param string $language
	 *   the page language
	 *   if not provided it will try to get the current one. (optional, default = "")
	 */
	public function delete_solr($id = "", $language = "") {

		if (!$this->core->module_enabled('solr')) {
			return false;
		}

		$solr = SolrFactory::create_instance('content', Content::CONTENT_SOLR_SERVER);
		if ($solr === false) {
			return;
		}

		if (empty($id)) {
			$id = $this->page_id;
		}

		if (empty($language)) {
			$language = $this->language;
		}

		$solr->deleteById('content::' . $id . ':' . $language);
	}

	/**
	 * Returns the alias for this page.
	 *
	 * @return string the alias, or if alias not exist returns false
	 */
	public function get_alias() {
		$alias_entry = $this->db->query_slave_first("SELECT `alias` FROM `" . UrlAliasObj::TABLE . "` WHERE `module` = 'content' AND `action` = 'view' AND `params` = :params", array(
			":params" => intval($this->page_id) . '|' . $this->language,
		));
		if (!empty($alias_entry)) {
			return $alias_entry['alias'];
		}

		return false;
	}
}

