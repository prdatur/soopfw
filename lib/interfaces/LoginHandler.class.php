<?php

/**
 * Provides an interface for a login handler.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Security
 */
interface LoginHandler
{

	/**
	 * Log the current user out, if $time is higher than 0 than the request_redirection will be used,
	 * else it will a direct header location event
	 *
	 * @param int $time
	 *   set the redirection timeout after logout (optional, default = 0)
	 * @param boolean $justreturn
	 *   if we just want to logout the current user with no redirect (optional, default = false)
	 */
	public function logout($time = 0, $justreturn = false);

	/**
	 * Returns the login url for this login handler
	 *
	 * @return string the login url
	 */
	public function get_login_url();

	/**
	 * Returns the logout url for this login handler
	 *
	 * @return string the logout url
	 */
	public function get_logout_url();

	/**
	 * Returns the profile url for this login handler
	 *
	 * @param UserObj $user_obj
	 *   the user object, if provided it will get the profile url for this account (optional, default = null)
	 *
	 * @return string the profile url
	 */
	public function get_profile_url($user_obj = null);

	/**
	 * Returns all urls for this handler which are not allowed for redirecting after login.
	 *
	 * This is needed to prevent redirect loops.
	 *
	 * @return array all urls on which we can not redirect.
	 */
	public function get_handler_urls();

	/**
	 * Check if the user is logged in and log the user in if a post was provided.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function check_login();

	/**
	 * This is called within the login page without posting something and is used for Single Sign On's like openID, shibboleth or Facebook.
	 * This is a direct check if the user is logged in without a need to provide credentials.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function pre_validate_login();

	/**
	 * Check if the given credentials are valid and if so setup a new session object
	 * or update the old one, also update the last login time
	 *
	 * @param string $username
	 *   the username
	 * @param string $password
	 *   the password
	 *
	 * @return UserObj return the user if provided credentials are valid, else false
	 */
	public function validate_login($username, $password);
}

