<?php

/**
 * Stores a content type
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class ContentTypeObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "content_types";

	/**
	 * Construct
	 *
	 * @param string $content_type
	 *   the content type (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($content_type = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("content_type"));
		$this->db_struct->add_required_field("content_type", t("Content type"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field("display_name", t("Displayed name"), PDT_STRING, '', 70);
		//$this->db_struct->add_field("permission", t("Permission"), PDT_STRING, '', 70);
		$this->db_struct->add_field("create_alias", t("create alias"), PDT_ENUM, 'yes', array('no' => t('No'), 'yes' => t('Yes')));


		if (!empty($content_type)) {
			if (!$this->load(array($content_type), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Delete the given content_type, also deletes all linked field groups for this content type
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$content_type = $this->get_value("content_type");
		if(parent::delete()) {

			$filter = DatabaseFilter::create(PageObj::TABLE)
				->add_where('content_type', $content_type)
				->add_column('page_id')
				->add_column('language');

			foreach ($filter->select_all() AS $row) {
				DatabaseFilter::create(PageRevisionObj::TABLE)
					->add_where('page_id', $row['page_id'])
					->add_where('language', $row['language'])
					->delete();
			}

			DatabaseFilter::create(PageObj::TABLE)
					->add_where('content_type', $content_type)
					->delete();

			$object_ids = array();

			//Get all menu entry ids which are linked with this menu
			foreach($this->db->query_slave_all("SELECT `id` FROM `".ContentTypeFieldGroupObj::TABLE."` WHERE `content_type` = @content_type", array("@content_type" => $content_type)) AS $entry) {
				$object_ids[] = $entry['id'];
			}
			if(count($object_ids) > 0) {
				//init content type group object container which loads our entry objects
				$entry_obj = new ContentTypeFieldGroupObj();

				//Load all groups for this content type
				foreach($entry_obj->load_multiple($object_ids) AS $obj) {
					$obj->delete();
				}
			}

			// If solr exists and the content type was indexed, remove it.
			if ($this->core->module_enabled('solr')) {

				$indexed_content_types = $this->core->get_dbconfig("content", Content::CONTENT_SOLR_INDEXED_TYPES, array());
				unset($indexed_content_types[$content_type]);
				$this->core->dbconfig("content", Content::CONTENT_SOLR_INDEXED_TYPES, $indexed_content_types);

				$solr = SolrFactory::create_instance('content', Content::CONTENT_SOLR_SERVER);
				if ($solr !== false) {
					$solr->deleteByQuery('contenttype_s:' . $content_type);
				}
			}
			return true;
		}
		return false;
	}

}

