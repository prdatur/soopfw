<?php
/**
 * This object holds the modul configuration for every module can be setup by Core::dbconfig where
 * we can store any key / value pairs for a given module
 * Notice. ModulConfigObj != CoreModulConfigObj ModulConfig holds the current state for the modul for example
 * if it is enabled and so
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Objects
 */
class CoreModulConfigObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "core_modul_config";

	/**
	 * Constructor
	 *
	 * @param string $key
	 *   the configuration key (optional, default = "")
	 * @param string $modul
	 *   the module name (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($key = "", $modul = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("key", "modul"));
		$this->db_struct->add_field("modul", t("Module"), PDT_STRING, '', 50);
		$this->db_struct->add_field("key", t('key'), PDT_STRING);
		$this->db_struct->add_field("value", t("Value"), PDT_TEXT);

		if (!empty($key) && !empty($modul)) {
			if (!$this->load(array($key, $modul), $force_db)) {
				return false;
			}
		}
	}

}

