<?php

/**
 * Provide a Object class which should be always extend if any database
 * translation or cache feature is required.
 * If you want to use pure php function than extending this class is not needed
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Core
 */
class Object
{

	/**
	 * The database object
	 *
	 * @var Db
	 */
	protected $db = null;

	/**
	 * The core object
	 *
	 * @var Core
	 */
	protected $core = null;

	/**
	 * The Smarty object
	 *
	 * @var Smarty
	 */
	protected $smarty = null;

	/**
	 * The Language object
	 *
	 * @var Language
	 */
	protected $lng = null;

	/**
	 * Holds the session object
	 *
	 * @var Session
	 */
	protected $session = null;

	/**
	 * Holds the RightManager object
	 *
	 * @var RightManager
	 */
	protected $right_manager = null;

	/**
	 * Constructor which sets the Core Object by using $GLOBALS['core'] if isset
	 * 
	 * @param Core &$core
	 *   The core object (optional, default = null)
	 */
 	public function __construct(&$core = null) {
		if (!is_null($core)) {
			$this->core = &$core;
		}
		else if (isset($GLOBALS['core'])) {
			$this->core = &$GLOBALS['core'];
		}

		if ($this->core) {
			$this->db = &$this->core->db;
			$this->smarty = &$this->core->smarty;
			$this->lng = &$this->core->lng;
			$this->session = &$this->core->session;
			$this->right_manager = &$this->core->right_manager;
		}
	}

	/**
	 * Returns the Session object.
	 * 
	 * @return Session the session object
	 */
	public function &get_session() {
		return $this->session;
	}

	/**
	 * Returns the Smarty object.
	 * 
	 * @return Smarty the smarty object
	 */
	public function &get_smarty() {
		return $this->smarty;
	}

	/**
	 * Returns the Language object.
	 * 
	 * @return Language the language object
	 */
	public function &get_language() {
		return $this->lng;
	}

	/**
	 * Returns the Database object.
	 * 
	 * @return Db the database object
	 */
	public function &get_db() {
		return $this->db;
	}

	/**
	 * Returns the Core object.
	 * 
	 * @return Core the core object
	 */
	public function &get_core() {
		return $this->core;
	}

	/**
	 * Overrides default tostring method, returns classname
	 *
	 * @return string the classname
	 */
	function __tostring() {
		return "Class: ".get_parent_class($this).":".get_class($this);
	}

	/**
	 * Get all the definied class vars
	 *
	 * @return array The class variables as an array
	 */
	public function get_class_vars() {
		return get_class_vars(__tostring($this));
	}

	/**
	 * print all the definied class vars
	 */
	public function print_class_vars() {
		echo __tostring()."\n";
		foreach ($this->get_class_vars() AS $var => $val) {
			echo $var.":".$val."\n";
		}
	}

	/**
	 * print out messages if core is != null and core debug = true
	 *
	 * @param mixed $msg 
	 *   the value. if it is not a string we will do print_r
	 * @param boolean $newline 
	 *   if we print out a br and \n or not (optional, default = true)
	 */
	public function log($msg, $newline = true) {
		if (empty($this->core) || $this->core->get_debug() == false) {
			return;
		}

		if (is_array($msg) || is_object($msg)) {
			print_r($msg);
		}
		else {
			echo $msg;
			if ($newline == true) {
				echo "<br>\n";
			}
		}
	}

}