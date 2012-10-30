<?php

/**
 * Provides a memcached wrapper with a database engine
 *
 * Please notice that this is not really a good cache because database queries
 * are much slower than a normal memcache or memcached.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Cache
 */
class DBMemcached extends CacheProvider implements CacheProviderInterface
{
	/**
	 * Holds the last result code
	 * @var int
	 */
	private $last_result_code;

	/**
	 * Performance feature, all deletes will be added to an array and only deleted after the object will be destroyed
	 * @var array
	 */
	private $delete_list = array();

	/**
	 * Construct
	 *
	 * @param Core $core
	 *   The core.
	 */
	public function __construct(Core &$core) {
		parent::__construct($core);
		$this->last_result_code = CacheProvider::RES_SUCCESS;
	}

	/**
	 * Destructor, will delete all needed entries
	 */
	function __destruct() {
		foreach ($this->delete_list as $key) {
			$this->delete($key);
		}
	}

	/**
	 * Set an option
	 *
	 * @param string $option
	 *   the option
	 * @param mixed $value
	 *   the value
	 *
	 * @return boolean true
	 */
	public function set_option($option, $value) {
		parent::set_option($option, $value);
		$this->last_result_code = CacheProvider::RES_SUCCESS;
		return true;
	}

	/**
	 * Add a server
	 *
	 * @param string $host
	 *   the host
	 * @param int $port
	 *   the port
	 * @param int $weight
	 *   the server weight (higher weight will increase the probability of the server being selected). (optional, default = 0)
	 *
	 * @return boolean true
	 */
	public function add_server($host, $port, $weight = 0) {
		$this->last_result_code = CacheProvider::RES_SUCCESS;
		return true;
	}

	/**
	 * Store an item.
	 *
	 * @param string $key
	 *   The key under which to store the value.
	 * @param mixed $value
	 *   The value to store.
	 * @param int $expiration
	 *   The expiration time, defaults to 0. See Expiration Times for more info.  (optional, default = 0)
	 *
	 * @return boolean true on success, else false
	 */
	public function set($key, $value, $expiration = 0) {
		parent::set($key, $value, $expiration);
		$expiration = (int) $expiration;
		if ($expiration > 0 && $expiration < 2592000) {
			$expiration += time();
		}
		if ($this->db->query_master("REPLACE INTO `__memcached` SET `key` = '" . Db::safe($this->prefix_key . $key) . "', `value` = '" . Db::safe(serialize($value)) . "', `expires` = " . $expiration)) {
			$this->last_result_code = CacheProvider::RES_SUCCESS;
			return true;
		}
		$this->last_result_code = CacheProvider::RES_FAILURE;
		return false;
	}

	/**
	 * Increment the given value for the key with the given offset.
	 *
	 * @param string $key
	 *   the cache key.
	 * @param int $offset
	 *   the int to be incremented. (optional, default = 1)
	 *
	 * @return boolean true on success, else false.
	 */
	public function increment($key, $offset = 1) {
		return $this->db->query_master("UPDATE `__memcached` SET value = value + " . (int) $offset . " WHERE `key` = '" . Db::safe($this->prefix_key . $key) . "'");
	}

	/**
	 * Decrement the given value for the key with the given offset.
	 *
	 * @param string $key
	 *   the cache key.
	 * @param int $offset
	 *   the int to be decremented. (optional, default = 1)
	 *
	 * @return boolean true on success, else false.
	 */
	public function decrement($key, $offset = 1) {
		return $this->db->query_master("UPDATE `__memcached` SET value = value - " . (int) $offset . " WHERE `key` = '" . Db::safe($this->prefix_key . $key) . "'");
	}

	/**
	 * Retrieve an item.
	 *
	 * @param string $key
	 *   The key under which to store the value.
	 *
	 * @return mixed Returns the value of the item or false on error
	 */
	public function get($key, $cache_db = null, &$cas_token = '') {
		//First set the return code to an error
		$this->last_result_code = CacheProvider::RES_FAILURE;

		//try to get the value
		$res = $this->db->query_slave_first("SELECT * FROM `__memcached` WHERE `key` = '" . Db::safe($this->prefix_key . $key) . "'");
		if (!$res) {
			return null;
		}

		//Check if it was expired
		if (!$this->check_expire($res)) {
			$this->last_result_code = CacheProvider::RES_NOTFOUND;
			return null;
		}

		//All went fine, set the success return code and return the value
		$this->last_result_code = CacheProvider::RES_SUCCESS;
		return unserialize($res['value']);
	}

	/**
	 * Return the result code of the last operation.
	 *
	 * @return int returns one of the CacheProvider::RES_* constants that is the result of the last executed cache method.
	 */
	public function get_result_code() {
		return $this->last_result_code;
	}

	/**
	 * Retrieve multiple items.
	 *
	 * @param array $key
	 *   The key under which to store the value.
	 *
	 * @return array Returns the array of found items or false on error
	 */
	public function get_multi(array $keys) {
		//First set the return code to an error
		$this->last_result_code = CacheProvider::RES_FAILURE;
		$return = array();

		//Setup all wanted keys
		foreach ($keys AS $key => $value) {
			$keys[$key] = "'" . Db::safe($this->prefix_key . $value) . "'";
		}

		//try to get the values
		$res = $this->db->query_master("SELECT * FROM `__memcached` WHERE `key` IN (" . implode(',', $keys) . ")");
		if (!$res) {
			return false;
		}

		//Loop through all found values and check if some expired
		while ($res = $this->db->fetch_array()) {
			if (!$this->check_expire($res)) {
				continue;
			}
			$return[$res['key']] = unserialize($res['value']);
		}

		//All went fine, set the success return code and return the value
		$this->last_result_code = CacheProvider::RES_SUCCESS;
		return $return;
	}

	/**
	 * Store multiple item.
	 *
	 * @param array $items
	 *   One or more key/value pair/s which to store.
	 * @param int $expiration
	 *   The expiration time, defaults to 0. See Expiration Times for more info.  (optional, default = 0)
	 *
	 * @return boolean true on success, else false
	 */
	public function set_multi(array $values, $expiration = 0) {
		parent::set_multi($values, $expiration);
		foreach ($values as $key => $value) {
			$this->set($key, $value, $expiration);
		}
		$this->last_result_code = CacheProvider::RES_SUCCESS;
		return true;
	}

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 *   The key to be deleted.
	 * @param int $time
	 *   The amount of time the server will wait to delete the item.  (optional, default = 0)
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public function delete($key) {
		parent::delete($key);
		return $this->db->query_master("DELETE FROM	`__memcached` WHERE `key` = '" . Db::safe($this->prefix_key . $key) . "'");
	}

	/**
	 * Flush all existing items at the server
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public function flush() {
		parent::flush();
		return $this->db->query_master("DELETE FROM	`__memcached` WHERE `key` LIKE '" . Db::safe($this->prefix_key) . "%'");
	}

	/**
	 * Returns if a given result row is not expired.
	 *
	 * @param array $res
	 *   the result row from database.
	 *
	 * @return boolean false if expired, else true
	 */
	private function check_expire($res) {
		if (empty($res['expires'])) {
			return true;
		}
		if ($res['expires'] > time()) {
			return true;
		}
		$this->delete_list[] = $res['key'];
		return false;
	}

}