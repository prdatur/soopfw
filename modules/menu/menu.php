<?php

/**
 * User action menu
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.menu
 */
class menu extends ActionModul
{

	//Default method
	protected $default_methode = "overview";

	/**
	 * Implementation of get_admin_menu()
	 * @return array the menu
	 */
	public function get_admin_menu() {
		return array(
			888 => array(//Order id, same order ids will be unsorted placed behind each
				'#id' => 'soopfw_menu', //A unique id which will be needed to generate the submenu
				'#title' => t("Menu"), //The main title
				'#link' => "/admin/menu", // The main link
				'#perm' => 'admin.menu', //Perm needed
				'#childs' => array(
					array(
						'#title' => t("Menu"), //The main title
						'#link' => "/admin/menu/overview", // The main link
					),

				)
			)
		);
	}

	public function __init() {
		parent::__init();
		//Need to be logged in
		$this->session->require_login();

		//Check perms
		if (!$this->right_manager->has_perm("admin.menu.manage")) {
			return $this->no_permission();
		}
	}

	/**
	 * Action: overview
	 * Display and/or search all users
	 */
	public function overview() {

		$this->title(t("Overview"));

		//Get menus and assign it
		$this->smarty->assign_by_ref("menus", $this->db->query_slave_all("SELECT * FROM `".MenuObj::TABLE."`"));
	}

	/**
	 * Action: change_menu
	 * Insert or add a menu
	 * if $menu_id is provided, it will change this menu (save), else insert a new menu
	 *
	 * @param int $menu_id the menu_id(optional, default = 0)
	 */
	public function change_menu($menu_id = ""){


		$force_loaded = false;

		//Save variables
		if (!empty($menu_id)) { //edit mode
			$this->title(t("Save menu"));

			//Load object
			$menu_obj = new MenuObj($menu_id);

			//Add save button
			$submit_button = new Submitbutton("save", t("save"));

			//Set form title
			$message = t("menu changed");
		}
		else {
			$this->title(t("add menu"));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add"));

			//Set form title
			$message = t("menu added");

			//Create empty object and prefill with primary key
			$menu_obj = new MenuObj();

			//Set the force loaded to true, else the objForm will fill out the the default values
			$force_loaded = true;
			$menu_obj->menu_id = $menu_id;
		}

		$this->static_tpl = 'form.tpl';

		//Init objForm
		$obj_form = new ObjForm($menu_obj, '', array(), $force_loaded);

		//Enable ajax
		$obj_form->set_ajax(true);

		//Add the button
		$obj_form->add($submit_button);

		//Add success ajax call to close the dialog
		$obj_form->add_js_success_callback("close_menu_dialog");
		if (empty($menu_id)) { //Insert mode
			$obj_form->add_js_success_callback("add_new_menu_row");
		}
		else { //edit mode
			$obj_form->add_js_success_callback("replace_new_menu_row");
		}

		//Assign form to smarty
		$obj_form->assign_smarty("form");

		//Check if form was submitted
		if ($obj_form->check_form()) {

			//Check if the insert command returned true
			if ($obj_form->save_or_insert()) {
				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message($message, Core::MESSAGE_TYPE_SUCCESS, $obj_form->is_ajax(), $obj_form->get_object()->get_values(true));
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not save menu"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
			}
		}
	}

	/**
	 * Action: entries
	 * Displays menu entries and provide the possibility to change menu orders
	 *
	 * @param int $menu_id the menu_id
	 */
	public function entries($menu_id) {

		$menu_entry_obj = new MenuObj($menu_id);
		if(!$menu_entry_obj->load_success()) {
			return $this->wrong_params("No such menu");
		}

		$this->title(	t("\"@menu\" menu entries", array("@menu" => $menu_entry_obj->title)),
						t("Change the order of one or more menu entries and add manually menu entries"));

		$array_2_tree = new Array2Tree();
		foreach($this->db->query_slave_all("SELECT `entry_id`, `parent_id`, `order` FROM `".MenuEntryObj::TABLE."` WHERE menu_id = @menu_id", array('@menu_id' => $menu_id)) AS $menu_entry) {

			$translations = $this->db->query_slave_all("SELECT * FROM `".MenuEntryTranslationObj::TABLE."` WHERE entry_id = ientry_id", array('ientry_id' => $menu_entry['entry_id']), 0,0,'language');
			$menu_translation = null;
			if(isset($translations[$this->core->current_language])) {
				$menu_translation = $translations[$this->core->current_language];
			}
			else {
				$menu_translation = current($translations);
			}
			$menu_entry = array_merge($menu_entry, $menu_translation);
			$array_2_tree->add_item($menu_entry);

		}
		$this->smarty->assign_by_ref("menus", $array_2_tree->get_tree());

		$this->core->js_config("menu_entries_menu_id", $menu_id);
	}

	/**
	 * Action: change_menu
	 * Insert or add a menu
	 * if $menu_id is provided, it will change this menu (save), else insert a new menu
	 *
	 * @param int $menu_id
	 *   the menu_id
	 * @param int $entry_id
	 *   the entry_id (optional, default = 0)
	 */
	public function change_menu_entry($menu_id, $entry_id = 0){


		$force_loaded = false;

		$loaded = false;

		/**
		 * Because we can "translate" an already existing entry the $entry_id could be not empty but must
		 * be handled as "insert" if the entry_id with current language does not exists
		 */
		if (!empty($entry_id)) {
			//Load object
			$menu_entry_obj = new MenuEntryTranslationObj($entry_id);
			if($menu_entry_obj->load_success()) { //edit mode
				$loaded = true;
			}
		}
		//Save variables
		if ($loaded) { //edit mode
			$this->title(t("Save menu entry"));



			//Add save button
			$submit_button = new Submitbutton("save", t("save menu entry"));

			//Set form title
			$message = t("menu entry changed");
		}
		else {
			$this->title(t("add menu entry"));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add menu entry"));

			//Set form title
			$message = t("menu entry added");

			$this->core->cache("menu_entry", "insert_menu_id", $menu_id);
			//Create empty object and prefill with primary key
			$menu_entry_obj = new MenuEntryTranslationObj();

			//Set the force loaded to true, else the objForm will fill out the the default values
			$force_loaded = true;
			$menu_entry_obj->entry_id = $entry_id;
			$menu_entry_obj->language = $this->core->current_language;
		}

		$this->static_tpl = 'form.tpl';

		//Init objForm
		$obj_form = new ObjForm($menu_entry_obj, '', array(), $force_loaded);

		//Enable ajax
		$obj_form->set_ajax(true);

		//Add the button
		$obj_form->add($submit_button);

		//Add success ajax call to close the dialog
		$obj_form->add_js_success_callback("entry_changed");

		//Assign form to smarty
		$obj_form->assign_smarty("form");


		//Check if form was submitted
		if ($obj_form->check_form()) {
			$pure_alias = $obj_form->get("destination");
			$pure_alias = preg_replace("/^\//","", $pure_alias);
			$pure_alias = preg_replace("/\.[^\.]+$/","", $pure_alias);

			$additional_sql_check = "";
			$sql_args = array('@alias' => $pure_alias, '@destination' => $obj_form->get("destination"));
			if ($loaded) {
				$additional_sql_check = ' AND et.entry_id != @self_entry_id';
				$sql_args['@self_entry_id'] = $entry_id;
			}

			//Return an error if the content page already exists within the menu structure (all menus will be checked)
			$entry_exists  = $this->db->query_slave_first("
				SELECT 1
				FROM `".MenuEntryObj::TABLE."` e
				JOIN `".MenuEntryTranslationObj::TABLE."` et ON (et.entry_id = e.entry_id)
				JOIN `".UrlAliasObj::TABLE."` al ON (al.alias = @alias)
				WHERE et.destination = @destination AND al.module = 'content' AND al.action = 'view'" . $additional_sql_check, $sql_args);
			if(!empty($entry_exists)) {
				//Setup error message to display
				$this->core->message(t("Could not save menu, this content page is already linked within a menu."), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
				return;
			}

			//Check if the insert command returned true
			if ($obj_form->save_or_insert()) {
				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message($message, Core::MESSAGE_TYPE_SUCCESS, $obj_form->is_ajax(), $obj_form->get_object()->get_values(true));
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not save menu entry"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
			}
		}
	}


	/**
	 * Install additional data
	 *
	 * @return boolean
	 *   if called from wrong method
	 */
	public function install() {
		if (!parent::install()) {
			$this->clear_output();
			return false;
		}

		// Install default main menu.
		$menu_obj = new MenuObj();
		$menu_obj->menu_id = 'main_menu';
		$menu_obj->title = 'Mainmenu';
		$menu_obj->insert();

		// Insert a menu entry for the "home" entry.
		$menu_item = new MenuEntryObj();
		$menu_item->menu_id = 'main_menu';
		$menu_item->order = 1;
		$menu_item->insert();

		$menu_tranlsation_item = new MenuEntryTranslationObj();
		$menu_tranlsation_item->entry_id = $menu_item->entry_id;
		$menu_tranlsation_item->language = 'en';
		$menu_tranlsation_item->title = 'Home';
		$menu_tranlsation_item->destination = '/';
		$menu_tranlsation_item->insert();

		$menu_tranlsation_item->language = 'de';
		$menu_tranlsation_item->insert();

		// Insert a menu entry for the "login" entry.
		$menu_item = new MenuEntryObj();
		$menu_item->menu_id = 'main_menu';
		$menu_item->order = 2;
		$menu_item->insert();

		$menu_tranlsation_item = new MenuEntryTranslationObj();
		$menu_tranlsation_item->entry_id = $menu_item->entry_id;
		$menu_tranlsation_item->language = 'en';
		$menu_tranlsation_item->title = 'Login';
		$menu_tranlsation_item->destination = '/user/login';
		$menu_tranlsation_item->insert();

		$menu_tranlsation_item->language = 'de';
		$menu_tranlsation_item->title = 'Anmelden';
		$menu_tranlsation_item->insert();

		return true;
	}
}

?>