<?php

/**
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.user.includes
 */
class DefaultLoginHandler extends Object implements LoginHandler
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
		return '/user/login.html';
	}

	/**
	 * Returns the logout url for this login handler.
	 *
	 * @return string the logout url
	 */
	public function get_logout_url() {
		return '/user/logout.html';
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
	 * Checks if the user is logged in, if not we will redirect him to the login page
	 *
	 * @param boolean $force_not_loggedin force not logged in that the user will be redirected to the user login page
	 * @param boolean $need_direct_handler need this login handler as valid
	 * @return boolean true if normal behaviour should checked (Session::require_login which redirects if the is_logged_in param is set to false), false if the login handler handles this event
	 */
	public function require_login($force_not_loggedin = false, $need_direct_handler = false) {
		return true;
	}

	/**
	 * Check if the user is logged in and log the user in if a post was provided.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function check_login() {

		//Initialize check variables, these variables will be ether filled from session or from post variables
		$check_user = "";

		$session_id = $this->session->get_session_id();
		//If we have NOT posted and session variables are NOT empty check the user with the current session
		if (!empty($session_id)) {
			//Load the session object for the current session
			$session_obj = new UserSessionObj($session_id);

			//Check if we found a valid session
			if ($session_obj->load_success() === false) {
				return false;
			}
			else {
				//Set the crrent user object
				$user_obj = new UserObj($session_obj->user_id);
				if(!$user_obj->load_success()) {
					return false;
				}
				return $this->session->validate_login($user_obj);
			}
		}
		//No session or login post found, we are on guest mode
		else {
			$check_user = 'guest';
		}

		return $this->validate_login($check_user, "");
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
	 *   the username
	 * @param string $password
	 *   the crypted password
	 *
	 * @return boolean return true if provided credentials are valid, else false
	 */
	public function validate_login($username, $password) {
		//Create the user object which will be filled if login succeed
		$user_obj = new UserObj();
		//Add the database filter to load the user based up on the username check variable

		if ($this->core->get_dbconfig("user", user::CONFIG_LOGIN_ALLOW_EMAIL, 'no') == 'yes') {
			$where_or = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
			$user_obj->db_filter->join(UserAddressObj::TABLE, 'usr.user_id = ' . UserObj::TABLE . '.user_id', 'usr');
			$where_or->add_where("email", $username);
			$where_or->add_where("username", $username);
			$user_obj->db_filter->add_where($where_or);
		}
		else {
			$user_obj->db_filter->add_where("username", $username);
		}
		//Check if a valid user account exists
		if (!$user_obj->load()) { //No user found
			return false;
		}

		//User must be active and not deleted
		if($user_obj->active != 'yes' || $user_obj->deleted != 'no') {
			return false;
		}

		$hash_check = new PasswordHash();
		if (!$hash_check->check_password(trim($password), $user_obj->password)) { //Password incorrect
			return false;
		}

		//Set the current user object
		return $this->session->validate_login($user_obj);
	}
}

