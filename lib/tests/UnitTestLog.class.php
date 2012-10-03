<?php
/**
 * Provide a log object for a unit test.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class UnitTestLog {

	/**
	 * The test type.
	 *
	 * @var string
	 */
	public $type = "";

	/**
	 * The description.
	 *
	 * @var string
	 */
	public $description = "";

	/**
	 * The message.
	 *
	 * @var string
	 */
	public $message = "";

	/**
	 * Determines if the test was passed or not.
	 *
	 * @var boolean
	 */
	public $passed = "";

	/**
	 * The log time (format Y-m-d H:i:s).
	 *
	 * @var string
	 */
	public $time = "";

	/**
	 * The line number where the asserts occured.
	 *
	 * @var int
	 */
	public $line = 0;

	/**
	 * The test method name where the asserts occured.
	 *
	 * @var string
	 */
	public $function = 'unknown';

	/**
	 * The test file path where the asserts occured.
	 *
	 * @var string
	 */
	public $file = 'unknown';

	/**
	 * The test class name where the asserts occured.
	 *
	 * @var string
	 */
	public $class = 'unknown';

	/**
	 * Create a new test log entry.
	 *
	 * @param string $type
	 *   the test type (assertTrue, ...)
	 * @param string $description
	 *   the log description
	 * @param string $message
	 *   the log message
	 * @param string $file
	 *   the file path where the assert occured.
	 * @param string $class
	 *   the classname of the test.
	 * @param stirng $function
	 *   the test method.
	 * @param int $line
	 *   the line number.
	 * @param boolean $passed
	 *   if the test was passed or not (optional, default = false)
	 * @param string $time
	 *   the time (format Y-m-d H:i:s).
	 *   If not provided it wull use the current one.
	 *   (optional, default null)
	 */
	public function __construct($type, $description, $message, $file, $class, $function, $line, $passed = false, $time = null) {
		if ($time == null) {
			$time = date(DB_DATETIME);
		}

		$this->type = $type;
		$this->description = $description;
		$this->message = $message;

		$this->file = $file;
		$this->class = $class;
		$this->function = $function;
		$this->line = $line;

		$this->passed = $passed;
		$this->time = $time;
	}

}
