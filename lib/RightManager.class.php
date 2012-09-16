<?php

/**
 * Provide a right manager which determines if something is shown to the user or not
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Security
 */
class RightManager extends Object
{
	/**
	 * Define constances
	 */
	const RIGHT_TYPE_ALL = "all";
	const RIGHT_TYPE_USER = "user";
	const RIGHT_TYPE_GROUP = "group";

	/**
	 * Holds all loaded rights for the user (cache)
	 * @var array
	 */
	static $rights_loaded = array();

	private $all_string_rights = array();

	/**
	 *
	 * @param Core &$core
	 *   the core object (optional, default = null)
	 */
 	public function __construct(Core &$core = null) {
		parent::__construct($core);

		$db_use = $this->core->core_config('db', 'use');
		if (!empty($db_use)) {
			foreach ($this->db->query_slave_all("SELECT `right`, `description` FROM `" . CoreRightObj::TABLE . "` ORDER BY `right`") AS $right) {
				$this->all_string_rights[$right['right']] = $right['description'];
			}

			// Fallback of the version where descriptions did not exists.
			if (empty($this->all_string_rights)) {
				foreach ($this->db->query_slave_all("SELECT `right` FROM `" . CoreRightObj::TABLE . "` ORDER BY `right`") AS $right) {
					$this->all_string_rights[$right['right']] = '';
				}
			}
		}
	}

	/**
	 * Get all groups where the provided user is a member
	 * if the $user_id is not provided it will use the current logged in one
	 *
	 * @param int $user_id
	 *   the userid (optional, default = '')
	 *
	 * @return array the user groups
	 */
	public function get_user_groups($user_id = "") {
		//Get current logged in user id if no provided
		if (empty($user_id)) {
			$user_id = $this->session->current_user()->user_id;
		}

		//Get the groups
		$groups = array();
		foreach ($this->db->query_slave_all("SELECT * FROM `" . User2RightGroupObj::TABLE . "` WHERE user_id = iuser_id", array('iuser_id' => $user_id)) AS $group) {
			$groups[$group['group_id']] = $group['group_id'];
		}
		return $groups;
	}

	/**
	 * Returns the pure rights which can be configured
	 *
	 * @return array all rights
	 */
	public function get_all_rights() {
		return $this->all_string_rights;
	}

	/**
	 * Returns all owned rights for the specified group
	 *
	 * @param int $group_id
	 *   the group id
	 *
	 * @return array the parsed rights
	 */
	public function get_group_rights($group_id) {
		$row = $this->db->query_slave_first("SELECT `permissions` FROM `" . UserRightGroupObj::TABLE . "` WHERE `group_id` = @group_id", array("@group_id" => $group_id));

		if (empty($row)) {
			return array();
		}
		return $this->parse_rights($row['permissions']);
	}

	/**
	 * Check a permission against the current user.
	 *
	 * @param string $perm
	 *   the permission to check
	 *
	 * @return boolean true if current user has the permission, else false
	 */
	public static function has_permission($perm) {
		$core = Core::get_instance();
		if (empty($core) || empty($core->right_manager)) {
			return false;
		}

		return $core->right_manager->has_perm($perm);
	}

	/**
	 * Checks the given user if he has given permission(s)
	 * if $user is not provided the current one is used
	 *
	 * @param string $right
	 *   the right
	 * @param boolean $login_check
	 *   Wether we want to redirect the user to the login page if not logged in or not (optional, default = false)
	 * @param UserObj $user
	 *   the user obj (optional, default = null)
	 *
	 * @return boolean if user has permission return true, else false
	 */
	public function has_perm($right, $login_check = false, UserObj $user = null) {


		//If no user was provided try to get current logged in user
		if (empty($user)) {
			//If no session object exists, we have no user module so return false
			if (empty($this->session)) {
				return false;
			}

			if($login_check == true) {
				//Need to be logged in
				$this->session->require_login();
			}
			//If we not require a login we need to check if the user is logged in, if not he can not own the right
			else if(!$this->session->is_logged_in()) {
				return false;
			}

			$user = $this->session->current_user();
		}

		//If we do not already have loaded the rights for the given user, load it
		if (!isset(RightManager::$rights_loaded[$user->user_id])) {
			RightManager::$rights_loaded[$user->user_id] = $user->get_rights();
		}

		//Return if the user has the right
		return $this->has_rights($right, RightManager::$rights_loaded[$user->user_id]);
	}

	/**
	 * check the the user rights against right
	 * if param $right is not ending with .* and has additional rights the "OR" condition will be used.
	 * So returns true if one of the rights is present.
	 * If you want that the user needs all additional permissions use .*
	 *
	 * @param string $right
	 *   the right to check for as a string like user.view.* or user.change
	 * @param array $user_rights
	 *   the current user rights
	 *
	 * @return boolean true if user has right, else false, if right is not found on global rights, return false
	 */
	public function has_rights($right, Array $user_rights) {
		static $cache = array();

		$cache_key = md5(implode("", $user_rights) . "|" . $right);

		//Initialize the or condition
		$condition = "or";

		//If right to check ends with .* set condition to "and" and remove the .* suffix from the right to check
		if(preg_match("/^(.*)\.\*$/is", $right, $matches)) {
			$right = $matches[1];
			$condition = "and";
		}

		if(isset($cache[$cache_key])) {
			$right_array = $cache[$cache_key];
		}
		else {

			/**
			 * If we provide a full right name we must extend our reg exp that it must end with a dot or the hole right (this is used for regular expression search
			 * we must use the dot regexp extension because if the searched right is "main" and we have another group which has "main_right" it would tell us we have the right
			 * if we do not own one of the main.* rights
			 */
			$or = "";
			if (preg_match("/[a-z]$/iUs", $right)) {
				$or = "(\.|$)";
			}
			$right_search = "/^" . preg_quote($right, '/') . $or . "/iUs";

			//The values within this array will be the rights which matched our right search regexp for the given right which we want to check
			$right_array = array();
			//Find all rights which matches the search reg exp
			foreach ($this->all_string_rights AS $right => $description) {
				if (preg_match($right_search, $right)) {
					$right_array[] = $right;
				}
			}

			$cache[$cache_key] = $right_array;
		}

		//If no right matched the regex, return false couse this right does not exist
		if (empty($right_array)) {
			return false;
		}

		//Loop though all matched arrays
		foreach ($right_array AS $v) {
			//Check if the user owned the right and if we are in the or condition, if so we can safely return true
			if (!empty($user_rights[$v]) && ($condition == "or")) {
					return true;
			}
			//This right did not matched and we are on 'and' condition (.*) se we can return false if we do not own the right because "and" does not allow a not allowed right
			else if($condition == 'and' && empty($user_rights[$v])){
				return false;
			}
		}

		//At this point we have all rights owned within and condition or we have no rights owned in or condition so we return true if condition is and, else false
		return ($condition == 'and');
	}

	/**
	 * Gets an array with all the user rights
	 *
	 * @param int $userid
	 *   the userid to look for
	 * @param int $filter
	 *   the filter type use one of RightManager::RIGHT_TYPE_* (optional, default = RightManager::RIGHT_TYPE_ALL)
	 * @param boolean $raw
	 *   do we remove negative rights or print raw data? (optional, default = false
	 *
	 * @return array returns an array with user rights
	 */
	public function get_rights($userid, $filter = self::RIGHT_TYPE_ALL, $raw = false) {

		$row = array();

		//Get the rights which are owned from groups
		if ($filter == self::RIGHT_TYPE_ALL || $filter == self::RIGHT_TYPE_GROUP) {
			$sql = "SELECT urg.`permissions` FROM `" . User2RightGroupObj::TABLE . "` uu2rg JOIN `" . UserRightGroupObj::TABLE . "` urg ON (urg.`group_id` = uu2rg.`group_id` AND uu2rg.`user_id` = '" . $userid . "')";
			foreach ($this->db->query_slave_all($sql) AS $right) {
				$row = array_merge($row, $this->parse_rights($right['permissions'], $raw));
			}
		}

		//Get the direct user rights
		if ($filter == self::RIGHT_TYPE_ALL || $filter == self::RIGHT_TYPE_USER) {
			$right = $this->db->query_slave_first("SELECT `permissions` FROM `" . UserRightObj::TABLE . "` WHERE `user_id` = 'iuserid'", array("iuserid" => $userid));
			if(!empty($right)) {
				$row = array_merge($row, $this->parse_rights($right['permissions'], $raw));
			}
		}

		return $row;
	}

	/**
	 * Gets an array with all the rights
	 * if raw is set to false negative rights starting with a - (minus) will removed from the returning array
	 * if set to true it will just leave the minus right within the returning array and the "disallowed" right will be
	 * still present if the user has this right
	 *
	 * @param array $permissions
	 *   the permissions array
	 * @param boolean $raw
	 *   set to true to get the raw rights (optional, default = false)
	 *
	 * @return array Returns an array with user rights
	 */
	public function parse_rights($permissions, $raw = false) {
		$return_arr = array();

		//Get all current rights
		$all_rights = $this->all_string_rights;
		//Loop through all provided right lines
		foreach (explode("\n", $permissions) AS $right) {
			//Remove windows \r and trim the string
			$right = str_replace("\r", "", $right);
			$right = trim($right);
			//If an empty line was provided, skip it
			if(empty($right)) {
				continue;
			}
			/**
			 * If the line is * we add directly all possible rights, but we must continue, we can not break here
			 * because it can be that we own a negative right which must be also added
			 */
			if($right == "*") {
				$return_arr = $all_rights;
				continue;
			}
			$return_arr[$right] = $right;
		}

		//Set all values within our current rights to 1
		foreach($return_arr AS &$value) {
			$value = 1;
		}

		//If we do not want the raw rights we search for rights which starts with a minus and remove set the value to 0 and also remove the minus right
		if ($raw == false) {
			foreach ($return_arr AS $right => $v) {
				if (preg_match("/^-(.+)$/iUs", $right, $matches)) {
					unset($return_arr[$right]);
					$return_arr[$matches[1]] = 0;
				}
			}
		}

		return $return_arr;
	}
}

