<?php

/**
 * User action module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.user
 */
class user extends ActionModul
{

	//Default method
	protected $default_methode = "overview";

	const CONFIG_DEFAULT_REGISTERED_USER_GROUPS = 'default_registered_user_groups';

	/**
	 * Implementation of get_admin_menu()
	 * @return array the menu
	 */
	public function get_admin_menu() {
		return array(
			999 => array(//Order id, same order ids will be unsorted placed behind each
				'#id' => 'soopfw_user', //A unique id which will be needed to generate the submenu
				'#title' => t("User"), //The main title
				'#link' => "/admin/user", // The main link
				'#perm' => 'admin.user', //Perm needed
				'#childs' => array(
					array(
						'#title' => t("Users"), //The main title
						'#link' => "/admin/user/overview", // The main link
					),
					array(
						'#title' => t("Groups"), //The main title
						'#link' => "/admin/user/user_groups", // The main link
						'#perm' => 'admin.user.group', //Perm needed
					),
					array(
						'#title' => t("config"), //The main title
						'#link' => "/admin/user/config", // The main link
						'#perm' => 'admin.user.config', //Perm needed
					),
				)
			)
		);
	}


	/**
	 * Action: config
	 * Configurate the system main settings.
	 */
	public function config() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			return $this->no_permission();
		}

		//Setting up title and description
		$this->title(t("System Config"), t("Here we can configure the main system settings"));

		//Configurate the settings form
		$form = new Form("system_config", t("Configuration"));

		$values = array();
		foreach($this->db->query_slave_all('SELECT * FROM `'. UserRightGroupObj::TABLE .'`') AS $row) {
			$values[$row['group_id']] = $row['title'];
		}
		$form->add(new Checkboxes(self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, $values, $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, array(), false, false, true), t('Default user groups'), t('The above selected groups will be automaticly assigned to newly created / registered users.')));

		//Add a submit button
		$form->add(new Submitbutton("saveconfig", t("Save Config")));

		//Assign form to smarty
		$form->assign_smarty();

		//Check if form is valid (does not return anything but should always be called manually)
		$form->check_form();

		//Wether the form is submit and valid
		if ($form->is_submitted() && $form->is_valid()) {
			$this->core->dbconfig('core', self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, $form->get_value(self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS), false, false, true);
			$this->core->message(t("Configuration saved"), Core::MESSAGE_TYPE_SUCCESS);
		}
		$this->static_tpl = "form.tpl";
	}

	/**
	 * Action: overview
	 * Display and/or search all users
	 */
	public function overview() {
		//Need to be logged in
		$this->session->require_login();

		$this->title(t("Overview"), t("Here we can [b]search[/b] for users or [b]add[/b] an user. There are some [b]inplace[/b] functions.
			First we can [b]enable[/b] or [b]disable[/b] a user by clickin the [b]status icon[/b] ([color=red]red[/color] or [color=green]green[/color]).
			On [b]mouseover[/b] on a [b]username[/b] we see the default address within a tooltip, with a click on the [b]Key icon[/b] we can change the user password.
			To [b]delete[/b] the user completly we can click on the [b]X[/b]-Icon"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.user.view")) {
			return $this->no_permission();
		}

		//add js to get password change function
		$this->core->add_js("/modules/user/js/user_password_change_dialog.js");

		//Setup search form
		$form = new Form("search_users", t("Search users:"));
		$form->add(new Textfield("username"));
		$form->add(new Submitbutton("searchUsers", t("Search")));
		$form->assign_smarty("search_form");

		//Check form and add errors if form is not valid
		$form->check_form();

		if ($form->is_submitted()) { //Search was submited
			//Set session key for user search values so a reload of a page will use the session values
			$this->session->set("search_user_overview", $form->get_values());
		}
		else {
			//Form was not submited so try to load session values
			$form->set_values($this->session->get("search_user_overview", array()));
		}
		$where = array();
		//Build up where statement
		foreach ($this->session->get("search_user_overview", array()) AS $field => $val) {
			if (empty($val)) {
				continue;
			}
			$where[] = "`" . sql_escape($field) . "` LIKE '" . $this->db->get_sql_string_search($val, "{v}%") . "'";
		}

		//If where array is not empty add the where.
		if (!empty($where)) {
			$where = " WHERE " . implode(" AND ", $where);
		}

		//Build query string for pager
		$query_string = "SELECT 1 FROM `" . UserObj::TABLE . "`" . $where;

		//Init pager
		$pager = new Pager(50, $this->db->query_slave_count($query_string));
		$pager->assign_smarty("pager");

		//Build query string
		$query_string = "SELECT * FROM `" . UserObj::TABLE . "`" . $where;

		//Search in DB
		$users = $this->db->query_slave_all($query_string, array(), $pager->max_entries_per_page(), $pager->get_offset());
		foreach ($users AS &$user) {
			switch ($user['active']) {
				case 'yes': $user['status_color'] = 'green';
					break;
				case 'no': $user['status_color'] = 'red';
					break;
				case 'warned': $user['status_color'] = 'yellow';
					break;
			}
			$user_obj = new UserObj();
			$user_obj->set_fields_bulk($user);
			$user['default_address'] = $user_obj->get_address_by_group();
		}

		//Assign found users
		$this->smarty->assign_by_ref("user_found", $result['result_count']);
		$this->smarty->assign_by_ref("users", $users);
	}

	/**
	 * Action: add_user
	 * Add an user
	 */
	public function add_user() {
		//Need to be logged in
		$this->session->require_login();

		if (!$this->right_manager->has_perm("admin.user.add")) {
			return $this->no_permission();
		}


		$form = new Form("form_add_user");
		$form->set_ajax(true);

		$username = new Textfield("username", '', t('username'));
		$username->add_validator(new RequiredValidator());
		$form->add($username);
		$password = new Textfield("password", '', t('password'), '<a href="javascript:void(0);" onclick="generate_password(8, \'#form_id_form_add_user_password\');">' . t("Generate password") . '</a>');
		$password->add_validator(new RequiredValidator());
		#$password->config("suffix", );


		$form->add($password);
		$email = new Textfield("email", '', t('email'));
		$email->add_validator(new EmailValidator());
		$form->add($email);

		$form->add(new Submitbutton("btn_add_user", t("Add user")));
		$form->add(new Button("btn_cancel", t("cancel")));

		$form->add_js_success_callback("add_user_success");

		$form->assign_smarty("form");

		//Check if form was submitted
		if ($form->is_submitted() && $form->is_valid(false)) {

			$user_obj = new UserObj();
			$user_obj->set_fields($form->get_values());
			$user_obj->password = md5($user_obj->password);
			$user_obj->transaction_auto_begin();
			//Check if the insert command returned true
			if ($user_obj->insert()) {

				$user_address_obj = new UserAddressObj();
				$user_address_obj->user_id = $user_obj->user_id;
				$user_address_obj->set_fields($form->get_values());
				if ($user_address_obj->insert()) {

					/**
					 * Provides hook: add_user
					 *
					 * Allow other modules to do tasks if the user is created
					 *
					 * @param int $user_id
					 *   The user id
					 */
					$this->core->hook('add_user', array($user_obj->user_id));
					$user_obj->transaction_auto_commit();
					//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
					$this->core->message(t("User added successfully"), Core::MESSAGE_TYPE_SUCCESS, $form->is_ajax(), $user_obj->user_id);
					return;
				}
			}
			$user_obj->transaction_auto_rollback();
			//Setup error message to display
			$this->core->message(t("Could not add user"), Core::MESSAGE_TYPE_ERROR, $form->is_ajax());
		}

		$this->static_tpl = 'form.tpl';
	}

	/**
	 * Action: userdata
	 * Displays user related information
	 *
	 * @param int $user_id the user id
	 */
	public function userdata($user_id = 0) {
		//Need to be logged in
		$this->session->require_login();

		$user_id = (int)$user_id;
		//Check perms
		if (!$this->right_manager->has_perm("admin.user.view") && $this->session->current_user()->user_id != $user_id) {
			return $this->no_permission();
		}

		//Check if a userid was provided
		if (empty($user_id)) {
			return $this->wrong_params();
		}

		//add js to get password change function
		$this->core->add_js("/modules/user/js/user_password_change_dialog.js");

		$user_obj = new UserObj($user_id);
		$this->title(t("User data: @username", array("@username" => $user_obj->username)), t("Displays related user information, ability to change the user password."));

		//Provide user_id to javascript
		$this->core->js_config("admin_userdata_user_id", $user_id);

		$this->smarty->assign_by_ref("user", $user_obj);
	}

	/**
	 * Action: user_address
	 * Display the all addresses from given user
	 *
	 * @param int $user_id the userid
	 */
	public function user_address($user_id = 0) {
		//Need to be logged in
		$this->session->require_login();

		$this->title(t("User address"), t("Manage the address for this user.
			Please note, a \"[b]default[/b]\" address [b]MUST[/b] exist. All default emails will be delivered to this address"));
		$user_id = (int)$user_id;

		//Check perms
		if (!$this->right_manager->has_perm("admin.user.view") && $this->session->current_user()->user_id != $user_id) {
			return $this->no_permission();
		}

		//Check if a userid was provided
		if (empty($user_id)) {
			return $this->wrong_params();
		}

		//Provide user_id to javascript
		$this->core->js_config("user_address", array('user_id' => $user_id));

		//Load the requested user
		$user_obj = new UserObj((int)$user_id);

		//Assign user values
		$this->smarty->assign_by_ref("user", $user_obj->get_values());

		//Assign addresses from requested user
		$this->smarty->assign_by_ref("addresses", $user_obj->get_addresses());
	}

	/**
	 * Action: add_address
	 * Insert or add an address for given user
	 * if addressID is provided, it will change this address (save), else insert a new address
	 *
	 * @param int $user_id the user_id
	 * @param int $address_id The addressID (optional, default = 0)
	 */
	public function add_address($user_id, $address_id = 0) {
		//Need to be logged in
		$this->session->require_login();

		$force_loaded = false;

		//Save variables
		$user_id = (int)$user_id;
		$address_id = (int)$address_id;
		if (!empty($address_id)) { //edit mode
			$this->title(t("Save address"));
			//Load address
			$address_obj = new UserAddressObj($address_id);
			//Check perms
			if ($this->session->current_user()->user_id != $address_obj->user_id && !$this->right_manager->has_perm("admin.user.change")) {
				return $this->wrong_params();
			}
			//Add save button
			$submit_button = new Submitbutton("save", t("Save"));

			//Set form title
			$title = t("Change address");
			$message = t("address changed");

			$old_values = $address_obj->get_values();
		}
		else {
			//Check perms
			if ($this->session->current_user()->user_id != $user_id && !$this->right_manager->has_perm("admin.user.change")) {
				return $this->wrong_params();
			}
			$this->title(t("Add address"));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add"));

			//Set form title
			$title = t("Add address");
			$message = t("address added");

			//Create empty address object, prefill with userid
			$address_obj = new UserAddressObj();

			//Set the force loaded to true, else the objForm will fill out the the default values and userid will be 0
			$force_loaded = true;
			$address_obj->user_id = $user_id;
		}


		//Init objForm
		$obj_form = new ObjForm($address_obj, '', array(), $force_loaded);

		//Enable ajax
		$obj_form->set_ajax(true);

		//Add the button
		$obj_form->add($submit_button);

		//Override address group with a selectbox
		$options = array(
			UserAddressObj::USER_ADDRESS_GROUP_DEFAULT => t('Default'),
			UserAddressObj::USER_ADDRESS_GROUP_DELIVER => t('Deliver'),
			UserAddressObj::USER_ADDRESS_GROUP_BILL => t('Bill'),
			UserAddressObj::USER_ADDRESS_GROUP_SUPPORT => t('Support'),
		);

		$obj_form->add(new Selectfield("group", $options, $obj_form->get_object()->group, t("Addressgroup"), "", "form_id_" . $obj_form->get_object()->get_dbstruct()->get_table() . "_group"), t("group"));

		//Add success ajax call to close the dialog
		$obj_form->add_js_success_callback("close_user_address_dialog");
		if (empty($address_id)) { //Insert mode
			$obj_form->add_js_success_callback("add_new_address_row");
		}
		else { //edit mode
			$obj_form->add_js_success_callback("replace_new_address_row");
		}

		//Assign form to smarty
		$obj_form->assign_smarty("form");

		//Provide current user_id and addressID for javascript
		$this->core->js_config("user_address", array('user_id' => $user_id, 'addressID' => $address_id));

		//Check if form was submitted
		if ($obj_form->check_form()) {
			//Check if the insert command returned true
			if (($lastinserted = $obj_form->save_or_insert())) {

				$args = array($user_id);
				if (!empty($address_id)) {
					$args[] = $address_id;
					$args[] = $old_values;
				}
				else {
					$args[] = $lastinserted;
				}

				/**
				 * Provides hook: add_address
				 *
				 * Allow other modules to do tasks if a address is added or changed for the user
				 *
				 * @param int $user_id
				 *   The user id
				 * @param int $address_id
				 *   The address id
				 * @param array $old_values
				 *   This array will be filled if the address is CHANGED so the address already exist. (optional, default = empty)
				 */
				$this->core->hook('add_address', $args);

				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message($message, Core::MESSAGE_TYPE_SUCCESS, $obj_form->is_ajax(), $obj_form->get_object()->get_values(true));
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not save address"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
			}
		}

		$this->static_tpl = 'form.tpl';
	}

	/**
	 * Action: user_rights
	 * Configuration of user rights
	 * @param int $user_id the user id
	 */
	public function user_rights($user_id) {
		//Need to be logged in
		$this->session->require_login();

		$this->title(t("Rights"), t("Define rights for this User.
				If a right is selected at [b]allowed[/b], he will always have this right wether the group is deny the right. To always deny the right, check the last radiobox ([b]Revoked[/b]).
				[b]Not owned[/b] means that if he is added to a group in future, this right will be automaticly managed by group, but for now the access is denied.
				If [b]Managed by group[/b] is selected than the allow/deny will be managed by the configuration of this group.
				If an user has 2 Groups which manage one or more rights both, a [b]deny will be favored[/b].
				"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.user.rights.change")) {
			return $this->no_permission();
		}

		$user_id = (int)$user_id;
		if (empty($user_id)) {
			return $this->wrong_params();
		}
		$user_obj = new UserObj($user_id);

		$all_rights = $this->right_manager->get_all_rights();
		foreach ($all_rights AS $k => $right) {
			if (preg_match("/%.+%/iUs", $right)) {
				unset($all_rights[$k]);
			}
		}

		$group_member = array();
		foreach ($this->db->query_slave_all("SELECT `group_id` FROM `" . User2RightGroupObj::TABLE . "` WHERE `user_id` = @user_id", array('@user_id' => $user_id)) AS $group) {
			$group_member[$group['group_id']] = true;
		}
		$this->core->js_config("user_rights", array(
			'user_rights_user' => $user_obj->get_raw_rights(RightManager::RIGHT_TYPE_USER),
			'user_rights_group' => $user_obj->get_raw_rights(RightManager::RIGHT_TYPE_GROUP),
			'all_rights' => $all_rights,
			'group_assignments' => $group_member,
			'right_groups' => $this->get_right_groups(),
			'user_id' => $user_id,
		));
	}

	/**
	 * Action: edit
	 * Display the user edit page.
	 * Initilize tabs for given tasks
	 *
	 * @param int $user_id the user_id
	 */
	public function edit($user_id) {
		//Require login
		$this->session->require_login();

		$user_obj = new UserObj($user_id);
		if ($user_obj->load_success()){
			$this->title(t("User: @username", array('@username' => $user_obj->username)));
		}
		else {
			$this->title(t("User"));
		}
		//Setup tabs
		$tabs = new HtmlTabs("user_edit");

		//Add tabs
		$tabs->add(t("User data"), "userdata", "/user/userdata/" . $user_id);
		$tabs->add(t("address"), "user_address", "/user/user_address/" . $user_id);
		$tabs->add(t("Rights"), "user_rights", "/user/user_rights/" . $user_id, "admin.user.rights.change");

		//Assign to smarty
		$tabs->assign_smarty("tabs");

		//Assign a submit button to add an address
		$this->smarty->assign_by_ref("add_address", new Submitbutton("add_address", t("Add address"), "form_button"));
	}

	/**
	 * Action: user_groups
	 * Display all user groups
	 */
	public function user_groups() {
		//Need to be logged in
		$this->session->require_login();

		$this->title(t("Groups"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.user.group.view")) {
			return $this->no_permission();
		}
		$this->core->js_config("admin_user_groups", $this->get_right_groups(true));
	}

	/**
	 * Action: user_groups_right_change
	 * Ability to change group rights
	 *
	 * @param int $group_id the group id
	 */
	public function user_groups_right_change($group_id) {
		//Need to be logged in
		$this->session->require_login();

		//Check perms
		if (!$this->right_manager->has_perm("admin.user.group.view")) {
			return $this->no_permission();
		}
		$group_id = (int)$group_id;

		//Check if a group_id was provided
		if (empty($group_id)) {
			return $this->wrong_params();
		}
		$all_rights = $this->right_manager->get_all_rights();
		foreach ($all_rights AS $k => $right) {
			if (preg_match("/%.+%/iUs", $right)) {
				unset($all_rights[$k]);
			}
		}
		$this->core->js_config("admin_user_groups_right_change_rights_array", $all_rights);
		$this->core->js_config("admin_user_groups_right_change_group_id", $group_id);
	}

	/**
	 * Action: login
	 * Provide the login form
	 */
	public function login() {

		$this->title(t('Login'), t('Please enter your username and password'));
		$login_form = new Form('login', '', 'soopfw_login');
		$login_form->add(new Textfield("user", '', t("Username")));
		$login_form->add(new Passwordfield("pass", '', t("Password")));
		$login_form->add(new Submitbutton("soopfw_login", t("Login"), t("Login")));
		$login_form->assign_smarty();

		$this->static_tpl = "form.tpl";

		if ($login_form->check_form()) {
			if ($this->session->validate_login($login_form->get_value("user"), $login_form->get_value("pass"))) {
				//If a location is found, redirect the user
				if (!empty($_SESSION['redir_after_login'])) {
					$redir = $_SESSION['redir_after_login'];
					unset($_SESSION['redir_after_login']);
					header("Location: " . $redir);
					exit();
				}
				//No location was found to redirt to /
				else {
					header("Location: /");
					exit();
				}
			}
			else {
				$this->core->message(t("You have entered a wrong username and/or password."), Core::MESSAGE_TYPE_ERROR);
			}
		}
	}

	/**
	 * Action: logout
	 * Logout user
	 */
	public function logout() {
		//logout current user
		$this->session->logout();
	}

	/**
	 * Install additional data
	 * @return boolean if called from wrong method
	 */
	public function install() {
		if (!parent::install()) {
			$this->clear_output();
			return false;
		}

		$user_group = new UserRightGroupObj();
		$user_group->title = 'Registered users';
		$user_group->permissions = 'admin.show_admin_menu';
		$user_group_id = $user_group->insert();

		$this->core->dbconfig('core', self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, array($user_group_id => $user_group_id), false, false, true);
		return true;
	}

	/**
	 * Get all right groups
	 *
	 * @param boolean $without_rights wether we want to include the rights which the group owns or not (optional, default = false)
	 * @return array
	 */
	private function get_right_groups($without_rights = false) {
		$groups = $this->db->query_slave_all("SELECT * FROM `" . UserRightGroupObj::TABLE . "`", array(), 0, 0, 'group_id');
		if ($without_rights == true) {
			return $groups;
		}
		foreach ($groups AS &$group) {
			$group['rights'] = $this->right_manager->get_group_rights($group['group_id']);
		}
		return $groups;
	}

}

?>