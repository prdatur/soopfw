<?php

/**
 * Represents a link between a user and a group so this determines
 * if a user is a member of a group or not
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.user.objects
 * @category ModelObjects
 */
class User2RightGroupObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = 'user_user2right_group';

	/**
	 * Constructor
	 *
	 * @param int $id 
	 *   the link id (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("id");
		$this->db_struct->set_auto_increment("id");
		$this->db_struct->add_field("id", '', PDT_INT);
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_field("group_id", t("Group ID"), PDT_INT);
		if (!empty($id)) {
			$this->load($id, $force_db);
		}
	}

}

