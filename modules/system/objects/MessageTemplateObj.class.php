<?php

/**
 * The base message template.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 * @category ModelObjects
 */
class MessageTemplateObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = 'core_message_templates';

	/**
	 * Constructor
	 * 
	 * @param string $id 
	 *   the message template id (optional, default = '')
	 * @param string $language 
	 *   the language (optional, default = 'en')
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $language = 'en', $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key(array("id", 'language'));

		$this->db_struct->add_field("id", t('Message ID'), PDT_STRING, '', 120);
		$this->db_struct->add_field("language", t("Language"), PDT_STRING, '', 3);
		$this->db_struct->add_field("template", t("The template str"), PDT_TEXT);
		if (!empty($id) && !empty($language)) {
			$this->load(array($id, $language), $force_db);
		}
	}

}

?>