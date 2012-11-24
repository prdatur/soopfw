<?php

/**
 * Provides an abstract class which should be extend to create a new login handler.
 * Will provide defaults for get profile url and other methods.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module
 */
abstract class AbstractLoginHandler extends Object implements LoginHandler
{

	/**
	 * Log the current user out, if $time is higher than 0 than the request_redirection will be used,
	 * else it will a direct header location event.
	 *
	 * @param int $time
	 *   set the redirection timeout after logout (optional, default = 0)
	 * @param boolean $justreturn
	 *   if we just want to logout the current user with no redirect (optional, default = false)
	 */
	public function logout($time = 0, $justreturn = false) {


		//If we do only want to log the user out and do not want to redirect after, return
		if ($justreturn) {
			return;
		}

		//if we have a time, request the redirection, else redirect with header location
		if ($time > 0) {
			$this->core->request_redirect("/", $time);
		}
		else {
			$this->core->location("/");
		}
	}

	/**
	 * Returns the login url for this login handler.
	 *
	 * @return string the login url
	 */
	public function get_login_url() {
		return '/user/login';
	}

	/**
	 * Returns the logout url for this login handler.
	 *
	 * @return string the logout url
	 */
	public function get_logout_url() {
		return '/user/logout';
	}

	/**
	 * Returns the profile url for this login handler.
	 *
	 * @param UserObj $user_obj
	 *   the user object, if provided it will get the profile url for this account (optional, default = null)
	 *
	 * @return string the profile url
	 */
	public function get_profile_url($user_obj = null) {
		$profile_url = '/user/edit/@userid';
		if (!is_null($user_obj)) {
			$profile_url = str_replace('@userid', $user_obj->user_id, $profile_url);
		}
		return $profile_url;
	}

	/**
	 * Returns all urls for this handler which are not allowed for redirecting after login.
	 *
	 * This is needed to prevent redirect loops.
	 *
	 * @return array all urls on which we can not redirect.
	 */
	public function get_handler_urls() {
		return array();
	}

	/**
	 * This is called within the login page without posting something and is used for Single Sign On's like openID, shibboleth or Facebook.
	 * This is a direct check if the user is logged in without a need to provide credentials.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function pre_validate_login() {
		return false;
	}

	/**
	 * Check if the given credentials are valid and if so setup a new session object
	 * or update the old one, also update the last login time.
	 *
	 * @param string $username
	 *   The username.
	 * @param string $password
	 *   The crypted password.
	 *
	 * @return boolean Return true if provided credentials are valid, else false.
	 */
	public function validate_login($username, $password) {
		return false;
	}

	/**
	 * Check if the user is logged and if he is we create or update the session entry to store and determine that the
	 * user is loggedin.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function check_login() {
		// Check if we are logged in.
		if ($this->is_logged_in($user_obj)) {
			// Provide the session that we have successfully logged in for this login handler.
			return $this->session->validate_login($user_obj);
		}
		return false;
	}

	/**
	 * Returns true if the user is logged in.
	 *
	 * @param UserObj &$user_obj
	 *   The variable will be filled if we have found a valid logged in user for the current session.
	 *
	 * @return boolean true on success, else false
	 */
	protected function is_logged_in(&$user_obj = null) {
		static $logged_in = null;

		if ($logged_in != null) {
			return $logged_in;
		}

		$session_id = $this->session->get_session_id();
		// If we have NOT posted and session variables are NOT empty check the user with the current session.
		if (!empty($session_id)) {
			// Load the session object for the current session.
			$session_obj = new UserSessionObj($session_id);

			// Check if we found a valid session.
			if ($session_obj->load_success() !== false) {
				// Set the crrent user object.
				$user_obj = new UserObj($session_obj->user_id);
				if($user_obj->load_success()) {
					$logged_in = true;
					return true;
				}
			}
		}
		$user_obj = null;
		$logged_in = false;
		return false;
	}

}