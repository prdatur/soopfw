<?php

/**
 * Holds a user session entry
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class UserSessionObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "user_sessions";

	/**
	 * Constructor
	 *
	 * @param string $session_id
	 *   the session id (optional, default = "")
	 * @param string $ip
	 *   the ip address (optional, default = '')
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($session_id = "", $ip = '', $force_db = false) {
		parent::__construct();
		if(empty($ip)) {
			$ip = NetTools::get_real_ip();
		}

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key(array("session_id", "ip"));

		$this->db_struct->add_field("session_id", t("Session ID"), PDT_STRING);
		$this->db_struct->add_field("username", t("username"), PDT_STRING);
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_field("ip", t("IP-address"), PDT_STRING, NetTools::get_real_ip());
		$this->db_struct->add_field("date", t("Date"), PDT_DATETIME, date(DB_DATETIME, TIME_NOW));
		if (!empty($session_id) && !empty($ip)) {
			$this->load(array($session_id, $ip), $force_db);
		}
	}

}

