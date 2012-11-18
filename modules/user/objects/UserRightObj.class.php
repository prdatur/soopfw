<?php

/**
 * Represents a possible user right
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Objects
 */
class UserRightObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = 'user_right_config';

	/**
	 * Holds the current permissions as an array.
	 *
	 * @var array
	 */
	protected $current_permissions = array();

	/**
	 * Constructor
	 *
	 * @param int $user_id
	 *   the user id (optional, default = "")
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($user_id = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("user_id"));
		$this->db_struct->add_field("user_id", t("User ID"), PDT_INT);
		$this->db_struct->add_field("permissions", t("Permissions"), PDT_TEXT);
		if (!empty($user_id)) {
			$this->load(array($user_id), $force_db);
		}
	}

	/**
	 * Grant one or more permissions.
	 *
	 * NOTICE: You need to call UserRightObj->flush_permissions(); after you finished
	 * modifying all permissions.
	 *
	 * @param mixed $permissions
	 *   the right, can be an array with right's as values or a single right string.
	 */
	public function grant_permission($permissions) {
		if (!is_array($permissions)) {
			$permissions = array($permissions);
		}

		$this->read_current_rights();

		foreach ($permissions AS $permission) {
			unset($this->current_permissions['-' . $permission]);
			$this->current_permissions[$permission] = $permission;
		}
	}

	/**
	 * Revoke one or more permissions.
	 *
	 * NOTICE: You need to call UserRightObj->flush_permissions(); after you finished
	 * modifying all permissions.
	 *
	 * @param mixed $permissions
	 *   the right, can be an array with right's as values or a single right string.
	 */
	public function revoke_permission($permissions) {
		if (!is_array($permissions)) {
			$permissions = array($permissions);
		}

		$this->read_current_rights();

		foreach ($permissions AS $permission) {
			unset($this->current_permissions[$permission]);
			$this->current_permissions['-' . $permission] = '-' . $permission;
		}
	}

	/**
	 * Remove one or more permissions.
	 *
	 * NOTICE: You need to call UserRightObj->flush_permissions(); after you finished
	 * modifying all permissions.
	 *
	 * @param mixed $permissions
	 *   the right, can be an array with right's as values or a single right string.
	 */
	public function remove_permission($permissions) {
		if (!is_array($permissions)) {
			$permissions = array($permissions);
		}

		$this->read_current_rights();

		foreach ($permissions AS $permission) {
			unset($this->current_permissions['-' . $permission]);
			unset($this->current_permissions[$permission]);
		}
	}

	/**
	 * Save the current internal permission array container.
	 *
	 * @return boolean returns true if the permissions were saved or false if not.
	 */
	public function flush_permissions() {

		$this->read_current_rights();

		//Get the new right string, set it as the permissions attribute and save or insert it.
		$current_rights = implode("\n", $this->current_permissions);
		$this->permissions = $current_rights;
		if ($this->save_or_insert()) {

			/**
			 * Provides hook: user_permission_change
			 *
			 * Allow other modules to do tasks if the rights changed for the specific user
			 *
			 * @param int $user_id
			 *   The user id
			 * @param array $permissions
			 *   the current permissions for the user (includes the changes)
			 */
			$this->core->hook('user_permission_change', array($this->user_id, $this->permissions));
			return true;
		}
		return false;
	}

	/**
	 * Load the current permission string into an internal permission array container.
	 */
	private function read_current_rights() {
		if (empty($this->current_permissions)) {
			//Add all current rights to an temporary array
			foreach (explode("\n", $this->permissions) AS $right) {
				$this->current_permissions[$right] = $right;
			}
		}
	}

}

