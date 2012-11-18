<?php

/**
 * Stores the values for the content type field group fields
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class ContentTypeFieldGroupFieldValueObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "content_types_field_group_field_values";

	/**
	 * Construct
	 *
	 * @param int $page_id
	 *   the page id (optional, default = "")
	 * @param string $language
	 *   the language (optional, default = "")
	 * @param int $revision
	 *   the revision (optional, default = 1)
	 * @param string $content_type_field_group_id
	 *   the id from ContentTypeFieldGroupObj (optional, default = "")
	 * @param string $field_type
	 *   the field type (optional, default = "")
	 * @param int $index
	 *   the value index for multi fields (optional, default = 0)
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($page_id = "", $language = "", $revision = 1, $content_type_field_group_id = "", $field_type = "", $index = 0, $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("page_id", "language", "revision", "content_type_field_group_id","field_type", "index"));
		$this->db_struct->add_field("page_id", t("page id"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("language", t("the language"), PDT_STRING, '', 4);
		$this->db_struct->add_field("revision", t("the revision"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field("content_type_field_group_id", t("content type field group id"), PDT_STRING, '', 70);
		$this->db_struct->add_field("field_type", t("field type"), PDT_STRING, '', 70);
		$this->db_struct->add_field("index", t("index"), PDT_MEDIUMINT, 0, 'UNSIGNED');
		$this->db_struct->add_field("value", t("value"), PDT_TEXT);

		if (!empty($page_id) && !empty($language) && !empty($revision) && !empty($content_type_field_group_id) && !empty($field_type)) {
			if (!$this->load(array($page_id,$language, $revision, $content_type_field_group_id, $field_type, $index), $force_db)) {
				return false;
			}
		}
	}


	/**
	 * Insert the current data and delete all previous revision except the last 20
	 *
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there (optional, default=false)
	 *
	 * @return boolean true on success, else false
	 */
	public function insert($ignore = false) {
		if(parent::insert($ignore)) {

			// Will delete all revisions except the last 20.
			$this->db->query_master("DELETE FROM `".self::TABLE."` WHERE `page_id` = ipage_id AND `language` = @language AND `content_type_field_group_id` = @content_type_field_group_id AND `field_type` = @field_type AND `index` = iindex AND `revision` <= irevision", array(
				'ipage_id' => $this->values['page_id'],
				'@language' => $this->values['language'],
				'@content_type_field_group_id' => $this->values['content_type_field_group_id'],
				'@field_type' => $this->values['field_type'],
				'iindex' => $this->values['index'],
				'irevision' => $this->values['revision']-20,
			));
			return true;
		}
		return false;
	}

}

