<?php

if (!class_exists('memcache')) {
	class memcache {}
}

/**
 * Provide an memcached wrapper for the memcache class
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 */
class MemcachedWrapper extends memcache {

	private $compress = false;
	private $prefix_key = '';

	private $last_result_code = 0;
	/**
	 * Store an item
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time, defaults to 0. See Expiration Times for more info.  (optional, default = 0)
	 */
	public function set($key , $value, $expiration = 0) {
		$this->last_result_code = @parent::replace($this->prefix_key.$key, $value, $this->compress, $expiration);
		if($this->last_result_code == false) {
			$this->last_result_code = parent::set($this->prefix_key.$key, $value, $this->compress, $expiration);
		}
	}
	/**
	 * Store multiple item
	 * @param array $items One or more key/value pair/s which to store
	 * @param int $expiration The expiration time, defaults to 0. See Expiration Times for more info.  (optional, default = 0)
	 */
	public function setMulti(array $items , $expiration = 0) {
		foreach($items AS $key=>$value) {
			$this->set($key, $value);
		}
	}

	/**
	 * Delete an item
	 * @param string $key The key to be deleted.
	 * @param int $time The amount of time the server will wait to delete the item.  (optional, default = 0)
	 * @return boolean Returns true on success, else false.
	 */
	public function delete($key, $time = 0) {
		return parent::delete($this->prefix_key.$key, $time);
	}

	/**
	 * Retrieve an item
	 * @param string $key The key under which to store the value.
	 * @return mixed Returns the value of the item or false on error
	 */
	public function get($key) {
		return @parent::get($this->prefix_key.$key);
	}

	/**
	 * Retrieve multiple items
	 * @param array $key The key under which to store the value.
	 * @return boolean Returns the array of found items or false on error
	 */
	public function getMulti(array $keys) {
		foreach($keys AS &$v) {
			$v = $this->prefix_key.$v;
		}
		return parent::get($keys);
	}

	/**
	 * This method sets the value of a Memcached option.
	 * Some options correspond to the ones defined by libmemcached, and some are specific to the extension.
	 * See Memcached Constants for more information.
	 * The options listed below require values specified via constants.
	 * @param int $option
	 * @param string $value
	 */
	public function setOption ($option , $value) {
		switch($option) {
			case Memcached::OPT_PREFIX_KEY:
				$this->prefix_key = substr($value,0,128);
				break;
			case Memcached::OPT_COMPRESSION:
				$this->compress = $value;
				break;
		}
	}

	/**
	 * Return the result code of the last operation
	 * @return int returns one of the Memcached::RES_* constants that is the result of the last executed Memcached method.
	 */
	public function getResultCode() {
		if($this->last_result_code == true) {
			return Memcached::RES_SUCCESS;
		}
		return Memcached::RES_FAILURE;
	}
}
?>
