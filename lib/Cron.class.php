<?php

//If we have not defined needed constances which normaly are defined within core (not included core) define it
if (!defined("NS")) {
	define("NS", "-||notset||-");
}
if (!defined('TIME_NOW')) {
	define("TIME_NOW", time());
}

/**
 * Provide an object to handle cronjobs
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Cli
 */
class Cron
{
	/**
	 * Define constances
	 */
	const CRON_TIME_MINUTE = 'minute';
	const CRON_TIME_HOUR = 'hour';
	const CRON_TIME_DAY = 'day';
	const CRON_TIME_MONTH = 'month';
	const CRON_TIME_DAY_OF_MONTH = 'dom';
	const CRON_TIME_DAY_OF_WEEK = 'dow';

	/**
	 * Whether to output some debug information
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * The last stored match, true if matched, else false
	 * @var boolean
	 */
	public $matched = false;

	/**
	 * This string represent the crontab match syntax
	 * @var string
	 */
	private $match = NS;

	/**
	 * The timestamp which will be used
	 *
	 * @var string
	 */
	private $timestamp = NS;

	/**
	 * Holds the match syntax as an array, only neccesary if setTimeMatch is used
	 *
	 * @var array
	 */
	private $time_array = array();

	/**
	 * Initialize cron, set up timestamp and crontab regular expression
	 * For detailed crontab syntax, see match function
	 * @see match
	 * @param string $match
	 *   The match string like a normal crontab string (optional, default = NS)
	 * @param int $timestamp
	 *   the timestamp to be used as the check value (optional, default = NS)
	 */
	public function __construct($match = NS, $timestamp = NS) {
		$this->match = $match;
		$this->timestamp = $timestamp;
	}

	/**
	 * Execute multiple commands
	 * The Syntax is like linux crontab
	 * lines with starting # or // will be ignored
	 * each line must start with the crontab match syntax followed by another whitespace char
	 * after this whitespace the function with a string must be provided which will be executed if
	 * the line match.
	 * You can also provide an array to execute an objects method, use this syntax:
	 * array(object,method)
	 * no quotes..
	 * Use only \n (unix newline) for new lines
	 *
	 * @param string $match_str
	 *   syntax as a string or a filename where the syntax is stored
	 */
	public function multiple_match($match_str) {

		//if $match_str is a filename load file contents
		if (file_exists($match_str)) {
			$match_str = file_get_contents($match_str);
		}

		//loop through lines
		foreach (explode("\n", $match_str) AS $line) {
			$line = trim($line);

			//ignore empty lines and comments
			if (empty($line) || preg_match("/^(#|\/\/)/iUs", $line)) {
				continue;
			}
			$call = '';
			//Check if it is a valid line
			if (preg_match("/(.+)\s([^\s]+)$/is", $line, $match)) {

				// Check if line action is an object
				if (preg_match("/array\((.*)\)/is", $match[2], $call_array_match)) {
					//line action is an object
					$call = explode(",", $call_array_match[1]);
				}
				else {
					//line action is a function
					$call = $match[2];
				}
			}
			$this->match($match[1], $call);
		}
	}

	/**
	 * Single setup the match syntax,<br />
	 * Be sure to use all Cron::CRON_TIME_* constants or the match return will be always false
	 *
	 * @param string $type
	 *   the type, use one of Cron::CRON_TIME_* constants
	 * @param string $val
	 *   the contab syntax for one value
	 */
	public function set_match_time($type, $val) {
		$this->time_array[$type] = $val;
	}

	/**
	 * Check if the given match syntax is within time
	 *
	 * Allowed special chars: * , - /
	 * only , (comma) can be multiple for one entry
	 * example * 3-7 * 3,2 4
	 *
	 * @param string $match
	 *  the match string, if not provided it will check if the class has a match set up (optional, default = NS)
	 * @param mixed $call
	 *   can be a string for function, or an array for an object (optional, default = NS)
	 * @param int $timestamp
	 *   setup manual timestamp (optional, default = NS)
	 *
	 * @return boolean if matched return true, else false
	 */
	public function match($match = NS, $call = NS, $timestamp = NS) {
		$this->log("");
		$this->log("########## MATCH STARTED ###############");
		$this->log("");
		if ($timestamp === NS) { //if timestamp is not set
			if ($this->timestamp === NS) { //if timestamp is not set by class init, set current timestamp
				$timestamp = TIME_NOW;
			}
			else {
				$timestamp = $this->timestamp;
			}
		}

		if ($match === NS) { //if match is not provided by method call
			if ($this->match === NS) { //if match is not provided by class init
				if (($match = $this->get_match_time()) === false) { //last try to fetch a match string from the match_array
					return false;
				}
			}
			else {
				$match = $this->match;
			}
		}

		if ($match === '*') {
			$match = '* * * * *';
		}

		$this->log("Time: " . $timestamp . " (" . date("d.m.Y H:i:s", $timestamp) . ") match against: " . $match);

		$now = explode(" ", trim(date("i H d m w", $timestamp)));

		$check_type_array = array(
			self::CRON_TIME_MINUTE,
			self::CRON_TIME_HOUR,
			self::CRON_TIME_DAY,
			self::CRON_TIME_MONTH,
			self::CRON_TIME_DAY_OF_WEEK,
		);

		foreach ($now AS &$tmp_v) { //date func returns some values only with leading zeros, so cut them off
			if (strlen($tmp_v) > 1 && substr($tmp_v, 0, 1) == "0") {
				$tmp_v = substr($tmp_v, 1);
			}
		}

		if (!preg_match("/((([0-9,\-\/\*]+)\s?){5})/is", $match, $matches)) { //match syntax incorrect
			$this->log("bad false");
			return false;
		}
		else {
			$this->log("match regexp found: " . var_export($matches, true));
		}

		preg_match_all("/([0-9\*\/\-,]+)\s?/is", trim($matches[1]), $times); //get all match types (min, hour, day, month, day of week)

		$this->log("match check against values:: " . var_export($times, true));
		foreach ($times[1] AS $i => $check_time) {

			//Check if the current entry matched
			if (!$this->check_times($check_time, $now[$i], $check_type_array[$i])) {
				$this->matched = false;
				$this->log("");
				$this->log("########## MATCH ENDED ###############");
				$this->log("");
				$this->log(" false");
				return false;
			}
			$this->log(" true");
		}
		$this->log(" true");
		$this->matched = true;

		if ($call !== NS) { //if $call provided execute the function / object method
			//Check if we call an object or just a function
			if (is_string($call)) {
				$call = trim($call);
			}
			if (is_array($call)) { //object
				foreach ($call AS &$call_value) {
					if (is_string($call_value)) {
						$call_value = trim($call_value);
					}
				}
				list($object, $method_name) = $call;
				if (method_exists($object, $method_name)) {
					call_user_func($call);
				}
			}
			else if (is_callable($call)) { //function
				call_user_func($call);
			}
		}
		$this->log("");
		$this->log("########## MATCH ENDED ###############");
		$this->log("");
		return true;
	}

	/**
	 * Check the $check_value against the $check_time from type $check_type
	 *
	 * @param string $check_time
	 *   value match syntax like * or 4,3 or 3
	 * @param int $check_value
	 *   the value
	 * @param string $check_type
	 *   use one of the Cron::CRON_TIME_* consts
	 *
	 * @return boolean true on match, else false
	 */
	private function check_times($check_time, $check_value, $check_type) {
		$this->log("check:" . $check_time . " against: " . $check_value . " type: " . $check_type);
		if ($check_time == '*') { // check_time * passes always
			$this->log("value was *: ", false);
			return true;
		}
		$check_value = (int) $check_value;
		$this->log($check_value);

		//Loop through all or-statements (comma list)
		foreach (explode(",", $check_time) AS $tmp_val) {
			//check_time is min-max range
			if (preg_match("/([0-9]+|\*)-([0-9]+|\*)/is", $tmp_val, $match)) {
				if (($match[0] == '*' || $check_value >= (int) $match[1]) && ($match[1] == '*' || $check_value <= (int) $match[2])) {
					return true;
				}
			}
			//Checktime is a loop */[0-9]
			else if (preg_match("/([0-9]+|\*)\/([0-9]+)/is", $tmp_val, $match)) {

				switch ($check_type) { //setup max values for the given type
					case self::CRON_TIME_MINUTE:
						$max_value = 59;
						break;
					case self::CRON_TIME_HOUR:
						$max_value = 23;
						break;
					case self::CRON_TIME_DAY:
						$max_value = 31;
						break;
					case self::CRON_TIME_MONTH:
						$max_value = 12;
						break;
					case self::CRON_TIME_DAY_OF_WEEK:
						$max_value = 6;
						break;
					default:
						$max_value = 0;
						break;
				}
				$start = (int) $match[1];
				if ($start > $max_value) { //start value can not be higher than max_value
					$start = $max_value;
				}
				$this->log("value check " . $check_value . " >= " . $start . " && " . (int) $check_value . "%" . (int) $match[2] . " == 0: ", false);
				if ($check_value >= $start && $check_value % (int) $match[2] == 0) {
					return true;
				}
			}
			//Checktime is just a number
			else if ((int) $tmp_val === $check_value) {
				$this->log("value was int: ", false);
				return true;
			}
		}
		$this->log("value match: ", false);
		return false;
	}

	/**
	 * Returns a String for the crontab match based on time_array values
	 *
	 * @return string if time_array is not empty return the match syntax, else return false
	 */
	private function get_match_time() {
		if (empty($this->time_array)) {
			return false;
		}

		// Check if all needed arrays are setup.
		if (!isset($this->time_array[self::CRON_TIME_MINUTE])) {
			return false;
		}
		if (!isset($this->time_array[self::CRON_TIME_HOUR])) {
			return false;
		}
		if (!isset($this->time_array[self::CRON_TIME_DAY])) {
			return false;
		}
		if (!isset($this->time_array[self::CRON_TIME_MONTH])) {
			return false;
		}
		if (!isset($this->time_array[self::CRON_TIME_DAY_OF_WEEK])) {
			return false;
		}

		$tmp_arr = array(
			$this->time_array[self::CRON_TIME_MINUTE],
			$this->time_array[self::CRON_TIME_HOUR],
			$this->time_array[self::CRON_TIME_DAY],
			$this->time_array[self::CRON_TIME_MONTH],
			$this->time_array[self::CRON_TIME_DAY_OF_WEEK]
		);
		return implode(" ", $tmp_arr);
	}

	/**
	 * print out log messages if debug is set to true or forced
	 *
	 * @param string $msg
	 *   The message
	 * @param boolean $newline
	 *   print newline at the end of the message? (optional, default = true)
	 * @param boolean $force
	 *   force output (optional, default = false)
	 */
	private function log($msg, $newline = true, $force = false) {
		if ($force === true || $this->debug === true) {
			echo $msg;
			if ($newline === true) {
				if (empty($_SERVER['argv'])) { //browser mode, print <br> instead of newline
					echo "<br />";
				}
				else {
					echo "\n";
				}
			}
		}
	}

}

