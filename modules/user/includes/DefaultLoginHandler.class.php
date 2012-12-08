<?php

/**
 * Provides the default login handler which checks the username and the password against the user table.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module
 */
class DefaultLoginHandler extends AbstractLoginHandler implements LoginHandler
{

	/**
	 * Check if the given credentials are valid and if so setup a new session object
	 * or update the old one, also update the last login time.
	 *
	 * @param string $username
	 *   the username.
	 * @param string $password
	 *   the crypted password.
	 *
	 * @return boolean return true if provided credentials are valid, else false.
	 */
	public function validate_login($username, $password) {
		// Create the user object which will be filled if login succeed.
		$user_obj = new UserObj();

		// Add the database filter to load the user based up on the username check variable.
		if ($this->core->get_dbconfig("user", User::CONFIG_LOGIN_ALLOW_EMAIL, 'no') == 'yes') {
			// We want to allow also to provide the email as the username, so we need to extend the databasefilter
			// to search also within the user address.
			$where_or = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
			$user_obj->db_filter->join(UserAddressObj::TABLE, 'usr.user_id = ' . UserObj::TABLE . '.user_id', 'usr');
			$where_or->add_where("email", $username);
			$where_or->add_where("username", $username);
			$user_obj->db_filter->add_where($where_or);
		}
		else {
			$user_obj->db_filter->add_where("username", $username);
		}

		// Just check default users.
		$user_obj->db_filter->add_where('account_type', 'default');

		// Check if a valid user account exists.
		if (!$user_obj->load()) {
			// No user found.
			return false;
		}
		
		// Check that the provided password is the correct one.
		$hash_check = new PasswordHash();
		if (!$hash_check->check_password(trim($password), $user_obj->password)) {
			// Password incorrect.
			return false;
		}

		// Set the current user object.
		return $this->session->validate_login($user_obj);
	}

}