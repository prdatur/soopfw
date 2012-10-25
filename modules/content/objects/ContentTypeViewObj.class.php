<?php

/**
 * Stores a content type view
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.content.objects
 * @category ModelObjects
 */
class ContentTypeViewObj extends AbstractDataManagment
{

	/**
	 * Define enum values.
	 */
	const PAGER_ENABLED = 1;
	const PAGER_DISABLED = 0;

	const TRUNCATE_POLICY_CHAR_SAVE = 'char_save';
	const TRUNCATE_POLICY_WORD_SAVE = 'word_save';
	const TRUNCATE_POLICY_PARAGRAPH_SAVE = 'paragraph_save';

	/**
	 * Define base table.
	 */
	const TABLE = "content_type_views";

	/**
	 * The view id.
	 *
	 * @var string
	 */
	const FIELD_ID = 'id';

	/**
	 * The view name.
	 *
	 * @var string
	 */
	const FIELD_NAME = 'name';

	/**
	 * The content type.
	 *
	 * @var string
	 */
	const FIELD_CONTENT_TYPE = 'content_type';

	/**
	 * Holds a json encoded string which includes all fields which we want to display.
	 *
	 * @var string
	 */
	const FIELD_DISPLAYED_FIELDS = 'displayed_fields';

	/**
	 * Holds a json encoded string with the configuration how we want to sort.
	 *
	 * @var string
	 */
	const FIELD_SORT_FIELDS = 'sort_fields';

	/**
	 * If we have a pager or not
	 * values:
	 *	0 => disabled
	 *  1 => enabled
	 *
	 * @var int
	 */
	const FIELD_USE_PAGER = 'use_pager';

	/**
	 * The maximum entries.
	 * If pager is not enabled we have also not more than the configured entries but without the abillity to get next results.
	 *
	 * @var int
	 */
	const FIELD_MEPP = 'mepp';

	/**
	 * The maximum chars a value can have before it gets truncated.
	 *
	 * @var int
	 */
	const FIELD_TRUNCATE_CHARS = 'truncate_chars';

	/**
	 * The truncate policy
	 * Use one of ContentTypeViewObj::TRUNCATE_POLICY_*
	 * @var array
	 */
	const FIELD_TRUNCATE_POLICY = 'truncate_policy';


	/**
	 * Construct
	 *
	 * @param string $name
	 *   the view name (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($name = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true); 
		$this->db_struct->add_reference_key(array(self::FIELD_ID));
		$this->db_struct->add_required_field(self::FIELD_ID, t("View id"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field(self::FIELD_NAME, t("View name"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field(self::FIELD_CONTENT_TYPE, t("content type"), PDT_STRING, '', 70);
		$this->db_struct->add_required_field(self::FIELD_DISPLAYED_FIELDS, t("Displayed fields"), PDT_TEXT);
		$this->db_struct->add_field(self::FIELD_SORT_FIELDS, t("Sort on"), PDT_TEXT);

		$this->db_struct->add_field(self::FIELD_USE_PAGER, t("Enable pager"), PDT_ENUM, self::PAGER_ENABLED, array(
			self::PAGER_DISABLED => t('Disabled'),
			self::PAGER_ENABLED => t('Enabled'),
		));

		$this->db_struct->add_field(self::FIELD_MEPP, t("Max entries (per page)"), PDT_INT, 10, 'UNSIGNED');

		$this->db_struct->add_field(self::FIELD_TRUNCATE_CHARS, t("Truncate chars"), PDT_INT, 300, 'UNSIGNED');
		$this->db_struct->add_description(self::FIELD_TRUNCATE_CHARS, t('Any field which is longer than configurated chars will be truncated'));

		$this->db_struct->add_field(self::FIELD_TRUNCATE_POLICY, t("Truncate policy"), PDT_ENUM, self::TRUNCATE_POLICY_WORD_SAVE, array(
			self::TRUNCATE_POLICY_CHAR_SAVE => t('Char save'),
			self::TRUNCATE_POLICY_WORD_SAVE => t('Word save'),
			self::TRUNCATE_POLICY_PARAGRAPH_SAVE => t('Paragraph save'),
		));

		$this->db_struct->add_index(MysqlTable::INDEX_TYPE_INDEX, array(
			self::FIELD_CONTENT_TYPE
		));

		if (!empty($name)) {
			if (!$this->load(array($name), $force_db)) {
				return false;
			}
		}
	}
}

