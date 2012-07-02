<?php

/**
 * The modul config, this holds the information about a modul
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 */
class ModulConfigObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "modul_config";

	/**
	 *
	 * @param type $modul
	 * @param boolean $force_db if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($modul = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("modul"));
		$this->db_struct->add_field("modul", t("Module"), PDT_STRING, '', 50);
		$this->db_struct->add_field("current_version", t("Current version"), PDT_INT, 1);
		$this->db_struct->add_field("enabled", t("Enabled"), PDT_INT, 1);

		if (!empty($modul)) {
			if (!$this->load(array($modul), $force_db)) {
				return false;
			}
		}
	}

}

?>