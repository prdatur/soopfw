<?php

/**
 * Stores the page
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.link.objects
 */
class PageRevisionObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "content_page_revisions";

	/**
	 * Construct
	 *
	 * @param int $page_id the page id (optional, default = "")
	 * @param string $language the language, if not provided current language will be used (optional, default = '')
	 * @param int $revision revision (optional, default = 0)
	 * @param boolean $force_db if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($page_id = "", $language = '', $revision = 0, $force_db = false) {
		parent::__construct();

		if(empty($language)) {
			$language = $this->core->current_language;
		}

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("page_id", "language", "revision"));
		$this->db_struct->add_field("page_id", t("page id"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("language", t("language"), PDT_STRING, '', 4);
		$this->db_struct->add_field("revision", t("revision"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("title", t("title"), PDT_STRING);
		$this->db_struct->add_field("created", t("created"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		$this->db_struct->add_field("created_by", t("created by"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("serialized_data", t("data"), PDT_TEXT);

		if (!empty($page_id) && !empty($language)) {
			if($revision == 0) {
				$revision = $this->get_last_revision($page_id, $language);
			}
			$this->load(array($page_id, $language, $revision));
		}
	}

	/**
	 * Insert the current data and delete all previous revision except the last 20
	 *
	 * @param boolean $ignore Don't throw an error if data is already there (optional, default=false)
	 * @param boolean $force_insert normaly we just insert a new revision if we changed something, with this we can force it (usefull if we reactivate without a change the page) (optional, default=false)
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false, $force_insert = false) {
		if(!$force_insert && $this->load_success && empty($this->values_changed)) {
			return true;
		}

		$this->values['revision'] = $this->get_next_revision($this->values['page_id']);
		if(parent::insert($ignore)) {

			$this->db->query_master("DELETE FROM `".self::TABLE."` WHERE `page_id` = ipage_id AND `language` = @language AND  `revision` <= irevision", array(
				'ipage_id' => $this->values['page_id'],
				'@language' => $this->values['language'],
				'@revision' => $this->values['revision']-20
			));

			$alias_title = UrlAliasObj::get_alias_string($this->values['title']);
			$alias_match = $this->db->query_slave_first("SELECT `id`, `alias` FROM `".UrlAliasObj::TABLE."` WHERE `module` = 'content' AND `action` = 'view' AND `params` = 'ipage_id|current_language'", array("ipage_id" => $this->values['page_id'], "current_language" => $this->values['language']));


			if(empty($alias_match) || $alias_match['alias'] != $alias_title) {
				$url_alias_object = new UrlAliasObj();
				if(!empty($alias_match)) {

					$url_alias_object = new UrlAliasObj($alias_match['id']);
				}
				else {
					$url_alias_object->module = 'content';
					$url_alias_object->action = 'view';
					$url_alias_object->params = $this->values['page_id']."|".$this->values['language'];
				}

				$change = true;
				$alias_count = -1;
				foreach($this->db->query_slave_all("SELECT `alias`,`params` FROM `".UrlAliasObj::TABLE."` WHERE `alias` LIKE 'alias_string%' ORDER BY `alias` ASC", array('alias_string' => $alias_title)) AS $row) {

					if(!preg_match("/^".preg_quote($alias_title, "/")."(-([0-9]+))?$/", $row['alias'], $matches)) {
						continue;
					}
					if($row['params'] == $this->values['page_id']."|".$this->values['language']) {
						$change = false;
						break;
					}

					if(!isset($matches[2])) {
						$matches[2] = 0;
					}

					if((int)$matches[2] > $alias_count) {
						$alias_count = (int)$matches[2];
					}

				}
				if($change == true) {

					$url_alias_object->alias = $alias_title;
					if($alias_count != -1) {
						$url_alias_object->alias .= '-'.($alias_count+1);
					}

					$url_alias_object->save_or_insert();
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns the next revision
	 *
	 * if page id is not provided it will try to get it from the current values
	 *
	 * @param int $page_id the page id (optional, default = 0)
	 * @return int the next revision
	 */
	public function get_next_revision($page_id = 0) {
		if($page_id == 0) {
			$page_id = $this->values['page_id'];
		}

		$latest_revision = $this->get_last_revision($page_id);

		return ++$latest_revision;
	}

	/**
	 * Returns the last revision for this page
	 * if page id is not provided it will try to get it from the current values
	 *
	 * @param int $page_id the page id (optional, default = 0)
	 * @param string $language the language (optional, default = '')
	 * @return int returns the last revision, if we have no page id it will return 0 as the first revision
	 */
	public function get_last_revision($page_id = 0, $language = '') {
		if($page_id == 0) {
			$page_id = $this->values['page_id'];
		}

		if(empty($language)) {
			$language = $this->core->current_language;
		}

		if(empty($page_id)) {
			return 0;
		}

		$row = $this->db->query_slave_first("SELECT `revision` FROM `".self::TABLE."` WHERE `page_id`  = ipage_id AND `language` = @language ORDER BY `revision` DESC", array(
			"ipage_id" => $page_id,
			"@language" => $language,
			));

		//No revision created yet, return 1
		if(empty($row)) {
			return 0;
		}
		return (int)$row['revision'];
	}

}

?>