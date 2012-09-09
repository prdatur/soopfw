<?php

/**
 * This object is a lock entry.
 * With this object we lock a user if we want it. based up on a unique identifer
 * for the given user.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 * @category ModelObjects
 */
class IPLockObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = "core_IPLock";

	/**
	 * The lock identifer
	 * @var string
	 */
	public $type = "";

	/**
	 * the locktime in minutes
	 *
	 * @var int
	 */
	private $locktime = 0;

	/**
	 * Constructor
	 * 
	 * @param int $locktime 
	 *   the locktime (optional, default = 0)
	 * @param string $type 
	 *   the lock identifer (optional, default = "")
	 * @param string $ip 
	 *   the ip, if left empty it will try to get the current user ip (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($locktime = 0, $type = "", $ip = "", $force_db = false) {
		parent::__construct();
		if (empty($ip)) {
			$ip = get_real_ip();
		}
		$this->locktime = (int)$locktime;
		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->runtime_cache = true;
		$this->db_struct->set_cache(false);
		$this->db_struct->add_reference_key(array("type", "ip"));

		$this->db_struct->add_field("type", t("Type"), PDT_STRING, $type, 60);
		$this->db_struct->add_field("ip", t("IP-address"), PDT_STRING, $ip, 32);
		$this->db_struct->add_field("time", t("Time"), PDT_DATE, date(DB_DATE, TIME_NOW));
		if (!empty($type) && !empty($ip)) {
			if (!$this->load(array($type, $ip), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Remove all expired locks
	 */
	public function clean() {
		$this->db->query_master("DELETE FROM `".IPLockObj::TABLE."` WHERE DATE_ADD(`time`, INTERVAL ".$this->locktime." MINUTE) > @date", array(
			"@date" => date(DB_DATE, TIME_NOW
		)));
	}

	/**
	 * Lock the current configured entry
	 *
	 * @return boolean true on success, else false
	 */
	public function lock() {
		if ($this->load_success() == false) {
			$this->values['type'] = parent::__get("type");
			$this->values['ip'] = parent::__get("ip");
			$this->values['time'] = parent::__get("time");
			return $this->insert();
		}
		else {
			$this->time = date(DB_DATE, TIME_NOW);
			return $this->save();
		}
	}

	/**
	 * Checks wether this lock entry is still locked or not
	 *
	 * @return boolean true on locked, else false
	 */
	public function locked() {
		if ($this->load_success() == false) {
			return false;
		}
		if (strtotime($this->values['time']) + (60 * $this->locktime) >= TIME_NOW) {
			return true;
		}
		return false;
	}

}

?>