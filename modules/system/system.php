<?php

/**
 * System action module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system
 */
class system extends ActionModul
{

	/**
	 * Define constances
	 */
	const CONFIG_CACHE_JS = "cache_js";
	const CONFIG_CACHE_CSS = "cache_css";
	const CONFIG_DEFAULT_LANGUAGE = "default_language";
	const CONFIG_LOGIN_HANDLER = "login_handler";
	const CONFIG_DEFAULT_PAGE = "default_page";
	const CONFIG_DEFAULT_THEME = "default_theme";

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
						'#title' => t("Modules"), //The main title
						'#link' => "/system/modules", // The main link
						'#perm' => 'admin.system.modules' //Perm needed
					),
					array(
						'#title' => t("Update Db"), //The main title
						'#link' => "/system/updatedb", // The main link
						'#perm' => 'admin.system.updatedb' //Perm needed
					),
					array(
						'#title' => t("Config"), //The main title
						'#link' => "/system/config", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
					array(
						'#title' => t("Generate classlist"), //The main title
						'#link' => "/system/generate_classlist", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
					array(
						'#title' => t("Generate smartylist"), //The main title
						'#link' => "/system/generate_smartylist", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
					array(
						'#title' => t("Reindex menu alias"), //The main title
						'#link' => "/system/reindex_menu", // The main link
						'#perm' => "admin.system.config", // perms needed
					),
				)
			)
		);
	}

	/**
	 *  Generates the classlist new
	 */
	public function generate_classlist() {
		
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			return $this->no_permission();
		}
		
		$this->core->generate_classlist();
		$this->core->message(t('classlist generated'), Core::MESSAGE_TYPE_SUCCESS);
		
		$this->clear_output();
	}

	/**
	 *  Generates the smartylist new
	 */
	public function generate_smartylist() {
		
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			return $this->no_permission();
		}
		
		if ($this->core->create_smarty_sdi()) {
			$this->core->message(t('smartylist generated'), Core::MESSAGE_TYPE_SUCCESS);
		}
		else {
			$this->core->message(t('could not generated smartylist'), Core::MESSAGE_TYPE_ERROR);
		}
		
		$this->clear_output();
	}

	/**
	 *  Reindex the menu alias
	 */
	public function reindex_menu() {
		
		//Check perms
		if (!$this->right_manager->has_perm('admin.system.config', true)) {
			return $this->no_permission();
		}
		
		if ($this->core->reindex_menu());
		$this->core->message(t('menu re-indexed'), Core::MESSAGE_TYPE_SUCCESS);
		
		$this->clear_output();
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
		$form = new SystemConfigForm($this, "system_config", t("Configuration"));
		
		$form->add(new YesNoSelectfield(self::CONFIG_CACHE_CSS, $this->core->get_dbconfig("system", self::CONFIG_CACHE_CSS, 'no'), t("Enable css cache?")), array(
			new FunctionValidator(t('Can not finde java, javascript cache can not be enabled, you need to install java first'), function($value) {
				if ($value == 'yes') {
					return (shell_exec('which java') !== null);
				}
				return true;
			})
		));
		$form->add(new YesNoSelectfield(self::CONFIG_CACHE_JS, $this->core->get_dbconfig("system", self::CONFIG_CACHE_JS, 'no'), t("Enable javascript cache?")), array(
			new FunctionValidator(t('Can not finde java, javascript cache can not be enabled, you need to install java first'), function($value) {
				if ($value == 'yes') {
					return (shell_exec('which java') !== null);
				}
				return true;
			})
		));
			
		if (!empty($this->lng)) {
			$form->add(new Selectfield(self::CONFIG_DEFAULT_LANGUAGE, $this->lng->get_enabled_languages(), $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_LANGUAGE, 'EN'), t("Default language")));
		}

		$dir = new Dir('templates', false);
		$dir->skip_dirs("images");
		$dir->just_dirs();
		$available_themes = array();
		foreach($dir AS $entry) {
			$available_themes[$entry->filename] = $entry->filename;
		}
		$form->add(new Selectfield(self::CONFIG_DEFAULT_THEME, $available_themes, $this->core->get_dbconfig("system", self::CONFIG_DEFAULT_THEME, 'standard'), t("Default theme")));

		global $classes;
		$login_handler = array();
		foreach($classes['classes'] AS $classname =>  &$class) {
			if(!empty($class['implements']) && in_array("LoginHandler", $class['implements'])) {
				$login_handler[$classname] = $classname;
			}
		}

		$form->add(new Selectfield(self::CONFIG_LOGIN_HANDLER, $login_handler, $this->core->dbconfig("system", self::CONFIG_LOGIN_HANDLER), t("Login handler")));
		$form->add(new Textfield(self::CONFIG_DEFAULT_PAGE, $this->core->dbconfig("system", self::CONFIG_DEFAULT_PAGE), t("Default page / Startpage")));

		//Execute the settings form
		$form->execute();
	}

	/**
	 * Action: modules
	 * Lists all available modules with there status. if a module is not found within database it will be listed but as disabled
	 */
	public function modules() {
		$this->session->require_login();

		//Check perms
		if (!$this->right_manager->has_perm("admin.system.modules")) {
			return $this->no_permission();
		}

		//Set title
		$this->title(t("Modul config"), t("Here you can enable or disable modules.
			A [b]disabled[/b] module can not be accessed anymore, also menu items will not be displayed"));
		$modules = array();

		//Loop through all available modules (from core dir scanning)
		foreach ($this->core->modules AS $module) {
			//skip system module couse we can not change anything there
			if ($module == "system") {
				continue;
			}

			//Try to load the module config object, if not found set it to disabled
			$mobj = new ModulConfigObj($module);
			if (!$mobj->load_success()) {
				$mobj->modul = $module;
				$mobj->enabled = false;
			}

			//Add the module to the list
			$modules[] = $mobj;
		}

		//Smarty assign
		$this->smarty->assign_by_ref("modules", $modules);
	}

	/**
	 * Action: updatedb
	 * Will on form submition update all modules
	 */
	public function updatedb() {

		$this->session->require_login();

		$form = new Form("Start update");
		$form->add(new Submitbutton("update", t("Start update")));
		$form->assign_smarty("form");

		if ($form->is_submitted()) {
			$modules = array();
			//Get all modules
			$dir = new Dir("/modules", false); //False to set recrusive off
			foreach ($this->core->modules AS $module) {
				if (!$this->core->module_enabled($module)) {
					continue;
				}
				$modules[] = $module;
			}
			
			$this->core->generate_classlist();
			$this->core->create_smarty_sdi();
			$this->core->js_config("update_db_modules", $modules);
		}
	}

	/**
	 * Action: update
	 * update a module
	 * @param string $module The module to be updated (optional, default = 'system')
	 * @param string $op the operation, if an ajax request calls this, usually this needs "js" (optional, default = '')
	 */
	public function update($module = "system", $op = '') {
		$this->install($module, $op, true);
	}
	
	/**
	 * Action: install
	 * Install or update a module
	 * @param string $module The module to be installed or updated (optional, default = 'system')
	 * @param string $op the operation, if an ajax request calls this, usually this needs "js" (optional, default = '')
	 * @param boolean $update
	 *   if we only update, if set to true it will not install an unconfigured module
	 *   (optional, default = false)
	 */
	public function install($module = "system", $op = '', $update = false) {

		$this->clear_output();


		//Check only perms if we have installed the system before (first time a fresh install will pass the perm check)
		if ($this->core->dbconfig("system", "installed") == "1" && $this->core->module_enabled("user")) {
			$this->session->require_login();
			//Check perms
			if (!$this->right_manager->has_perm("admin.system.modules")) {
				return $this->no_permission();
			}
		}


		$installed = false;

		//We do not need to generate every javascript request to generate the classlist, direct after form submission or direkt call is more enough
		if ($op != "js") {
			$this->core->generate_classlist();
			$this->core->create_smarty_sdi();
		}

		//Check if the provided module is a valid module (has a valid module info file)
		$info_file = SITEPATH."/modules/".$module."/".$module.".info";
		if (!file_exists($info_file)) {
			$this->core->message("\"".$module.".info\" file is missing within module dir: \"modules/".$module."\"", Core::MESSAGE_TYPE_ERROR);
			return;
		}

		//Get the module information
		$module_info = parse_ini_file($info_file, true);
		$module_info['version'] = (int)$module_info['version'];

		$results = array();

		//Creating Database tables from objects if the table does not exist.
		$dir = new Dir("modules/".$module."/objects");

		foreach ($dir AS $entry) {
			if (preg_match("/(.*)\.class\.php$/", $entry->filename, $matches)) {
				$obj = $matches[1];

				$results[$obj] = $this->db->mysql_table->create_database_from_object(new $obj());
			}
		}
		
		//Loop through each results. and check if the creation was success, if not we need to update the current table
		foreach ($results AS $obj => $result) {
			$msg = "Created Database table for object: ".$obj;
			$type = Core::MESSAGE_TYPE_SUCCESS;
			if (empty($result)) {
				//Get the object which we want to create
				$mobj = new $obj();

				//Get the tablename
				$table = $mobj->get_dbstruct()->get_table();

				//Check if the table realy exist.
				if ($this->db->query_slave("SELECT 1 FROM `".$table."`")) {

					//These fields must be changed again for auto_increment after wie added the field.
					$add_fields = array();

					//Get the old database fields to check wether we must rename, modify, delete or add the field
					$db_fields = $this->db->get_table_fields($table);

					//Get the current object fields
					$obj_fields = $mobj->get_dbstruct()->get_struct();

					//Get the database primary keys
					$database_primary_keys = $this->db->get_primary_key($table, true);

					//Get the new primary keys
					$object_primary_keys = $mobj->get_dbstruct()->get_reference_key();

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
							 * if the found field is a primary key we can not add it as a new field, we must just rename it
							 *
							 */
							foreach ($db_fields AS $field_name => $db_options) {
								if ($object_index == $db_options['ORDINAL_POSITION']) {
									if ($db_options['COLUMN_KEY'] == 'PRI') {
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
					//Change all fresh added fields but now with the auto increment value
					foreach ($add_fields AS $field_option) {
						$this->db->change_table_field($table, $field_option['field'], $field_option['options'], $field_option['ai'], true);
					}

					//Run the queued sql statements
					$this->db->alter_table_queue($table);
					$msg = "Database table already exists, or now up to date: ".$obj;
					$type = Core::MESSAGE_TYPE_SUCCESS;
				}
				else {
					$msg = "Could not create Database table for object: ".$obj;
					$type = Core::MESSAGE_TYPE_ERROR;
				}
			}

			if ($module == "system" && $type != Core::MESSAGE_TYPE_ERROR) {
				$installed = true;
			}

			$this->core->message($msg, $type);
		}
		
		//Generating rights
		if (isset($module_info['rights'])) {
			foreach ($module_info['rights'] AS $right) {
				$right_obj = new CoreRightObj();
				$right_obj->right = $right;
				$right_obj->insert(false);
				$this->core->message("Right \"".$right."\" inserted", Core::MESSAGE_TYPE_SUCCESS);
			}
		}
		
		//If we are not updateing the system module we must call the module update method to perform maybe needed actions on a module update
		if ($module != "system") {
			
			//Check if we are on a fresh module install or do just an update
			$module_object = new $module();
			$modul_config = new ModulConfigObj($module);

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
				}
			}
			else {
				//Install the module fresh
				if ($module_object->install()) {
					$modul_config->modul = $module;
					$modul_config->current_version = $module_info['version'] + 1;
					$modul_config->save_or_insert();
				}
				else {
					$this->core->message(t("Could not update module @modul", array("@modul" => $module)), Core::MESSAGE_TYPE_ERROR);
				}
			}
		}

		if ($op == "js") {
			AjaxModul::return_code(core::GLOBEL_RETURN_CODE_SUCCESS, null, true);
		}
	}

}

?>