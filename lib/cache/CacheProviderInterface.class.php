<?php

/**
 * Provides an interface for cache providers.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.cache
 * @category Cache
 */
interface CacheProviderInterface
{

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
	public function set_option($option, $value);

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
	public function add_server($host, $port, $weight = 0);

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
	public function set($key, $value, $expiration = 0);

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
	public function increment($key, $offset = 1);

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
	public function decrement($key, $offset = 1);

	/**
	 * Retrieve an item.
	 *
	 * @param string $key
	 *   The key under which to store the value.
	 *
	 * @return mixed Returns the value of the item or false on error
	 */
	public function get($key, $cache_db = null, &$cas_token = '');

	/**
	 * Return the result code of the last operation.
	 *
	 * @return int returns one of the CacheProvider::RES_* constants that is the result of the last executed cache method.
	 */
	public function get_result_code();

	/**
	 * Retrieve multiple items.
	 *
	 * @param array $key
	 *   The key under which to store the value.
	 *
	 * @return array Returns the array of found items or false on error
	 */
	public function get_multi($keys);

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
	public function set_multi($values, $expiration = 0);

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
	public function delete($key);

	/**
	 * Flush all existing items at the server
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public function flush();
}

?>
