<?php

/**
 * Provide an memcached wrapper for the memcache class
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Cache
 */
class MemcachedWrapper extends CacheProvider implements CacheProviderInterface
{
	/**
	 * If we want to compress the data.
	 *
	 * @var boolean
	 */
	private $compress = false;

	/**
	 * Last result code.
	 *
	 * @var int
	 */
	private $last_result_code = 0;

	/**
	 * Holds the memcache client.
	 * @var Memcache
	 */
	private $client = null;

	/**
	 * Constructor
	 *
	 * Init memcache.
	 *
	 * @param Core $core
	 *   The core.
	 */
	public function __construct(Core &$core) {
		parent::__construct($core);
		$this->client = new Memcache();
	}

	/**
	 * This method sets the value of a Memcached option.
	 *
	 * Some options correspond to the ones defined by libmemcached, and some are specific to the extension.
	 * See Memcached Constants for more information.
	 * The options listed below require values specified via constants.
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
		switch ($option) {
			case CacheProvider::OPT_COMPRESSION:
				$this->compress = $value;
				break;
		}
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
		return @$this->client->addServer($host, $port, true, $weight);
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
		$this->last_result_code = @$this->client->replace($this->prefix_key . $key, $value, $this->compress, $expiration);
		if ($this->last_result_code == false) {
			$this->last_result_code = @$this->client->set($this->prefix_key . $key, $value, $this->compress, $expiration);
		}
		return $this->last_result_code;
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
		return @$this->client->increment($key, $offset);
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
		return @$this->client->decrement($key, $offset);
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
		return @$this->client->get($this->prefix_key . $key);
	}

	/**
	 * Return the result code of the last operation
	 * @return int returns one of the CacheProvider::RES_* constants that is the result of the last executed Memcached method.
	 */
	public function get_result_code() {
		if ($this->last_result_code == true) {
			return CacheProvider::RES_SUCCESS;
		}
		return CacheProvider::RES_FAILURE;
	}

	/**
	 * Retrieve multiple items
	 * @param array $key The key under which to store the value.
	 * @return boolean Returns the array of found items or false on error
	 */
	public function get_multi(array $keys) {
		foreach ($keys AS &$v) {
			$v = $this->prefix_key . $v;
		}
		return @$this->client->get($keys);
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
	public function set_multi(array $items, $expiration = 0) {
		parent::set_multi($values, $expiration);
		foreach ($items AS $key => $value) {
			if ($this->set($key, $value) != true) {
				return false;
			}
		}
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
	public function delete($key, $time = 0) {
		parent::delete($key);
		return @$this->client->delete($this->prefix_key . $key, $time);
	}

	/**
	 * Flush all existing items at the server
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public function flush() {
		parent::flush();
		return @$this->client->flush();
	}

}

