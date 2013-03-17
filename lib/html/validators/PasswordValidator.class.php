<?php
/**
 * Provide a password validator
 * Possible parameters:
 * 		value => the password to validate (user input).
 * 		options => if you provde a valid array key 'secure', it will be used to build up a lock time check to prevent too much tries.
 *					valid array values:
 *                  array(
 *						'password' => 'required key, this is the encrypted password to check against',
 *						'secure' => array(
 *							'lock_identifer' => 'the lock identifer from security class (required)'
 *							'max_actions' => 'max_actions from security class (required),
 *							'block_range' => 'block_range from security class (required),
 *							'message_on_block' => 'The message which will be shown if a lock occur, if provided an empty value it will be silent. (optional, default = ''),
 *							'block_time' => 'block_time from security class (optional, default = 10),
 *							'update_block_time' => 'update_expire_data from security class (optional, default = true),
 *						),
 *					)
 *					if you do not want to have a security lock workflow, you can also just provide the password within the options.
 * IMPORTANT NOTICE:
 * If a user provide an empty value it will be validated as TRUE in order that a required validator can work correctly.
 * 
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class PasswordValidator extends AbstractHtmlValidator
{

	/**
	 * constructor (reverse parameter order)
	 *
	 * @param mixed $options
	 *   the options (optional, default = null)
	 * @param string $error
	 *   the error message (optional, default = "")	 * 
	 */
	public function __construct($options = null, $error = "") {
		parent::__construct($error, $options);
	}
	/**
	 * Validates the value against the email
	 * Be aware, an empty value validates it to true so a maybe attached
	 * RequiredValidator can handle it if the EmailValidator was provided first

	 * @return boolean true if valid, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		$val = trim($this->get_value());
		if (empty($val)) {
			return true;
		}
		$options = $this->get_options();
		
		$hash_check = new PasswordHash();	
		
		if (!is_array($options)) {
			$pw_check = $hash_check->check_password($val, $options . "");
		}
		else {
			$pw_check = $hash_check->check_password($val, $options['password'] . "");
			if (!$pw_check && isset($options['secure']) && isset($options['secure']['lock_identifier']) && isset($options['secure']['max_actions']) && isset($options['secure']['block_range'])) {

				if (empty($options['secure']['block_time'])) {
					$options['secure']['block_time'] = 10;
				}
				if (empty($options['secure']['update_block_time'])) {
					$options['secure']['update_block_time'] = true;
				}

				$security = new SecurityLock($options['secure']['lock_identifier']);
				if (!$security->check_lock_within_time_range($options['secure']['max_actions'], $options['secure']['block_range'], $options['secure']['block_time'], NS, $options['secure']['update_block_time'])) {
					if (!empty($options['secure']['message_on_block'])) {
						$this->core->message($options['secure']['message_on_block'], Core::MESSAGE_TYPE_ERROR);
					}
					return false;
				}
			}
		}
			
		if ($pw_check) {
			if (!empty($security)) {
				$security->unlock();
			}
			return true;
		}
		return false;
	}

}