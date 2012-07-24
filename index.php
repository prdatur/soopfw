<?php
	/* @var $core Core */
    require("default_index.php");
    $smarty = &$core->smarty;

    if(!empty($_REQUEST['logout'])) {
        $core->get_session()->logout();
    }

    #####################################################
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
	if(empty($params->module)) {
		$start_page = $core->dbconfig("system", System::CONFIG_DEFAULT_PAGE);
		if(!empty($start_page)) {
			$start_page_params = UrlAliasObj::get_params_from_alias($start_page);
			if($start_page === false) {
				$start_page_params = parse_url_string($start_page);
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

	$loggedin = false;

    if((!empty($core->db) && ($params->module == 'system' && $core->dbconfig("system", "installed") == "1") || $params->module != 'system')) {

		$session = $core->get_session();
		if(!empty($session)) {

			$loggedin = $session->check_login();
		}

    }

	if(!empty($additional_function_params)) {

		$lastindex = count($additional_function_params)-1;
		if(preg_match("/\/$/", $url)) {
			if(empty($additional_function_params[$lastindex])) {
				unset($additional_function_params[$lastindex]);
				$lastindex--;
			}
		}
		if(!empty($additional_function_params)) {
			if(preg_match("/(.*)\.(ajax|ajax_html|html)?$/is", $additional_function_params[$lastindex], $matches)) {

				$additional_function_params[$lastindex] = $matches[1];
			}
		}
	}

    switch ($params->type) {
        case 'ajax_request':
			$module = $params->module;
			$mod = "modules/".$params->module;
			if(!empty($params->ajax_module)) {
				$module = $params->ajax_module;
				$mod = "modules/".$params->ajax_module;
			}

			$module_conf_obj = new ModulConfigObj($module);
			if((!$module_conf_obj->load_success() || $module_conf_obj->enabled != 1) && $module != "system") {
				AjaxModul::return_code(AjaxModul::ERROR_MODULE_NOT_FOUND);
			}

			if(!empty($params->perm) && !$core->get_right_manager()->has_perm($params->perm)) {
				AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS);
			}

			$ajax_file = SITEPATH."/".$mod."/ajax/".$params->action.".php";
			if(file_exists($ajax_file)) {
				include($ajax_file);
			}
			break;
		case 'ajax_html':
			$core->init_type = Core::INIT_TYPE_AJAXHTML;
        default:

			$used_default_module = false;
			$original_module = $params->module;
			if(!file_exists(SITEPATH."/modules/".$params->module."/".$params->module.".php")) {
				$params->module = $core->core_config("core", "default_module");
				$used_default_module = true;
			}

			if($core->lng) {
				$core->lng->load("intl");
				$core->lng->load("menu");
				$core->lng->load($params->module);
				$core->lng->load_javascript($params->module);

			}
			$modulname = $params->module;
			$module_conf_obj = new ModulConfigObj($modulname);
			if((!$module_conf_obj->load_success() || $module_conf_obj->enabled != 1) && $modulname != "system") {
				assign_default_js_css($core);
				$core->message(t("Module not found or disabled"), Core::MESSAGE_TYPE_ERROR);
				$core->smarty->assign("module_tpl", "");
				aborting_loading();
			}

			$actions_path = SITEPATH.'/modules/'.$modulname.'/actions';
			if($used_default_module && (file_exists($actions_path.'/'.$original_module.'.php') || method_exists($modulname, $original_module))) {

				if(!empty($params->action)) {
					array_unshift($additional_function_params, $params->action);
				}
				$params->action = $original_module;
			}

			$action = $params->action;

			if(file_exists($actions_path.'/'.$action.'.php')) {
				$load_class = $modulname."_".$action;
			}
			else {
				$load_class = $modulname;
			}

			/* @var $module ActionModul */
			$module = new $load_class();

			$module->modulname = $params->module;
			$module->action = $action;
			$module->additional_params = $additional_function_params;

			$module->__init();

			if(!empty($params->perm) && !$core->get_right_manager()->has_perm($params->perm)) {
				assign_default_js_css($core);
				$module->no_permission();
				aborting_loading();
			}

			$core->cache("core", "current_module",$module->modulname);
			$core->cache("core", "current_action", $module->action);

			$parent = get_parent_class($modulname);
			if($parent != "ActionModul" && get_parent_class($parent) != "ActionModul") {
				assign_default_js_css($core);
				$module->wrong_params();
				aborting_loading();

			}

			if ($module->action == ActionModul::NO_DEFAULT_METHOD) {
				assign_default_js_css($core);
				$module->clear_output();
				aborting_loading();
			}
			//Get the calling class method
			$method = new ReflectionMethod($module, $module->action);

			//Check if we provided all required parameters, if not abort loading and display error message
			if($method->getNumberOfRequiredParameters() > count($additional_function_params)) {
				assign_default_js_css($core);
				$module->wrong_params();
				aborting_loading();
			}

			//Call the wanted module action
			call_user_func_array(array($module,$action), $additional_function_params);

			$module_template = "";

			if(!empty($module->static_tpl)) {
				if($module->static_tpl == NS) {
					$module->static_tpl = "";
				}
				$module_template = $module->static_tpl;
			}
			else {
				$module_template = SITEPATH."/modules/".$module->modulname."/templates/".$module->module_tpl;

			}

			if(!empty($module_template) && $core->smarty->templateExists($module_template)) {
				$core->smarty->assign("module_tpl", $module_template);
			}
			else if(!empty($module_template)) {
				$core->smarty->assign("module_tpl_old", $module_template);
				$core->smarty->assign("module_tpl", "template_not_exists.tpl");
			}

			if(!empty($params->popup)) {
				$core->smarty->assign("popup", "1");
			}

			if(!empty($params->dialog)) {
				$core->smarty->assign("dialog", "1");
			}

			switch($params->type) {
				case 'ajax_html':
					$core->js_config("is_ajax_html", true);
					$core->template = "index_ajax_html.tpl";
					break;
				default:
					assign_default_js_css($core);
					$core->assign_menus();
					define("BUILD_END",microtime(true));
					$core->smarty->assign("page_generated", (BUILD_END-BUILD_START));
					break;
			}
			$module->assign_default();
			$core->smarty_assign_default_vars();
			$core->smarty->display($core->template);


		break;
    }

	function aborting_loading() {
		global $core;
		$core->assign_menus();
		$core->smarty_assign_default_vars();
		$core->smarty->display($core->template);
		display_xhprof_run();
		die();
	}
	function assign_default_js_css(Core &$core) {

		//Define default css files
		$core->add_css("/css/master.css");
		$dir = new Dir('/css/jquery_soopfw', false);
		$dir->just_files();
		$dir->file_extension('css');
		$dir->file_regexp('.*jquery-ui-[0-9.]+.*');

		$jquery_ui_css_versions = array();
		$jquery_ui_js_version = "";

		foreach($dir->search() AS $file_entry) {
			if (preg_match("/jquery-ui-([0-9]+\.[0-9]+\.[0-9]+).*/", $file_entry->filename, $matches)) {
				$jquery_ui_css_versions[$matches[1]] = $file_entry->path;
			}
		}
		krsort($jquery_ui_css_versions);
		foreach($jquery_ui_css_versions AS $version => $file) {
			$jquery_ui_js_version =  '/js/jquery_plugins/jquery-ui-' . $version . '.custom.min.js';
			if(file_exists(SITEPATH . $jquery_ui_js_version)) {
				$core->add_css(str_replace(SITEPATH, '', $file));
				break;
			}
			elseif(file_exists(SITEPATH . $core->smarty->get_tpl() . $jquery_ui_js_version)) {
				$core->add_css(str_replace(SITEPATH, '', $file));
				break;
			}
			else {
				$jquery_ui_js_version = "";
			}

		}

		$core->add_css("/css/jquery_soopfw/jquery-ui-datetime-picker.css");
		$core->add_css("/css/jquery_soopfw/jquery.qtip.css");
		$core->add_css("/css/jquery_soopfw/jquery.sceditor.default.min.css");
		$core->add_css("/css/jquery_overrides.css");

		$core->add_css("/css/soopfw_icons.css");

		$core->add_css("/css/fileuploader.css");

		$core->add_css("/css/admin_menu.css");
		$core->add_css("/css/menu.css");
		$core->add_css("/css/box.css");
		$core->add_css("/css/form.css");
		$core->add_css("/css/popup.css");
		$core->add_css("/css/table.css");
		$core->add_css("/css/pager.css");

		$core->add_css($core->smarty->get_tpl()."/css/styles.css");



		//Add default javascript files
		$core->add_js("/js/jquery-1.7.2.min.js", Core::JS_SCOPE_SYSTEM);
		if (!empty($jquery_ui_js_version)) {
			$core->add_js($jquery_ui_js_version, Core::JS_SCOPE_SYSTEM);
		}
		$core->add_js("/js/jquery_plugins/jquery.ui.droppable.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery-ui-timepicker-addon.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.maskedinput-1.3.min.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.validator-0.3.3.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.ajaxQueue.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.qtip.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.tablednd.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.metadata.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.tablesorter.min.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.ui.tabs.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.sceditor.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/jquery_plugins/jquery.sceditor.bbcode.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/adminmenu.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/common.js", Core::JS_SCOPE_SYSTEM);
		$core->add_js("/js/core.js", Core::JS_SCOPE_SYSTEM);

		$core->add_js("/js/fileuploader.js", Core::JS_SCOPE_USER);
		$core->add_js("/js/SoopfwPager.js", Core::JS_SCOPE_USER);
	}



display_xhprof_run();

function display_xhprof_run() {
	global $prof_enable, $core;
	if($prof_enable) {
		$xhprof_data = xhprof_disable();
		$XHPROF_ROOT = "/opt/xhprof-0.9.2";
		include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
		include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

		$xhprof_runs = new XHProfRuns_Default();
		$mem_usage = memory_get_usage(true)/1024/1024;
		$wall_time = round($xhprof_data['main()']['wt']/1000/1000, 3);
		$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
		echo "<br /><div style='text-align:right;'><a href='https://".$core->core_config("core", "xhprof_domain")."/index.php?run=$run_id&source=xhprof_foo' target='_blank''>".$wall_time."s ".$mem_usage."MB Profile data</a></div>";
	}
}

?>
