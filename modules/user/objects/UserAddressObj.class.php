<?php

/**
 * Represents a user address
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class UserAddressObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = 'user_address';

	const USER_ADDRESS_GROUP_DEFAULT = "default";
	const USER_ADDRESS_GROUP_SUPPORT = "support";
	const USER_ADDRESS_GROUP_BILL = "bill";
	const USER_ADDRESS_GROUP_DELIVER = "deliver";

	/**
	 * Constructor
	 *
	 * @param int $id
	 *   the address id (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct("user_address");
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key("id");
		$this->db_struct->set_auto_increment("id");
		$this->db_struct->runtime_cache = true;
		$this->db_struct->add_hidden_field("id", '', PDT_INT);
		$this->db_struct->add_hidden_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_hidden_field("parent_id", t("Parent ID"), PDT_INT);
		$this->db_struct->add_field("title", t("title"), PDT_STRING, '', 30);
		$this->db_struct->add_field("company", t("company"), PDT_STRING, '', 60);
		$this->db_struct->add_required_field("lastname", t("lastname"), PDT_STRING, '', 30);
		$this->db_struct->add_required_field("firstname", t("firstname"), PDT_STRING, '', 30);
		$this->db_struct->add_field("nation", t("nation"), PDT_STRING, '', 30);
		$this->db_struct->add_field("zip", t("zip"), PDT_STRING, '', 30);
		$this->db_struct->add_field("city", t("city"), PDT_STRING, '', 30);
		$this->db_struct->add_field("address", t("address"), PDT_STRING, '', 60);
		$this->db_struct->add_field("address2", t("address2"), PDT_STRING, '', 60);
		$this->db_struct->add_field("phone", t("phone"), PDT_STRING, '', 20);
		$this->db_struct->add_field("mobile", t("mobile"), PDT_STRING, '', 20);
		$this->db_struct->add_field("fax", t("fax"), PDT_STRING, '', 20);
		$this->db_struct->add_required_field("email", t("email"), PDT_STRING, '', 30);
		$this->db_struct->add_field("group", t("address group"), PDT_STRING, self::USER_ADDRESS_GROUP_DEFAULT, 50);
		if (!empty($id)) {
			if (!$this->load($id, $force_db)) {
				return false;
			}
		}
	}

}

