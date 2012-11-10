<?php

/**
 * System action module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class System extends ActionModul
{
	/**
	 * Define constances
	 */
	const CONFIG_CACHE_JS = "cache_js";
	const CONFIG_CACHE_CSS = "cache_css";
	const CONFIG_DEFAULT_LANGUAGE = "default_language";
	const CONFIG_LOGIN_HANDLER = "login_handler";
	const CONFIG_SSL_AVAILABLE = "ssl_available";
	const CONFIG_SECURE_DOMAIN = "ssl_domain";
	const CONFIG_DEFAULT_PAGE = "default_page";
	const CONFIG_DEFAULT_THEME = "default_theme";
	const CONFIG_ADMIN_THEME = "admin_theme";
	const CONFIG_RECAPTCHA_PRIVATE_KEY = "recaptcha_private_key";
	const CONFIG_RECAPTCHA_PUPLIC_KEY = "recaptcha_public_key";
	const CONFIG_DEFAULT_UPLOAD_MAX_FILE_SIZE = "default_upload_max_file_size";
	const CONFIG_RUN_MODE = "system_run_mode";

	/**
	 * The default method
	 * @var string
	 */
	protected $default_methode = "modules";

	/**
	 * Implementation of get_admin_menu()
	 *
	 * @return array the menu
	 */
	public function get_admin_menu() {
		return array(
			1000 => array(//Order id, same order ids will be unsorted placed behind each
				'#id' => 'soopfw_system', //A unique id which will be needed to generate the submenu
				'#title' => t("System"), //The main title
				'#perm' => 'admin.system', //Perm needed
				'#childs' => array(
					array(
						'#title' => t("Config"), //The main title
						'#link' => "/admin/system/config", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
					array(
						'#title' => t("Email templates"), //The main title
						'#link' => "/admin/system/email_templates", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
					array(
						'#title' => t("Modules"), //The main title
						'#link' => "/admin/system/modules", // The main link
						'#perm' => 'admin.system.modules', //Perm needed
						'#childs' => array(
							array(
								'#title' => t("Update modules"), //The main title
								'#link' => "/admin/system/updatedb", // The main link
								'#perm' => 'admin.system.updatedb' //Perm needed
							),
							array(
								'#title' => t("Reindex module menu alias"), //The main title
								'#link' => "/admin/system/reindex_menu", // The main link
								'#perm' => "admin.system.config", // perms needed
							),
						),
					),
					array(
						'#title' => t("Tools"), //The main title
						'#perm' => "admin.system", // perms needed
						'#childs' => array(
							array(
								'#title' => t("Generate classlist"), //The main title
								'#link' => "/admin/system/generate_classlist", // The main link
								'#perm' => "admin.system.config", // perms needed
							),
							array(
								'#title' => t("Generate smartylist"), //The main title
								'#link' => "/admin/system/generate_smartylist", // The main link
								'#perm' => "admin.system.config", // perms needed
							),
						),
					),
				)
			)
		);
	}

	/**
	 * Displays information about a web unit test request.
	 *
	 * @param string $report_id
	 *   the unit test report id.
	 * @param int $count_id
	 *   the count id.
	 */
	public function view_webtest_report($report_id, $count_id) {
		if (!$this->right_manager->has_perm("admin.system.config", true)) {
			throw new SoopfwNoPermissionException();
		}

		$this->core->mcache_set_prefix('test_' . $this->db->table_prefix());
		$data = $this->core->mcache('webtest_report::' . $report_id . '::' . $count_id);
		$max_count_id = (int) $this->core->mcache('webtest_report::' . $report_id . '::max_counter');
		$this->core->mcache_set_prefix($this->db->table_prefix());
		if (empty($data)) {
			throw new SoopfwWrongParameterException();
		}


		$prev_counter_id = $count_id - 1;
		$header = "<div style='padding: 15px'>";
		if ($prev_counter_id > 0) {
			$header .= "<a href='/admin/system/view_webtest_report/" . $report_id . "/" . $prev_counter_id . "' class='form_button' style='margin-right: 20px;'>" . t('Previous') . "</a>";
		}
		if ($max_count_id > 0 && ($count_id + 1) <= $max_count_id) {
			$header .= "<a href='/admin/system/view_webtest_report/" . $report_id . "/" . ($count_id + 1) . "' class='form_button'>" . t('Next') . "</a>";
		}
		$header .= "<br /><br />Request url: " . $data['url'] . "<br />";
		$header .= "Request type: " . $data['type'] . "<br />";

		if (!empty($data['args'])) {
			$args = print_r($data['args'], true);
			$args = str_replace("\n", "<br>", $args);
			$args = preg_replace("/\s/", "&nbsp;&nbsp;", $args);

			$header .= "Request arguments: <br />" . $args . "<br />";
		}
		$header .= "</div>";
		$content = $data['data'];
		if (preg_match('/^(.*<\s*body\s*>)(.*)$/iUs', $content, $matches)) {
			echo $matches[1] . $header . $matches[2];
		}
		else {
			$header .= "############### RETURN DATA ################### <br />";
			echo $header;
			$json_content = json_decode($content, true);
			if (!empty($json_content)) {
				$json_content = print_r($json_content, true);
				$json_content = str_replace("\n", "<br>", $json_content);
				$json_content = preg_replace("/\s/", "&nbsp;&nbsp;", $json_content);
				echo "Data is JSON:<br />" . $json_content;
			}
			else {
				echo $content;
			}
		}
		die();
	}

	public function hello_world() {

	}

	/**
	 * Action: precheck_module_state
	 *
	 * Displays a confirm dialog to active/deactivate the given module.
	 * If dependency problems exist, display what to do.
	 *
	 * @param string $module
	 *   the module name
	 */
	public function precheck_module_state($module) {
		//Check perms
		if (!$this->right_manager->has_perm("admin.system.modules", true)) {
			throw new SoopfwNoPermissionException();
		}

		if (empty($module) || !file_exists(SITEPATH . '/modules/' . $module . '/' . $module . '.info')) {
			throw new SoopfwWrongParameterException();
		}

		// Get the info for the current module
		if (($info = SystemHelper::get_module_info($module)) === false) {
			throw new SoopfwWrongParameterException();
		}

		$system_helper = new SystemHelper();

		$this->smarty->assign_by_ref('moduleinfo', $info);

		if ($this->core->module_enabled($module)) {
			$this->title(t('Disable module: @module', array('@module' => $module)));
			$this->static_tpl = $this->module_tpl_dir . '/precheck_module_state_disable.tpl';

			$dependencies = $system_helper->get_dependet_modules($module, true, SystemHelper::DEPENDENCY_FILTER_ENABLED);
			$this->core->js_config('system_disable_dependencies', array_keys($dependencies));
			$this->smarty->assign_by_ref('dependencies', $dependencies);
		}
		else {
			$this->title(t('Enable module: @module', array('@module' => $module)));
			$this->static_tpl = $this->module_tpl_dir . '/precheck_module_state_enable.tpl';
			$dependencies = $system_helper->get_module_dependencies($module, true, true, SystemHelper::DEPENDENCY_FILTER_DISABLED);
			$this->core->js_config('system_enable_dependencies', array_keys($dependencies));
			$this->smarty->assign_by_ref('dependencies', $dependencies);
		}
	}

	/**
	 * Action: email_templates
	 *
	 * Configurate the email templates
	 */
	public function email_templates() {

		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			throw new SoopfwNoPermissionException();
		}

		//Setup search form
		$form = new Form("search_email_templates", t("search:"));
		$form->add(new Textfield("id", '', t("template id")));
		$form->add(new Textfield("subject", '', t("subject")));
		$form->add(new Textfield("body", '', t("body")));

		$this->lng->load_language_list('', array(), true);
		$options = array(
			'' => t('All')
		);
		$options = array_merge($options, $this->lng->languages);
		$form->add(new Selectfield('language', $options, '', t("Language")));



		$form->add(new Submitbutton("search", t("Search"), "form_button"));
		$form->assign_smarty("search_form");

		//Check form and add errors if form is not valid
		$form->check_form();
		if ($form->is_submitted()) { //Search was submited
			//Set session key for server search values so a reload of a page will use the session values
			$this->session->set("search_system_email_templates", $form->get_values());
		}
		else {
			//Form was not submited so try to load session values
			$form->set_values($this->session->get("search_system_email_templates", array()));
		}

		$templates = DatabaseFilter::create(MailTemplateObj::TABLE);

		//Build up where statement
		foreach ($this->session->get("search_system_email_templates", array()) AS $field => $val) {
			if (empty($val)) {
				continue;
			}
			$templates->add_where($field, $this->db->get_sql_string_search($val, "*.*", false), 'LIKE');
		}

		$templates = $templates->add_column('id')
				->group_by('id')
				->order_by('id')
				->select_all();

		$this->smarty->assign('templates', $templates);
	}

	/**
	 * Action: change_email_template
	 * Save or create a email template, if $id is provided update the current one
	 * if left empty it will create a new template
	 *
	 * @param int $id
	 *   the email template id (optional, default = "")
	 * @param string $available_variables
	 *   the available variables which can be provided as a comma seperated string
	 *   to provide information about the template which variables can be used.
	 *   (optional, default = "")
	 */
	public function change_email_template($id = "", $available_variables = "") {
		//Need to be logged in
		$this->session->require_login();

		if (!$this->right_manager->has_perm("admin.system.config")) {
			throw new SoopfwNoPermissionException();
		}
		$description = "";
		if (!empty($available_variables)) {
			$vars = array();
			foreach (explode(",", $available_variables) AS $var) {
				$vars[] = '<a href="javascript:system_change_email_template_insert_variable(\'' . trim($var) . '\')">{' . trim($var) . '}</a>';
			}
			$description = t('The following variables can be used: <b>!variables</b>', array('!variables' => implode(", ", $vars)));
		}

		$form = new Form('change_email_template');

		$this->lng->load_language_list('', array(), true);

		$validators = array(
			new RequiredValidator(),
		);
		if (empty($id)) {
			$title = t('Add a new template');
			$validators[] = new NotExistValidator(t('This email template already exists'), array(MailTemplateObj::TABLE => 'id'));
		}
		else {
			$title = t('Change email template');
		}

		$this->title($title, $description);
		$form->add(new Textfield('id', $id, t('Template id')), $validators);

		$values = DatabaseFilter::create(MailTemplateObj::TABLE)
				->add_where('id', $id)
				->select_all('language');

		foreach ($this->lng->languages AS $language => $label) {
			if (!isset($values[$language])) {
				$values[$language] = array('subject' => '', 'body' => '');
			}
			$form->add(new Fieldset('language_' . $language, t('Language: @language', array('@language' => $label))));
			$form->add(new Textfield($language . '[subject]', $values[$language]['subject'], t('Subject')));
			$form->add(new Textarea($language . '[body]', $values[$language]['body'], t('Body')));
		}

		$form->set_ajax(true);
		$form->add_js_success_callback("save_email_template_success");
		//Add a save submit button
		$form->add(new Submitbutton("insert", t("Save")));

		$form->assign_smarty("form");


		//Check if form was submitted
		if ($form->check_form()) {

			$values = $form->get_array_values();

			$this->db->transaction_begin();
			foreach ($this->lng->languages AS $language => $label) {
				$obj = new MailTemplateObj($id, $language);
				if (!$obj->load_success()) {
					$obj->id = $values['id'];
					$obj->language = $language;
				}

				$obj->set_fields($values[$language]);
				if (!$obj->save_or_insert()) {
					$this->db->transaction_rollback();
					$this->core->message("Error while saving mail template", Core::MESSAGE_TYPE_ERROR, true);
				}
			}

			//Setup success message
			$this->db->transaction_commit();
			$this->core->message("Email template saved ", Core::MESSAGE_TYPE_SUCCESS, true, $values['id']);
		}

		$this->static_tpl = 'form.tpl';
	}

	/**
	 *  Action generate_classlist
	 *
	 *  Generates the classlist new
	 */
	public function generate_classlist() {

		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			throw new SoopfwNoPermissionException();
		}
		$loader = new cli_generate_classlist();
		$loader->generate_classlist();
		$this->core->message(t('classlist generated'), Core::MESSAGE_TYPE_SUCCESS);

		$this->clear_output();
	}

	/**
	 *  Action generate_smartylist
	 *
	 *  Generates the smartylist new
	 */
	public function generate_smartylist() {

		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			throw new SoopfwNoPermissionException();
		}

		$smarty_sdi = new cli_generate_smartylist();
		if ($smarty_sdi->create_smarty_sdi()) {
			$this->core->message(t('smartylist generated'), Core::MESSAGE_TYPE_SUCCESS);
		}
		else {
			$this->core->message(t('could not generated smartylist'), Core::MESSAGE_TYPE_ERROR);
		}

		$this->clear_output();
	}

	/**
	 *  Action: reindex_menu
	 *
	 *  Reindex the menu alias
	 */
	public function reindex_menu() {

		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			throw new SoopfwNoPermissionException();
		}

		$this->core->reindex_menu();
		$this->core->message(t('menu re-indexed'), Core::MESSAGE_TYPE_SUCCESS);
		$this->clear_output();
	}

	/**
	 * Action: config
	 *
	 * Configurate the system main settings.
	 */
	public function config() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			throw new SoopfwNoPermissionException();
		}

		//Setting up title and description
		$this->title(t("System Config"), t("Here we can configure the main system settings"));

		//Configurate the settings form
		$form = new SystemConfigForm($this, "system_config");

		$form->add(new Fieldset('performance', t('Performance')));
		$form->add(new YesNoSelectfield(self::CONFIG_CACHE_CSS, $this->core->get_dbconfig("system", self::CONFIG_CACHE_CSS, 'no'), t("Enable css cache?")), array(
			new FunctionValidator(t('Can not find java, javascript cache can not be enabled, you need to install java first'), function($value) {
				if ($value == 'yes') {
					return (shell_exec('which java') !== null);
				}
				return true;
			})
		));
		$form->add(new YesNoSelectfield(self::CONFIG_CACHE_JS, $this->core->get_dbconfig("system", self::CONFIG_CACHE_JS, 'no'), t("Enable javascript cache?")), array(
			new FunctionValidator(t('Can not find java, javascript cache can not be enabled, you need to install java first'), function($value) {
				if ($value == 'yes') {
					return (shell_exec('which java') !== null);
				}
				return true;
			})
		));

		$form->add(new Fieldset('system', t('System')));
		if (!empty($this->lng)) {
			$form->add(new Selectfield(self::CONFIG_DEFAULT_LANGUAGE, $this->lng->get_enabled_languages(), $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_LANGUAGE, 'en'), t("Default language")));
		}

		$form->add(new Fieldset('appearance', t('Appearance')));
		$dir = new Dir('templates', false);
		$dir->skip_dirs("images");
		$dir->just_dirs();
		$available_themes = array();
		foreach ($dir AS $entry) {
			$available_themes[$entry->filename] = $entry->filename;
		}
		$form->add(new Selectfield(self::CONFIG_DEFAULT_THEME, $available_themes, $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_THEME, 'standard'), t("Default theme")));
		$form->add(new Selectfield(self::CONFIG_ADMIN_THEME, $available_themes, $this->core->get_dbconfig("system", self::CONFIG_ADMIN_THEME, 'standard'), t("Admin theme"), t('All urls which starts with /admin will get this theme.')));
		$form->add(new Selectfield(self::CONFIG_RUN_MODE, array(
			Core::RUN_MODE_DEVELOPEMENT => t('Development'),
			Core::RUN_MODE_PRODUCTION => t('Production'),
		), $this->core->get_dbconfig("system", self::CONFIG_RUN_MODE, Core::RUN_MODE_DEVELOPEMENT), t("Run-mode"), t('In developing it is highly recommended to use development mode, there you will see all errors which occures, If you switch to production it is also highly recommended to switch here also in production mode else if an error occured other user could see sensible data.')));

		$form->add(new Textfield(self::CONFIG_DEFAULT_PAGE, $this->core->dbconfig("system", self::CONFIG_DEFAULT_PAGE), t("Default page / Startpage")));

		$classes = $this->core->get_classlist();
		$login_handler = array();
		foreach ($classes['classes'] AS $classname => &$class) {
			if (!empty($class['implements']) && in_array("LoginHandler", $class['implements'])) {
				$login_handler[$classname] = $classname;
			}
		}

		$form->add(new Fieldset('security', t('Security')));
		$form->add(new YesNoSelectfield(self::CONFIG_SSL_AVAILABLE, $this->core->get_dbconfig("system", self::CONFIG_SSL_AVAILABLE, 'no'), t("Is SSL available?"), t('If enabled the user critical data process will be ssl encrypted, also all admin links will be redirected to ssl domain.')));
		$form->add(new Textfield(self::CONFIG_SECURE_DOMAIN, $this->core->get_dbconfig("system", self::CONFIG_SECURE_DOMAIN, ''), t("Secure SSL-Domain"), t('If you have a differenct domain for your ssl connection, please provide it here.')));

		$form->add(new HtmlContainerInput('<a href="javascript:void(0);" class="change_login_handler_priority form_button">' . t('Configurate login handler') . '</a>'));

		$form->add(new Textfield(self::CONFIG_RECAPTCHA_PRIVATE_KEY, $this->core->dbconfig("system", self::CONFIG_RECAPTCHA_PRIVATE_KEY), t("Recaptcha private key"), t('Only use it if you really want your own, an internal key already exists which works on all domains')));
		$form->add(new Textfield(self::CONFIG_RECAPTCHA_PUPLIC_KEY, $this->core->dbconfig("system", self::CONFIG_RECAPTCHA_PUPLIC_KEY), t("Recaptcha public key"), t('Only use it if you really want your own, an internal key already exists which works on all domains')));
		$form->add(new Textfield(self::CONFIG_DEFAULT_UPLOAD_MAX_FILE_SIZE, $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_UPLOAD_MAX_FILE_SIZE, 52428800), t("Default upload max size"), t('Determines the default maximun size of uploaded files')));


		//Execute the settings form
		$form->execute();
	}

	public function configurate_login_handler() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config')) {
			throw new SoopfwNoPermissionException();
		}

		$this->core->add_js("/js/jquery_plugins/jquery.tablednd.js", Core::JS_SCOPE_SYSTEM);

		$classes = $this->core->get_classlist();
		$login_handler = array();
		foreach ($classes['classes'] AS $classname => &$class) {
			if (!empty($class['implements']) && in_array("LoginHandler", $class['implements'])) {
				$login_handler[$classname] = $classname;
			}
		}

		$configured_handlers = $this->core->get_dbconfig("system", self::CONFIG_LOGIN_HANDLER, array());

		$handlers = array();
		foreach ($configured_handlers AS $key => $val) {
			$handlers[$val] = array(
				'val' => $val,
				'enabled' => true,
			);
			unset($login_handler[$val]);
		}

		foreach ($login_handler AS $key => $val) {
			$handlers[$key] = array(
				'val' => $val,
				'enabled' => false,
			);
		}

		$this->smarty->assign_by_ref('login_handlers', $handlers);
	}

	/**
	 * Action: modules
	 *
	 * Lists all available modules with there status. if a module is not found within database it will be listed but as disabled
	 */
	public function modules() {
		$this->session->require_login();

		//Check perms
		if (!$this->right_manager->has_perm("admin.system.modules")) {
			throw new SoopfwNoPermissionException();
		}

		//Set title
		$this->title(t("Modul config"), t("Here you can enable or disable modules.
			A [b]disabled[/b] module can not be accessed anymore, also menu items will not be displayed.
			To enable or disable a module please click on the status icon."));
		$modules = array();

		$helper = new SystemHelper();

		//Loop through all available modules (from core dir scanning)
		foreach ($this->core->modules AS $module) {
			//Try to load the module config object, if not found set it to disabled
			$mobj = new ModulConfigObj($module);
			if (!$mobj->load_success()) {
				$mobj->modul = $module;
				$mobj->enabled = false;
				$db_version = "-";
			}
			else {
				$db_version = $mobj->current_version - 1;
			}

			$info = SystemHelper::get_module_info($module);
			$info['obj'] = $mobj;
			$info['dependencies'] = $helper->get_module_dependencies($module);
			$info['current_version'] = $db_version;

			if ($info['current_version'] != $info['version']) {
				$info['updated_needed'] = 1;
			}

			foreach ($info['dependencies'] AS $dependency) {
				if ($dependency['state'] === SystemHelper::DEPENDENCY_UNAVAILABLE) {
					$info['not_installable'] = true;
					break;
				}
			}

			if ($mobj->enabled && empty($info['updated_needed'])) {
				$object_updates = SystemHelper::get_updateable_objects($module);
				$info['updated_needed'] = !empty($object_updates);
			}

			//Add the module to the list
			$modules[] = $info;
		}

		//Smarty assign
		$this->smarty->assign_by_ref("modules", $modules);
	}

	/**
	 * Action: updatedb
	 *
	 * Will on form submission update all modules
	 */
	public function updatedb() {

		$this->session->require_login();

		$this->title(t("Update modules"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.system.modules")) {
			throw new SoopfwNoPermissionException();
		}

		$form = new Form("Start update");
		$form->add(new Submitbutton("update", t("Start update")));
		$form->assign_smarty("form");

		if ($form->is_submitted()) {
			$modules = array('system');
			foreach ($this->core->modules AS $module) {
				if ($module == 'system' || !$this->core->module_enabled($module)) {
					continue;
				}
				$modules[] = $module;
			}

			$loader = new cli_generate_classlist();
			$loader->generate_classlist();

			$smarty_sdi = new cli_generate_smartylist();
			$smarty_sdi->create_smarty_sdi();
			$this->core->js_config("update_db_modules", $modules);
		}
	}

	/**
	 * Action: update_module
	 *
	 * update a module
	 *
	 * @param string $module
	 *   The module to be updated (optional, default = 'system')
	 * @param string $op
	 *   the operation, if an ajax request calls this, usually this needs "js" (optional, default = '')
	 */
	public function update_module($module = "system", $op = '') {
		$this->install_module($module, $op);
	}

	/**
	 * Action: install
	 *
	 * Install or update a module
	 *
	 * @param string $module
	 *   The module to be installed or updated (optional, default = 'system')
	 * @param string $op
	 *   the operation, if an ajax request calls this, usually this needs "js" (optional, default = '')
	 */
	public function install_module($module = "system", $op = '') {

		$this->clear_output();

		$classlist_already_generated = false;

		//Check only perms if we have installed the system before (first time a fresh install will pass the perm check)
		if (!defined('is_shell') && $this->core->dbconfig("system", "installed") == "1" && $this->core->module_enabled("user")) {
			$this->session->require_login();
			//Check perms
			if (!$this->right_manager->has_perm("admin.system.modules")) {
				throw new SoopfwNoPermissionException();
			}
		}

		//We do not need to generate every javascript request to generate the classlist, direct after form submission or direkt call is more enough
		if ($op != "js") {

			$loader = new cli_generate_classlist();
			$loader->generate_classlist();

			$classlist_already_generated = true;

			$smarty_sdi = new cli_generate_smartylist();
			$smarty_sdi->create_smarty_sdi();
		}

		//Check if the provided module is a valid module (has a valid module info file)
		$info_file = SITEPATH . "/modules/" . $module . "/" . $module . ".info";
		if (!file_exists($info_file)) {
			$msg = t("\"@module.info\" file is missing within module dir: \"modules/@module\"", array(
				'@module' => $module,
			));
			if ($op == 'js') {
				AjaxModul::return_code(AjaxModul::ERROR_MODULE_NOT_FOUND, $msg);
			}
			throw new SoopfwModuleNotFoundException($msg);
		}

		//Get the module information
		$module_info = SystemHelper::get_module_info($module);
		$module_info['version'] = (int) $module_info['version'];

		$helper = new SystemHelper();
		$depends = $helper->get_module_dependencies($module);
		foreach ($depends AS $dependency) {
			if ($dependency['state'] != SystemHelper::DEPENDENCY_ENABLED) {
				$msg = t("\"@module\" can not be updated because one or more dependent modules are missing", array(
					'@module' => $module,
				));
				if ($op == 'js') {
					AjaxModul::return_code(AjaxModul::ERROR_MODULE_NOT_FOUND, $msg);
				}
				throw new SoopfwModuleNotFoundException($msg);
			}
		}

		$results = array();

		$update_succeeds = true;

		//Creating Database tables from objects if the table does not exist or needs to be updated.
		foreach (SystemHelper::get_updateable_objects($module) AS $module_info_obj) {
			$obj = $module_info_obj->classname;
			if (!class_exists($obj)) {
				if ($classlist_already_generated === true) {
					continue;
				}
				$loader = new cli_generate_classlist();
				$loader->generate_classlist();

				$smarty_sdi = new cli_generate_smartylist();
				$smarty_sdi->create_smarty_sdi();
				$classlist_already_generated = true;
			}

			$results[$obj] = $this->db->mysql_table->create_database_from_object(new $obj());

			if ($results[$obj]) {
				$module_info_obj->classname = $obj;
				$module_info_obj->last_modified = filemtime(SITEPATH . '/modules/' . $module . '/objects/' . $obj . '.class.php');
				$module_info_obj->save_or_insert();
			}
		}

		//Loop through each results. and check if the creation was success, if not we need to update the current table
		foreach ($results AS $obj => $result) {
			$msg = "Created Database table for object: " . $obj;
			$type = Core::MESSAGE_TYPE_SUCCESS;
			if (empty($result)) {
				//Get the object which we want to create

				/* @var $mobj AbstractDataManagment */
				$mobj = new $obj();

				if ($mobj->get_dbstruct()->autoupdate_disabled()) {
					$msg = "Database update skipped because auto-update is disabled: " . $obj;
					$type = Core::MESSAGE_TYPE_SUCCESS;
				}
				else {
					//Get the tablename
					$table = $mobj->get_dbstruct()->get_table();

					//Check if the table really exist.
					if ($this->db->query_slave("SELECT 1 FROM `" . $table . "`")) {

						//These fields must be changed again for auto_increment after wie added the field.
						$add_fields = array();

						//Get the old database fields to check wether we must rename, modify, delete or add the field
						$db_fields = $this->db->get_table_fields($table);

						//Get the current object fields
						$obj_fields = $mobj->get_dbstruct()->get_struct();

						//Get the database primary keys
						$database_primary_keys = $this->db->get_primary_key($table, true);

						//Get the database indexe
						$database_indexe_keys = $this->db->get_table_indexes($table);

						$index_fields = array();
						foreach ($database_indexe_keys AS $type => $indexe) {
							foreach ($indexe AS $index_name => $fields) {
								foreach ($fields AS $field) {
									$index_fields[$field] = true;
								}
							}
						}

						//Get the new primary keys
						$object_primary_keys = $mobj->get_dbstruct()->get_reference_key();

						// Get indexe
						$object_indexes = $mobj->get_dbstruct()->get_indexes();


						//Initialize the field where we store the last processed field, because if we add a new field we must add it right after this one
						$after = "";

						/**
						 * We need this object index (loop index increment for the obj_fields) because we must check it against the database ordered index
						 * This is needed to determine if we must just rename the field or change / add / delete it
						 */
						$object_index = 1;
						foreach ($obj_fields AS $field => $options) {

							//Pre init default value
							if (!isset($options['default'])) {
								$options['default'] = null;
							}

							//Check if the field should have auto increment
							$ai = ($mobj->get_dbstruct()->get_auto_increment() == $field);

							//Check if the current field already exists within table
							if (isset($db_fields[$field])) {
								//If the database index order is not the current object index, we must move the field to the correct position
								if ($db_fields[$field]['ORDINAL_POSITION'] != $object_index) {
									$this->db->move_table_field($table, $field, $options, $ai, $after);
								}
								else {
									//If it is the same we just change the field options
									$this->db->change_table_field($table, $field, $options, $ai);
								}
							}
							else {
								//Pre init that we do not rename
								$rename = false;

								//This will be set to the original field for the index to check if we must add or change the field
								$original_index_table_field = null;

								/**
								 * loop through all database fields and get the old field for the current position,
								 * if the found field is a primary key or a field which is used within an index we can not add it as a new field, we must just rename it
								 *
								 */
								foreach ($db_fields AS $db_options) {
									if ($object_index == $db_options['ORDINAL_POSITION']) {
										if ($db_options['COLUMN_KEY'] == 'PRI' || isset($index_fields[$field])) {
											$rename = $db_options['COLUMN_NAME'];
										}

										$original_index_table_field = $db_options;
										break;
									}
								}

								//Check if we must rename because it is a primary key
								if ($rename != false) {
									$options['new_field'] = $field;
									$this->db->change_table_field($table, $rename, $options, $ai);
								}
								//Check if we must rename because the original field does not longer exist within the current object
								else if (!empty($original_index_table_field) && !isset($obj_fields[$original_index_table_field['COLUMN_NAME']])) {
									$options['new_field'] = $field;
									$this->db->change_table_field($table, $original_index_table_field['COLUMN_NAME'], $options, $ai);
								}
								/**
								 * Field is not a primary and the original key for the index is still available so we have a complete new field, add it, but without auto increment
								 * The auto increment will be set up after we changed the primary keys because the field must have an index to set the auto increment flag
								 */
								else {
									$this->db->add_table_field($table, $field, $after, $options, false);
									$add_fields[] = array(
										'field' => $field,
										'options' => $options,
										'ai' => $ai
									);
								}
							}
							$after = $field;
							//Remove the field from database array so all fields left within this array must be deleted because they are no longer within the current object
							unset($db_fields[$field]);
							$object_index++;
						}

						//Remove fields
						foreach ($db_fields AS $field) {
							$this->db->remove_table_field($table, $field['COLUMN_NAME']);
						}

						//Check if we must set the primary key, if the old primary keys did not changed to the current one, we do not need to update the key
						//Get all values which are both within the provided arrays, if we have the same count of the intersection and one of the intersect array the 2 arrays MUST be equal
						$intersect = array_intersect($object_primary_keys, $database_primary_keys);
						if (count($intersect) != count($object_primary_keys)) {
							//Set primary key
							$this->db->set_primary_key($table, $object_primary_keys);
						}

						//Check if we must update an index, if the old index keys did not changed to the current one, we do not need to update the key
						//Get all values which are both within the provided arrays, if we have the same count of the intersection and one of the intersect array the 2 arrays MUST be equal
						$object_index_array = array();
						foreach ($object_indexes AS $indexe) {
							if (!isset($object_index_array[$indexe['type']])) {
								$object_index_array[$indexe['type']] = array();
							}
							$object_index_array[$indexe['type']][md5(implode("|", $indexe['fields']))] = $indexe['fields'];
						}

						foreach ($database_indexe_keys AS $index_type => $indexe) {
							foreach ($indexe AS $index_name => $index_fields) {

								$field_check = md5(implode("|", $index_fields));
								if (!isset($object_index_array[$index_type]) || !isset($object_index_array[$index_type][$field_check])) {
									$this->db->remove_index($table, $index_name);
								}
								unset($object_index_array[$index_type][$field_check]);
							}
						}

						foreach ($object_index_array AS $index_type => $indexe) {
							foreach ($indexe AS $fields) {
								$this->db->add_index($table, $index_type, $fields);
							}
						}


						//Change all fresh added fields but now with the auto increment value
						foreach ($add_fields AS $field_option) {
							$this->db->change_table_field($table, $field_option['field'], $field_option['options'], $field_option['ai'], true);
						}

						//Run the queued sql statements
						$this->db->alter_table_queue($table);
						$msg = "DB Table already exists or now up to date: " . $obj;
						$type = Core::MESSAGE_TYPE_SUCCESS;
						$module_info_obj = new CoreModelObjectObj($obj);
						$module_info_obj->last_modified = filemtime(SITEPATH . '/modules/' . $module . '/objects/' . $obj . '.class.php');
						$module_info_obj->save_or_insert();
					}
					else {
						$update_succeeds = false;
						$msg = "Could not create Database table for object: " . $obj;
						$type = Core::MESSAGE_TYPE_ERROR;
					}
				}
			}

			$this->core->message($msg, $type);
		}

		//Generating rights
		if (isset($module_info['rights'])) {
			foreach ($module_info['rights'] AS $right => $description) {
				if (((int) $right) . "" === $right . "") {
					$right = $description;
					$description = "";
				}

				$right_obj = new CoreRightObj($right, true);
				$right_obj->right = $right;
				$right_obj->description = $description;
				if ($right_obj->save_or_insert()) {
					$this->core->message("Right \"" . $right . "\" inserted/updated", Core::MESSAGE_TYPE_SUCCESS);
				}
				else {
					$this->core->message("Right \"" . $right . "\" could not be inserted/updated", Core::MESSAGE_TYPE_ERROR);
				}
			}
		}
		$classname_module = WebAction::generate_classname($module);

		//Check if we are on a fresh module install or do just an update
		$module_object = new $classname_module();
		$modul_config = new ModulConfigObj($module);
		if ($update_succeeds) {
			$call_enable = false;
			if ($modul_config->load_success()) {
				$call_enable = ($modul_config->enabled === 0);
			}


			$modul_config->enabled = 1;
			//Module exist within the database so we do only an update
			if ($modul_config->load_success() && $modul_config->current_version != 1) {
				$this->current_version = $modul_config->current_version;
				$error = false;
				//Loop through all version which we do not have run yet.
				for ($i = $modul_config->current_version; $i <= $module_info['version']; $i++) {

					//If update fails, display message
					if (!$module_object->update($i)) {
						$error = true;
						$this->core->message(t("Could not update module @modul for version @version", array("@modul" => $module, "@version" => $i)), Core::MESSAGE_TYPE_ERROR);
						break;
					}
				}
				//Increment the current version if we succeed this update
				if (!$error) {
					$modul_config->current_version = $module_info['version'] + 1;
					$modul_config->save();
					$this->core->message(t("updated module: @modul", array("@modul" => $module)), Core::MESSAGE_TYPE_SUCCESS);
				}
			}
			else {
				//Install the module fresh
				if ($module_object->install()) {
					$modul_config->modul = $module;
					$modul_config->current_version = $module_info['version'] + 1;
					$modul_config->save_or_insert();
					$this->core->message(t("installed module: @modul", array("@modul" => $module)), Core::MESSAGE_TYPE_SUCCESS);
				}
				else {
					$this->core->message(t("Could not update module @modul", array("@modul" => $module)), Core::MESSAGE_TYPE_ERROR);
				}
			}

			// If previous the module was not enabled but present (so it was disabled) and a enable method exists for this module, call it.
			if ($call_enable) {
				if (method_exists($module_object, 'enable')) {
					$module_object->enable();
				}
			}
			if ($op == "js") {
				AjaxModul::return_code(core::GLOBEL_RETURN_CODE_SUCCESS, null, true);
			}
		}
		else {
			if ($op == "js") {
				AjaxModul::return_code(core::MESSAGE_TYPE_ERROR, null, true);
			}
			$this->core->message(t("Could not update module @modul", array("@modul" => $module)), Core::MESSAGE_TYPE_ERROR);
		}
	}

}

