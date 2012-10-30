<?php
/**
 * Represents a right group
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class UserRightGroupObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = 'user_right_groups';

	/**
	 * Constructor
	 *
	 * @param int $group_id 
	 *   the group id (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($group_id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("group_id");
		$this->db_struct->set_auto_increment("group_id");
		$this->db_struct->add_field("group_id", t("Group ID"), PDT_INT);
		$this->db_struct->add_field("title", t("title"), PDT_STRING);
		$this->db_struct->add_field("permissions", t("Permissions"), PDT_TEXT);
		if (!empty($group_id)) {
			$this->load($group_id, $force_db);
		}
	}

	/**
	 * Deletes the given group, also we remove all member entries for this group
	 * (the link between a user and a group)
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		foreach ($this->db->query_slave_all("SELECT `id` FROM `".User2RightGroupObj::TABLE."` WHERE `group_id` = @group_id", array("@group_id" => $this->group_id)) AS $grouo_2_user_assignment) {
			$tmp_obj = new User2RightGroupObj($grouo_2_user_assignment['id']);
			if (!$tmp_obj->delete()) {
				return false;
			}
		}

		return parent::delete();
	}

}

