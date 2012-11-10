<?php

/**
 * Holds a one time access login entry
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class UserOneTimeActionObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "user_one_time_access";

	/**
	 * Constructor
	 *
	 * @param string $id
	 *   the unique random id (optional, default = "")
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key(array("id"));

		$this->db_struct->add_field("id", t("Unique ID"), PDT_STRING, md5(uniqid()));
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_field("date", t("Date"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		if (!empty($id)) {
			$this->load(array($id), $force_db);
		}
	}

}

