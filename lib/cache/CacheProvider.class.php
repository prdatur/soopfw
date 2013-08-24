<?php

/**
 * Provides an interface for cache providers.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Cache
 */
abstract class CacheProvider extends Object
{
	const OPT_PREFIX_KEY = '';
	const OPT_COMPRESSION = TRUE;
	const OPT_LIBKETAMA_COMPATIBLE = FALSE;
	const OPT_BUFFER_WRITES = FALSE;
	const OPT_BINARY_PROTOCOL = FALSE;
	const OPT_NO_BLOCK = FALSE;
	const OPT_TCP_NODELAY = FALSE;
	const OPT_SOCKET_RECV_SIZE = 1000;
	const OPT_RETRY_TIMEOUT = 0;
	const OPT_SEND_TIMEOUT = 0;
	const OPT_RECV_TIMEOUT = 0;
	const OPT_POLL_TIMEOUT = 1000;
	const OPT_CACHE_LOOKUPS = FALSE;
	const OPT_SERVER_FAILURE_LIMIT = 0;
	const RES_SUCCESS = 0;
	const RES_FAILURE = 1;

	/**
	 * The prefix for keys.
	 *
	 * @var string
	 */
	protected $prefix_key = "";

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
		switch ($option) {
			case CacheProvider::OPT_PREFIX_KEY:
				$this->prefix_key = substr($value, 0, 128);
				break;
		}
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
	 */
	public function set($key, $value, $expiration = 0) {

	}

	/**
	 * Store multiple item.
	 *
	 * @param array $items
	 *   One or more key/value pair/s which to store.
	 * @param int $expiration
	 *   The expiration time, defaults to 0. See Expiration Times for more info.  (optional, default = 0)
	 */
	public function set_multi(array $values, $expiration = 0) {

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

		return true;
	}

	/**
	 * Flush all existing items at the server
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public function flush() {

		return true;
	}
}