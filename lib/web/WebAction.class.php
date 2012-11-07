<?php

/**
 * Provides a class to handle web actions like ajax, ajax html or normal page calls
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Web
 */
class WebAction extends Object
{

	/**
	 * Define constances.
	 */
	const ABORT_NO_PERMISSION = 0;
	const ABORT_WRONG_PARAMS = 1;
	const ABORT_CLEAR_OUTPUT = 2;
	const ABORT_MODULE_NOT_FOUND = 3;

	/**
	 * The current action module.
	 *
	 * @var ActionModul
	 */
	protected $current_action_module = null;

	/**
	 * The parameters to determine which action we should call.
	 *
	 * @var array
	 */
	protected $action_params = array();

	public function process_action() {
		$this->init();
		$this->setup_params();
		$this->check_login();
		$this->switch_action_type();
	}

	/**
	 * Main initializing of an action.
	 */
	private function init() {
		ob_implicit_flush();
		header('Content-Type: text/html; charset=utf-8');
	}

	/**
	 * Setup call params.
	 */
	private function setup_params() {
		// Parse our request uri
		$override_params = UrlAliasObj::parse_url_string();
		$language = '';
		if (!empty($override_params['language'])) {
			$language = $override_params['language'];
			unset($override_params['language']);
		}

		// Now we have the required language which is needed to boot up the core.
		// So we do it.
		$this->core->boot($language);

		// Check if we are on an administration link, if so ssl is required (if available).
		$this->core->cache('core', 'admin_theme', $override_params['admin_link']);
		if ($override_params['admin_link'] === true) {
			$this->core->need_ssl();
		}

		if ($this->core->cache('core', 'admin_theme')) {
			$this->smarty->set_tpl(SITEPATH . "/templates/" . $this->core->get_dbconfig("system", System::CONFIG_ADMIN_THEME, 'standard') . "/");
		}
		else {
			$this->smarty->set_tpl(SITEPATH . "/templates/" . $this->core->get_dbconfig("system", System::CONFIG_DEFAULT_THEME, 'standard') . "/");
		}

		// Get params from a maybe existing alias for current uri.
		$alias_params = UrlAliasObj::get_alias_from_uri($override_params);
		if ($alias_params !== false) {
			$override_params = array_merge($override_params, $alias_params);
		}


		$additional_function_params = $override_params['additional_function_params'];
		unset($override_params['additional_function_params']);

		// Prevent predefining perms within _GET request.
		if (isset($_GET['perm'])) {
			unset($_GET['perm']);
		}

		// Override the $_GET params with our override params.
		$param_array = array_merge($_GET, $override_params);

		// If we requested a logout, log the user out.
		if (!empty($_REQUEST['logout'])) {
			$this->session->logout();
		}

		#####################################################
		//Build the param array for the param struct
		$params = new ParamStruct();
		$params->add_param("module", PDT_STRING, "");
		$params->add_param("action", PDT_STRING, "");
		$params->add_param("popup", PDT_INT, 0);
		$params->add_param("dialog", PDT_INT, 0);
		$params->add_param("type", PDT_STRING, "");
		$params->add_param("file", PDT_STRING, "");
		$params->add_param("ajax_module", PDT_STRING, "");

		//This is only a filter, if the perm is not provided it will not allow security things, also it will always be overriden by alias perm definitions
		$params->add_param("perm", PDT_STRING, "");

		$params->fill($param_array);

		/**
		 * if we have no module provided we must check if we have setup a start page within system configuration
		 * if so we need to override the module, action and additional params, first we check if we have an alias
		 * if not we just parse the url string
		 */
		if (empty($params->module)) {
			$start_page = $this->core->dbconfig("system", System::CONFIG_DEFAULT_PAGE);
			if (!empty($start_page)) {
				$start_page_params = UrlAliasObj::get_params_from_alias($start_page);
				if ($start_page_params === false) {
					$start_page_params = UrlAliasObj::parse_url_string($start_page);
				}
				else {
					$start_page_params['type'] = "";
				}
				$params->type = $start_page_params['type'];
				$params->module = $start_page_params['module'];
				$params->action = $start_page_params['action'];
				$additional_function_params = $start_page_params['additional_function_params'];
			}
		}

		// Parse additional function params.
		if (!empty($additional_function_params)) {

			$lastindex = count($additional_function_params) - 1;
			// Check if the request uri ends with a slash, if so we should remove the last array element if it is empty.
			if (preg_match("/\/$/", NetTools::get_request_uri())) {
				if (empty($additional_function_params[$lastindex])) {
					unset($additional_function_params[$lastindex]);
					$lastindex--;
				}
			}

			// Next we have a none empty last element, so check for common file endings and remove it.
			// Last parameter will be without the extension.
			if (!empty($additional_function_params)) {
				if (preg_match("/(.*)\.(ajax|ajax_html|html)?$/is", $additional_function_params[$lastindex], $matches)) {
					$additional_function_params[$lastindex] = $matches[1];
				}
			}
		}

		$this->action_params = $params->get_values();
		$this->action_params['action_params'] = $additional_function_params;
	}

	/**
	 * Check for login.
	 */
	private function check_login() {
		if (!empty($this->core->db)) {
			$this->core->get_session()->check_login();
		}
	}

	/**
	 * Switch on action type.
	 */
	private function switch_action_type() {
		switch ($this->action_params['type']) {
			case 'ajax_request':
				$this->request_ajax();
				break;
			case 'ajax_html':
				$this->core->init_type = Core::INIT_TYPE_AJAXHTML;
			default:
				$this->request_normal();
				break;
		}
	}

	/**
	 * Request an ajax call.
	 */
	private function request_ajax() {

		try {

			$module = $this->action_params['module'];
			$mod = "modules/" . $this->action_params['module'];
			if (!empty($this->action_params['ajax_module'])) {
				$module = $this->action_params['ajax_module'];
				$mod = "modules/" . $this->action_params['ajax_module'];
			}
			if (!empty($this->db)) {
				$module_conf_obj = new ModulConfigObj($module);
				if ((!$module_conf_obj->load_success() || $module_conf_obj->enabled != 1) && $module != "system") {
					throw new SoopfwModuleNotFoundException();
				}

				if (!empty($this->action_params['perm']) && !$this->core->get_right_manager()->has_perm($this->action_params['perm'])) {
					throw new SoopfwNoPermissionException();
				}
			}
			$ajax_file = SITEPATH . "/" . $mod . "/ajax/" . $this->action_params['action'] . ".php";
			if (file_exists($ajax_file)) {
				$class = $this->generate_classname('ajax_' . $this->action_params['module'] . '_' . $this->action_params['action']);

				if (!class_exists($class)) {
					include($ajax_file);
				}

				$ajax_run = new $class();
				$ajax_run->run();
			}

		}
		catch (SoopfwWrongParameterException $e) {
			AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true, $e->getMessage());
		}
		catch (SoopfwNoPermissionException $e) {
			AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true, $e->getMessage());
		}
		catch (SoopfwModuleNotFoundException $e) {
			AjaxModul::return_code(AjaxModul::ERROR_MODULE_NOT_FOUND, null, true, $e->getMessage());
		}
		catch (Exception $e) {
			AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true, $e->getMessage());
		}
	}

	/**
	 * Request a normal page.
	 */
	private function request_normal() {

		try {

			$used_default_module = false;
			$original_module = $this->action_params['module'];
			if (!file_exists(SITEPATH . "/modules/" . $this->action_params['module'] . "/" . $this->action_params['module'] . ".php")) {
				$this->action_params['module'] = 'DefaultWebAction';
				$used_default_module = true;
			}

			if ($this->core->lng) {
				$this->core->lng->load("intl");
				$this->core->lng->load("menu");
				$this->core->lng->load($this->action_params['module']);
				$this->core->lng->load_javascript($this->action_params['module']);
			}

			$modulname = $this->action_params['module'];
			$module_conf_obj = new ModulConfigObj($modulname);
			if ($used_default_module === false && (!$module_conf_obj->load_success() || $module_conf_obj->enabled != 1) && $modulname != "system") {
				throw new SoopfwModuleNotFoundException(t("Module not found or disabled"));
			}

			$actions_path = SITEPATH . '/modules/' . $modulname . '/actions';
			if ($used_default_module && (file_exists($actions_path . '/' . $original_module . '.php') || method_exists($modulname, $original_module))) {

				if (!empty($this->action_params['action'])) {
					array_unshift($this->action_params['action_params'], $this->action_params['action']);
				}
				$this->action_params['action'] = $original_module;
			}
			$action = $this->action_params['action'];

			if ($used_default_module === false) {
				if (file_exists($actions_path . '/' . $action . '.php')) {
					$load_class = $modulname . "_" . $action;
				}
				else {
					$load_class = $modulname;
				}
			}
			else {
				$load_class = $this->action_params['module'];
			}

			// Prevent class format and get the camel case classname.
			$load_class = $this->generate_classname($load_class);

			/* @var $module ActionModul */
			$module = new $load_class();
			$this->current_action_module = &$module;
			$this->current_action_module->modulname = $this->action_params['module'];
			$this->current_action_module->action = $action;
			$this->current_action_module->additional_params = $this->action_params['action_params'];

			$this->current_action_module->__init();


			if (!empty($this->action_params['perm']) && !$this->core->get_right_manager()->has_perm($this->action_params['perm'])) {
				throw new SoopfwNoPermissionException();
			}

			$this->core->cache("core", "current_module", $this->current_action_module->modulname);

			$parent = get_parent_class($load_class);
			if ($parent != "ActionModul" && get_parent_class($parent) != "ActionModul") {
				throw new SoopfwWrongParameterException();
			}

			if ($this->current_action_module->action == ActionModul::NO_DEFAULT_METHOD || !method_exists($this->current_action_module, $this->current_action_module->action)) {
				$this->aborting_loading(self::ABORT_CLEAR_OUTPUT);
			}

			// Prevent direct calling a hook method.
			if (preg_match("/^hook_/", $this->current_action_module->action)) {
				throw new SoopfwWrongParameterException();
			}

			//Get the calling class method
			$method = new ReflectionMethod($this->current_action_module, $this->current_action_module->action);

			//Check if we provided all required parameters, if not abort loading and display error message
			if ($method->getNumberOfRequiredParameters() > count($this->action_params['action_params'])) {
				throw new SoopfwWrongParameterException();
			}

			//Call the wanted module action
			call_user_func_array(array($this->current_action_module, $action), $this->action_params['action_params']);

			$module_template = "";

			if (!empty($this->current_action_module->static_tpl)) {
				if ($this->current_action_module->static_tpl === NS) {
					$this->current_action_module->static_tpl = "";
				}
				$module_template = $this->current_action_module->static_tpl;
			}
			else {
				$module_template = SITEPATH . "/modules/" . $this->current_action_module->modulname . "/templates/" . $this->current_action_module->module_tpl;
			}

			if (!empty($module_template) && $this->smarty->templateExists($module_template)) {
				$this->smarty->assign("module_tpl", $module_template);
			}
			else if (!empty($module_template)) {
				$this->smarty->assign("module_tpl_old", $module_template);
				$this->smarty->assign("module_tpl", "template_not_exists.tpl");
			}

			if (!empty($this->action_params['popup'])) {
				$this->smarty->assign("popup", "1");
			}

			if (!empty($this->action_params['dialog'])) {
				$this->smarty->assign("dialog", "1");
			}

			switch ($this->action_params['type']) {
				case 'ajax_html':
					$this->core->js_config("is_ajax_html", true);
					$this->core->template = "index_ajax_html.tpl";
					break;
				default:
					$this->assign_default_js_css();
					$this->core->assign_menus();
					break;
			}
			$this->current_action_module->assign_default();
			$this->core->smarty_assign_default_vars();
			$this->smarty->display($this->core->template);

		}
		catch (SoopfwWrongParameterException $e) {
			$this->aborting_loading(self::ABORT_WRONG_PARAMS, $e->getMessage());
		}
		catch (SoopfwNoPermissionException $e) {
			if ($this->session->get('redirect_from_login') === true) {
				$this->core->location($this->session->get_login_url());
			}
			$this->aborting_loading(self::ABORT_NO_PERMISSION, $e->getMessage());
		}
		catch (SoopfwModuleNotFoundException $e) {
			$this->aborting_loading(self::ABORT_MODULE_NOT_FOUND, $e->getMessage());
		}
		catch (Exception $e) {
			$this->aborting_loading(self::ABORT_CLEAR_OUTPUT, $e->getMessage());
		}

		$this->session->set('redirect_from_login', false);
	}

	/**
	 * Get the loadable class name for a module or a direct modul action.
	 *
	 * This will transform underline form into camel case.
	 *
	 * @param string $name
	 *   The original name.
	 *
	 * @return string The transformed class name.
	 */
	public static function generate_classname($name) {
		$arr = explode("_", $name);
		foreach ($arr AS &$val) {
			$val = ucfirst($val);
		}
		return ucfirst(implode("", $arr));
	}

	/**
	 * Abort loading.
	 *
	 * @param int $type
	 *   Why are we aborting? use one of WebAction::ABORT_*
	 *   (optional, default = WebAction::ABORT_CLEAR_OUTPUT)
	 * @param string $message
	 *   the message to display (optional, default = '')
	 * @param string $message_type
	 *   the message type, use one of Core::MESSAGE_TYPE_*
	 *   this has only an effect if $message is not empty
	 *   (optional, default = Core::MESSAGE_TYPE_ERROR)
	 */
	private function aborting_loading($type = self::ABORT_CLEAR_OUTPUT, $message = '', $message_type = Core::MESSAGE_TYPE_ERROR) {

		if (!empty($message)) {
			$this->core->message($message, $message_type);
		}

		$this->assign_default_js_css();

		switch($type) {
			case self::ABORT_CLEAR_OUTPUT:
				$this->current_action_module->clear_output();
				break;
			case self::ABORT_NO_PERMISSION:
				$this->current_action_module->no_permission();
				break;
			case self::ABORT_MODULE_NOT_FOUND:
				$this->smarty->assign("module_tpl", "");
				break;
			case self::ABORT_WRONG_PARAMS:
				$this->current_action_module->wrong_params();
				break;
		}

		$this->core->assign_menus();
		$this->core->smarty_assign_default_vars();
		$this->core->smarty->display($this->core->template);
		die();
	}

	/**
	 * Assign default js and css files.
	 */
	private function assign_default_js_css() {

		//Define default css files
		$this->core->add_css("/css/master.css");
		$dir = new Dir('/css/jquery_soopfw', false);
		$dir->just_files();
		$dir->file_extension('css');
		$dir->file_regexp('.*jquery-ui-[0-9.]+.*');

		$jquery_ui_css_versions = array();
		$jquery_ui_js_version = "";

		foreach ($dir->search() AS $file_entry) {
			if (preg_match("/jquery-ui-([0-9]+\.[0-9]+\.[0-9]+).*/", $file_entry->filename, $matches)) {
				$jquery_ui_css_versions[$matches[1]] = $file_entry->path;
			}
		}
		krsort($jquery_ui_css_versions);
		foreach ($jquery_ui_css_versions AS $version => $file) {
			$jquery_ui_js_version = '/js/jquery_plugins/jquery-ui-' . $version . '.custom.min.js';
			if (file_exists(SITEPATH . $jquery_ui_js_version)) {
				$this->core->add_css(str_replace(SITEPATH, '', $file));
				break;
			}
			elseif (file_exists(SITEPATH . $this->core->smarty->get_tpl() . $jquery_ui_js_version)) {
				$this->core->add_css(str_replace(SITEPATH, '', $file));
				break;
			}
			else {
				$jquery_ui_js_version = "";
			}
		}

		$this->core->add_css("/css/jquery_soopfw/jquery-ui-datetime-picker.css");
		$this->core->add_css("/css/jquery_soopfw/jquery.qtip.css");
		$this->core->add_css("/css/jquery_soopfw/jquery.sceditor.default.min.css");
		$this->core->add_css("/css/jquery_overrides.css");

		$this->core->add_css("/css/soopfw_icons.css");

		$this->core->add_css("/css/fileuploader.css");

		$this->core->add_css("/css/admin_menu.css");
		$this->core->add_css("/css/menu.css");
		$this->core->add_css("/css/box.css");
		$this->core->add_css("/css/form.css");
		$this->core->add_css("/css/popup.css");
		$this->core->add_css("/css/table.css");
		$this->core->add_css("/css/pager.css");

		$this->core->add_css($this->core->smarty->get_tpl() . "/css/styles.css");



		//Add default javascript files
		$this->core->add_js("/js/jquery-1.7.2.min.js", Core::JS_SCOPE_SYSTEM);
		if (!empty($jquery_ui_js_version)) {
			$this->core->add_js($jquery_ui_js_version, Core::JS_SCOPE_SYSTEM);
		}
		$this->core->add_js("/js/jquery_plugins/jquery.ui.i18n.all.min.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.ui.droppable.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery-ui-timepicker-addon.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.maskedinput-1.3.min.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.validator-0.3.3.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.ajaxQueue.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.qtip.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.metadata.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.tablesorter.min.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.ui.tabs.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.sceditor.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.sceditor.bbcode.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery-fieldselection.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/jquery_plugins/jquery.endless-scroll.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/adminmenu.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/common.js", Core::JS_SCOPE_SYSTEM);
		$this->core->add_js("/js/core.js", Core::JS_SCOPE_SYSTEM);

		$this->core->add_js("/js/fileuploader.js", Core::JS_SCOPE_USER);
		$this->core->add_js("/js/SoopfwPager.js", Core::JS_SCOPE_USER);
		if (file_exists(SITEPATH . $this->core->smarty->get_tpl() . '/js/main.js')) {
			$this->core->add_js($this->core->smarty->get_tpl() . '/js/main.js', Core::JS_SCOPE_USER);
		}
	}

}