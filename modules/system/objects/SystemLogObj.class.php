<?php

/**
 * This holds all log entries which the system did.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Objects
 */
class SystemLogObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "system_log";

	/**
	 * Define log levels
	 */
	const LEVEL_DEBUG = -1;
	const LEVEL_NOTICE = 1;
	const LEVEL_NORMAL = 2;
	const LEVEL_WARNING = 3;
	const LEVEL_ALERT = 4;
	const LEVEL_CRITICAL = 5;
	const LEVEL_EMERGENCY = 6;

	/**
	 * Constructor
	 *
	 * @param int $id
	 *   the id (optional, default = '')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key("id");
		$this->db_struct->add_field("id", t("ID"), PDT_STRING, md5(uniqid()), '32');
		$this->db_struct->add_field("type", t("The type"), PDT_STRING, 'core');
		$this->db_struct->add_field("log_level", t("The log level"), PDT_TINYINT, self::LEVEL_NORMAL);
		$this->db_struct->add_field("uid", t("The user id"), PDT_INT, $this->session->current_user()->user_id, 'UNSIGNED');
		$this->db_struct->add_field("date", t("The log date"), PDT_DATETIME, date(DB_DATETIME));
		$this->db_struct->add_field("ip", t("The users ip"), PDT_STRING, NetTools::get_real_ip());
		$this->db_struct->add_field("referer", t("The browser referer"), PDT_STRING, (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''));
		$this->db_struct->add_field("message", t("The log message"), PDT_TEXT);

		$this->set_default_fields();

		if (!empty($id)) {
			if (!$this->load($id, $force_db)) {
				return false;
			}
		}
	}
}
