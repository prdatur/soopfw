<?php

/**
 * Test main web functionality of the Framework.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
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
		$this->assert_true((preg_match("/type=\"submit\"\s+value=\"([^\"]+)\"\s+name=\"soopfw_login\"/", $this->content, $matches) == 1), t('Validate user login form'));
		$this->do_post('/user/login.html', array(
			'soopfw_login' => $matches[1],
		), true);

		$this->assert_web_regexp('/You have entered a wrong username and\/or password\./', t('Check invalid login'));

		$this->do_post('/user/login.html', array(
			'soopfw_login' => $matches[1],
			'user' => 'admin_create',
			'pass' => 'admin',
		), true);

		$this->assert_web_regexp('/>My account<\/a>/', t('Check valid login'));
	}
}

