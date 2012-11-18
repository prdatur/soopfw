<?php

/**
 * Provide a session class which holds all necessary information about the current user
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Security
 */
class Session extends Object
{
	/**
	 * Define constances
	 */
	const SESSION_KEY_USER_ID = 'user_id';
	const SESSION_KEY_USER_USERNAME = 'username';
	const SESSION_KEY_USER_PASSWORD = 'password';

	/**
	 * Determines if the user is loggedin or not
	 * @var boolean
	 */
	private $logged_in = false;

	/**
	 * The current user object
	 * @var UserObj
	 */
	private $current_user = null;

	/**
	 * The php session id
	 *
	 * @var string
	 */
	private $session_id = null;

	/**
	 * Holds all available login handlers.
	 *
	 * @var array
	 */
	private $login_handlers = array();

	/**
	 * Provides the current login handler which is active.
	 *
	 * @var LoginHandler
	 */
	private $login_handler = null;

	/**
	 * hold the session keys for an user login
	 * @var array
	 */
	private $session_keys = array(
		"id" => "user_id",
		"user" => "username",
		"pass" => "password",
	);

	/**
	 * Construct
	 *
	 * Creating a session object will always creates a session if not already exist
	 *
	 * @param Core &$core
	 *  the core object to setup (This is needed because globals core maybe does not exists (optional, default = null)
	 */
 	public function __construct(&$core = null) {
		parent::__construct($core);
		$this->current_user = new UserObj();
		if (!defined('is_shell')) {
			$this->start_session();

			// Get all wanted login handlers.
			$login_handlers = $this->core->get_dbconfig("system", System::CONFIG_LOGIN_HANDLER, array("DefaultLoginHandler"));

			// Setup all login handlers.
			foreach ($login_handlers AS $login_handler) {
				if(empty($login_handler) || !class_exists($login_handler)) {
					continue;
				}
				$this->login_handlers[$login_handler] = new $login_handler($core);
			}

			// If no login handler is checked we auto activate the default login handler.
			if (empty($this->login_handlers)) {
				$this->login_handlers['DefaultLoginHandler'] = new DefaultLoginHandler($core);
			}
		}
	}

	/**
	 * Returns all session keys for the login
	 *
	 * @return array the session keys
	 */
	public function get_login_session_keys() {
		return $this->session_keys;
	}

	/**
	 * Log the current user out, if $time is higher than 0 than the request_redirection will be used,
	 * else it will a direct header location event
	 *
	 * @param int $time
	 *   set the redirection timeout after logout (optional, default = 0)
	 * @param boolean $justreturn
	 *   if we just want to logout the current user with no redirect (optional, default = false)
	 */
	public function logout($time = 0, $justreturn = false) {

		SystemHelper::audit(t('User "@username" logged out.', array('@username' => $this->session->current_user()->username)), 'session', SystemLogObj::LEVEL_NOTICE);

		$this->set('user_login_handler', null);
		//Loop through all user session keys and unset it
		foreach ($this->session_keys AS $i) {
			$this->session->delete($i);
		}

		//Create or Load the session object
		$user_session = new UserSessionObj($this->get_session_id());
		if($user_session->load_success()) {
			$user_session->delete();
		}
		//Unset the redirection after login session key couse we do not want to redirect to an secured url
		$this->session->delete('redir_after_login');

		if ($this->login_handler !== null) {
			$this->login_handler->logout($time, $justreturn);
		}
	}

	/**
	 * Returns the login url for the current active login handler.
	 * If the user is not logged in (which he always shoud be when calling this)
	 * it will return the login url with the highest priority.
	 *
	 * @return string the login url.
	 */
	public function get_login_url() {
		return $this->get_best_login_handler()->get_login_url();
	}

	/**
	 * Returns the logout url for the current active login handler.
	 * If the user is not logged in (which he always shoud NOT be when calling this)
	 * it will return the logout url with the highest priority.
	 *
	 * @return string the logout url.
	 */
	public function get_logout_url() {
		return $this->get_best_login_handler()->get_logout_url();
	}

	/**
	 * Returns the profile url for the current active login handler.
	 * If the user is not logged in (which he always shoud NOT be when calling this)
	 * it will return the profile url with the highest priority.
	 *
	 * @param UserObj $user_obj
	 *   the user object, if provided it will get the profile url for this account (optional, default = null)
	 *
	 * @return string the profile url
	 */
	public function get_profile_url($user_obj = null) {
		return $this->get_best_login_handler()->get_profile_url($user_obj);
	}

	/**
	 * Returns the best matching return handler.
	 *
	 * If user is logged in we already have the login handler.
	 * if not we get the first in the array back.
	 *
	 * @return LoginHandler
	 */
	public function &get_best_login_handler() {
		static $cache = null;

		if ($cache === null) {
			if (!$this->logged_in || empty($this->login_handler)) {
				reset($this->login_handlers);
				$cache = &$this->login_handlers[key($this->login_handlers)];
			}
			else {
				$cache = &$this->login_handler;
			}
		}

		return $cache;
	}

	/**
	 * Returns whether the $login_handler is enabled or not.
	 *
	 * @param string $login_handler.
	 *   the classname of the login handler.
	 *
	 * @return boolean true if enabled, else false
	 */
	public function is_login_handler_enabled($login_handler) {
		return isset($this->login_handlers[$login_handler]);
	}

	/**
	 * Checks if the user is logged in, if not we will redirect him to the login page
	 *
	 * @param boolean $force_not_loggedin
	 *   force not logged in that the user will be redirected to the user login page (optional, default = false)
	 * @param boolean $need_direct_handler
	 *   the can be set to true to tell the handler that we NEED to be logged in through the third-party login handler,
	 *   normal login is wrong (optional, default = false)
	 */
	public function require_login($force_not_loggedin = false, $need_direct_handler = false) {

		if(empty($this->login_handler) || $this->login_handler->require_login($force_not_loggedin, $need_direct_handler)) {
			//If we force to be not logged in or the current_user object is empty redirect the user to the login page
			if ($force_not_loggedin == true || !$this->is_logged_in()) {
				$login_url = $this->get_login_url();
				if($this->core->init_type == Core::INIT_TYPE_AJAXHTML) {
					$this->smarty->assign("logout_url", $login_url);
					$this->smarty->display("js_logout.tpl");
					die();
				}
				$this->core->location($login_url);
			}
		}
	}

	/**
	 * Checks all available login handler for pre validated login.
	 *
	 * @return boolean returns true on success, else false
	 */
	public function pre_validate_login() {
		foreach ($this->login_handlers AS &$handler) {
			// We direct set the current login handler to the active one.
			// If the login fails it will be overwritten by the next try, the active one will be the one where the login succeeds.
			$this->login_handler = $handler;
			if($handler->pre_validate_login() === true) {
				//Set the loggedin fast check variable to true
				$this->logged_in = true;
				return true;
			}
		}
		return false;
	}
	/**
	 * Check if the user is logged in and log the user in if a post was provided.
	 *
	 * @return boolean returns true on successfully login else false
	 */
	public function check_login() {
		//Pre assign the loggedin to 0 (not logged in)
		$this->smarty->assign("loggedin", "0");

		$timeout_value = $this->core->get_dbconfig("user", User::CONFIG_INACTIVE_LOGOUT_TIME, 60);
		//Remove all old session entries which are not available anymore
		$this->db->query_master("DELETE FROM `".UserSessionObj::TABLE."` WHERE DATE_ADD(`date`, INTERVAL iminutes MINUTE) <= @data", array(
			"@data" => date(DB_DATETIME, TIME_NOW),
			'iminutes' => $timeout_value,
		));

		$unallowed_urls_for_redirect = array(
			"/" => true,
		);
		foreach ($this->login_handlers AS &$handler) {
			$unallowed_urls_for_redirect[$handler->get_login_url()] = true;
			$unallowed_urls_for_redirect[$handler->get_logout_url()] = true;
			$unallowed_urls_for_redirect = array_merge($unallowed_urls_for_redirect, $handler->get_handler_urls());

		}
		//If the request_uri is not /, /user/login.html and the uri has a .html or no ending, we will setup the current request uri to the reditAfterLogin session variable
		//This variable will be used to redirect the user to this page after successfully login
		if (!isset($unallowed_urls_for_redirect[$_SERVER['REQUEST_URI']]) && preg_match("/(\.html|.*\/[^\/]*)$/is", $_SERVER['REQUEST_URI'])) {
			$this->set("redir_after_login", $_SERVER['REQUEST_URI']);
		}
		$this->logged_in = false;

		$login_handler = $this->get('user_login_handler');
		if (!empty($login_handler) && isset($this->login_handlers[$login_handler]) && $this->login_handlers[$login_handler] instanceof LoginHandler) {
			if ($this->login_handlers[$login_handler]->check_login()) {
				$this->login_handler = $this->login_handlers[$login_handler];
				//Set the loggedin fast check variable to true
				$this->logged_in = true;
			}
		}

		if ($this->logged_in !== true) {
			foreach ($this->login_handlers AS &$handler) {
				// We direct set the current login handler to the active one.
				// If the login fails it will be overwritten by the next try, the active one will be the one where the login succeeds.
				$this->login_handler = $handler;
				if($handler->check_login() === true) {
					//Set the loggedin fast check variable to true
					$this->logged_in = true;
					break;
				}
			}
		}

		if($this->logged_in === true) {
			$this->smarty->assign("loggedin", "1");
			$this->current_user()->grant_static_permission('user.loggedin');
		}
		else {
			$this->login_handler = null;
		}
		return $this->logged_in;
	}

	/**
	 * Returns the current login handler
	 *
	 * @return LoginHandler the current login handler
	 */
	public function get_login_handler() {
		return $this->login_handler;
	}

	/**
	 * Check if the given credentials are valid and if so setup a new session object
	 * or update the old one, also update the last login time
	 * if check_login is called the Login handler MUST call this method with the user object as $username and only if login succed, if login fails return false
	 *
	 * @param string $username
	 *   the username
	 * @param string $password
	 *	 the plain password to check
	 *   optional only if a valid $username is provided as a UserObj, this will determine
	 *   that the user is logged in (optional, default = null)
	 *
	 * @return boolean return true if provided credentials are valid, else false
	 */
	public function validate_login($username, $password = null) {

		$this->smarty->assign("loggedin", "0");

		if($username instanceof UserObj) {
			$return = $username;
		}
		else {
			//We have a direct username password call, check the login handler and return result.
			$this->logged_in = false;
			foreach ($this->login_handlers AS $handler) {
				// We direct set the current login handler to the active one.
				// If the login fails it will be overwritten by the next try, the active one will be the one where the login succeeds.
				$this->login_handler = $handler;
				if($handler->validate_login($username, $password) === true) {
					//Set the loggedin fast check variable to true
					$this->logged_in = true;
					break;
				}
			}
			if ($this->logged_in) {
				SystemHelper::audit(t('User "@username" logged in.', array('@username' => $this->session->current_user()->username)), 'session', SystemLogObj::LEVEL_NOTICE);
			}
			else {
				$this->login_handler = null;
			}
			return $this->logged_in;
		}

		$this->current_user = $return;
		if (!empty($this->login_handler) && $this->login_handler instanceof LoginHandler) {
			$this->set('user_login_handler', get_class($this->login_handler));
		}
		//Assign the loggedin variable for smarty to logged in
		$this->smarty->assign("loggedin", "1");

		//Create or Load the session object
		$user_session = new UserSessionObj($this->get_session_id());

		//Set required values
		$user_session->username = $this->current_user->username;
		$user_session->user_id = $this->current_user->user_id;
		$user_session->date = date(DB_DATETIME, TIME_NOW);
		$user_session->session_id = $this->get_session_id();

		//Save or insert the new session
		$user_session->save_or_insert();

		//Store the current login time to the last login time
		$this->current_user->last_login = date(DB_DATETIME, TIME_NOW);
		$this->current_user->logincode = "";

		//Save the new user but do not change the password
		$this->current_user->save(false, false);

		//Set the loggedin fast check variable to true
		$this->logged_in = true;

		return true;
	}

	/**
	 * Returns whether the user is logged in or not
	 *
	 * @return boolean true if the user is logged in, else false
	 */
	public function is_logged_in() {
		return $this->logged_in;
	}

	/**
	 * Return or set the current user object
	 * If $user_obj is provided set mode is active, else current user will be returned

	 * @param UserObj $user_obj
	 *   The user object (optional, default = null)
	 *
	 * @return UserObj return the user object on get mode, else nothing will be returned
	 */
	public function current_user(UserObj $user_obj = null) {
		if (!empty($user_obj)) {
			$this->current_user = $user_obj;
			return;
		}

		return $this->current_user;
	}

	/**
	 * Set session variable keys
	 *
	 * @param string $key
	 *   the array key
	 * @param string $value
	 *   the value to be set
	 *
	 * @return boolean static true
	 */
	public function set($key, $value) {
		$_SESSION[$key] = $value;
		return true;
	}

	/**
	 * Get session variable keys
	 *
	 * @param string $key
	 *   the array key
	 * @param string $default_value
	 *   return this value if key not found (optional, default = null)
	 *
	 * @return mixed return the value from given key, if key not exists return $default_value
	 */
	public function get($key, $default_value = null) {
		return (isset($_SESSION[$key])) ? $_SESSION[$key] : $default_value;
	}

	/**
	 * Deletes the given key from session
	 *
	 * @param string $key
	 *   The session key
	 */
	public function delete($key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	/**
	 * Start a php session, it will start it only if no session_id exists
	 */
	public function start_session() {
		//Get current session id
		$tmp_sessid = session_id();

		//If we have no session id we start the session
		if (empty($tmp_sessid)) {
			session_start();
			$this->session_id = session_id();
		}
		else if (empty($this->session_id)) {
			$this->session_id = $tmp_sessid;
		}
	}

	/**
	 * Returns the current session id
	 *
	 * @return string the current session id
	 */
	public function get_session_id() {
		return $this->session_id;
	}

}

