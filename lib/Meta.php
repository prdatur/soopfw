<?php
/**
 * Provide a class which holds all meta data for the current page
 * It implements an iterator so it can be used within a foreach function
 * it will print only the values which was set.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class Meta implements Iterator {

	/**
	 * Define all possible meta keys as constances
	 */
	const TITLE = 'title';
	const AUDIENCE = 'audience';
	const AUTHOR = 'author';
	const CORYRIGHT = 'copyright';
	const DESCRIPTION = 'description';
	const KEYWORDS = 'keywords';
	const PAGE_TYPE = 'page-type';
	const PUBLISHER = 'publisher';
	const REVISIT_AFTER = 'revisit-after';
	const ROBOTS = 'robots';

	/**
	 * The meta value holder
	 * @var array
	 */
	private $values = array();

	/**
	 * Internal usage to implement 'valid' method
	 * @var int
	 */
	private $iterator_count = 0;

	/**
	 * Set the given meta key if it is a valid one
	 *
	 * @param string $name
	 *   The type, use one of the class consts
	 * @param string $value
	 *   the value to be set
	 */
	function __set($name, $value) {
		if(defined('self::'.strtoupper($name))) {
			$this->values[strtoupper($name)] = $value;
		}
	}

	/**
	 * Get the given meta key if it is a valid one
	 *
	 * @param string $name
	 *   The type, use one of the class consts
	 *
	 * @return string returns the value, if value was not set return an empty string or null if it is not a valid meta key
	 */
	function __get($name) {
		//Only return the value if it is a valid meta key
		if(defined('self::'.strtoupper($name))) {
			//Return the value if we had set it before, else we return an empty string
			if(isset($this->values[strtoupper($name)])) {
				return $this->values[strtoupper($name)];
			}
			return "";
		}

		//Wrong meta key, return null
		return null;
	}

	/**
	 * Rewind the array
	 */
	public function rewind() {
		reset($this->values);
		$this->iterator_count = 0;
	}

	/**
	 * Returns true if the current position do not exceed the array maximum values
	 *
	 * @return boolean true if valid, else false
	 */
	public function valid() {
		return $this->iterator_count < count($this->values);
	}

	/**
	 * Advance the internal array pointer of an array
	 */
	public function next() {
		$this->iterator_count++;
		next($this->values);
	}

	/**
	 * Returns the current key
	 *
	 * @return string the key
	 */
	public function key() {
		return key($this->values);
	}

	/**
	 * Returns the current value
	 *
	 * @return string the current element
	 */
	public function current() {
		return current($this->values);
	}
}
?>