<?php

/**
 * Main "Content" module file.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Content
 * @category Module
 */
class Content extends ActionModul implements Widget
{
	//Default method
	protected $default_methode = "list_content_types";

	/**
	 * Define config variables.
	 */
	const CONTENT_SOLR_SERVER = 'solr_server';
	const CONTENT_SOLR_INDEXED_TYPES = 'solr_index_types';
	const CONTENT_SITEMAP_INDEXED_TYPES = 'sitemap_index_types';

	/**
	 * Define possible widgets.
	 */
	const WIDGET_VIEWS = 'views';

	/**
	 * Implements hook: admin_menu
	 *
	 * Returns an array which includes all links and childs for the admin menu.
	 * There are some special categories in which the module can be injected.
	 * The following categories are current supported:
	 *   style, security, content, structure, authentication, system, other
	 *
	 * @return array the menu
	 */
	public function hook_admin_menu() {


		$content_types = $create_types = array();
		foreach ($this->db->query_slave_all("SELECT * FROM `" . ContentTypeObj::TABLE . "`") AS $content_type) {

			$content_type_views = DatabaseFilter::create(ContentTypeViewObj::TABLE)
				->add_where(ContentTypeViewObj::FIELD_CONTENT_TYPE, $content_type['content_type']);

			$views = array();
			foreach ($content_type_views->select_all() AS $view) {
				$views[] = array(
					'#title' => $view[ContentTypeViewObj::FIELD_ID], //The main title
					'#link' => "/admin/content/change_view/" . $content_type['content_type'] . '/' . $view[ContentTypeViewObj::FIELD_ID], // The main link
					'#perm' => 'admin.content.manage', //Perm needed
					'#childs' => array(
						array(
							'#title' => t("Show the view"), //The main title
							'#link' => "/content/views/" . $view[ContentTypeViewObj::FIELD_ID], // The main link
							'#perm' => 'admin.content.manage', //Perm needed
						),
					),
				);
			}

			$content_types[] = array(
				'#title' => $content_type['display_name'], //The main title
				'#link' => "/admin/content/manage_content_type/" . $content_type['content_type'], // The main link
				'#childs' => array(
					array(
						'#title' => t("Manage fields"), //The main title
						'#link' => "/admin/content/manage_content_type_fields/" . $content_type['content_type'], // The main link
						'#perm' => 'admin.content.manage', //Perm needed
					),
					array(
						'#title' => t("Views"), //The main title
						'#link' => "javascript:void(0)", // The main link
						'#perm' => 'admin.content.manage', //Perm needed
						'#childs' => $views,
					),
					array(
						'#title' => t("Create view"), //The main title
						'#link' => "/admin/content/change_view/" . $content_type['content_type'], // The main link
						'#perm' => 'admin.content.manage', //Perm needed
					),
				),
			);
			$create_types[] = array(
				'#title' => $content_type['display_name'], //The main title
				'#link' => "/admin/content/create/" . $content_type['content_type'] // The main link
			);
		}
		return array(
			AdminMenu::CATEGORY_CONTENT => array(
				'#id' => 'soopfw_content_type', //A unique id which will be needed to generate the submenu
				'#title' => t("Content"), //The main title
				'#link' => "/admin/content", // The main link
				'#perm' => 'admin.content', //Perm needed
				'#childs' => array(
					array(
						'#title' => t("Config"), //The main title
						'#link' => "/admin/content/config", // The main link
						'#perm' => 'admin.content.manage', //Perm needed
					),
					array(
						'#title' => t("Content types"), //The main title
						'#link' => "/admin/content/list_content_types", // The main link
						'#perm' => 'admin.content.manage', //Perm needed
						'#childs' => $content_types
					),
					array(
						'#title' => t("Create content"), //The main title
						'#link' => "/admin/content/create", // The main link
						'#perm' => 'admin.content.manage', //Perm needed
						'#childs' => $create_types
					),
					array(
						'#title' => t("List content"), //The main title
						'#link' => "/admin/content/list_content", // The main link
						'#perm' => 'admin.content.create', //Perm needed
						'#childs' => array(
							array(
								'#title' => t("Unreachable content"), //The main title
								'#link' => "/admin/content/list_unreachable_content_types", // The main link
								'#perm' => 'admin.content.create', //Perm needed
							),
						),
					),
				),
			),
		);
	}

	public function __init() {
		parent::__init();

		if ($this->action != 'view') {
			//Need to be logged in
			$this->session->require_login();
		}
	}

	/**
	 * Implements hook: sitemap_section
	 *
	 * All modules which implements hook_sitemap_section() must implement this method.
	 * Each hook call will provide the array of all sections which we want back, so each
	 * module needs to switch on the provided section if the choosen configuration want the section.
	 *
	 * @param array $sections
	 *   the sections which we want back.
	 *
	 * @return array
	 *   An array with all site pathes excluding the protocoll and domain, just the path.
	 *   If you want to provide the last modified time or the update frequenz
	 *   please provide an array as the value for the array.
	 *   this "entry" array can have the following keys:
	 *     'loc' => the path what you normaly return as the single array value.
	 *     'changefreq' => the frequenz based up on changefreq http://www.sitemaps.org/protocol.html
	 *     'lastmod' => the last modifiction date as YYYY-MM-DD
	 *     'priority' => the priority (default priority is 0.5)
	 */
	public function hook_sitemap_section() {
		$filter = DatabaseFilter::create(ContentTypeObj::TABLE)
				->add_column('content_type');

		$content_types = array();
		foreach ($filter->select_all(0, true) AS $content_type) {
			$content_types['content::' . $content_type] = t("Content: @content_type", array('@content_type' => $content_type));
		}

		return $content_types;
	}

	/**
	 * Provides hook: sitemap_get_entries
	 *
	 * All modules which implements hook_sitemap_section() must implement this method.
	 * Each hook call will provide the array of all sections which we want back, so each
	 * module needs to switch on the provided section if the choosen configuration want the section.
	 *
	 * @param array $sections
	 *   the sections which we want back.
	 *
	 * @return array
	 *   An array with all site pathes excluding the protocoll and domain, just the path.
	 *   If you want to provide the last modified time or the update frequenz
	 *   please provide an array as the value for the array.
	 *   this "entry" array can have the following keys:
	 *     'loc' => the path what you normaly return as the single array value.
	 *     'changefreq' => the frequenz based up on changefreq http://www.sitemaps.org/protocol.html
	 *     'lastmod' => the last modifiction date as YYYY-MM-DD
	 *     'priority' => the priority (default priority is 0.5)
	 */
	public function hook_sitemap_get_entries($sections) {

		$generate = false;
		$where = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
		foreach ($sections AS $k => $val) {
			if (preg_match("/^content::(.+)$/", $k, $matches)) {
				$generate = true;
				$where->add_where('content_type', $matches[1]);
			}
		}

		if ($generate == false) {
			return array();
		}

		$results = array();

		$filter = DatabaseFilter::create(PageObj::TABLE)
				->add_column('page_id')
				->add_column('language')
				->add_column('last_modified')
				->add_where('deleted', 'no')
				->add_where($where);

		foreach ($filter->select_all() AS $row) {
			$alias = $this->get_alias_for_page_id($row['page_id'], $row['language']);
			$url = '/' . $row['language'] . '/content/view/' . $row['page_id'];
			if ($alias !== false) {
				$url = '/' . $alias . '.html';
			}

			$results[] = array(
				'loc' => $url,
				'lastmod' => date('Y-m-d', strtotime($row['last_modified'])),
			);
		}

		return $results;
	}

	/**
	 * Action: config
	 *
	 * Configurate the docu settings.
	 */
	public function config() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.content.manage', true)) {
			throw new SoopfwNoPermissionException();
		}

		//Setting up title and description
		$this->title(t("Content Config"), t("Here we can configure the main content settings"));

		//Configurate the settings form
		$form = new SystemConfigForm($this, "content_config");

		$form->add(new Fieldset('main', t('Main')));



		if ($this->core->module_enabled('solr')) {

			// Solr server.
			$old_solr_server = $this->core->get_dbconfig("content", self::CONTENT_SOLR_SERVER, 'none');
			$factory = new SolrFactory();
			$form->add(new Selectfield(self::CONTENT_SOLR_SERVER, $factory->get_all_instances(true), $old_solr_server, t("Solr server")));

			// Solr index types.
			$delete_check = array();
			$content_types = array();

			$filter = DatabaseFilter::create(ContentTypeObj::TABLE)
					->add_column('content_type');

			foreach ($filter->select_all(0, true) AS $content_type) {
				$content_types[$content_type] = t("Content: @content_type", array('@content_type' => $content_type));
				$delete_check[$content_type] = $content_type;
			}

			$old_values = $this->core->get_dbconfig("content", self::CONTENT_SOLR_INDEXED_TYPES, array());
			foreach ($old_values AS $k => $v) {
				$delete_check[$k] = $k;
			}

			$form->add(new Checkboxes(self::CONTENT_SOLR_INDEXED_TYPES, $content_types, $old_values, t('Indexed content'), t('Only the checked content types will be indexed within Solr')));
		}

		//Execute the settings form
		if ($form->execute()) {

			if ($this->core->module_enabled('solr')) {

				$skip_delete = false;
				$new_solr_server = $this->core->get_dbconfig("content", self::CONTENT_SOLR_SERVER, 'none');

				// It was already disabled dont do anything.
				if ($new_solr_server === 'none' && $old_solr_server === 'none') {
					return;
				}

				// If we changed the solr server, be sure to remove all entries for the old server.
				if (!empty($old_solr_server) && $old_solr_server !== 'none' && $old_solr_server != $new_solr_server) {
					$skip_delete = true;
					$solr = SolrFactory::create_instance($old_solr_server);
					if ($solr !== false) {
						$solr->deleteByQuery('type:content');
					}

					// We have choose to disable solr service so just commit the delete and return.
					if ($new_solr_server === 'none') {
						$solr->commit();
						return;
					}
				}


				$force_insert_all = false;
				if (!empty($new_solr_server) && $new_solr_server !== 'none' && $old_solr_server != $new_solr_server) {
					$force_insert_all = true;
				}


				$add_types = false;

				$new_index = $this->core->get_dbconfig("content", self::CONTENT_SOLR_INDEXED_TYPES, array());
				$add_filter = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);

				// Check if we need to direct add content types because it was checked.
				foreach ($new_index AS $key => $val) {

					// Only reindex the selected content type if it was not checked before.
					if ($force_insert_all === true || !isset($old_values[$key])) {
						$add_filter->add_where('content_type', $key);
						$add_types = true;
					}

					// We need to check on $delete_check because this will be the merged values
					// For current content types and previous selected.
					// This is needed because maybe we removed a content type but did not removed it from the
					// Index state.
					if (isset($delete_check[$key])) {
						unset($delete_check[$key]);
					}
				}

				if ($add_types === true) {
					$filter = DatabaseFilter::create(PageObj::TABLE)
							->add_column('page_id')
							->add_column('language')
							->add_column('content_type')
							->add_where('last_revision', '', '!=')
							->add_where($add_filter);

					foreach ($filter->select_all() AS $row) {
						$revision = new PageRevisionObj($row['page_id'], $row['language']);
						if (!$revision->load_success()) {
							continue;
						}
						$revision->update_solr($row['content_type'], false);
					}
				}

				if ($skip_delete === false) {
					$solr = SolrFactory::create_instance('content', Content::CONTENT_SOLR_SERVER);
					if ($solr !== false) {
						// Those which are within this array are the old indexed content types which we removed now so delete them.
						foreach ($delete_check AS $key => $val) {
							$solr->deleteByQuery('contenttype_s:' . $key);
						}
					}
				}
			}
		}
	}

	/**
	 * Action: change_view
	 *
	 * Creates or save a view for a given content type.
	 *
	 * @param string $content_type
	 *   The content type.
	 * @param string $view_name
	 *   the view name, if provided we will edit the view name. (optional, default = '')
	 */
	public function change_view($content_type, $view_name = '') {
		if (!$this->right_manager->has_perm('admin.content.manage', true)) {
			throw new SoopfwNoPermissionException();
		}

		$ct = new ContentTypeObj($content_type);
		if (!$ct->load_success()) {
			throw new SoopfwWrongParameterException(t('No such content type'));
		}
		$view_obj = new ContentTypeViewObj($view_name);
		// If we provided a view name we want to edit it, so we need to verify that the view exist.
		if ($view_name !== '' && $view_obj->load_success() === false) {
			throw new SoopfwWrongParameterException(t('No such view'));
		}

		if ($view_obj->load_success()) {
			$title = t('Change view "@view"', array('@view' => $view_obj->name));
		}
		else {
			$title = t('Create view for content type "@content_type"', array('@content_type' => $ct->display_name));
		}
		$this->static_tpl = 'form.tpl';

		$form = new Form('create_view');

		$view_id_obj = $view_obj->get_dbstruct()->get_textfield(ContentTypeViewObj::FIELD_ID, NS, $view_obj->id, t('Please provide a system readable string.<br>Tthis is a string which contains only alphanumeric letteres (a-z, 0-9) and the _ (underline), the first character <b>MUST</b> be an alphabetic character (a-z).<br>If you provide upper case characters they will be transformed to lower case'));

		$form->add($view_id_obj, array(
			new RegexpValidator(t('Only alphanumeric and underline letteres are allowed and it must start with an alphabetic lettere'), '/^[a-z][a-z0-9_]+$/'),
		));

		// This will be set to the view id which we should delete after we change the id, this is needed because the view id is the primary key.
		$delete_old_one = false;
		if ($view_obj->load_success() === false || $view_obj->id !== $view_id_obj->get_value()) {
			$delete_old_one = $view_obj->id;
			$view_id_obj->add_validator(new NotExistValidator(t('This view id is already in use, please choose a different.'), array('content_type_views' => ContentTypeViewObj::FIELD_ID)));
		}


		$form->add($view_obj->get_dbstruct()->get_textfield(ContentTypeViewObj::FIELD_NAME, NS, $view_obj->name), array(
			new RequiredValidator(),
		));

		$this->core->add_js("/js/jquery_plugins/jquery.tablednd.js");

		$filter = DatabaseFilter::create(ContentTypeFieldGroupObj::TABLE)
				->add_where(ContentTypeViewObj::FIELD_CONTENT_TYPE, $content_type);

		$view_fields = $sort_fields = array(
			'title' => t('Title'),
			'created' => t('Date of creation'),
			'created_by' => t('Author'),
			'last_modified' => t('Date of last change'),
			'last_modified_by' => t('User of last modification'),
			'last_access' => t('Date of last view'),
		);
		foreach ($filter->select_all() AS $row) {
			$view_fields[$row['id']] = $row['name'];
			if ($row['max_value'] == "1") {
				$sort_fields[$row['id']] = $row['name'];
			}
		}

		$displayed_field_values = json_decode($view_obj->displayed_fields, true);
		if (empty($displayed_field_values)) {
			$displayed_field_values = array();
		}

		$form->add(new ColumnCheckboxes('displayed_fields', $view_fields, $displayed_field_values, 1, t('Displayed fields')));

		$form->add($view_obj->get_dbstruct()->get_textfield(ContentTypeViewObj::FIELD_TRUNCATE_CHARS, NS, $view_obj->truncate_chars));
		$form->add($view_obj->get_dbstruct()->get_selectfield(ContentTypeViewObj::FIELD_TRUNCATE_POLICY, NS, $view_obj->truncate_policy));

		$db_sort_values = array();
		if (!empty($_POST) && isset($_POST['sort'])) {
			$tmp_fields = $sort_fields;
			$sort_fields = array();
			foreach ($_POST['sort'] AS $values) {
				$id = key($values);
				if (isset($tmp_fields[$id])) {
					$sort_fields[$id] = $tmp_fields[$id];
				}
			}
		}
		else {

			$db_sort_values = json_decode($view_obj->sort_fields, true);
			if (!empty($db_sort_values)) {

				$tmp_fields = $sort_fields;
				$sort_fields = array();
				foreach ($db_sort_values AS $id => $enabled) {
					if (isset($tmp_fields[$id])) {
						$sort_fields[$id] = $tmp_fields[$id];
						unset($tmp_fields[$id]);
					}
				}
				$sort_fields = $sort_fields + $tmp_fields;
			}
		}

		$sort_values = '
			<table class="tablednd ui-widget-content" id="sort">
				<thead>
					<tr>
						<td></td>
						<td>' . t('Enabled') . '</td>
						<td>' . t('Field') . '</td>
						<td>' . t('Direction') . '</td>
					</tr>
				</head>
				<tbody>';

		$sort_inputs = array();
		$i = 0;
		foreach ($sort_fields AS $id => $name) {

			$enable_sort_chk = new Checkbox('sort[' . $i . '][' . $id . '][enable]', 1, isset($db_sort_values[$id]));
			$sort_field = new Selectfield('sort[' . $i . '][' . $id . '][direction]', array(
						'asc' => t('Asc'),
						'desc' => t('Desc'),
							), (isset($db_sort_values[$id]) ? $db_sort_values[$id] : 'asc'));
			$sort_values .= '<tr>';
			$sort_values .= '<td class="handle_cell" style="width:20px;vertical-align:top;padding-top:5px;padding-left:5px;"><a class="tabledrag-handle" href="javascript:void(0);" title="' . t("drag and drop to move") . '"><div class="handle">&nbsp;</div></a></td>';
			$sort_values .= '<td style="text-align: center">' . $enable_sort_chk->fetch() . '</td>';
			$sort_values .= '<td style="text-align: left">' . $name . '</td>';
			$sort_values .= '<td style="text-align: right">' . $sort_field->fetch() . '</td>';
			$sort_values .= '</tr>';
			$sort_inputs[] = $enable_sort_chk;
			$sort_inputs[] = $sort_field;
			$i++;
		}

		$sort_values .= '</tbody></table>';

		$sort_field_html = new HtmlContainerInput($sort_values);
		$sort_field_html->config('label', t('Sort on'));
		$sort_field_html->config('description', t('Please select the fields where you want to sort (<b>only on selected fields will be sorted</b>).<br>
With "<b>drag and drop</b>" you can choose the field order, the field which is on top will be first sorted, then the next one and so on...<br>
Notice: You can only select fields which are no multi fields (max value needs to be set to 1)'));
		$form->add($sort_field_html);

		$pager_values = array();

		if ($view_obj->use_pager == ContentTypeViewObj::PAGER_ENABLED) {
			$pager_values[ContentTypeViewObj::PAGER_ENABLED] = ContentTypeViewObj::PAGER_ENABLED;
		}
		$form->add(new Checkboxes(ContentTypeViewObj::FIELD_USE_PAGER, array(ContentTypeViewObj::PAGER_ENABLED => t('Enable paging')), $pager_values, t('Paging')));
		$form->add($view_obj->get_dbstruct()->get_textfield(ContentTypeViewObj::FIELD_MEPP, NS, $view_obj->mepp));

		if ($form->check_form()) {

			$values = $form->get_values();
			$values[ContentTypeViewObj::FIELD_ID] = strtolower($values[ContentTypeViewObj::FIELD_ID]);
			$values[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS] = json_encode($values[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS]);
			$values[ContentTypeViewObj::FIELD_USE_PAGER] = isset($values[ContentTypeViewObj::FIELD_USE_PAGER][ContentTypeViewObj::PAGER_ENABLED]);
			$values[ContentTypeViewObj::FIELD_CONTENT_TYPE] = $content_type;

			$sort_values = array();
			foreach ($_POST['sort'] AS $s_values) {
				$id = key($s_values);
				if (empty($s_values[$id]['enable'])) {
					continue;
				}
				$sort_values[$id] = $s_values[$id]['direction'];
			}
			$values[ContentTypeViewObj::FIELD_SORT_FIELDS] = json_encode($sort_values);

			$view_obj->set_fields($values);
			if ($view_obj->save_or_insert()) {
				if ($delete_old_one !== false) {
					$delete_view = new ContentTypeViewObj($delete_old_one);
					$delete_view->delete();
				}
				$this->core->message(t('View successfully created/updated'), Core::MESSAGE_TYPE_SUCCESS);
				if (empty($view_name)) {
					$this->core->location('/admin/content/change_view/' . $content_type . '/' . $values[ContentTypeViewObj::FIELD_ID]);
				}
			}
			else {
				$this->core->message(t('Could not save view'), Core::MESSAGE_TYPE_ERROR);
			}
		}
	}

	/**
	 * Action: views
	 *
	 * Display a view.
	 * @param string $view_name
	 *   The view name.
	 */
	public function views($view_name) {
		$ct_helper = new ContentHelper();
		$clean_uuid = 'no_widget';
		$this->smarty->append(array('views' => array($clean_uuid => $ct_helper->get_view($view_name, $static_tpl))), '', true);
		$this->smarty->assign_by_ref('widget_id', $clean_uuid);

		$view = new ContentTypeViewObj($view_name);
		$this->title($view->name);
		$this->static_tpl = $static_tpl;
	}

	/**
	 * Action: view
	 *
	 * View a content page
	 * Revision can be provided but then the current user must have the permission to create content
	 *
	 * @param int $page_id
	 *   the page id
	 * @param int $revision
	 *   the revision to show (optional, default = '')
	 * @param boolean $return_html
	 *   if set to true the content will returned instead of smarty assigned (optional, default = false)
	 */
	public function view($page_id, $revision = '', $return_html = false) {

		$page_data_array = explode("|", $page_id, 2);
		if (!isset($page_data_array[1])) {
			$page_data_array[1] = '';
		}

		// Check if we have called this page with default content/view/{page_id} and if so check if we have an alias which is the better choice.
		// But only if do show the content, not within return mode.
		if ($return_html === false && preg_match("/content\/view\/[0-9]+/i", $_SERVER['REQUEST_URI'])) {
			$alias = $this->get_alias_for_page_id($page_data_array[0], $page_data_array[1]);
			if (!empty($alias)) {
				$this->core->location('/' . $alias . '.html');
			}
		}
		$page_id = $page_data_array[0];
		$page = new PageObj($page_data_array[0], $page_data_array[1]);
		if (!$page->load_success()) {
			throw new SoopfwWrongParameterException(t("No such page"));
		}

		if ($page->deleted == 'yes' && !$this->right_manager->has_perm("admin.content.delete", false)) {
			throw new SoopfwWrongParameterException(t("No such page"));
		}

		if ((empty($page->last_revision) || !empty($revision)) && !$this->right_manager->has_perm("admin.content.create", false)) {
			throw new SoopfwWrongParameterException(t("No such page"));
		}

		$page_revision = new PageRevisionObj($page_data_array[0], $page_data_array[1], $revision);
		if (!$page_revision->load_success()) {
			throw new SoopfwWrongParameterException(t("No such page"));
		}
		$page->view_count++;
		$page->last_access = date(DB_DATETIME, TIME_NOW);
		$page->save();
		$values = $page->get_values()+$page_revision->get_values();

		$this->title($values['title']);

		$data_array = json_decode($values['serialized_data'], true);
		$content_smarty = new Smarty();
		$content_smarty->enableSecurity(); //Can not be transformed into underscore couse this comes from original smarty class
		$content_smarty->init();

		$implemented_field_groups = array();

		/**
		 * Provides hook: content_add_field_groups
		 *
		 * Allow other modules to add content type fields group.
		 *
		 * The Returning array values are not the direct field values instead
		 * each array value represents a content type field group which is also an array.
		 * The key is used as the unique field id and NEED to be the classname of the field.
		 *
		 * The following values are required for a content field group:
		 *  - template => The full path to the default template file (without SITEPATH).
		 *  - label => The label for this content type field group
		 *
		 * @return array An array with the additional content type fields
		 */
		$additional_field_groups = $this->core->hook('content_add_field_groups');
		if (!empty($additional_field_groups)) {
			foreach ($additional_field_groups AS $return_values) {
				if (!empty($return_values) && is_array($return_values)) {
					foreach ($return_values AS $id => $tmp_values) {
						$implemented_field_groups[$id] = $tmp_values;
					}
				}
			}
		}
		// Provide template overrides.
		$template_override_field_groups_path = $this->smarty->get_tpl(true) . 'content/field_groups';
		$field_groups_path = SITEPATH . '/modules/content/templates/field_groups';


		$content_type_tpl = $template_override_field_groups_path . '/' . $values['content_type'] . ".tpl";
		if (file_exists($content_type_tpl)) {
			$tpl_path = $template_override_field_groups_path . '/';
		}
		else {
			$content_type_tpl = $field_groups_path . '/' . $values['content_type'] . ".tpl";
			$tpl_path = $field_groups_path . '/';
		}

		$content_smarty->set_tpl($tpl_path);

		/**
		 * Provides hook: content_view_alter_data
		 *
		 * Allow other modules to change the content data
		 *
		 * You need to change it directly within the provided array
		 * so you need to also get the $data_array by reference
		 *
		 * @param string $content_type
		 *   The name of the content type
		 * @param array &$data_array
		 *   The data array passed by reference
		 */
		$this->core->hook('content_view_alter_data', array($values['content_type'], &$data_array));

		$field_group_dummy = new ContentTypeFieldGroupObj();
		$field_group_value_array = $field_group_dummy->load_multiple(array_keys($data_array), PDT_ARR, 'id');

		foreach ($data_array AS $field_group_id => &$field_group_values) {
			$field_group_values = array(
				'elements' => $field_group_values
			);
			if (!isset($field_group_value_array[$field_group_id])) {
				continue;
			}
			$field_group_values['field_group_config'] = $field_group_value_array[$field_group_id];
		}

		$content = "";
		if (file_exists($content_type_tpl)) {
			$content_smarty->assign_by_ref("data", $data_array);
			$content = $content_smarty->fetch($content_type_tpl);
		}
		else {

			foreach ($data_array AS $field_group_id => $fld_group_values) {
				if (file_exists($template_override_field_groups_path . '/' . $field_group_id . ".tpl")) {
					$field_group_tpl = $template_override_field_groups_path . '/' . $field_group_id . ".tpl";
				}
				else {
					$field_group_tpl = $field_groups_path . '/' . $field_group_id . ".tpl";
				}
				$group_obj = new ContentTypeFieldGroupObj($field_group_id);
				if (!file_exists($field_group_tpl)) {
					if (isset($implemented_field_groups[$group_obj->field_group]) && file_exists(SITEPATH . '/' . $implemented_field_groups[$group_obj->field_group]['template'])) {
						$field_group_tpl = SITEPATH . '/' . $implemented_field_groups[$group_obj->field_group]['template'];
					}
					elseif (file_exists($template_override_field_groups_path . '/' . $group_obj->field_group . ".tpl")) {
						$field_group_tpl = $template_override_field_groups_path . '/' . $group_obj->field_group . ".tpl";
					}
					else {
						$field_group_tpl = $field_groups_path . '/' . $group_obj->field_group . ".tpl";
					}
				}

				if (!file_exists($field_group_tpl)) {
					continue;
				}

				$content_smarty->clearAllAssign();
				$class = $group_obj->field_group;
				$class::parse_value($fld_group_values['elements']);

				$content_smarty->assign_by_ref("data", $fld_group_values);
				$content .= $content_smarty->fetch($field_group_tpl);
			}
		}

		if ($return_html == true) {
			return $content;
		}

		$view_links = array();

		//Just check the permission wether the use is logged in or not, else we would redirected to login page if user is not logged in
		if ($this->right_manager->has_perm("admin.content.create", false)) {
			$view_links[] = array(
				'href' => '/admin/content/view/' . $page_id,
				'title' => t("view")
			);
			$view_links[] = array(
				'href' => '/admin/content/edit/' . $page_id,
				'title' => t("edit")
			);
			$view_links[] = array(
				'href' => '/admin/content/revision_list/' . $page_id,
				'title' => t("revisions")
			);
		}

		//Just check the permission wether the use is logged in or not, else we would redirected to login page if user is not logged in
		if ($this->right_manager->has_perm("admin.translate", false)) {
			$view_links[] = array(
				'href' => '/admin/content/translate_list/' . $page_id,
				'title' => t("translate")
			);
		}

		$this->smarty->assign_by_ref("data", $content);
		$this->smarty->assign_by_ref("view_links", $view_links);
	}

	public function content_menu_chooser() {
		$this->title(t("Select a menu entry"), t("Please select the parent entry for this content page. Click on the +/- to show/hide subentries"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.content.create", true)) {
			throw new SoopfwNoPermissionException();
		}

		$entries = array();
		foreach ($this->db->query_slave_all("SELECT * FROM `" . MenuObj::TABLE . "`") AS $menu) {
			$menu_obj = new MenuObj($menu['menu_id']);
			$entries[] = array(
				'#title' => $menu_obj->title,
				'menu_id' => $menu_obj->menu_id,
				'#childs' => $menu_obj->get_menu_tree()
			);
		}

		$this->core->add_css('/css/jquery_soopfw/jquery.treeview.css');
		$this->core->add_js('/js/jquery_plugins/jquery.treeview.js');
		$this->smarty->assign_by_ref("data", $entries);
	}

	/**
	 * Action: list_content
	 * Lists all content pages, provide filter functionality to search for content
	 */
	public function list_content() {
		$this->title(t("list/search content"), t("Here we can search for content pages"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.content.create", true)) {
			throw new SoopfwNoPermissionException();
		}

		//Setup search form
		$form = new Form("search_content", t("search:"));
		$form->add(new Textfield("title", '', t("Title")));

		$this->lng->load_language_list('', array(), true);

		$options = array(
			'' => t('All')
		);

		$langs = array_merge($options, $this->lng->languages);
		$form->add(new Selectfield('language', $langs, $this->core->current_language, t("Language")));

		foreach ($this->db->query_slave_all("SELECT `content_type`, `display_name` FROM `" . ContentTypeObj::TABLE . "`") AS $content_type) {
			$options[$content_type['content_type']] = $content_type['display_name'];
		}
		$form->add(new Selectfield('content_type', $options, '', t("Content type")));

		$form->add(new Submitbutton("search_submit", t("Search"), "form_button"));

		$form->assign_smarty("search_form");

		//Check form and add errors if form is not valid
		$form->check_form();
		if ($form->is_submitted()) { //Search was submited
			//Set session key for server search values so a reload of a page will use the session values
			$this->session->set("search_content_overview", $form->get_values());
		}
		else {
			//Form was not submited so try to load session values
			$form->set_values($this->session->get("search_content_overview", array()));
		}




		$language_search = '';

		$filter = DatabaseFilter::create(PageObj::TABLE, 'p');
		//Build up where statement
		foreach ($this->session->get("search_content_overview", array()) AS $field => $val) {
			if (empty($val) || $field == 'search_submit') {
				continue;
			}
			if ($field == 'language') {
				$language_search = $val;
				continue;
			}

			if ($field == 'title') {
				$filter->join(PageRevisionObj::TABLE, "p.page_id = pr.page_id AND p.language = pr.language", 'pr');
				$filter->add_where($field, $this->db->get_sql_string_search($val, "*.*", false), 'LIKE', 'pr');
				continue;
			}

			$filter->add_where($field, $this->db->get_sql_string_search($val, "*.*", false), 'LIKE');
		}

		if (empty($language_search)) {
			$language_search = $this->core->current_language;
		}

		$filter->add_where('language', $language_search);

		//Init pager
		$max = $filter->select_count();
		$pager = new Pager(50, $max);
		$pager->assign_smarty("pager");

		$filter->add_column(array(
			'page_id',
			'deleted',
			'language',
			'last_revision',
			'last_modified',
			'last_modified_by',
		));

		$filter->join(UserObj::TABLE, "p.last_modified_by = u.user_id", 'u');
		$filter->add_column('`username` AS last_modified_by_username', 'u');

		$filter->join(ContentTypeObj::TABLE, "p.content_type = ct.content_type", 'ct');
		$filter->add_column('display_name', 'ct');
		$filter->limit($pager->max_entries_per_page());
		$filter->offset($pager->get_offset());

		//Search in DB
		$pages = $filter->select_all();

		$language_filter = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
		foreach ($this->lng->languages AS $key => $lang) {
			$language_filter->add_where('language', $key);
		}

		foreach ($pages AS &$page) {
			$filter = DatabaseFilter::create(PageRevisionObj::TABLE)
					->add_column('title')
					->add_column('language')
					->add_where('page_id', $page['page_id'])
					->add_where($language_filter);

			$page['translated'] = $filter->select_all('language');
			$page['title'] = $page['translated'][$language_search]['title'];
		}

		$this->smarty->assign("available_languages", $this->lng->languages);

		//Assign found content
		$this->smarty->assign_by_ref("pages", $pages);
	}

	/**
	 * Action: list_unreachable_content_types
	 * Lists all content pages, provide filter functionality to search for content
	 */
	public function list_unreachable_content() {
		$this->title(t("list unreachable content"), t("Here we see all content pages which are not reachable by a normal user if he do not know the direct link"));

		//Check perms
		if (!$this->right_manager->has_perm("admin.content.create", true)) {
			throw new SoopfwNoPermissionException();
		}

		$menu_entry_obj = new MenuEntryObj();
		$unreachable_menus = $menu_entry_obj->get_all_deactivated_menu_entries();
		foreach ($unreachable_menus AS &$val) {
			$val = Db::safe($val);
		}
		$unreachable_pages = $this->db->query_slave_all("
			SELECT *
			FROM `" . PageObj::TABLE . "` p
			WHERE p.`deleted` != '" . PageObj::DELETED_YES . "' AND p.`current_menu_entry_id` IN (" . implode(",", $unreachable_menus) . ")");

		foreach ($unreachable_pages AS &$page) {
			$revision_object = new PageRevisionObj($page['page_id'], $this->core->current_language);
			if ($revision_object->load_success()) {
				$page = array_merge($page, $revision_object->get_values());
			}
		}

		//Assign found servers
		$this->smarty->assign_by_ref("pages", $unreachable_pages);
	}

	/**
	 * Action: revision_list
	 * show a list for revisions for this page
	 */
	public function revision_list($page_id) {
		//Need to be logged in
		$this->session->require_login();

		$page = new PageObj($page_id);
		if (!$page->load_success()) {
			throw new SoopfwWrongParameterException(t('no such page'));
		}

		$page_revision = new PageRevisionObj($page_id);
		if (!$page_revision->load_success()) {
			throw new SoopfwWrongParameterException(t('no such page'));
		}
		$this->title(t("revision overview: @title", array("@title" => $page_revision->title)), t("this displays all available revisions for this page"));

		//Check perms
		if (!$this->right_manager->has_perm(array("admin.content.create", "admin.translate"))) {
			throw new SoopfwNoPermissionException();
		}

		$revisions = $this->db->query_slave_all("SELECT `page_id`,`title`, `revision`, `created`, `created_by` FROM `" . PageRevisionObj::TABLE . "` WHERE `page_id` = ipage_id AND `language` = @language ORDER BY `revision` DESC", array(
			'ipage_id' => $page_id,
			'@language' => $this->core->current_language
				));

		foreach ($revisions AS &$revision) {
			$user_obj = new UserObj($revision['created_by']);
			if ($user_obj->load_success()) {
				$revision['created_by'] = $user_obj->get_values();
			}
			else {
				unset($revision['created_by']);
			}
		}
		//Assign found revisions
		$this->smarty->assign_by_ref("revisions", $revisions);
	}

	/**
	 * Action: edit
	 * Save the page
	 * If revision is not provided it will use the latest revision.
	 *
	 * @param int $page_id the page id
	 * @param int $revision the revision id (optional, default = '')
	 */
	public function edit($page_id, $revision = '') {


		$this->title(t("change content"), t('Please fill out all required fields to create this content page.
Current language: [b]@language[/b]', array(
					'@language' => $this->core->current_language
				)));

		$page = new PageObj($page_id);
		if (!$page->load_success()) {
			throw new SoopfwWrongParameterException(t('No such page or wrong language'));
		}

		$page_revision = new PageRevisionObj($page_id, $this->core->current_language, $revision);
		if (!$page_revision->load_success()) {
			throw new SoopfwWrongParameterException(t('No such page or wrong language'));
		}

		$this->title(t("change content: @title", array("@title" => $page_revision->title)), t('Please fill out all required fields to create this content page.
Current language: [b]@language[/b]', array(
					'@language' => $this->core->current_language
				)));


		$values = array_merge($page->get_values(true), $page_revision->get_values(true));
		$values['serialized_data'] = json_decode($values['serialized_data'], true);

		$this->change_content($values);
	}

	/**
	 * Action: create
	 *
	 * Creates a page based up on the given content type,
	 * if content type is empty or not provided it will show up a list with all possible content types
	 *
	 * @param string $content_type
	 *   The content type. (optional, default = '')
	 */
	public function create($content_type = "") {
		$this->title(t("create content: @content_type", array("@content_type" => $content_type)), t('Please fill out all required fields to create this content page.
Current language: [b]@language[/b]', array(
					'@language' => $this->core->current_language
				)));

		if (!empty($content_type)) {
			$this->change_content($content_type);
		}
		else {
			//Check perms
			if (!$this->right_manager->has_perm("admin.content.create")) {
				throw new SoopfwNoPermissionException();
			}

			$this->smarty->assign_by_ref("list", $this->db->query_slave_all("SELECT `content_type`, `display_name` FROM `" . ContentTypeObj::TABLE . "`"));
		}
	}

	/**
	 * Action: translate
	 * translate a page, the prefilled data will be taken from the provided language
	 *
	 * @param int $page_id
	 *   The page id
	 * @param string $language
	 *   The language from which we want to translate
	 */
	public function translate($page_id, $language) {

		$this->title(t("translate"), t('Please fill out all required fields to create this content page'));

		if (!$this->right_manager->has_perm("admin.translate")) {
			throw new SoopfwNoPermissionException();
		}

		$page = new PageObj($page_id, $language);
		if (!$page->load_success()) {
			throw new SoopfwWrongParameterException(t('No such page'));
		}

		$page_revision = new PageRevisionObj($page_id, $language);
		if (!$page_revision->load_success()) {
			throw new SoopfwWrongParameterException(t('No such page'));
		}
		$this->title(t("translate: @title", array("@title" => $page_revision->title)), t('Please fill out all required fields to create this content page'));

		$values = array_merge($page->get_values(true), $page_revision->get_values(true));
		$values['serialized_data'] = json_decode($values['serialized_data'], true);

		$this->change_content($values, true);
	}

	/**
	 * Action: list_content_type
	 *
	 * List all content types.
	 *
	 * @throws SoopfwNoPermissionException
	 */
	public function list_content_types() {
		$this->title(t("Content types"), t('Add or change content types'));
		//Check perms
		if (!$this->right_manager->has_perm("admin.content.manage")) {
			throw new SoopfwNoPermissionException();
		}
		//Get content types and assign it
		$this->smarty->assign_by_ref("values", $this->db->query_slave_all("SELECT * FROM `" . ContentTypeObj::TABLE . "`"));
	}

	/**
	 * Action: translate_list
	 *
	 * Show a list for translateable options for this page (the languages in which we could translate).
	 *
	 * @param int $page_id
	 *   The content page id.
	 */
	public function translate_list($page_id) {
		//Need to be logged in
		$this->session->require_login();

		$this->title(t("Translate overview"), t("Choose what to translate"));

		//Check perms
		if (!$this->right_manager->has_perm(array("admin.content.create", "admin.translate"))) {
			throw new SoopfwNoPermissionException();
		}

		// Get already translated entries.
		$already_translated = $this->db->query_slave_all("
			SELECT cp.`page_id`,cpt.`title`, cpt.`language`
			FROM `" . PageObj::TABLE . "` cp
			JOIN `" . PageRevisionObj::TABLE . "` cpt ON (cp.`page_id` = cpt.`page_id` AND cp.`language` = cpt.`language` AND cp.`last_revision` = cpt.`revision`)
			WHERE cp.`page_id` = ipage_id", array(
			'ipage_id' => $page_id
		), 0, 0, 'language');
		$this->lng->load_language_list('', array(), true);

		$translations = $this->lng->languages;
		$fallback = $translations;
		unset($fallback[$this->core->current_language]);
		foreach ($translations AS $key => &$language) {
			$from_lang = $this->core->current_language;
			if ($key == $from_lang) {
				$from_lang = key($fallback);
			}
			$language = array(
				'language' => $language,
				'title' => t('create translation'),
				'link' => '/' . $key . '/admin/content/translate/' . $page_id . '/' . $from_lang
			);
			if (isset($already_translated[$key])) {
				$language['title'] = $already_translated[$key]['title'];
				$language['link'] = '/' . $key . '/admin/content/edit/' . $page_id;
			}
		}

		//Assign found servers
		$this->smarty->assign_by_ref("translations", $translations);
	}

	/**
	 * Action: manage_content_type
	 *
	 * Insert or add a content type.
	 * If $menu_id is provided, it will change this menu (save), else insert a new menu
	 *
	 * @param int $menu_id
	 *   The menu_id. (optional, default = 0)
	 */
	public function manage_content_type($content_type = "") {
		//Check perms
		if (!$this->right_manager->has_perm("admin.content.manage")) {
			throw new SoopfwNoPermissionException();
		}
		$force_loaded = false;
		$config_array = array();
		//Save variables
		if (!empty($content_type)) { //edit mode
			$this->title(t("Save content type"));

			//Load object
			$obj = new ContentTypeObj($content_type);
			$obj->get_dbstruct()->set_field_hidden("content_type");
			//Add save button
			$submit_button = new Submitbutton("save", t("save"));

			//Set form title
			$message = t("content type changed");

			$config_array = array(
				'content_type' => array(
					'title' => ''
				)
			);
		}
		else {
			$this->title(t("add content type"));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add"));

			//Set form title
			$message = t("content type added");

			//Create empty object
			$obj = new ContentTypeObj();

			//Set the force loaded to true, else the objForm will fill out the the default values
			$force_loaded = true;
		}

		$this->static_tpl = 'form.tpl';

		//Init objForm
		$obj_form = new ObjForm($obj, '', $config_array, $force_loaded);

		//Enable ajax
		$obj_form->set_ajax(true);

		//Add the button
		$obj_form->add($submit_button);

		//Add success ajax call to close the dialog
		$obj_form->add_js_success_callback("close_dialog");
		if (empty($content_type)) { //Insert mode
			$obj_form->add_js_success_callback("add_new_content_type_row");
		}
		else { //edit mode
			$obj_form->add_js_success_callback("replace_new_content_type_row");
		}

		//Assign form to smarty
		$obj_form->assign_smarty("form");

		//Check if form was submitted
		if ($obj_form->check_form()) {

			//If we are within insert mode, check if the entry already exists, if yes display the error
			if (empty($content_type)) {
				$obj_test = new ContentTypeObj($obj_form->get_value("content_type"));
				if ($obj_test->load_success()) {
					return $this->core->message(t("Could not insert content type, content type already exists"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
				}
			}

			//Check if the insert command returned true
			if ($obj_form->save_or_insert()) {
				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message($message, Core::MESSAGE_TYPE_SUCCESS, $obj_form->is_ajax(), $obj_form->get_object()->get_values(true));
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not save content type"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
			}
		}
	}

	/**
	 * Action: manage_content_type_fields
	 *
	 * List all configured content type fields where we can add or change content type fields.
	 *
	 * @param string $content_type
	 *   The content type.
	 *
	 * @throws SoopfwNoPermissionException
	 */
	public function manage_content_type_fields($content_type) {
		$this->title(t('"@content_type" fields', array("@content_type" => $content_type)), t('Add or change content type fields'));
		//Check perms
		if (!$this->right_manager->has_perm("admin.content.manage")) {
			throw new SoopfwNoPermissionException();
		}

		$this->core->add_js("/js/jquery_plugins/jquery.tablednd.js", Core::JS_SCOPE_SYSTEM);

		$this->core->js_config("content_type", $content_type);

		//Get content type field groups
		$this->smarty->assign_by_ref("field_groups", $this->db->query_slave_all("SELECT * FROM `" . ContentTypeFieldGroupObj::TABLE . "` WHERE `content_type` = @content_type ORDER BY `order`", array("@content_type" => $content_type)));
	}

	/**
	 * Action: change_content_type_field
	 *
	 * Insert or add a content type field group
	 * if $field_group_id is provided, it will change this field group (save), else insert a new field group
	 *
	 * @param string $content_type
	 *   The content type
	 * @param string $field_group_id
	 *   The field group id. (optional, default = "")
	 */
	public function change_content_type_field($content_type, $field_group_id = "") {
		//Check perms
		if (!$this->right_manager->has_perm("admin.content.manage")) {
			throw new SoopfwNoPermissionException();
		}
		$force_loaded = false;
		//Save variables
		if (!empty($field_group_id)) { //edit mode
			$this->title(t("Save field"));

			//Load object
			$obj = new ContentTypeFieldGroupObj($field_group_id);

			// Set not changeable values to hidden ones.
			$obj->get_dbstruct()->set_field_hidden("id");

			//Add save button
			$submit_button = new Submitbutton("save", t("save"));

			//Set form title
			$message = t("field changed");
		}
		else {
			$this->title(t("Add field"), t('Once you have added this field you <b>can\'t</b> change the field id and type anymore.'));

			//Add insert button
			$submit_button = new Submitbutton("add", t("add"));

			//Set form title
			$message = t("field added");

			//Create empty object and prefill with primary key
			$obj = new ContentTypeFieldGroupObj();

			//Set the force loaded to true, else the objForm will fill out the the default values
			$force_loaded = true;
		}

		$obj->content_type = $content_type;

		$this->static_tpl = 'form.tpl';

		$obj->get_dbstruct()->add_description('max_value', t('Provide the number 0 to get unlimited fields'));

		//Init objForm
		$obj_form = new ObjForm($obj, '', array(), $force_loaded);
		$obj_form->add(new Fieldset('default_values', t('Default field config')), array(), true);
		// If we are editing this field, we want to provide the field type as a string value for information.
		if (!empty($field_group_id)) {
			$element = new HtmlContainerInput(t('Type: @type', array('@type' => $obj->field_group)));
			$obj_form->add($element, array(), true);
		}



		$options = array();

		$dir = new Dir('modules/content/field_groups');
		$dir->just_files();
		foreach ($dir AS $entry) {
			$filename = $entry->filename;
			if (!preg_match("/^(FieldGroup(.+))\.class\.php$/", $filename, $matches)) {
				continue;
			}
			$options[$matches[1]] = $matches[2];
		}

		$additional_field_groups = $this->core->hook('content_add_field_groups');
		if (!empty($additional_field_groups)) {
			foreach ($additional_field_groups AS $return_values) {
				if (!empty($return_values) && is_array($return_values)) {
					foreach ($return_values AS $id => $values) {
						$options[$id] = $values['label'];
					}
				}
			}
		}

		// If we are editing the field we have to preload the additional config parameters if the field provides one,
		if (!empty($field_group_id)) {

			// Generate dummy form.
			$form = new Form('form_' . ContentTypeFieldGroupObj::TABLE, '', '');

			// Call the config method from the content field to get config parameters.
			$classname = $obj->field_group;
			$field_group_obj = new $classname();
			if (!empty($obj->config)) {
				$field_config = json_decode($obj->config, true);
				if (is_array($field_config)) {
					$field_group_obj->set_config($field_config);
				}
			}
			$field_group_obj->config($form);

			if (!empty($form->elements['visible'])) {
				$obj_form->add(new Fieldset('field_specific_value', t('Field specific config')));
			}
			// Get the elements
			foreach ($form->elements as &$fields) {
				foreach ($fields as &$element) {
					// Wrap our config array with an "config" array key, so we can easy get the additional config params.
					$element->config('name', 'config[' . $element->config('name') . ']');

					// Add the input to our real form.
					$obj_form->add($element);
				}
			}

			$input = new Hiddeninput('field_group', $obj->field_group);
		}
		else {
			$input = new Selectfield("field_group", $options, $obj->field_group, '', '', 'field_group_selector', "form_id_" . $obj->get_dbstruct()->get_table() . "_field_group");
			$input->add_validator(new RequiredValidator());
			$input->config('label', t('Field type'));

			$obj_form->add(new HtmlContainerInput('', 'field_group_selector_replace'));
		}

		$obj_form->add(new Fieldset('operation_buttons'));
		$obj_form->add($input);

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

			//If we are within insert mode, check if the entry already exists, if yes display the error
			if (empty($field_group_id)) {
				$obj_test = new ContentTypeFieldGroupObj($obj_form->get_value("id"));
				if ($obj_test->load_success()) {
					return $this->core->message(t("Could not insert field, field already exists"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
				}
			}

			// Set the group specific values.
			if (isset($_POST['config'])) {
				$obj_form->get_object()->config = json_encode($_POST['config']);
			}
			else {
				$obj_form->get_object()->config = '';
			}

			//Check if the insert command returned true
			if ($obj_form->save_or_insert()) {
				//Setup success message to display and return saved or inserted data (force return of hidden value to get insert id by boolean true)
				$this->core->message($message, Core::MESSAGE_TYPE_SUCCESS, $obj_form->is_ajax(), $obj_form->get_object()->get_values(true));
			}
			else {
				//Setup error message to display
				$this->core->message(t("Could not save field"), Core::MESSAGE_TYPE_ERROR, $obj_form->is_ajax());
			}
		}
	}

	/**
	 * Returns the alias for a page_id
	 *
	 * @param int $page_id
	 *   The page id.
	 * @param string $language
	 *   The language. (optional, default = '')
	 *
	 * @return string|boolean the alias, or if alias not exist returns false.
	 */
	public function get_alias_for_page_id($page_id, $language = '') {
		if (empty($language)) {
			$language = $this->core->current_language;
		}

		$alias_entry = $this->db->query_slave_first("SELECT `alias` FROM `" . UrlAliasObj::TABLE . "` WHERE `module` = 'content' AND `action` = 'view' AND `params` = 'ipage_id|current_language'", array("ipage_id" => $page_id, 'current_language' => $language));
		if (!empty($alias_entry)) {
			return $alias_entry['alias'];
		}

		return false;
	}

	/**
	 * Returns the translated link for the current content page
	 *
	 * @param string $language_key
	 *   the language key
	 *
	 * @return string the translated url.
	 */
	public function get_translation_link($language_key) {
		static $cache = array();
		list($url) = explode('?', $_SERVER['REQUEST_URI'], 2);

		// Remove all starting slashes.
		$url = preg_replace('/^\/+/is', '', $url);

		// Replace a possible language indicator.
		$url = preg_replace('/^[a-z]{2}\//is', '', $url);

		// Remove the file ending
		$url = preg_replace('/\.html?$/is', '', $url);

		// Check if cache has already this entry.
		if (!isset($cache[$url . "|" . $language_key])) {

			// Try to get the url alias for this url if cache was not setup.
			if (!isset($cache[$url])) {
				$alias_url = new UrlAliasObj();
				$alias_url->db_filter->add_where("alias", $url);
				$alias_url->load();

				// Check if the url alias exist, if not we must fallback to parent method.
				if (!$alias_url->load_success()) {
					$cache[$url] = false;
					$cache[$url . "|" . $language_key] = false;
					return parent::get_translation_link($language_key);
				}
				$cache[$url] = $alias_url->params;
			}
			// The url was cached but an alias could not be found, fallback to parent method.
			else if ($cache[$url] == false) {
				return parent::get_translation_link($language_key);
			}
			$data_array = explode("|", $cache[$url], 2);

			// Try to get the alias for this page id
			$alias = $this->get_alias_for_page_id($data_array[0], $language_key);

			// If nothing could be found use the page id fallback.
			if (empty($alias)) {
				$alias = 'content/view/' . $data_array[0];
			}

			// Setup the cache key with the translated link.
			$cache[$url . "|" . $language_key] = '/' . $language_key . '/' . $alias . '.html';
		}
		// The url and language was cached but an alias could not be found, fallback to parent method.
		else if ($cache[$url . "|" . $language_key] == false) {
			return parent::get_translation_link($language_key);
		}

		// Return the alias for this language.
		return $cache[$url . "|" . $language_key];
	}

	/**
	 * Create or change a content page.
	 *
	 * If $values is an array it will think it is within edit mode and therfore a hole page content value array must be present
	 * else within create mode just provide the content type as a string
	 *
	 * @param mixed $values
	 *   the content type in create mode or the prefilled values within edit mode
	 * @param boolean $force_create
	 *   if we want to force the create mode instead of save/insert
	 *   This is usefull if we have loaded a content page but changed for example the language
	 *   which does the translate behaviour.
	 *   So we do not need to copy all values to a new object, we can just use the verified loaded object.
	 *   It is important that the provided $values provides the primary key which is then unique. (optional, default = false)
	 */
	private function change_content($values, $force_create = false) {

		//Check perms
		if (!$this->right_manager->has_perm("admin.content.create")) {
			throw new SoopfwNoPermissionException();
		}

		// Setup static template, normal form is not a choice for this complex content form.
		$this->static_tpl = $this->module_tpl_dir . '/change_content.tpl';

		// Add needed JS-Files.
		$this->core->add_js("/js/jquery_plugins/jquery.tablednd.js");
		$this->core->add_js('/js/jquery_plugins/jquery.treeview.js');
		$this->core->add_js('/modules/content/js/change_content.js');

		// Add needed CSS-Files.
		$this->core->add_css('/css/jquery_soopfw/jquery.treeview.css');

		// Pre-init variables.
		$title_value = "";
		$menu_value = "";
		$old_menu_title = "";
		$old_menu_entry_id = "";
		$fill_values = array();
		$provided_load_values = $values;
		$create_mode = !is_array($values);

		if (!$create_mode) {
			// Edit mode.
			$content_type = $values['content_type'];
			$fill_values = $values['serialized_data'];
			$title_value = $values['title'];

			// If we have a menu entry.
			if (!empty($values['current_menu_entry_id'])) {

				// Try to read out previous menu entry.
				$menu_entry_obj = new MenuEntryObj($values['current_menu_entry_id']);
				$menu_entry_translation_obj = new MenuEntryTranslationObj($values['current_menu_entry_id']);

				if ($menu_entry_obj->parent_id == 0) {
					$menu_parent_obj = new MenuObj($menu_entry_obj->menu_id);
				}
				else {
					$menu_parent_obj = new MenuEntryTranslationObj($menu_entry_obj->parent_id, $this->core->current_language);
				}

				// Build our menu entry value.
				$menu_value = $menu_entry_obj->menu_id . ":" . $menu_entry_obj->parent_id . ": " . $menu_parent_obj->title;

				// Setup variables.
				$old_menu_entry_id = $menu_entry_obj->entry_id;
				$old_menu_title = $menu_entry_translation_obj->title;
			}
		}
		else {
			// Create mode.

			// Within create mode the given $values is the content type which we want to add, so before we clear the values
			// we need to store the content type in the $content_type variable.
			$content_type = $values;
			$values = array();
		}

		// At this point we have the content type, check that the given content type is a valid one.
		$content_type_obj = new ContentTypeObj($content_type);
		if (!$content_type_obj->load_success()) {
			return $this->wrong_params(t("No such content type"));
		}

		// This array holds ALL AbstractHtmlInput-Elements which we want to display.
		// This special behaviour is needed, because we can not use the default form-object for this complex one.
		// It has dynamic values, also while creating the content new input can be added through multi value inputs.
		// Also we can not simple get the HTML for the objects through get_html() or fetch() methods, because we need to
		// get the HTML AFTER we have checked that the elements are valid to get a correct invalid class if the input is invalid
		$objects_to_fetch_html = array();

		// Create the dummy form.
		$form = new Form("create_content_form");

		// Add the content title.
		$title = new Textfield("title", $title_value, t("title"), t("the page title"));
		$title->add_validator(new RequiredValidator());
		$objects_to_fetch_html[] = &$title;
		$form->add($title);

		// Add the menu chooser.
		$menu_chooser = new Textfield("menu_chooser", $menu_value, t("menu settings"), t("select the parent menu"));
		$menu_chooser->config('other', 'disabled="disabled"');
		$objects_to_fetch_html[] = &$menu_chooser;
		$form->add($menu_chooser);

		$menu_title = new Textfield("menu_title", $old_menu_title, t("menu title"), t("choose a good title for the menu entry, short is always better"));
		$objects_to_fetch_html[] = &$menu_title;
		$form->add($menu_title);

		// Add a hidden input which stores the menu entry value, this value will be really used to setup the menu point.
		$form->add(new Hiddeninput("menu_chooser_hidden", $menu_value));

		// Setup the force create input to have the value within the form values.
		$form->add(new Hiddeninput("force_create", $force_create));

		// Store the content type.
		$form->add(new Hiddeninput("content_type", $content_type));

		// Pre-init field group variable.
		$field_groups = array();

		// If we are on create mode make sure that we have an array for the fill_array which will be used to prefill
		// the content type fields.
		if (empty($fill_values)) {
			$fill_values = array();
		}

		// Get all content type fields for this content type.
		$filter = DatabaseFilter::create(ContentTypeFieldGroupObj::TABLE)
				->add_where('content_type', $content_type)
				->order_by('order');

		// Setup all content type fields.
		foreach ($filter->select_all() AS $field_group) {
			/* @var $field_object AbstractFieldGroup */
			$field_object = new $field_group['field_group']($fill_values);

			// Setup dynamic values.
			$field_object->set_label($field_group['name']);
			$field_object->set_prefix($field_group['id'], '0', ($field_group['max_value'] == 1) ? true : false);
			$field_object->set_max_value($field_group['max_value']);
			$field_object->set_required($field_group['required']);

			// If the content type field group defines configurations, provide the them to the field object.
			if (!empty($field_group['config'])) {
				$config = json_decode($field_group['config'], true);
				if (is_array($config)) {
					$field_object->set_config($config);
				}
			}

			// Add the field to the dummy form.
			$field_object->add_element_to_form($form);
			$field_groups[] = $field_object;
			$objects_to_fetch_html[] = $field_object;
		}

		// Setup the save/insert button.
		if ($create_mode) {
			$submit_button = new Submitbutton("submit", t("Create content"));
		}
		else {
			$submit_button = new Submitbutton("submit", t("Save content"));
		}
		$form->add($submit_button);

		// Add dummy element to get a label for the special options.
		$objects_to_fetch_html[] = new Hiddeninput('no_value_options', '', t('Options'));

		// Setup publish checkbox.
		$publish = new Checkbox("publish", 1, ($create_mode || !empty($values['last_revision'])) ? 1 : 0, t("publish"));
		$objects_to_fetch_html[] = &$publish;
		$form->add($publish);

		// Setup alias checkbox.
		$create_alias = new Checkbox("create_alias", 1, ($content_type_obj->create_alias == 'yes') ? 1 : 0, t("create auto alias?"));
		$create_alias->config('suffix', '<br />');
		$objects_to_fetch_html[] = &$create_alias;
		$form->add($create_alias);

		// Add cancel and delete button if we are within edit mode.
		if (!$create_mode) {
			$form->add(new Submitbutton("cancel", t("cancel"), 'form_button'));
			$form->add(new Submitbutton("delete", t("delete"), 'form_button'));

			// If we have the real delete permission add a delete button which will delete really the content page.
			// (The normal button will just mark it as deleted)
			if ($this->right_manager->has_perm("admin.content.delete")) {
				$form->add(new Submitbutton("really_delete", t("delete (really delete)!!!!")));
			}
		}

		// Assign the form to smarty.
		// This will hold all elements except the content type fields and some special one like the menu chooser.
		$form->assign_smarty();

		// If we pressed cancel
		if ($form->is_submitted("cancel")) {
			// Try to get the alias for the current page. if so redirect to it, else redirect to the pure content/view/{id} page.
			$alias = $this->get_alias_for_page_id($values['page_id']);
			if (empty($alias)) {
				$this->core->location("/content/view/" . $values['page_id']);
			}
			$this->core->location("/" . $alias . ".html");
		}
		// If we want to delete the content.
		else if ($form->is_submitted("delete") || $form->is_submitted("really_delete")) {
			$page_obj = new PageObj($provided_load_values['page_id'], $provided_load_values['language']);
			// Delete the cotnent, if really_delete was submitted delete the content really.
			if ($page_obj->delete($form->is_submitted("really_delete"))) {
				$this->core->message(t("Page deleted"), Core::MESSAGE_TYPE_SUCCESS);
				$this->core->location("/content/list_content");
			}
			else {
				$this->core->message(t("Page could not be deleted"), Core::MESSAGE_TYPE_ERROR);
			}
		}

		// Validate our content type fields.
		$groups_valid = true;
		foreach ($field_groups AS &$field_grp) {
			if (!$field_grp->is_valid()) {
				$groups_valid = false;
			}
		}

		// Only do something if we have validated our content type fields AND the form it self.
		// (Content type fields will be not checked within $form->check_form())
		if ($groups_valid && $form->check_form()) {

			// Reset alias variable.
			$alias = "";

			// Get all posted values.
			$values = $form->get_array_values(true);

			// Pre-init special values (values which are not content type field values)
			$title = $values['title'];
			$new_menu_title = $values['menu_title'];
			$new_menu = $values['menu_chooser_hidden'];
			$content_type = $values['content_type'];
			$force_create = $values['force_create'];
			if (isset($values['publish'])) {
				$publish = $values['publish'];
			}
			$create_alias = 1;
			if (isset($values['create_alias'])) {
				$create_alias = $values['create_alias'];
			}
			// After we have saved our special values, we unset them, so we can use the $values for the pure content type fields.
			unset($values['create_content_form_submit']);
			unset($values['save_content_form_submit']);
			unset($values['submit']);
			unset($values['cancel']);
			unset($values['delete']);
			unset($values['publish']);
			unset($values['create_alias']);
			unset($values['really_delete']);
			unset($values['content_type']);
			unset($values['title']);
			unset($values['menu_chooser']);
			unset($values['menu_chooser_hidden']);
			unset($values['menu_title']);
			unset($values['force_create']);

			// This is really needed :P many operations will be executed and if something breaks we should return to the
			// previous state without changing something.
			$this->db->transaction_begin();

			// Pre init if we want to force the page revision.
			$force_insert_page_translation = false;

			// If we are within create mode initialize page and page revision object.
			if ($create_mode) {

				// Initialize the page object and store directly the new page id and some static informations.
				$page_obj = new PageObj();
				$page_obj->page_id = $page_obj->get_free_id();
				$page_obj->language = $this->core->current_language;
				$page_obj->content_type = $content_type;

				// Setup the page revision
				$page_revision_obj = new PageRevisionObj();
				$page_revision_obj->page_id = $page_obj->page_id;
				$page_revision_obj->language = $this->core->current_language;
				$page_revision_obj->created_by = $this->session->current_user()->user_id;
			}
			// Edit mode.
			else {

				// Load the current page object.
				$page_obj = new PageObj($provided_load_values['page_id'], $provided_load_values['language']);

				// Get the current alias.
				$alias_string = $this->get_alias_for_page_id($page_obj->page_id);

				// If the current page object is a "marked as deleted" one we need to really insert a new revision no matter
				// if we did not change something.
				$force_insert_page_translation = ($page_obj->deleted == 'yes') ? true : false;

				// If not we need to check if we had no alias before and wanted now an alias, if so we also need to force insert it.
				if ($force_insert_page_translation === false) {
					$force_insert_page_translation = ($create_alias && $alias_string === false) ? true : false;
				}

				// Setup the language, this is needed because if we translate the current object it will have the old
				// language from the original source, but we want the current language for that object.
				$page_obj->language = $this->core->current_language;

				// Reset delete flag.
				$page_obj->deleted = 'no';

				// Load the last revision or if provided the provided revision.
				$page_revision_obj = new PageRevisionObj($provided_load_values['page_id'], $provided_load_values['language'], $provided_load_values['revision']);

				// Also update the language.
				$page_revision_obj->language = $this->core->current_language;
			}

			// Set the current user as the last modified user.
			$page_obj->last_modified_by = $this->session->current_user()->user_id;

			// Updated last modified date.
			$page_obj->last_modified = date(DB_DATETIME, TIME_NOW);

			// Update the edit count (how much this object was edited).
			$page_obj->edit_count++;

			// Set the title of the revision.
			$page_revision_obj->title = $title;

			// Insert all content type field values as a serialize value, this is usefull within getting the data back,
			// else we would need to get the values from the content_type_field_group_values table which is slower than
			// getting just the serialize data and decode it.
			$page_revision_obj->serialized_data = str_replace("\t", "    ", json_encode($values));

			// Setup publish variable to determine if we need to publish the content.
			$publish_ct = $content_type;
			if (empty($publish)) {
				// Set NS to unpublish it from solr if needed.
				$publish_ct = NS;
			}

			// Setup check variable if it is currently published.
			$old_published = !empty($page_obj->last_revision);

			// Setup check variable if revision value was changed.
			$revision_values_changed = !empty($page_revision_obj->values_changed);

			// Insert the revision.
			$revision_inserted = $page_revision_obj->insert(false, $force_insert_page_translation, $create_alias, $publish_ct);

			// If revision could be stored set the last_revision for the page_obj.
			if ($revision_inserted) {
				$page_obj->last_revision = $page_revision_obj->revision;
			}

			// If we do not want to publish it, remove the last_revision value.
			if (empty($publish)) {
				$page_obj->last_revision = "";
			}

			// Check if want to publish.
			$new_published = !empty($page_obj->last_revision);

			// We only need to manually update the solr index here if the revision value was not changed and
			// we changed the publish state, because if revision is changed it will be updated through the insert
			// process of the revision object.
			if ($revision_values_changed === false && $old_published != $new_published) {
				$page_revision_obj->update_solr($publish_ct);
			}

			// Pre-init variable.
			$inserted = false;

			// If the revision could be inserted, insert or save the page object.
			if ($revision_inserted) {
				// Insert it if we forced it, if we force it a unique primary key is mandatory.
				if ($force_create) {
					$inserted = $page_obj->insert();
				}
				else {
					$inserted = $page_obj->save_or_insert();
				}
			}

			// If the page object could be saved or inserted.
			if ($inserted) {

				// If we do not want to create an alias or the alias could not be found setup the direct content/view/{id} location.
				if ($create_alias === false || ($alias_string = $this->get_alias_for_page_id($page_obj->page_id)) === false) {
					$alias = '/' . $this->core->current_language . '/content/view/' . $page_obj->page_id;
				}
				// Setup the alias location.
				else {
					$alias = '/' . $alias_string . '.html';
				}

				//Just insert the content type fields and setup menu entry/alias if we have changed some values
				if ($force_create || $create_mode || $page_revision_obj->has_values_changed()) {
					//Insert all content type field values
					foreach ($values AS $content_type_field_group_id => $value_array) {
						if (!is_array($value_array)) {
							$value_array = array($value_array);
						}
						foreach ($value_array AS $index => $elements) {
							foreach ($elements AS $field_type => $value) {
								if (empty($value)) {
									continue;
								}
								$value_obj = new ContentTypeFieldGroupFieldValueObj();
								$value_obj->page_id = $page_obj->page_id;
								$value_obj->language = $page_obj->language;
								$value_obj->revision = $page_revision_obj->revision;
								$value_obj->content_type_field_group_id = $content_type_field_group_id;
								$value_obj->field_type = $field_type;
								$value_obj->index = $index;
								$value_obj->value = str_replace("\t", "    ", $value);
								if (!$value_obj->insert()) {
									$this->core->message(t("Could not store value entries"), Core::MESSAGE_TYPE_ERROR);
									$this->db->transaction_rollback();
									return;
								}
							}
						}
					}
				}

				//Add menu
				if ($menu_value != $new_menu || $old_menu_title != $new_menu_title) {

					/**
					 * If we want to change the menu but let the menu title empty or cleared it, we must
					 * use the page title to not have an empty menu entry.
					 */
					if (empty($new_menu_title) && !empty($new_menu)) {
						$new_menu_title = $page_revision_obj->title;
					}

					// Get the old menu values.
					$old_menu_values = array();
					if (!empty($menu_value)) {
						$old_menu_values = explode(":", $menu_value, 3);
					}

					// Get the new menu values.
					$new_menu_values = array();
					if (!empty($new_menu)) {
						$new_menu_values = explode(":", $new_menu, 3);
					}

					$entry_id = "";
					//Delete old menu entry if old menu was present but new menu is empty.
					if (!empty($old_menu_values) && empty($new_menu_values)) {
						$menu_obj = new MenuEntryTranslationObj($old_menu_entry_id, $this->core->current_language);
						if ($menu_obj->has_childs()) {
							$message_type = Core::MESSAGE_TYPE_NOTICE;
							$message = t("The menu entry \"@menu_entry\" was just disabled, because it has child elements, please look at the menu configuration to choose what to do with the menu entry", array('@menu_entry' => $menu_obj->title));
						}
						else {
							$message_type = Core::MESSAGE_TYPE_SUCCESS;
							$message = t("The menu entry \"@menu_entry\" is removed", array('@menu_entry' => $menu_obj->title));
						}
						if (!$menu_obj->save_delete()) {
							$this->core->message(t("Could not delete old menu entry"), Core::MESSAGE_TYPE_ERROR);
							$this->db->transaction_rollback();
							return;
						}

						$this->core->message($message, $message_type);
					}
					//Insert new entry, because we do not have an old menu entry.
					else if (empty($old_menu_values) && !empty($new_menu_values)) {
						$menu_obj = new MenuEntryObj();
						$menu_obj->menu_id = $new_menu_values[0];
						$menu_obj->parent_id = $new_menu_values[1];

						if (!$menu_obj->insert()) {
							$this->core->message(t("Could not create menu entry"), Core::MESSAGE_TYPE_ERROR);
							$this->db->transaction_rollback();
							return;
						}
						$entry_id = $menu_obj->entry_id;
						$menu_translation_obj = new MenuEntryTranslationObj();
						$menu_translation_obj->entry_id = $menu_obj->entry_id;
						$menu_translation_obj->language = $this->core->current_language;
						$menu_translation_obj->title = $new_menu_title;
						$menu_translation_obj->destination = $alias;
						if (empty($publish) || $publish == 'no') {
							$menu_translation_obj->active = 'no';
						}

						if (!$menu_translation_obj->insert()) {
							$this->core->message(t("Could not create menu entry"), Core::MESSAGE_TYPE_ERROR);
							$this->db->transaction_rollback();
							return;
						}
					}
					//Update current one
					else if (!empty($old_menu_values) && !empty($new_menu_values)) {
						$menu_obj = new MenuEntryObj($old_menu_entry_id);
						$menu_obj->menu_id = $new_menu_values[0];
						$menu_obj->parent_id = $new_menu_values[1];

						if ($menu_obj->load_success()) {
							$menu_result = $menu_obj->save();
						}
						else {
							$menu_result = $menu_obj->insert();
						}

						if (!$menu_result) {
							$this->core->message(t("Could not save menu entry"), Core::MESSAGE_TYPE_ERROR);
							$this->db->transaction_rollback();
							return;
						}
						$entry_id = $menu_obj->entry_id;
						$menu_translation_obj = new MenuEntryTranslationObj($old_menu_entry_id, $this->core->current_language);
						$menu_translation_obj->entry_id = $menu_obj->entry_id;
						$menu_translation_obj->language = $this->core->current_language;
						$menu_translation_obj->destination = $alias;
						$menu_translation_obj->title = $new_menu_title;
						if (empty($publish) || $publish == 'no') {
							$menu_translation_obj->active = 'no';
						}

						if ($menu_translation_obj->load_success()) {
							$menu_trans_result = $menu_translation_obj->save();
						}
						else {
							$menu_trans_result = $menu_translation_obj->insert();
						}

						if (!$menu_trans_result) {
							$this->core->message(t("Could not save menu entry translation"), Core::MESSAGE_TYPE_ERROR);
							$this->db->transaction_rollback();
							return;
						}
					}

					//Save the current menu entry if we changed it.
					$page_obj->current_menu_entry_id = $entry_id;
					$page_obj->save();
				}


				if ($force_create || $create_mode) {
					$this->core->message(t("Page created"), Core::MESSAGE_TYPE_SUCCESS);
				}
				else {
					$this->core->message(t("Page saved, new revision created"), Core::MESSAGE_TYPE_SUCCESS);
				}

				// Yay, all fine. commit all changes :)
				$this->db->transaction_commit();

				// Go to the created / edited page.
				if ($create_alias == true) {
					$this->core->location($alias);
				}
				else {
					$this->core->location("/content/view/" . $page_obj->page_id);
				}
			}
			else {
				// Uncool :(
				$this->core->message(t("Could not store page"), Core::MESSAGE_TYPE_ERROR);
				$this->db->transaction_rollback();
			}
		}

		// Pre init form content which will hold the html code for ALL displayed form elements.
		$form_content = "";

		// Loop through all elements which we want to display.
		foreach ($objects_to_fetch_html AS &$element) {

			// If the elment is an instance of AbstractFieldGroup it has no fetch() method as the other AbstractHtmlInputs
			// instead the method to get the html code is get_gtml().
			if ($element instanceof AbstractFieldGroup) {
				$form_content .= $element->get_html();
			}
			else {
				$form_content .= $element->fetch();
			}
		}

		// Assign the form content.
		$this->smarty->assign_by_ref("form_content", $form_content);
	}

	/**
	 *
	 * Initialize the widget, will also perform form handlings for the widget if needed.
	 * This method must perform all actions what the widget should can do.
	 *
	 * Use only the returned uuid to access the widget because non "word" character will be replaced
	 * to _ (underline)
	 *
	 * @param string $name
	 *   the widget name
	 * @param string $unique_id
	 *   the unique id for this widget
	 * @param Configuration $widget_config
	 *   the widget configuration object (optional, default = null)
	 *
	 * @return mixed the cleaned uuid or null if the widget name is not supported.
	 */
	public function get_widget($name, $unique_id, Configuration $widget_config = null) {
		$clean_uuid = WidgetHelper::clean_widget_id($unique_id);

		switch ($name) {
			case self::WIDGET_VIEWS:

				// Initialize default CommentWidgetConfiguration if nothin is provided or not a Configuration object.
				if (($widget_config instanceof Configuration) === false || $widget_config === null) {
					$widget_config = new ContentViewConfiguration();
				}

				// Check if we have configurated the mandatory configuration key VIEW_NAME.
				if (!$widget_config->is_set(ContentViewConfiguration::VIEW_NAME)) {
					$this->core->message(t('You have tried to display a "view" widget without configurate the VIEW_NAME within the Configuration, but the VIEW_NAME is mandatory'), Core::MESSAGE_TYPE_NOTICE);
					return $clean_uuid;
				}
				$template_file = "";
				// Get the view data.
				$ct_helper = new ContentHelper();
				$view_data = $ct_helper->get_view($widget_config->get(ContentViewConfiguration::VIEW_NAME), $template_file, $widget_config);

				// Register the widget and the template file.
				$this->core->register_widget('views', $template_file);

				// Append the view result.
				$this->smarty->append(array('views' => array($clean_uuid => &$view_data, $widget_config)), '', true);

				return $clean_uuid;
		}

		return null;
	}

}
