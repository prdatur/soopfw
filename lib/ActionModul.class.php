<?php

/**
 * Provides an action module<br />
 * This needed for index actions which will be called with the browser url
 *
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Page
 */
class ActionModul extends Object
{
	/**
	 * Define constances
	 */
	const LOGOUT_URL = "logout_url";
	const LOGIN_URL = "login_url";
	const PROFILE_URL = "profile_url";

	const NO_DEFAULT_METHOD = "|no_default|";

	/**
	 * This will be filled with the template file path which will be used as the template file to parse for this action
	 *
	 * @var string
	 */
	public $module_tpl = "";

	/**
	 * The static template path, must be a full path to an existing template<br />
	 * This will override the module / action template usage
	 *
	 * @var string
	 */
	public $static_tpl = "";

	/**
	 * Will be filled up with the modulename
	 *
	 * @var string
	 */
	public $modulname = "";

	/**
	 * Will be filled up with the action
	 *
	 * @var string
	 */
	public $action = "";

	/**
	 * Will be filled up with the module dir
	 *
	 * @var string
	 */
	public $module_dir = "";

	/**
	 * Will be filled up with the module configurations from database
	 *
	 * @var array
	 */
	public $config = array();

	/**
	 * This will be filled with the module template directory
	 *
	 * @var string
	 */
	public $module_tpl_dir = "";

	/**
	 * The default method if the called one does not exists
	 *
	 * @var string
	 */
	protected $default_methode = "";

	/**
	 * Init the module, we need this method because
	 * 1. If we want to call an action module class self, (not from index.php) we do not
	 * 		need the smarty assignations and template calculations
	 * 2. If we create the module we have not the action/module parameter so we need to
	 * 		initialize the class provide the module / action and then init it
	 */
	public function __init() {

		//If we have not provided an action or the action does not exist, setup the action to the default method
		if (empty($this->action) || !method_exists($this, $this->action)) {
			$this->action = $this->default_methode;
		}

		//Set up the module directory
		$this->module_dir = "modules/".$this->modulname;

		//Set the template file for this action
		$this->module_tpl = $this->action.".tpl";

		//Set the module template dir.
		$this->module_tpl_dir = SITEPATH."/modules/".$this->modulname."/templates/";

		//Set the current action as the meta title
		$this->core->meta->title = $this->action;

		//Assign smarty variables
		$this->smarty->assign("module_template_path", $this->module_tpl_dir);
		$this->smarty->assign("module_template_dir", "/modules/".$this->modulname."/templates/");
	}

	/**
	 * This method is called everytime we call an method which does not exists
	 * This will call our default method every time if it is not empty.
	 *
	 * @param string $name
	 *   the method name
	 * @param array $arguments
	 *   the method arguments (optional, default = array())
	 */
	function __call($name, array $arguments = array()) {
		$default_method = $this->default_methode;
		if (empty($default_method)) {
			trigger_error("NO DEFAULT METHOD DEFINED", E_USER_ERROR);
		}
		
		if ($default_method == self::NO_DEFAULT_METHOD) {
			$this->core->message(t('Page not found'), Core::MESSAGE_TYPE_ERROR);
			return $this->clear_output();
		}
		$this->$default_method();
	}

	/**
	 * Set the content title and description
	 *
	 * @param string $title
	 *   the title
	 * @param string $description
	 *   the description (optional, default = '')
	 */
	public function title($title, $description = "") {
		//Provide smarty the title and the description
		$this->smarty->assign("content_title", array(
			'title' => $title,
			'description' => $description
		));
		//Override the meta title with the provided
		$this->core->meta->title = $title;
		$this->core->meta->description = $description;
	}

	/**
	 * Set the content description
	 *
	 * @param string $description
	 *   the description
	 */
	public function description($description) {
		//Set just the description (We get the old title to not override previous one)
		$oldtitle = $this->core->meta->title;
		$this->smarty->assign("content_title", array(
			'title' => $oldtitle,
			'description' => $description
		));
		
		$this->core->meta->description = $description;
	}

	/**
	 * This function should be overriden if we want to do not just insert database tables and rights
	 * also we must set the static template dir couse this method will be called from system/install
	 *
	 * Within the overriden method we should then call this as the parent method like:
	 * if(!parent::install()) { return false; }
	 *
	 * Because this checks for us if the install was called in a secure way.
	 *
	 * @return boolean true on success, else false
	 */
	public function install() {
		$trace = debug_backtrace();
		array_shift($trace);
		$caller = array_shift($trace); //Get second stack trace

		//If system/install calls this method return that everything worked well
		if (!empty($caller['class']) && $caller['class'] == "System" && $caller['function'] == "install_module") {
			return true;
		}

		/*
		 * if file is empty it determines us that the current stacktrace is directly within install class of module
		 * This should not be empty if we call it from module system / install so return false to prevent direct access
		 */
		if (empty($caller['file'])) {
			return false;
		}

		//Get third stack trace (if we have a trace left the module implemented install so get the real trace.)
		if (count($trace) > 0) {
				$caller = array_shift($trace);
		}
		unset($trace); //free memory

		//If system/install calls this method return that everything worked well
		if (!empty($caller['class']) && $caller['class'] == "System" && $caller['function'] == "install_module") {
			return true;
		}

		//only if file is system.php or /install and caller method is install allow processing with install
		if ((SITEPATH . "/modules/system/system.php" == $caller['file'] || SITEPATH . "/install" == $caller['file']) && $caller['function'] == "install_module") {
			return true;
		}

		//all other files are not allowed to call install method
		return false;
	}

	/**
	 * This function should be overriden if we want to do not just insert database tables and rights
	 * also we must set the static template dir couse this method will be called from system/install
	 *
	 * Updates the modul for given version
	 *
	 * @param int $version
	 *   the version
	 */
	public function update($version) {
		return true;
	}

	/**
	 * Assign all default variables to smarty
	 * this includes adding default css/js files for the module/action
	 *
	 */
	public function assign_default() {

		//Assign our current action
		$this->smarty->assign_by_ref("action", $this->action);


		//Getting module css files which are within templates directory
		$dir = new Dir("modules/".$this->modulname."/templates");
		$dir->file_extension("css");
		$dirs = $dir->search();

		if (is_array($dirs)) {
			foreach ($dirs AS $v) {
				$this->core->add_css("/".$v->directory."/".$v->filename);
			}
		}
		//Getting module css files for the current action
		if (file_exists(SITEPATH."/modules/".$this->modulname."/css/".$this->action.".css")) {
			$this->core->add_css("/modules/".$this->modulname."/css/".$this->action.".css");
		}

		//Getting module js files for the current action
		if (file_exists(SITEPATH."/modules/".$this->modulname."/js/".$this->action.".js")) {
			$this->core->add_js("/modules/".$this->modulname."/js/".$this->action.".js");
		}

		//Getting module js files which are within templates directory
		$dir = new Dir("modules/".$this->modulname."/templates");
		$dir->file_extension("js");
		foreach ($dir AS $v) {
			$this->core->add_js("/".$v->directory."/".$v->filename);
		}
	}

	/**
	 * Load the configuration for this module and if $assign is set to true we also assign it to smarty
	 *
	 * @param boolean $assign
	 *   If we want to assign the config to smarty (optional, default = false)
	 */
	public function load_config($assign = false) {
		$sql = $this->db->query_slave_all("SELECT `key`, `value` FROM `".CoreModulConfigObj::TABLE."` WHERE `modul` = @modul", array(
			"@modul" => $this->modulname
		));
		foreach ($sql AS $data) {
			$this->config[$data['key']] = $data['value'];
		}
		if ($assign == true) {
			$this->core->smarty->assign_by_ref("modul_config", $this->config);
		}
	}

	/**
	 * Call this method if a user provide wrong params, it will print out the message
	 * and if needed clear the output of further template processing
	 *
	 * @param string $message
	 *   The message (optional, default = '')
	 * @param boolean $clear_output
	 *   if we want to clear the output (optional, default = true)
	 *
	 * @return boolean static false
	 */
	public function wrong_params($message = '', $clear_output = true) {
		if (empty($message) || $message === NS) {
			$message = t('Wrong params');
		}

		$this->core->message($message, Core::MESSAGE_TYPE_ERROR);
		if ($clear_output) {
			$this->clear_output();
		}
		return false;
	}

	/**
	 * Call this method if a permission denied.
	 * will print out a message
	 * "No Permission" and clear the output
	 *
	 * @return boolean static false
	 */
	public function no_permission() {
		$this->core->message(t("No permission"), Core::MESSAGE_TYPE_ERROR);
		$this->clear_output();
		return false;
	}

	/**
	 * Returns the translated link for the current url
	 * If you can be more preciser override it.
	 *
	 * @param string $language_key
	 *   the language key
	 *
	 * @return string the translated url
	 */
	public function get_translation_link($language_key) {
		list($url) = explode('?', $_SERVER['REQUEST_URI'],2);
		return preg_replace('/\/+/','/', '/'.strtolower($language_key).'/'.preg_replace('/^\/[a-z]{2}(\/|$)/is','', $url));
	}

	/**
	 * Clear output the display no inner content html
	 *
	 * @return boolean static false
	 */
	public function clear_output() {
		$this->static_tpl = NS;
		return false;
	}
	
	protected function required_admin_theme() {
		$this->smarty->set_tpl(SITEPATH . "/templates/" . $this->core->get_dbconfig("system", System::CONFIG_ADMIN_THEME, 'standard') . "/");
	}
}