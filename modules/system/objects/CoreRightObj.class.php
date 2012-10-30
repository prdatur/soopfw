<?php

/**
 * This object holds a right string
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class CoreRightObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "core_rights";

	/**
	 * Constructor
	 *
	 * @param string $right
	 *   the right string (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($right = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("right");
		$this->db_struct->add_field("right", t("Right"), PDT_STRING);
		$this->db_struct->add_field("description", t("Description"), PDT_TEXT);

		if (!empty($right)) {
			if (!$this->load($right, $force_db)) {
				return false;
			}
		}
	}

}

