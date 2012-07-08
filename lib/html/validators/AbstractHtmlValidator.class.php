<?php
/**
 * Provide an abstract class for an HTML-Validator
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.html.validators
 * @category Form.Validators
 */
abstract class AbstractHtmlValidator extends Object
{
	/**
	 * The value for that element
	 *
	 * @var string
	 */
	private $value = NS;

	/**
	 * The options for the element
	 *
	 * @var mixed
	 */
	private $options;

	/**
	 * The error message
	 *
	 * @var string
	 */
	private $error;

	/**
	 * If set to true then this validator will be always valid
	 *
	 * @var boolean
	 */
	protected $is_always_valid = false;

	/**
	 * constructor
	 *
	 * @param string $error 
	 *   the error message (optional, default = "")
	 * @param mixed $options 
	 *   the options (optional, default = null)
	 */
	public function __construct($error = "", $options = null) {
		parent::__construct();
		$this->error = &$error;
		$this->options = &$options;
	}

	/**
	 * Returns the error ,essage if it's invalid
	 *
	 * @return string the error message
	 */
	public function get_error() {
		return $this->error;
	}

	/**
	 * Set the error message
	 *
	 * @param string &$val 
	 *   The error
	 */
	public function set_error(&$val) {
		$this->error = &$val;
	}

	/**
	 * Returns the value
	 *
	 * @return string the value
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Set the value
	 *
	 * @param string &$val 
	 *   The value
	 */
	public function set_value(&$val) {
		$this->value = $val;
	}

	/**
	 * Returns the options
	 *
	 * @return array the options
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Set the options
	 *
	 * @param string &$val 
	 *   The options
	 */
	public function set_options(&$val) {
		$this->options = $val;
	}

	/**
	 * Get the validator type
	 *
	 * @return string The Validator typ
	 */
	public function get_type() {
		return $this->__toString();
	}

	/**
	 * Set this validator to always be valid
	 */
	public function set_valid() {
		$this->is_always_valid = true;
	}

	/**
	 * Returns wether the validator should be always valid or not
	 *
	 * @return boolean if this validator is always valid return true, else false
	 */
	public function is_always_valid() {
		return $this->is_always_valid;
	}

	/**
	 * Returns wether the validator is valid or not
	 *
	 * @return boolean true on valid, else false
	 */
	abstract function is_valid();

	/**
	 * Returns the classname of the current object
	 *
	 * @return string
	 *   the classname
	 */
	public function __tostring() {
		return get_class($this);
	}
}
?>