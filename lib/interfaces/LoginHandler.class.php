<?php
/*
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.
 */

interface LoginHandler
{

	/**
	 * Log the current user out, if $time is higher than 0 than the request_redirection will be used,
	 * else it will a direct header location event
	 *
	 * @param int $time set the redirection timeout after logout (optional, default = 0)
	 * @param boolean $justreturn if we just want to logout the current user with no redirect (optiona, default = false)
	 */
	public function logout($time = 0, $justreturn = false);

	/**
	 * Returns the login url for this login handler
	 * @return string
	 */
	public function get_login_url();

	/**
	 * Returns the logout url for this login handler
	 * @return string
	 */
	public function get_logout_url();

	/**
	 * Returns the logout url for this login handler
	 * @return string
	 */
	public function get_profile_url();

	/**
	 * Checks if the user is logged in, if not we will redirect him to the login page
	 *
	 * @param boolean $force_not_loggedin force not logged in that the user will be redirected to the user login page
	 * @param boolean $need_direct_handler need this login handler as valid
	 * @return boolean true if normal behaviour should checked (Session::require_login which redirects if the is_logged_in param is set to false), false if the login handler handles this event
	 */
	public function require_login($force_not_loggedin = false, $need_direct_handler =  false);

	/**
	 * Check if the user is logged in and log the user in if a post was provided.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function check_login();

	/**
	 * Check if the given credentials are valid and if so setup a new session object
	 * or update the old one, also update the last login time
	 *
	 * @param string $username the username
	 * @param string $password the md5 password
	 * @return UserObj return the user if provided credentials are valid, else false
	 */
	public function validate_login($username, $password);
}

?>