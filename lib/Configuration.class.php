<?php
/**
 * Provides a class to handle configurations.
 * This class must be extended and should only define configuration keys as constances.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
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
	 *
	 * @return Configuration self returning.
	 */
	public function &enable($key) {
		$this->config[$key] = true;
		return $this;
	}

	/**
	 * Disables a configuration parameter (set the value to false).
	 *
	 * @param mixed $key
	 *   the configuration key
	 *
	 * @return Configuration self returning.
	 */
	public function &disable($key) {
		$this->config[$key] = false;
		return $this;
	}

	/**
	 * Set a configuration parameter
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $vaĺue
	 *   the value
	 *
	 * @return Configuration self returning.
	 */
	public function &set($key, $value) {
		$this->config[$key] = $value;
		return $this;
	}

	/**
	 * Get a configuration parameter
	 *
	 * @param mixed $key
	 *   the configuration key
	 * @param mixed $default_value
	 *   the default value which will be returned if the key is not set. (optional, default = null)
	 */
	public function get($key, $default_value = null) {
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
	 *   (optional, default = null)
	 */
	public function is_enabled($key, $default_value = null) {
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
	 *   (optional, default = null)
	 */
	public function is_disabled($key, $default_value = null) {
		if (!isset($this->config[$key])) {
			return $default_value;
		}
		return ($this->config[$key] === false);
	}

	/**
	 * Returns whether the given configuration $key is set.
	 *
	 * @param string $key
	 *   the configuration key.
	 *
	 * @return boolean true if config is set, else false
	 */
	public function is_set($key) {
		return isset($this->config[$key]);
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

	/**
	 * Returns all currently configurated values.
	 * 
	 * @return array The current configuration array.
	 */
	public function get_values() {
		return $this->config;
	}
}

