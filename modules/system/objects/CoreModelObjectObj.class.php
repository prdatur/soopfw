<?php

/**
 * This object holds the information about the model.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Objects
 */
class CoreModelObjectObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "core_model_objects";

	/**
	 * Constructor
	 *
	 * @param string $classname
	 *   the classname (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($classname = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key("classname");
		$this->db_struct->add_field("classname", t("the classname"), PDT_STRING);
		$this->db_struct->add_field("last_modified", t("last modification time"), PDT_INT);

		if (!empty($classname)) {
			if (!$this->load($classname, $force_db)) {
				return false;
			}
		}
	}

}

