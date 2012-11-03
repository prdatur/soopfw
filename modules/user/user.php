<?php

/**
 * User action module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class User extends ActionModul
{

	//Default method
	protected $default_methode = "overview";

	const CONFIG_DEFAULT_REGISTERED_USER_GROUPS = 'default_registered_user_groups';
	const CONFIG_ENABLE_REGISTRATION = 'enable_registration';
	const CONFIG_SIGNUP_ALIAS = 'signup_alias';
	const CONFIG_SIGNUP_NEED_CAPTCHA = 'signup_need_captcha';
	const CONFIG_SIGNUP_TYPE = 'signup_type';
	const CONFIG_SIGNUP_UNIQUE_EMAIL = 'signup_unique_email';
	const CONFIG_LOGIN_PING = 'login_ping';
	const CONFIG_INACTIVE_LOGOUT_TIME = 'login_default_logout_time';
	const CONFIG_LOGIN_ALLOW_EMAIL = 'login_allow_email';
	const CONFIG_LOST_PW_TYPE = 'lost_pw_type';
	const CONFIG_LOST_PW_ONE_TIME_EXPIRE = 'lost_pw_one_time_expire';
	const CONFIG_LOGIN_GLOBAL_FAIL_LOGINS = 'login_global_fail_logins';
	const CONFIG_LOGIN_GLOBAL_FAIL_LOGIN_LOCKTIME = 'login_global_fail_login_locktime';
	const CONFIG_LOGIN_USERNAME_FAIL_LOGINS = 'login_username_fail_logins';
	const CONFIG_LOGIN_USERNAME_FAIL_LOGIN_LOCKTIME = 'login_username_fail_login_locktime';

	const CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD = 'admin_change_customer_passsword';
	const CONFIG_MAIL_TEMPLATE_CONFIRM_SIGNUP = 'customer_confirm_signup';
	const CONFIG_MAIL_TEMPLATE_SIGNUP_SEND_PASSWORD = 'customer_signup_send_password';
	const CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_NEW = 'customer_lost_pw_new_pw';
	const CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_ONE = 'customer_lost_pw_one_time_access';


	const SIGNUP_TYPE_CONFIRM = 'confirm';
	const SIGNUP_TYPE_RANDOM = 'random';

	const LOST_PW_TYPE_NONE = 1;
	const LOST_PW_TYPE_RANDOM = 2;
	const LOST_PW_TYPE_ONE_TIME_ACCESS = 3;

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
	 * Implements menu().
	 * @return array The alias mappings
	 */
	public function menu() {
		if ($this->core->get_dbconfig("user", self::CONFIG_ENABLE_REGISTRATION, 'no') == 'yes') {
			return array(
				$this->core->get_dbconfig("user", self::CONFIG_SIGNUP_ALIAS, 'signup') => array(
					'menu' => 'signup'
				)
			);
		}
	}

	/**
	 * Implements hook_core_assign_default_vars().
	 */
	public function hook_core_assign_default_vars() {
		if ($this->session->is_logged_in()) {
			if ($this->core->get_dbconfig("user", self::CONFIG_LOGIN_PING, 'no') == 'yes') {

				// Load the ping javascript and setup ping timeout interval to prevent a logout while staying on the page inactive.
				$logout_time = (int)($this->core->get_dbconfig("user", self::CONFIG_INACTIVE_LOGOUT_TIME, 60) * 0.75);
				$this->core->js_config('user_ping_time', $logout_time);
				$this->core->add_js('/modules/user/js/ping.js');
			}
		}
	}

	/**
	 * Action: config
	 * Configurate the system main settings.
	 */
	public function config() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.user.config', true)) {
			throw new SoopfwNoPermissionException();
		}

		$this->static_tpl = "form.tpl";
		//Setting up title and description
		$this->title(t("User Config"), t("Here we can configure the main system settings"));

		//Configurate the settings form
		$form = new Form("system_config", t("Configuration"));

		$form->add(new Fieldset('main_settings', t('Main settings')));
		$values = array();
		foreach($this->db->query_slave_all('SELECT * FROM `'. UserRightGroupObj::TABLE .'`') AS $row) {
			$values[$row['group_id']] = $row['title'];
		}
		$form->add(new Checkboxes(self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, $values, $this->core->get_dbconfig("user", self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, array(), false, false, true), t('Default user groups'), t('The above selected groups will be automaticly assigned to newly created / registered users.')));

		$form->add(new YesNoSelectfield(
			self::CONFIG_LOGIN_PING,
			$this->core->get_dbconfig("user", self::CONFIG_LOGIN_PING, 'no'),
			t("Enable ping?"),
			t('If enabled an ajax request will be executed sequently to prevent a logout timeout, the time will be the 3/4 of the time which is configured by "@config"', array(
				'@config' => t('Default logout time while inactive'),
			))
		));
		$form->add(new Textfield(self::CONFIG_INACTIVE_LOGOUT_TIME, (int)$this->core->get_dbconfig("user", self::CONFIG_INACTIVE_LOGOUT_TIME, 60), t("Default logout time while inactive"), t('Value is in minutes')));
		$form->add(new YesNoSelectfield(self::CONFIG_ENABLE_REGISTRATION, $this->core->get_dbconfig("user", self::CONFIG_ENABLE_REGISTRATION, 'no'), t("Enable user signup?")));
		$form->add(new YesNoSelectfield(self::CONFIG_SIGNUP_NEED_CAPTCHA, $this->core->get_dbconfig("user", self::CONFIG_SIGNUP_NEED_CAPTCHA, 'yes'), t("user signups needs captcha?")));
		$form->add(new YesNoSelectfield(self::CONFIG_SIGNUP_UNIQUE_EMAIL, $this->core->get_dbconfig("user", self::CONFIG_SIGNUP_UNIQUE_EMAIL, 'no'), t("Are the emails unique?")));
		$form->add(new YesNoSelectfield(self::CONFIG_LOGIN_ALLOW_EMAIL, $this->core->get_dbconfig("user", self::CONFIG_LOGIN_ALLOW_EMAIL, 'no'), t("Allow login with email?"), t('In order to allow the email login, unique email must be set to yes')));

		$signup_types = array(
			User::SIGNUP_TYPE_CONFIRM => t('User needs to confirm his account'),
			User::SIGNUP_TYPE_RANDOM => t('User get a random password send to his email'),
		);
		$form->add(new Radiobuttons(self::CONFIG_SIGNUP_TYPE, $signup_types, $this->core->get_dbconfig("user", self::CONFIG_SIGNUP_TYPE, self::SIGNUP_TYPE_CONFIRM), t('Signup type'), t('How the user should be verified after signup.')));

		$form->add(new Radiobuttons(self::CONFIG_LOST_PW_TYPE, array(
			self::LOST_PW_TYPE_NONE => t('Disabled'),
			self::LOST_PW_TYPE_RANDOM => t('Send new random password'),
			self::LOST_PW_TYPE_ONE_TIME_ACCESS => t('One time direct access link'),
		), $this->core->get_dbconfig("user", self::CONFIG_LOST_PW_TYPE, self::LOST_PW_TYPE_ONE_TIME_ACCESS), t('Lost password action'), t('How the user can recovery his password.')));

		$form->add(new Textfield(self::CONFIG_LOST_PW_ONE_TIME_EXPIRE, $this->core->get_dbconfig("user", self::CONFIG_LOST_PW_ONE_TIME_EXPIRE, '24'), t('If "@action" is set to "@value" the link expires after this time', array(
			'@action' => t('Lost password action'),
			'@value' => t('One time direct access link'),
		)), t('Time is given in hours, only integers are valid')));

		$form->add(new Textfield(self::CONFIG_SIGNUP_ALIAS, $this->core->get_dbconfig("user", self::CONFIG_SIGNUP_ALIAS, 'signup'), t("Alias for signup")));

		$form->add(new Fieldset('security', t('Security settings')));
		$form->add(new Textfield(self::CONFIG_LOGIN_GLOBAL_FAIL_LOGINS, (int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_GLOBAL_FAIL_LOGINS, '20'), t('Max global login attempts'), t('This will block a user after the configurated failed login attempts, no matter which username he tries')));
		$form->add(new Textfield(self::CONFIG_LOGIN_GLOBAL_FAIL_LOGIN_LOCKTIME, (int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_GLOBAL_FAIL_LOGIN_LOCKTIME, DateTools::TIME_MINUTE_45), t('Global login block time'), t('How long the user will get blocked. (in seconds)')));

		$form->add(new Textfield(self::CONFIG_LOGIN_USERNAME_FAIL_LOGINS, (int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_USERNAME_FAIL_LOGINS, '5'), t('Max same username login attempts'), t('This will block a user after the configurated failed login attempts for the same username')));
		$form->add(new Textfield(self::CONFIG_LOGIN_USERNAME_FAIL_LOGIN_LOCKTIME, (int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_GLOBAL_FAIL_LOGIN_LOCKTIME, DateTools::TIME_MINUTE_15), t('Same username login block time'), t('How long the user will get blocked. (in seconds)')));


		$description = "";
		if ($this->right_manager->has_perm('admin.system.config')) {
			$description = '<a href="/admin/system/email_templates">' . t('Manage email templates') . '</a>';
		}
		$form->add(new Fieldset('mail_templates', t('email templates'), $description));

		$form->add(new EmailTemplateSelectField(self::CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD, array('username', 'password'), $this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD, ''),
				t('Administrator changes password'),
				t('This email will be send if an administrator changes a customer password')));

		$form->add(new EmailTemplateSelectField(self::CONFIG_MAIL_TEMPLATE_CONFIRM_SIGNUP, array('username', 'link'), $this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_CONFIRM_SIGNUP, ''),
				t('Customer confirm signup'),
				t('This email will be send if a customer signup an account and he needs to confirm his account')));

		$form->add(new EmailTemplateSelectField(self::CONFIG_MAIL_TEMPLATE_SIGNUP_SEND_PASSWORD, array('username', 'password'), $this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_SIGNUP_SEND_PASSWORD, ''),
				t('Customer signup send password'),
				t('This email will be send if a customer signup an account and instead of a confirmation link the user get a welcome mail including a random password.')));

		$form->add(new EmailTemplateSelectField(self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_NEW, array('username', 'password'), $this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_NEW, ''),
				t('Lost password mail for sending new password'),
				t('This email will be send if a customer wants to recover his password. It will only send if "@action" is set to "@value"', array(
					'@action' => t('Lost password action'),
					'@value' => t('One time direct access link'),
				))));

		$form->add(new EmailTemplateSelectField(self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_ONE, array('link', 'username', 'expires'), $this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_ONE, ''),
				t('Lost password mail for one time direct access'),
				t('This email will be send if a customer wants to recover his password. It will only send if "@action" is set to "@value"', array(
					'@action' => t('Send new random password'),
					'@value' => t('One time direct access link'),
				))));

		//Add a submit button
		$form->add(new Submitbutton("saveconfig", t("Save Config")));

		//Assign form to smarty
		$form->assign_smarty();

		//Check if form is valid (does not return anything but should always be called manually)
		$form->check_form();

		//Wether the form is submit and valid
		if ($form->is_submitted() && $form->is_valid()) {

			$values = $form->get_values();
			if ($values[self::CONFIG_LOGIN_ALLOW_EMAIL] == 'yes' && $values[self::CONFIG_SIGNUP_UNIQUE_EMAIL] != 'yes') {
				return $this->core->message(t('To allow email login the "unique email" option must be activated'), Core::MESSAGE_TYPE_ERROR);
			}

			foreach ($values AS $k => $v) {
				if ($k == self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS) {
					$this->core->dbconfig('user', $k, $v, false, false, true);
				}
				else {
					$this->core->dbconfig('user', $k, $v);
				}
			}
			$this->core->message(t("Configuration saved"), Core::MESSAGE_TYPE_SUCCESS);
		}
	}

	/**
	 * Action: list_password
	 *
	 * Provides the feature for a customer to recovery his password.
	 *
	 * @param string $id
	 *   the unique one time action key (optional, default = "")
	 */
	public function lost_password($id = "") {
		$this->core->need_ssl();
		if ($this->session->is_logged_in()) {
			throw new SoopfwWrongParameterException(t('You are logged in, you can not recovery your password'));
		}

		if (!empty($id)) {

			$err_msg = t('Invalid secret key or key is expired or one time access is disabled');
			$one_time_access = new UserOneTimeActionObj($id);
			if (!$one_time_access->load_success() || $this->core->get_dbconfig("user", self::CONFIG_LOST_PW_TYPE, self::LOST_PW_TYPE_ONE_TIME_ACCESS) != self::LOST_PW_TYPE_ONE_TIME_ACCESS) {
				throw new SoopfwWrongParameterException($err_msg);
			}

			$expire_hours = $this->core->get_dbconfig("user", self::CONFIG_LOST_PW_ONE_TIME_EXPIRE, '24');
			if (TIME_NOW >= strtotime($one_time_access->date) + ((int)$expire_hours * 60)) {
				throw new SoopfwWrongParameterException($err_msg);
			}

			$user = new UserObj($one_time_access->user_id);
			if (!$user->load_success()) {
				throw new SoopfwWrongParameterException($err_msg);
			}
			$one_time_access->delete();
			$this->session->validate_login($user);
			$this->core->location($this->session->get_login_handler()->get_profile_url($user));
		}
		else {
			$this->static_tpl = 'form.tpl';

			if ($this->core->get_dbconfig("user", self::CONFIG_LOST_PW_TYPE, self::LOST_PW_TYPE_ONE_TIME_ACCESS) == self::LOST_PW_TYPE_NONE) {
				$this->core->message(t('Password recovery currently disabled'), Core::MESSAGE_TYPE_NOTICE);
				return $this->clear_output();
			}
			$this->title(t("Recovery password"), t('We will send you an email with additional information'));

			$form = new Form('lost_password');
			if ($this->core->get_dbconfig("user", self::CONFIG_LOGIN_ALLOW_EMAIL, 'no') == 'yes') {
				$title = t('Please enter your username or email address');
			}
			else {
				$title = t('Please enter your username');
			}
			$form->add(new Textfield('account', '', $title), new RequiredValidator());

			$form->add(new Submitbutton('submit', t('Recovery password.')));

			if ($form->check_form()) {
				$account = $form->get_value('account');

				$acc = DatabaseFilter::create(UserObj::TABLE)
					->add_column('user_id')
					->add_where('username', $account)
					->select_first();

				if (empty($acc) && $this->core->get_dbconfig("user", self::CONFIG_LOGIN_ALLOW_EMAIL, 'no') == 'yes') {
					$acc = DatabaseFilter::create(UserAddressObj::TABLE)
						->add_column('user_id')
						->add_where('email', $account)
						->select_first();
				}


				if (empty($acc)) {
					throw new SoopfwWrongParameterExceptiont(t('No such account'));
				}

				$this->db->transaction_begin();

				$mail = new Email();

				$user_obj = new UserObj($acc['user_id']);

				if ($this->core->get_dbconfig("user", self::CONFIG_LOST_PW_TYPE, self::LOST_PW_TYPE_ONE_TIME_ACCESS) == self::LOST_PW_TYPE_RANDOM) {
					$new_pw = UserTools::generate_pw(12);

					$user_obj->password = $new_pw;

					if ($user_obj->save()) {
						if ($mail->send_tpl($this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_NEW, ''), $this->core->current_language, $user_obj, array(
							'password' => $new_pw,
							'username' => $user_obj->username,
						))) {
							$this->db->transaction_commit();
							$this->core->message(t('We have send you an email with further information, please check your inbox'), Core::MESSAGE_TYPE_SUCCESS);
							$this->clear_output();
						}
						else {
							$this->db->transaction_rollback();
							return $this->core->message(t('We could not send you the required email information, your account is left untouched.'), Core::MESSAGE_TYPE_ERROR);
						}
					}
					else {
						$this->db->transaction_rollback();
						return $this->core->message(t('Could not generate a random password for you, please contact an administrator'), Core::MESSAGE_TYPE_ERROR);
					}
				}
				else {
					$one_time = new UserOneTimeActionObj();
					$one_time->user_id = $acc['user_id'];

					if ($one_time->insert()) {

						if ($mail->send_tpl($this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_ONE, ''), $this->core->current_language, $user_obj, array(
							'link' => $this->core->get_secure_url().'/user/lost_password/' . $one_time->id,
							'username' => $user_obj->username,
						))) {
							$this->db->transaction_commit();
							$this->core->message(t('We have send you an email with further information, please check your inbox'), Core::MESSAGE_TYPE_SUCCESS);
							$this->clear_output();
						}
						else {
							$this->db->transaction_rollback();
							return $this->core->message(t('We could not send you the required email information, your account is left untouched.'), Core::MESSAGE_TYPE_ERROR);
						}
					}
				}

			}
		}


	}
	/**
	 * Action: signup
	 * Allow users to self signup
	 */
	public function signup() {
		if ($this->session->is_logged_in()) {
			$this->core->location($this->session->get_login_handler()->get_profile_url($this->session->current_user()));
		}

		if ($this->core->get_dbconfig("user", self::CONFIG_ENABLE_REGISTRATION, 'no') == 'no') {
			$this->core->message(t('User signup is disabled'), Core::MESSAGE_TYPE_NOTICE);
			return $this->clear_output();
		}

		$this->core->need_ssl();

		$this->title(t('signup'));
		$this->static_tpl = 'form.tpl';

		$signup_type = $this->core->get_dbconfig("user", self::CONFIG_SIGNUP_TYPE, User::SIGNUP_TYPE_CONFIRM);

		$form = new Form('user_signup');

		$form->add(new Textfield('username', '', t('username'), t('Please choose a username')), array(
			new RequiredValidator(),
			new NotExistValidator(t('This username is already taken, please choose a different'), array(UserObj::TABLE => 'username')),
		));

		if ($signup_type == User::SIGNUP_TYPE_CONFIRM) {
			$password_field = new Passwordfield('password', '', t('Password'), t('Please choose a good password'));
			$form->add($password_field, array(
				new RequiredValidator(),
				new LengthValidator("", array('min' => 6)),
			));
			$form->add(new Passwordfield('password2', '', t('Re-type password'), t('For security issues please retype the choosen password')), array(
				new EqualsValidator(t('Both passwords must match.'), $password_field->config('value')))
			);
		}

		$email_validators = array(
			new EmailValidator(),
			new RequiredValidator(),
		);
		if ($this->core->get_dbconfig("user", self::CONFIG_SIGNUP_UNIQUE_EMAIL, 'no') == 'yes') {
			$email_validators[] = new NotExistValidator(t('This email is already taken, please choose a different'), array(UserAddressObj::TABLE => 'email'));
		}
		$form->add(new Textfield('email', '', t('email'), t('Please provide your email address')), $email_validators);

		if ($this->core->get_dbconfig("user", self::CONFIG_SIGNUP_NEED_CAPTCHA, 'yes') == 'yes') {
			$form->add(new Captcha(t("I'm human"), t('Please verify that you are a human person.')));
		}

		if ($form->check_form()) {
			$password = "";
			if ($signup_type == User::SIGNUP_TYPE_RANDOM) {
				$password = UserTools::generate_pw(12);
			}

			$user_obj = $this->create_user($form, $password);
			if ($user_obj !== false) {

				$tpl_vals = array(
						'username' => $user_obj->username
				);

				switch($signup_type) {
					case User::SIGNUP_TYPE_CONFIRM:
						$user_obj->active = 'no';
						$user_obj->confirm_key = md5(uniqid());
						$user_obj->save();
						$mail_tpl_key = User::CONFIG_MAIL_TEMPLATE_CONFIRM_SIGNUP;

						$tpl_vals['link'] = 'http';
						if ($this->core->get_dbconfig("system", System::CONFIG_SSL_AVAILABLE, 'no') === 'yes') {
							$tpl_vals['link'] .= 's';
						}
						$tpl_vals['link'] .= '://' . $this->core->core_config('core', 'domain') . '/user/confirm/' . $user_obj->confirm_key;
						break;
					case User::SIGNUP_TYPE_RANDOM:
						$mail_tpl_key = User::CONFIG_MAIL_TEMPLATE_SIGNUP_SEND_PASSWORD;
						$tpl_vals['password'] = $password;
						break;
				}

				$template = $this->core->get_dbconfig("user", $mail_tpl_key);
				if (!empty($template)) {
					$email_template = new Email();

					if ($email_template->send_tpl($template, $this->core->current_language, $form->get_value('email'), $tpl_vals)) {
						//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
						$this->core->message(t("Your account was successfully created."), Core::MESSAGE_TYPE_SUCCESS);

						switch($signup_type) {
							case User::SIGNUP_TYPE_CONFIRM:
								$this->core->message(t("We have send you an email with a confirmation link included, please check your inbox."), Core::MESSAGE_TYPE_NOTICE);
								break;
							case User::SIGNUP_TYPE_RANDOM:
								$this->core->message(t("We have send you an email which includes your password, please check your inbox."), Core::MESSAGE_TYPE_NOTICE);
								break;
						}
						$user_obj->transaction_auto_commit();
					}
					else {
						$user_obj->transaction_auto_rollback();
						$this->core->message(t("Could not send the email to you, please contact the administrator.\n Errors: @errors", array('@errors' => implode('\n', $email_template->errors))), Core::MESSAGE_TYPE_ERROR);
					}
				}
				else {
					$user_obj->transaction_auto_rollback();
					$this->core->message(t("Could not find the email template which i should send you, please contact the administrator."), Core::MESSAGE_TYPE_ERROR);
				}
				$this->clear_output();
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not create your account, please try again"), Core::MESSAGE_TYPE_ERROR);
			}
		}
	}

	/**
	 * Action: confirm
	 *
	 * Confirms a registered user
	 *
	 * @param string $confirm_key
	 *   the confirmation key
	 */
	public function confirm($confirm_key) {

		if ($this->session->is_logged_in()) {
			$this->core->location($this->session->get_login_handler()->get_profile_url($this->session->current_user()));
		}

		$this->core->need_ssl();

		$this->clear_output();

		if (!preg_match("/^[a-z0-9]{32}$/", $confirm_key)) {
			return $this->core->message(t('Invalid confirmation key, account already confirmed or confirmation key expired.'), Core::MESSAGE_TYPE_ERROR);
		}

		$user_obj = new UserObj();
		$user_obj->db_filter->add_where('confirm_key', $confirm_key);
		$user_obj->load();

		if (!$user_obj->load_success()) {
			return $this->core->message(t('Invalid confirmation key, account already confirmed or confirmation key expired.'), Core::MESSAGE_TYPE_ERROR);
		}

		$user_obj->confirm_key = '';
		$user_obj->active = 'yes';

		if ($user_obj->save()) {
			$this->core->message(t('Confirmation succeed, you can now login with your username and password.'), Core::MESSAGE_TYPE_SUCCESS);
		}
		else {
			$this->core->message(t('Could not activate the user account, please contact an administrator'), Core::MESSAGE_TYPE_ERROR);
		}
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
			throw new SoopfwNoPermissionException();
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

		$filter = DatabaseFilter::create(UserObj::TABLE);

		//Build up where statement
		foreach ($this->session->get("search_user_overview", array()) AS $field => $val) {
			if (empty($val)) {
				continue;
			}
			$filter->add_where($field, $this->db->get_sql_string_search($val, ".*", false), 'LIKE');
		}

		//Init pager
		$pager = new Pager(50, $filter->select_count());
		$pager->assign_smarty("pager");

		//Search in DB
		$filter->limit($pager->max_entries_per_page());
		$filter->offset($pager->get_offset());
		$users = $filter->select_all();
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
			throw new SoopfwNoPermissionException();
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
			$user_obj = $this->create_user($form);
			if ($user_obj !== false) {
				$user_obj->transaction_auto_commit();
				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message(t("User added successfully"), Core::MESSAGE_TYPE_SUCCESS, $form->is_ajax(), $user_obj->user_id);
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not add user"), Core::MESSAGE_TYPE_ERROR, $form->is_ajax());
			}
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
			throw new SoopfwNoPermissionException();
		}

		//Check if a userid was provided
		if (empty($user_id)) {
			throw new SoopfwWrongParameterException();
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
			throw new SoopfwNoPermissionException();
		}

		//Check if a userid was provided
		if (empty($user_id)) {
			throw new SoopfwWrongParameterException();
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
				throw new SoopfwNoPermissionException();
			}
			//Add save button
			$submit_button = new Submitbutton("save", t("Save"));

			$message = t("address changed");

			$old_values = $address_obj->get_values();
		}
		else {
			//Check perms
			if ($this->session->current_user()->user_id != $user_id && !$this->right_manager->has_perm("admin.user.change")) {
				throw new SoopfwNoPermissionException();
			}
			$this->title(t("Add address"));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add"));

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
			throw new SoopfwNoPermissionException();
		}

		$user_id = (int)$user_id;
		if (empty($user_id)) {
			throw new SoopfwWrongParameterException();
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

		/**
		 * Provides hook: edit_user_tabs
		 *
		 * Allow other modules to add user data tabs
		 *
		 * @param int $user_id
		 *   The user id
		 * @param HtmlTabs $tabs
		 *   The tabs
		 */
	    $this->core->hook('edit_user_tabs', array($user_id, &$tabs));

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
			throw new SoopfwNoPermissionException();
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
			throw new SoopfwNoPermissionException();
		}
		$group_id = (int)$group_id;

		//Check if a group_id was provided
		if (empty($group_id)) {
			throw new SoopfwWrongParameterException();
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

		$this->core->need_ssl();

		if ($this->session->is_logged_in()) {
			$this->core->location($this->session->get_login_handler()->get_profile_url($this->session->current_user()));
		}

		if ($this->session->pre_validate_login() === true) {
			$this->core->message(t('Successfully logged in.'), Core::MESSAGE_TYPE_SUCCESS);
			$this->redirect_after_login();
		}
		$this->title(t('Login'), t('Please enter your username and password'));
		$login_form = new Form('login', '', 'soopfw_login');

		/**
		 * Provides hook: alter_user_login_form
		 *
		 * Please do only modifify $form if you really need it.
		 * Normally it is enough to return the new elements
		 * the "sections" are all optional so if you do not want
		 * to add elements before the buttons you do not need to provide the section "middle"
		 *
		 * valid sections are:
		 *   top = right after the form initializing
		 *   middle = between the last default input and the buttons
		 *   bottom = after the buttons
		 *
		 * @param Form &$form
		 *   The login form
		 *
		 * @return array the new input fields.
		 *   the array must have the following format:
		 *   array(
		 *     'top' => array(elements),
		 *     'middle' => array(elements),
		 *     'bottom' => array(elements),
		 *   )
		 */
		$hook_results = $this->core->hook('alter_user_login_form', array(&$login_form));
		$new_elements = array(
			'top' => array(),
			'middle' => array(),
			'bottom' => array(),
		);

		// Setup sections.
		foreach ($hook_results AS &$sections) {
			if (isset($sections['top'])) {
				// We can directly add the top elements here because we are already at the right position.
				foreach ($sections['top'] AS &$element) {
					$login_form->add($element);
				}
			}
			if (isset($sections['middle'])) {
				$new_elements['middle'] = array_merge($new_elements['middle'], $sections['middle']);
			}
			if (isset($sections['bottom'])) {
				$new_elements['bottom'] = array_merge($new_elements['bottom'], $sections['bottom']);
			}
		}

		// Setup security lock class.
		$security = new SecurityLock('user_login');

		// Get the user identification and provide it directly to the security check, this is needed because if we do not
		// Set the user identifier the system will determine it self and within the "check" the user is not logged in
		// but at the point we release the lock due to a successfull login the user is already logged in and this releases
		// the wrong user identifier.
		$user_identifier = NetTools::get_user_identification();

		$login_form->add(new Textfield("user", '', t("Username")), array(
			new FunctionValidator(t('This account has been locked due to too many login attempts, please try again later.'), function($value) use ($security) {
				$core = Core::get_instance();
				return $security->check_lock(
						(int) $core->get_dbconfig("user", User::CONFIG_LOGIN_USERNAME_FAIL_LOGINS, '5'),
						(int) $core->get_dbconfig("user", User::CONFIG_LOGIN_USERNAME_FAIL_LOGIN_LOCKTIME, DateTools::TIME_MINUTE_15),
						'user_login_' . $value,
						true,
						'usernamecheck'
				);
			})
		));
		$login_form->add(new Passwordfield("pass", '', t("Password")));

		// Add the middle section.
		foreach ($new_elements['middle'] AS &$element) {
			$login_form->add($element);
		}

		$login_form->add(new Submitbutton("soopfw_login", t("Login"), t("Login")));

		// Add the bottom section.
		foreach ($new_elements['bottom'] AS &$element) {
			$login_form->add($element);
		}

		$login_form->assign_smarty();
		$this->smarty->assign('lost_password_type', $this->core->get_dbconfig('user', self::CONFIG_LOST_PW_TYPE, self::CONFIG_LOST_PW_ONE_TIME_EXPIRE));
		if ($login_form->check_form()) {

			// Check lock status for global login.
			if (!$security->check_lock((int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_GLOBAL_FAIL_LOGINS, '20'), (int) $this->core->get_dbconfig("user", self::CONFIG_LOGIN_GLOBAL_FAIL_LOGIN_LOCKTIME, DateTools::TIME_MINUTE_45))) {
				throw new SoopfwSecurityException(t('Currently you are not allowed to login because you are blocked due to too many login attempts, try again later.'));
			}

			if ($this->session->validate_login($login_form->get_value("user"), $login_form->get_value("pass"))) {
				$security->unlock('user_login', $user_identifier);

				// We set a static user identifier so the lock is for all people to get more security.
				$security->unlock('user_login_' . $login_form->get_value("user"), 'usernamecheck');
				$this->core->message(t('Successfully logged in.'), Core::MESSAGE_TYPE_SUCCESS);
				$this->session->set('redirect_from_login', true);
				$this->redirect_after_login();
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

		/**
		 * Creating default user group.
		 */
		$user_group = new UserRightGroupObj();
		$user_group->title = 'Registered users';
		$user_group->permissions = 'admin.user.show_admin_menu';
		$user_group_id = $user_group->insert();

		$this->core->dbconfig('core', self::CONFIG_DEFAULT_REGISTERED_USER_GROUPS, array($user_group_id => $user_group_id), false, false, true);

		/**
		 * Creating default email templates
		 */
		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'admin_change_customer_password';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Password change';
		$mail_tpl->body = "Dear {username},

An administrator has changed your password.
Your new password is:
{password}";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD, 'admin_change_customer_password');
		}

		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'customer_confirm_signup';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Welcome, confirmation needed';
		$mail_tpl->body = "Dear {username},

Welcome to our page.
Your account was successfully created.
For security, you need to confirm your account before you can use it.

Click on the following link to confirm your account:
{link}";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_CONFIRM_SIGNUP, 'customer_confirm_signup');
		}

		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'customer_signup_send_password';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Welcome';
		$mail_tpl->body = "Dear {username},

You have successfull signup to our page.
You may now login to your account with the password below.
You can change your password after you have logged in.

Your password: {password}";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_SIGNUP_SEND_PASSWORD, 'customer_signup_send_password');
		}

		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'admin_change_customer_password';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Password change';
		$mail_tpl->body = "Dear {username},

An administrator has changed your password.
Your new password is:
{password}";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_NEW, 'admin_change_customer_password');
		}

		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'user_lost_password_random_pw';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Your new password.';
		$mail_tpl->body = "Dear {username},

You have requested to recovery your password.
We have setup a new password for your account.

Your new password: {password}";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_LOST_PW_TYPE_ONE, 'user_lost_password_random_pw');
		}

		$mail_tpl = new MailTemplateObj();
		$mail_tpl->id = 'user_lost_password_one_time';
		$mail_tpl->language = 'en';
		$mail_tpl->subject = 'Recovery your password';
		$mail_tpl->body = "Dear {username},

You have requested to recovery your password.
In order to complete this step we give you a one time login with your user account.
Click on the link below and you will be logged in within your account where you can change your password.

{link}

Please aware, this link expires right after you used it. It can not be used twice.
Also it expires after {expires} hours";
		if ($mail_tpl->insert()) {
			$this->core->get_dbconfig("user", self::CONFIG_MAIL_TEMPLATE_CHANGE_PASSWORD, 'admin_change_customer_password');
		}

		return true;
	}

	/**
	 * Redirects the user to the last browsed page.
	 */
	private function redirect_after_login() {
		//If a location is found, redirect the user
		if (!empty($_SESSION['redir_after_login'])) {
			$redir = $_SESSION['redir_after_login'];
			unset($_SESSION['redir_after_login']);
			$this->core->location($redir);
		}
		//No location was found to redirt to /
		else {
			#$this->core->location($this->session->get_login_handler()->get_profile_url($this->session->current_user()));
			$this->core->location('/');
		}
	}

	/**
	 * Creates a new user from the form
	 *
	 * @param Form $form
	 *   the Form
	 * @param string $password
	 *   the password for the user, if provided this password will be used
	 *   for the user (optional, default = "")
	 * @return UserObj the user object if the user was created, else false
	 */
	private function create_user($form, $password = "") {
		$user_obj = new UserObj();
		$user_obj->set_fields($form->get_values());

		if (!empty($password)) {
			$user_obj->password = $password;
		}
		$user_obj->transaction_auto_begin();
		//Check if the insert command returned true
		if ($user_obj->insert()) {

			$user_address_obj = new UserAddressObj();
			$user_address_obj->user_id = $user_obj->user_id;
			$user_address_obj->set_fields($form->get_values());
			if ($user_address_obj->insert()) {
				return $user_obj;
			}
		}
		$user_obj->transaction_auto_rollback();
		return false;
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

