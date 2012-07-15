<?php

/**
 * Represents a possible user right
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.user.objects
 * @category ModelObjects
 */
class UserRightObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = 'user_right_config';

	/**
	 * Constructor
	 *
	 * @param int $user_id 
	 *   the user id (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($user_id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("user_id"));
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_field("permissions", t("Permissions"), PDT_TEXT);
		if (!empty($user_id)) {
			$this->load(array($user_id), $force_db);
		}
	}

}

?>