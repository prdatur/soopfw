<?php
/**
 * This object holds the translations for a given key and language
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.language.objects
 * @category ModelObjects
 */
class TranslationObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "translations";

	/**
	 * Constructor
	 *
	 * We have a special note on loading, if you provide not a fully md5 hash as the id, the id string will be made to an md5 sum and the be loaded.
	 * So you can also load the object by passing the direct translation key string to it.
	 *
	 * @param string $id
	 *   the translation id, its the md5 sum of the key (optional, default = "")
	 * @param string $language
	 *   the language (optional, default = "en")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $language = "en", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("id", "language"));
		$this->db_struct->add_field("id", '', PDT_STRING, '', 32);
		$this->db_struct->add_field("key", t('key'), PDT_TEXT);
		$this->db_struct->add_field("translation", t("Translation"), PDT_TEXT);
		$this->db_struct->add_field("language", t("Language"), PDT_STRING, 'en', 4);

		if (!empty($id) && !empty($language)) {
			if(!preg_match("/[a-f0-9]{32}/", $id)) {
				$id = md5($id);
			}

			return $this->load(array($id, $language), $force_db);
		}
	}
}

?>