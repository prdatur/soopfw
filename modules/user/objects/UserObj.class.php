<?php

/**
 * Represents a user.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class UserObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = 'user';

	/**
	 * Holds the rights for the user.
	 *
	 * @var array
	 */
	private $rights = array();

	/**
	 * Determines if we have loaded the rights yet or not.
	 *
	 * @var boolean
	 */
	private $rights_loaded = false;

	/**
	 * constructor
	 *
	 * @param int $user_id
	 *   the user id. (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database. (optional, default = false)
	 */
	function __construct($user_id = "", $force_db = false) {
		parent::__construct();
		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(false);
		$this->db_struct->runtime_cache = true;
		$this->db_struct->add_reference_key("user_id");
		$this->db_struct->set_auto_increment("user_id");
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT, 0);
		$this->db_struct->add_field("username", t("username"), PDT_STRING);
		$this->db_struct->add_field("account_type", t("The account type"), PDT_STRING, 'default');
		$this->db_struct->add_hidden_field("password", t("password"), PDT_STRING);
		$this->db_struct->add_field("language", t("Language"), PDT_STRING, 'en');
		$this->db_struct->add_field("registered", t("Registered"), PDT_DATETIME, '0000-00-00 00:00:00');
		$this->db_struct->add_field("last_login", t("Last login"), PDT_DATETIME, '0000-00-00 00:00:00');
		$this->db_struct->add_field("active", t("active"), PDT_ENUM, 'yes', array('yes' => t('Yes'), 'no' => t('No')));
		$this->db_struct->add_field("confirm_key", t("confirm_key"), PDT_STRING);
		$this->db_struct->add_field("deleted", t("Deleted"), PDT_ENUM, 'no', array('yes' => t('Yes'), 'no' => t('No')));

		$this->db_struct->add_index(MysqlTable::INDEX_TYPE_INDEX, array('username'));

		$this->set_default_fields();
		if (!empty($user_id)) {
			if (!$this->load($user_id, $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Save the user.
	 *
	 * @param boolean $save_if_unchanged
	 *   If we want to save the current values also if we do not changed anything. (optional, default = false)
	 * @param boolean $crypt_pw
	 *   Whether we want to crypt the password or not. (optional, default = true)
	 *
	 * @return boolean true on success, else false.
	 */
	public function save($save_if_unchanged = false, $crypt_pw = true) {
		if ($crypt_pw == true && ($save_if_unchanged == true || (isset($this->values_changed['password']) && $this->values_changed['password'] != $this->old_values['password']))) {
			$this->crypt_pw();
		}
		return parent::save($save_if_unchanged);
	}

	/**
	 * Save or insert the user.
	 *
	 * @param boolean $crypt_pw
	 *   Whether we want to crypt the password or not. (optional, default = true)
	 * @param boolean $save_if_unchanged
	 *   If we want to save the current values also if we do not changed anything. (optional, default = false)
	 *
	 * @return boolean true on success, else false.
	 */
	public function save_or_insert($crypt_pw = true, $save_if_unchanged = false) {
		if ($crypt_pw == true && ($save_if_unchanged == true || (isset($this->values_changed['password']) && $this->values_changed['password'] != $this->old_values['password']))) {
			$this->crypt_pw();
		}
		return parent::save_or_insert();
	}

	/**
	 * Save the user.
	 *
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there. (optional, default=false)
	 * @param boolean $crypt_pw
	 *   Whether we want to crypt the password or not. (optional, default = true)
	 *
	 * @return boolean true on success, else false.
	 */
	public function insert($ignore = false, $crypt_pw = true) {
		if ($crypt_pw == true) {
			$this->crypt_pw();
		}

		if (parent::insert($ignore)) {

			// Add the user to all default permission groups.
			foreach($this->core->get_dbconfig("system", User::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, array(), false, false, true) AS $group_id) {
				$user2group = new User2RightGroupObj();
				$user2group->user_id = $this->user_id;
				$user2group->group_id = $group_id;
				$user2group->insert();
			}

			/**
			 * Provides hook: add_user
			 *
			 * Allow other modules to do tasks if the user is created
			 *
			 * @param int $user_id
			 *   The user id
			 */
			$this->core->hook('add_user', array($this->user_id));

			SystemHelper::audit(t('The user "@username" was created', array('@username' => $this->username)), 'user');
			return true;
		}
		return false;
	}

	/**
	 * Deletes the user, also delete all linked elements for a user.
	 *
	 * @return boolean true on success, else false.
	 */
	public function delete() {
		$this->transaction_auto_begin();
		$user_id = $this->user_id;
		$username = $this->username;

		// Delete all user addresses.
		$this->db->query_master("DELETE FROM `".UserAddressObj::TABLE."` WHERE `user_id` = @user_id", array(
			'@user_id' => $user_id
		));

		// Delete all memberships to permission groups.
		$this->db->query_master("DELETE FROM `".User2RightGroupObj::TABLE."` WHERE `user_id` = @user_id", array(
			'@user_id' => $user_id
		));

		// Delete all individuell permission configs.
		$this->db->query_master("DELETE FROM `".UserRightObj::TABLE."` WHERE `user_id` = @user_id", array(
			'@user_id' => $user_id
		));

		// Remove all session entries.
		$this->db->query_master("DELETE FROM `".UserSessionObj::TABLE."` WHERE `user_id` = @user_id", array(
			'@user_id' => $user_id
		));

		if (parent::delete()) {

			/**
			 * Provides hook: user_delete
			 *
			 * Allow other modules to do tasks if the user is deleted
			 *
			 * @param int $user_id
			 *   The user id
			 */
			$this->core->hook('user_delete', array($user_id));

			$this->transaction_auto_commit();
			SystemHelper::audit(t('The user "@username" was deleted', array('@username' => $username)), 'user');
			return true;
		}
		$this->transaction_auto_rollback();
		return false;
	}

	/**
	 * Returns the raw user rights.
	 *
	 * Don't use this to check permissions, disallowed rights are not marked as permission denied.
	 *
	 * @param string $type
	 *   filter the return rights, use one of RightManager::RIGHT_TYPE_*. (optional, default = RightManager::RIGHT_TYPE_USER);
	 *
	 * @return array with all rights.
	 */
	public function get_raw_rights($type = RightManager::RIGHT_TYPE_USER) {
		return $this->right_manager->get_rights($this->user_id, $type, true);
	}

	/**
	 * Returns all permissions for this user.
	 *
	 * @return array The permissions for this user.
	 */
	public function get_rights() {
		if(!$this->rights_loaded) {
			$this->rights = $this->right_manager->get_rights($this->user_id);
		}
		return $this->rights;
	}

	/**
	 * Checks the user if he has given permission(s).
	 *
	 * @param string $right
	 *   the right.
	 *
	 * @return boolean if user has permission return true, else false.
	 */
	public function has_perm($right) {
		return $this->right_manager->has_perm($right, false, $this);
	}

	/**
	 * Add one or more right's to this user.
	 *
	 * Use this method with caution
	 *
	 * This will only grant the permission for the current request, it will be not
	 * saved into the user rights database table entry.
	 *
	 * @param mixed $right
	 *   the right, can be an array with right's as values or a single right string.
	 */
	public function grant_static_permission($right) {
		if (empty($this->rights) && $this->rights_loaded == false) {
			$this->rights = $this->right_manager->get_rights($this->user_id);
			$this->rights_loaded = true;
		}

		if (!is_array($right)) {
			$right = array($right);
		}

		foreach ($right AS $single_right) {
			$this->rights[$single_right] = true;
		}
	}

	/**
	 * Revoke one or more right's from this user.
	 *
	 * Use this method with caution
	 *
	 * This will only revoke the permission for the current request, it will be not
	 * saved into the user rights database table entry.
	 *
	 * @param mixed $right
	 *   the right, can be an array with right's as values or a single right string.
	 */
	public function revoke_static_permission($right) {
		if (empty($this->rights) && $this->rights_loaded == false) {
			$this->rights = $this->right_manager->get_rights($this->user_id);
			$this->rights_loaded = true;
		}

		if (!is_array($right)) {
			$right = array($right);
		}

		foreach ($right AS $single_right) {
			$this->rights[$single_right] = false;
		}
	}

	/**
	 * get the addresses of this user
	 *
	 * @return array returns an array with all the address information
	 */
	public function get_addresses() {
		return $this->db->query_slave_all("SELECT * FROM `".UserAddressObj::TABLE."` WHERE IF(`user_id` != '0', `user_id`, `parent_id`) = @user_id", array(
			'@user_id' => $this->user_id
		));
	}

	/**
	 * Get the address of this user favored by group,
	 * if group is not default and result is empty default group will be returned,
	 * if after this the result is empty first address of this user will be returned
	 *
	 * @param string $group
	 *   the group, use one of UserConsts::USER_ADDRESS_GROUP_* (optional, default = UserConsts::USER_ADDRESS_GROUP_DEFAULT)
	 * @param string $db_field
	 *   only this databasefield will be returned (optional, default = '')
	 *
	 * @return array returns an array with the address information, if db_field is given, only this field will be returned as a string
	 */
	public function get_address_by_group($group = UserAddressObj::USER_ADDRESS_GROUP_DEFAULT, $db_field = "") {
		$return = "";
		if (!empty($db_field)) {
			$return = $db_field;
			$db_field .= "`".Db::sql_escape($db_field)."`";
		}
		else {
			$db_field = "*";
		}
		$result = $this->db->query_slave_first("SELECT ".$db_field." FROM `".UserAddressObj::TABLE."` WHERE IF(`user_id` != '0', `user_id`, `parent_id`) = @user_id AND `group` = @group", array(
			'@user_id' => $this->user_id,
			'@group' => $group
		));

		if (empty($result) && $group != UserAddressObj::USER_ADDRESS_GROUP_DEFAULT) {
			$result = $this->db->query_slave_first("SELECT ".$db_field." FROM `".UserAddressObj::TABLE."` WHERE IF(`user_id` != '0', `user_id`, `parent_id`) = @user_id AND `group` = '".UserAddressObj::USER_ADDRESS_GROUP_DEFAULT."'", array(
				'@user_id' => $this->user_id
			));
		}

		if (empty($result)) {
			$result = $this->db->query_slave_first("SELECT ".$db_field." FROM `".UserAddressObj::TABLE."` WHERE IF(`user_id` != '0', `user_id`, `parent_id`) = @user_id", array(
				'@user_id' => $this->user_id
			));
		}


		if (!empty($return)) {
			if (empty($result)) {
				return null;
			}
			return $result[$return];
		}

		if (empty($result)) {
			$address_obj = new UserAddressObj();
			$address_obj->set_default_fields();
			return $address_obj->get_values();
		}

		return $result;
	}

	/**
	 * Creates a new account.
	 *
	 * This method will directly assign errors, if the creation succeed the success message will NOT be assigned.
	 *
	 * @param UserAddressObj $address_obj
	 *   The address for this new user. (optional, default = null)
	 * @param boolean $ignore
	 *   Don't throw an error if data is already there. (optional, default=false)
	 * @param boolean $crypt_pw
	 *   Whether we want to crypt the password or not. (optional, default = true)
	 *
	 * @return boolean true on success, else false.
	 */
	public function create_account(UserAddressObj $address_obj = null, $ignore = false, $crypt_pw = true) {
		$this->db->transaction_begin();

		// Only insert if the current user object is not loaded.
		if (!$this->load_success()) {
			if (!$this->insert($ignore, $crypt_pw)) {
				$this->core->message(t('Could not create the account'), Core::MESSAGE_TYPE_ERROR);
				$this->db->transaction_rollback();
				return false;
			}
		}

		// Only insert address if we provide some data.
		if (!empty($address_obj)) {
			$address_obj->user_id = $this->user_id;

			// Validates the email to be unique if user modul is configured to hold just unique emails per user.
			if ($this->core->get_dbconfig("user", user::CONFIG_SIGNUP_UNIQUE_EMAIL, 'no') == 'yes') {
				$filter = DatabaseFilter::create(UserAddressObj::TABLE)
					->add_where('email', $address_obj->email)

					// Duplicated emails are just allowed within the same user.
					->add_where('user_id', $this->user_id, '!=');

				if ($filter->select_exists()) {
					$this->core->message(t('Could not create the account, the provided email address is already taken from another user and the system is configured to accept only unique email addresses'), Core::MESSAGE_TYPE_ERROR);
					$this->db->transaction_rollback();
					return false;
				}
			}

			if (!$address_obj->insert($ignore)) {
				$this->core->message(t('Could not create the account address'), Core::MESSAGE_TYPE_ERROR);
				$this->db->transaction_rollback();
				return false;
			}
		}

		$this->db->transaction_commit();
		return true;
	}

	/**
	 * Crypts a password, also we remove the values_changed password entry if the current password is empty
	 * else we would set on every user change the password to an empty one.
	 */
	private function crypt_pw() {

		if (empty($this->password)) {

			unset($this->values['password']);
			unset($this->values_changed['password']);
		}
		else {
			$hash_check = new PasswordHash();
			$this->password = $hash_check->hash_password($this->password);
		}
	}

}

