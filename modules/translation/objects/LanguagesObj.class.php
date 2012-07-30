<?php

/**
 * This object holds all configured languages if it is enabled or not
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.language.objects
 * @category ModelObjects
 */
class LanguagesObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "languages";

	/**
	 * Constructor
	 *
	 * @param string $lang
	 *   the language key (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($lang = "", $force_db = false) {
		parent::__construct();

		$lang = strtolower($lang);
		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("lang");
		$this->db_struct->add_field("lang", t("Language"), PDT_STRING, '', 4);
		$this->db_struct->add_field("enabled", t("Enabled"), PDT_BOOL, 1);

		if (!empty($lang)) {
			return $this->load($lang, $force_db);
		}
	}

}

?>