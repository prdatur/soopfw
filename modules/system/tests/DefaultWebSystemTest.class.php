<?php

/**
 * Test main web functionality of the Framework.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class DefaultWebSystemTest extends WebUnitTest implements UnitTestInterface
{

	/**
	 * Returns an array with all test methods.
	 * If an empty array is returned all class method will be used.
	 *
	 * @return array the array with all methods which should be used for unit tests.
	 */
	public function get_tests() {
		return array();
	}

	/**
	 * Checks main database functionality.
	 *
	 */
	public function check_root() {
		$this->do_get('/user/login.html');
		$this->assert_web_regexp('/value="Login" name="soopfw_login"/', t('Validate user login form'));
	}
}
?>
