<?php

/**
 * This holds all log entries which the system did after log rotating..
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModelObjects
 */
class SystemLogArchiveObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "system_log_archive";

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
		$this->db_struct->add_reference_key('id');
		$this->db_struct->set_auto_increment('id');
		$this->db_struct->add_field('id', t("ID"), PDT_INT, 0, 'UNSIGNED');
		$this->db_struct->add_field('date_from', t("The time when the log archive starts"), PDT_DATETIME);
		$this->db_struct->add_field('date_to', t("The time when the log archive end"), PDT_DATETIME);
		$this->db_struct->add_field('data', t("The archived compressed log data"), PDT_BLOB);

		$this->set_default_fields();

		if (!empty($id)) {
			if (!$this->load($id, $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Returns the decoded gzip data.
	 * 
	 * @return null|string|boolean On success returns the decoded string, if object is not loaded or data is not a gzip format returns null, returns false on error.
	 */
	public function get_decoded_data() {
		if (!$this->load_success()) {
			return null;
		}
		return StringTools::gzdecode($this->data);
	}
}
