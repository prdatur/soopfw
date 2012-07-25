<?php
/**
 * Provides a class to handle configurations.
 * This class must be extended and should only define configuration keys as constances.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Core
 */
class Configuration {

	/**
	 * Holds all configurations
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Enables a configuration parameter (set the value to true).
	 *
	 * @param mixed $key
	 *   the configuration key
	 */
	public function enable($key) {
		$this->config[$key] = true;
	}

	/**
	 * Disables a configuration parameter (set the value to false).
	 *
	 * @param mixed $key
	 *   the configuration key
	 */
	public function disable($key) {
		$this->config[$key] = false;
	}

	/**
	 * Set a configuration parameter
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $vaĺue
	 *   the value
	 */
	public function set($key, $value) {
		$this->config[$key] = $value;
	}

	/**
	 * Get a configuration parameter
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $default_value
	 *   the default value which will be returned if the key is not set.
	 */
	public function get($key, $default_value) {
		if (!isset($this->config[$key])) {
			return $default_value;
		}
		return $this->config[$key];
	}

	/**
	 * Returns if a configuration parameter is enabled
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $default_value
	 *   the default value which will be returned if the key is not set.
	 */
	public function is_enabled($key, $default_value) {
		if (!isset($this->config[$key])) {
			return $default_value;
		}
		return ($this->config[$key] === true);
	}

	/**
	 * Returns if a configuration parameter is disabled
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $default_value
	 *   the default value which will be returned if the key is not set.
	 */
	public function is_disabled($key, $default_value) {
		if (!isset($this->config[$key])) {
			return $default_value;
		}
		return ($this->config[$key] === false);
	}

	/**
	 * Construct a configuration object with the given values.
	 *
	 * @param array $array
	 *   An array which holds the wanted configuration params as param => value
	 *
	 * @return Configuration the configuration object
	 */
	public static function build(Array $array) {
		$obj = new self();
		foreach($array AS $k => $v) {
			$obj->set($k, $v);
		}
		return $obj;
	}
}
?>
