<?php
/**
 * Provide basic unit tests.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class UnitTest extends Object {

	/**
	 * Holds all test results.
	 *
	 * @var array
	 */
	protected $test_log = array();

	/**
	 * Determines if a past has failed currently.
	 *
	 * @var boolean
	 */
	protected $has_failed_tests = false;

	/**
	 * The number of passed tests
	 * @var int
	 */
	protected $passed_tests = 0;

	/**
	 * The number of failed tests
	 * @var int
	 */
	protected $failed_tests = 0;

	/**
	 * The original table prefix without the appended "test_".
	 *
	 * @var string
	 */
	protected $original_table_prefix = '';

	public function __construct($original_table_prefix) {
		parent::__construct();
		$this->original_table_prefix = $original_table_prefix;
	}
	/**
	 * Check if $value is boolean true.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_true($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is boolean true');
		}

		$test = ($value === true);
		$this->add_log('assert_true', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is boolean false.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_false($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is boolean false');
		}

		$test = ($value !== true);
		$this->add_log('assert_false', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is NULL.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_null($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is NULL');
		}

		$test = ($value === null);
		$this->add_log('assert_null', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is not NULL.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_not_null($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is NOT NULL');
		}

		$test = ($value !== null);
		$this->add_log('assert_not_null', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is empty.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_empty($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value empty');
		}

		$test = empty($value);
		$this->add_log('assert_empty', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is not empty.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_not_empty($value, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value not empty');
		}

		$test = !empty($value);
		$this->add_log('assert_not_empty', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $pattern is found within $value using regular expression.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $pattern
	 *   the pattern for preg_match().
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_regexp($value, $pattern, $description, $message = "") {
		if (empty($message)) {
			$message = t('Regular expression "@regexp" found within message', array(
				'@regexp' => $pattern,
			));
		}

		$test = preg_match($pattern, $value);
		$this->add_log('assert_regexp', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $pattern is NOT found within $value using regular expression.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $pattern
	 *   the pattern for preg_match().
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_not_regexp($value, $pattern, $description, $message = "") {
		if (empty($message)) {
			$message = t('Regular expression "@regexp" not found within message', array(
				'@regexp' => $pattern,
			));
		}

		$test = !preg_match($pattern, $value);
		$this->add_log('assert_not_regexp', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is an instance of $value.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $instance_of
	 *   the instance check.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_instance_of($value, $instance_of, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is an instance of "@instance_of"', array(
				'@instance_of' => $instance_of,
			));
		}

		$test = ($value instanceof $instance_of);
		$this->add_log('assert_instance_of', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is not an instance of $value.
	 *
	 * @param mixed $value
	 *   the value to be checked
	 * @param string $instance_of
	 *   the instance check.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_not_instance_of($value, $instance_of, $description, $message = "") {
		if (empty($message)) {
			$message = t('Value is NOT an instance of "@instance_of"', array(
				'@instance_of' => $instance_of,
			));
		}

		$test = !($value instanceof $instance_of);
		$this->add_log('assert_instance_of', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $first value equals $second.
	 *
	 * @param mixed $first
	 *   the first value-
	 * @param string $second
	 *   the second value.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_equals($first, $second, $description, $message = "") {
		$test = ($first === $second);

		if (empty($message)) {

			if ($test) {
				$message = 'Values equals.';
			}
			else {
				$message = "Values are different.\nDiff:\n";
				$diff = new FineDiff($first, $second, FineDiff::$wordGranularity);

				$diff_string = $diff->renderDiffToHTML();

				if (preg_match_all("/<del>(.*)<\/del><ins>(.*)<\/ins>/U", $diff_string, $matches)) {
					foreach ($matches[0] AS $k => $orig) {
						$diff_string = str_replace($orig, "", $diff_string);
						$message .= $matches[1][$k] . ' != ' . $matches[2][$k] . "\n";
					}
				}
				if (preg_match_all("/<del>(.*)<\/del>/U", $diff_string, $matches)) {
					foreach ($matches[1] AS $k => $from) {
						$message .= 'Not found in "second" string: ' . $from;
					}
				}

				if (preg_match_all("/<ins>(.*)<\/ins>/U", $diff_string, $matches)) {
					foreach ($matches[1] AS $k => $from) {
						$message .= 'Not found in "first" string: ' . $from;
					}
				}
				$message = htmlspecialchars_decode($message);
				$message = htmlspecialchars_decode($message);
			}
		}

		$this->add_log('assert_equals', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $first value not equals $second.
	 *
	 * @param mixed $first
	 *   the first value-
	 * @param string $second
	 *   the second value.
	 * @param string $description
	 *   the description which descripes this test.
	 * @param string $message
	 *   the message to be returned.
	 *   if not provided it will use the default
	 *   message defined within this test.
	 *   (optional, default = "")
	 *
	 * @param boolean if test passes returns true, else false
	 */
	public function assert_not_equals($first, $second, $description, $message = "") {
		if (empty($message)) {
			$message = t('"@first" not equals "@second"', array(
				'@first' => $first,
				'@second' => $second,
			));
		}

		$test = ($first !== $second);
		$this->add_log('assert_not_equals', $description, $message, $test);
		return $test;
	}

	/**
	 * Check if $value is boolean false.
	 *
	 * @param string $type
	 *   the test type
	 * @param string $description
	 *   A test which descripes this test.
	 * @param string $message
	 *   the message.
	 * @param boolean $passed
	 *   Wether the test passed or not (optional, default = false)
	 */
	public function add_log($type, $description, $message, $passed = false) {
		if ($passed === false) {
			$this->failed_tests++;
			$this->has_failed_tests = true;
		}
		else {
			$this->passed_tests++;
		}
		array_push($this->test_log, new UnitTestLog($type, $description, $message, $passed));
	}

	/**
	 * Returns wether currently a pass failed or not.
	 *
	 * @return boolean returns true if a pass failed else false
	 */
	public function has_failed_tests() {
		return $this->has_failed_tests;
	}

	/**
	 * Returns the count of passed tests.
	 *
	 * @return int the count
	 */
	public function get_passed_test_count() {
		return $this->passed_tests;
	}

	/**
	 * Returns the count of failed tests.
	 *
	 * @return int the count
	 */
	public function get_failed_test_count() {
		return $this->failed_tests;
	}

	/**
	 * Returns all test results.
	 *
	 * @return array
	 */
	public function get_results() {
		return $this->test_log;
	}
}
?>
