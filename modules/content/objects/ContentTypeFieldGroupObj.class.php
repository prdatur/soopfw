<?php

/**
 * Stores the field groups for the content type
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class ContentTypeFieldGroupObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "content_types_field_groups";
	const ACTIVE_YES = 'yes';
	const ACTIVE_NO = 'no';

	/**
	 * Construct
	 * The field group id is unique and links a content type and a field group
	 *
	 * @param string $id
	 *   the field group id, this must be unique (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("id"));
		$this->db_struct->add_required_field("id", t("id"), PDT_STRING, '', 70);
		$this->db_struct->add_hidden_field("content_type", t("content type"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field("field_group", t("field group"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field("name", t("name"), PDT_STRING, '', 70);
		$this->db_struct->add_hidden_field("order", t("order"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("max_value", t("max values"), PDT_INT, 1, 'UNSIGNED');
		$this->db_struct->add_required_field("required", t("required"), PDT_ENUM, 'no', array(self::ACTIVE_YES => t('Yes'), self::ACTIVE_NO => t('No')));

		if (!empty($id)) {
			if (!$this->load(array($id), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Delete the given field group, also deletes values for this group
	 * It will search all pages for this content field and will remove the value from the content too.
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$this->transaction_auto_begin();
		$id = $this->get_value("id");
		if(parent::delete()) {
			if(!$this->db->query_master("DELETE FROM `".ContentTypeFieldGroupFieldValueObj::TABLE."` WHERE `content_type_field_group_id` = @id", array("@id" => $id))) {
				$this->transaction_auto_rollback();
				return false;
			}

			foreach($this->db->query_slave_all("SELECT `serialized_data` , `page_id`, `language`, `revision` FROM `".PageObj::TABLE."` WHERE `serialized_data` LIKE '%\"search_string\":%'", array("search_string" => $id)) AS $page) {
				$page['serialized_data'] = json_decode($page['serialized_data'], true);
				unset($page['serialized_data'][$id]);
				$page['serialized_data'] = json_encode($page['serialized_data']);

				$page_translation = new PageRevisionObj($page['page_id'], $page['language'], $page['revision']);
				$page_translation->serialized_data = $page['serialized_data'];
				if(!$page_translation->save()) {
					$this->transaction_auto_rollback();
					return false;
				}
			}
			$this->transaction_auto_commit();
			return true;
		}
		$this->transaction_auto_rollback();
		return false;
	}

}

