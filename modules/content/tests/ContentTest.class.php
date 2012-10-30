<?php

/**
 * Test content module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Testing
 */
class ContentTest extends WebUnitTest implements UnitTestInterface
{

	private $content_type = 'webtest_content_type';
	private $content_type_description = 'Content type description';

	private $default_field_types = array(
		'FieldGroupList' => array(
			'id' => 'webtest_field_group_list',
			'name' => 'Web test field list',
		),
		'FieldGroupUpload' => array(
			'id' => 'webtest_field_group_upload',
			'name' => 'Web test field upload',
		),
		'FieldGroupTextfield' => array(
			'id' => 'webtest_field_group_textfield',
			'name' => 'Web test field textfield',
		),
		'FieldGroupText' => array(
			'id' => 'webtest_field_group_text',
			'name' => 'Web test field text',
		),
		'FieldGroupLink' => array(
			'id' => 'webtest_field_group_link',
			'name' => 'Web test field link',
		),
		'FieldGroupWysiwyg' => array(
			'id' => 'webtest_field_group_wysiwyg',
			'name' => 'Web test field wysiwyg',
		),
	);

	private $post_values = array(
		'title' => 'test create content',
		'webtest_field_group_link[0][text]' => 'this is a link',
		'webtest_field_group_link[0][link]' => 'http://www.google.de',
		'webtest_field_group_list[0][list]' => 'list element',
		'webtest_field_group_textfield[0][text]' => 'textfield element',
		'webtest_field_group_text[0][text]' => 'testarea field',
		'webtest_field_group_wysiwyg[0][text]' => 'wysiwyg testarea field',
		'webtest_textfield[0][text]' => 'testarea2 field',
		'create_alias' => '0',
		'publish' => '0',
	);

	private $post_edit_values = array(
		'title' => 'test 2create content',
		'webtest_field_group_link[0][text]' => 'this is a link2',
		'webtest_field_group_link[0][link]' => 'http://www.google2.de',
		'webtest_field_group_list[0][list]' => 'list element2',
		'webtest_field_group_textfield[0][text]' => 'textfield element2',
		'create_alias' => '1',
		'webtest_field_group_text[0][text]' => 'testarea field2',
		'webtest_field_group_wysiwyg[0][text]' => 'wysiwyg testarea field2',
		'webtest_textfield[0][text]' => 'testarea2 field2',
	);

	private $nid = 0;

	/**
	 * Returns an array with all test methods.
	 * If an empty array is returned all class method will be used.
	 *
	 * @return array the array with all methods which should be used for unit tests.
	 */
	public function get_tests() {
		return array();
	}

	/**
	 * Check all needed module dependencies.
	 */
	public function check_dependencies() {
		$this->assert_true($this->core->module_enabled('translation'), t('Check dependency: translation'));
		$this->assert_true($this->core->module_enabled('user'), t('Check dependency: user'));
		$this->assert_true($this->core->module_enabled('menu'), t('Check dependency: menu'));
	}

	/**
	 * Checks content type overview.
	 */
	public function check_content_type() {
		$this->login('admin_create');
		$this->do_get('/admin/content');
		$this->assert_web_regexp('/add content type/', t('Find "add content type" button'));

	}

	/**
	 * Checks content type create.
	 */
	public function check_create_content_type() {
		$this->do_get('/admin/content/manage_content_type.ajax_html');
		$this->assert_web_regexp('/form_id_form_content_types_content_type/', t('Find content type add form'));

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => $this->content_type_description,
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('The field "Content type" is required.', $this->content['desc'], t('Check for valid missing param: content type'));
		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => $this->content_type,
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => '',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('The field "Displayed name" is required.', $this->content['desc'], t('Check for valid missing param: display_name'));

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => 'Content type description',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('content type added', $this->content['desc'], t('Check if content was successfully added'));
	}

	/**
	 * Checks editing a content type.
	 */
	public function check_change_content_type() {
		$this->do_ajax_post('/admin/content/manage_content_type/webtest_content_type.ajax_html', array(
			'save' => 'save',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => 'Content type description changed',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('content type changed', $this->content['desc'], t('Check if content was successfully saved'));
	}

	/**
	 * Checks adding a already existing content type.
	 */
	public function check_duplicated_content_type() {
		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => 'Content type description',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('Could not insert content type, content type already exists', $this->content['desc'], t('Check for correct duplicated content type error message'));
	}

	/**
	 * Checks for valid content type field form.
	 */
	public function check_content_type_fields() {
		$this->do_get('/admin/content/manage_content_type_fields/' . $this->content_type);

		$this->assert_web_regexp('/"' . preg_quote($this->content_type, '/') . '" fields/',t('Check valid content type field manage page'));
		$this->assert_web_regexp('/>add new field</',t('Check valid add content type field button'));
		$this->assert_web_regexp('/>save new order</',t('Check if save new order button is found'));

		$this->do_get('/admin/content/change_content_type_field/' . $this->content_type . '.ajax_html');
		$this->assert_web_regexp('/form_id_form_content_types_field_groups_add/', t('Find content type field add form'));

		foreach ($this->default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find default field type: @type', array('@type' => $type)));
		}
	}

	/**
	 * Checks correct error message within creating content type fields.
	 */
	public function check_content_type_create_field_errors() {
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
		));
		$this->assert_equals('The field "id" is required.
The field "field type" is required.
The field "name" is required.
The field "required" is required.', $this->content['desc'], t('Check for valid missing content create field params'));

	}

	/**
	 * Checks creating content type field.
	 */
	public function check_content_type_create_field() {
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_textfield',
			'field_group' => 'FieldGroupText',
			'name' => 'Webtest Textfield',
			'max_value' => 1,
			'required' => 'no',
		));
		$this->assert_equals('field added', $this->content['desc'], t('Check if field was added'));
		$this->assert_equals($this->content['data'], array(
			'content_type' => 'webtest_content_type',
            'id' => 'webtest_textfield',
            'field_group' => 'FieldGroupText',
            'name' => 'Webtest Textfield',
            'max_value' => 1,
            'required' => 'no',
		), t('Validate added field.'));

		//Add all default field types.
		foreach ($this->default_field_types AS $type => $name) {
			$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type, array(
				'form_content_types_field_groups_submit' => $this->csrf_token,
				'add' => 'add',
				'id' => $name['id'],
				'field_group' => $type,
				'name' => $name['name'],
				'max_value' => 1,
				'required' => 'no',
			));
		}

		$this->do_get('/admin/content/manage_content_type_fields/' . $this->content_type);

		// Check if default field types were added.
		foreach ($this->default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find added default field type: @type', array('@type' => $type)));
		}
	}

	/**
	 * Checks changeing a field.
	 */
	public function check_content_type_change_fields() {
		// Check save field
		$this->do_get('/admin/content/change_content_type_field/' . $this->content_type . '/webtest_field_group_text.ajax_html');
		$this->assert_web_regexp('/Save field/', t('Check save form'));
		$this->assert_web_regexp('/FieldGroupText"\s+selected/', t('Check field type: type'));
		$this->assert_web_regexp('/name="name"\s+value="Web test field text"/', t('Check field type: name'));
		$this->assert_web_regexp('/name="max_value"\s+value="1"/', t('Check field type: max_value'));
		$this->assert_web_regexp('/option\s+value="no"\s+selected/', t('Check field type: required'));
		$this->assert_web_regexp('/name="save"\s+id="form_id_form_content_types_field_groups_save"/', t('Check field edit save button'));

		$this->default_field_types['FieldGroupText']['name'] = 'Web test field text changed';
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type . '/webtest_field_group_text.ajax_html', array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'save' => 'save',
			'field_group' => 'FieldGroupText',
			'name' => $this->default_field_types['FieldGroupText']['name'],
			'required' => 'no',
			'max_value' => '1',
		));
		$this->assert_equals('field changed', $this->content['desc'], t('Check if field was saved'));

		// Check again all default fields with changed field "webtest_field_group_text".
		$this->do_get('/admin/content/manage_content_type_fields/' . $this->content_type);

		// Check if default field types were added.
		foreach ($this->default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find saved default field type: @type', array('@type' => $type)));
		}
	}

	/**
	 * Checks for valid error on adding an already existing field.
	 */
	public function check_content_type_duplicated_field() {
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_field_group_text',
			'field_group' => 'FieldGroupText',
			'name' => 'duplicated',
			'required' => 'no',
		));
		$this->assert_equals('Could not insert field, field already exists', $this->content['desc'], t('Check if duplicated field error found'));
	}

	/**
	 * Checks deleting a field.
	 */
	public function check_content_type_delete_field() {
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $this->content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_field_group_check_delete',
			'field_group' => 'FieldGroupText',
			'name' => 'check for deleted field',
			'required' => 'no',
		));

		$this->do_ajax_post('/admin/content/delete_field_group.ajax', array(
			'id' => 'webtest_field_group_check_deletef',
		));
		$this->assert_equals(405, $this->content['code'], t('Check if not existing field was not deleted'));
		$this->assert_equals('no such field', $this->content['desc'], t('Check if not existing field was not deleted (on description)'));

		$this->do_ajax_post('/admin/content/delete_field_group.ajax', array(
			'id' => 'webtest_field_group_check_delete',
		));
		$this->assert_equals(200, $this->content['code'], t('Check if field was deleted'));
	}

	/**
	 * Checks deleting a content type and included fields.
	 */
	public function check_delete_content_type() {

		$this->do_ajax_post('/admin/content/delete_content_type.ajax', array(
			'content_type' => 'webtest_ct_delete_not_exist',
		));
		$this->assert_equals(405, $this->content['code'], t('Check if not existing content type was not deleted'));
		$this->assert_equals('no such content type', $this->content['desc'], t('Check if not existing content type was not deleted (on description)'));

		// Create content type to delete.
		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type_check_delete',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'display_name' => 'Content type description for delete',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->do_get('/admin/content/list_content_types');
		$this->assert_web_regexp('/Content type description changed/',t('Check for valid saved content type'));

		$this->do_ajax_post('/admin/content/change_content_type_field/webtest_content_type_check_delete', array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_field_group_text_already_deleted',
			'field_group' => 'FieldGroupText',
			'name' => 'duplicated',
			'required' => 'no',
		));

		$this->do_ajax_post('/admin/content/delete_content_type.ajax', array(
			'id' => 'webtest_content_type_check_delete',
		));
		$this->assert_equals(406, $this->content['code'], t('Check for invalid param'));

		$this->do_ajax_post('/admin/content/delete_content_type.ajax', array(
			'content_type' => 'webtest_content_type_check_delete',
		));

		$this->assert_equals(200, $this->content['code'], t('Check if content type was deleted'));

		$this->do_ajax_post('/admin/content/delete_field_group.ajax', array(
			'id' => 'webtest_field_group_text_already_deleted',
		));

		$this->assert_equals(405, $this->content['code'], t('Check if content type field was already deleted by content type deletion'));
		$this->assert_equals('no such field', $this->content['desc'], t('Check if not existing field was not deleted (on description)'));
	}

	/**
	 * Check if config form is correct.
	 */
	public function check_config() {

		$this->do_get('/admin/content/config');

		if ($this->core->module_enabled('solr')) {
			$this->assert_web_regexp('/form_id_content_config_solr_server/i', t('Check if solr config was found if solr module is enabled'));
			$this->assert_web_regexp('/Content:\s*webtest_content_type/i', t('Check if content type checkbox is found'));

			$this->do_post('/admin/content/config', array(
				'solr_index_types[webtest_content_type]' => 'webtest_content_type',
				'saveconfig' => 'Save Config',
				'content_config_submit' => $this->csrf_token,
			));
			$this->assert_web_regexp('/Configuration saved/i', t('Check if config was saved'));
			$this->do_get('/admin/content/config');
			$this->assert_web_regexp('/name\s*=\s*"solr_index_types\[webtest_content_type\]"[^>]*checked\s*=\s*"checked"/i', t('Check if config was really saved'));
		}
		else {
			$this->assert_web_not_regexp('/form_id_content_config_solr_server/i', t('Check if solr config was not found if solr module is not enabled'));
		}
	}

	/**
	 * Check if all needed test languages are present or could be created
	 */
	public function check_content_language_dependencies() {
		// Get enabled languages.
		$enabled_languages = $this->lng->get_enabled_languages();

		// Activate english language if not already.
		if (!isset($enabled_languages['en'])) {
			$this->do_ajax_post('/admin/translation/language_change.ajax', array(
				'lang' => 'en',
				'value' => 1,
			));

			$this->assert_ajax_success(t('Check if english language could be enabled'));
		}

		// Activate german language if not already.
		if (!isset($enabled_languages['de'])) {
			$this->do_ajax_post('/admin/translation/language_change.ajax', array(
				'lang' => 'de',
				'value' => 1,
			));

			$this->assert_ajax_success(t('Check if german language could be enabled'));
		}

		// Get enabled languages again, because maybe we have activated a missing one.
		$enabled_languages = $this->lng->get_enabled_languages();

		// Check if needed languages are available.
		$this->assert_true(isset($enabled_languages['en']), t('Check if language is enabled: english'));
		$this->assert_true(isset($enabled_languages['de']), t('Check if language is enabled: english'));
	}

	/**
	 * Check content create / delete / translate..
	 */
	public function check_valid_create_content_form() {
		$this->do_get('/admin/content/create/webtest_content_type');
		$this->assert_web_regexp('/create content: webtest_content_type/', t('Check for correct create page'));
		$this->assert_form_exist('create_content_form', t('Check if create form exists'));
		$this->assert_form_field_exist('create_content_form','create_alias', t('Check if create alias form field exists'));

		$needed_fields = array(
			'title',
			'menu_chooser',
			'menu_title',
			'webtest_field_group_link_0_text',
			'webtest_field_group_link_0_link',
			'webtest_field_group_list_0_list',
			'webtest_field_group_textfield_0_text',
			'publish',
			'create_alias',
			'create_content_form_submit',
			'menu_chooser_hidden',
			'force_create',
			'webtest_field_group_text_0_text',
			'webtest_field_group_wysiwyg_0_text',
		);
		foreach ($needed_fields AS $field) {
			$this->assert_form_field_exist('create_content_form', $field, t('Check if form field exists "@field"', array(
				'@field' => $field,
			)));
		}
		$this->assert_form_field_tag_equals('create_content_form','create_alias', 'checked', 'checked', t('Check if create alias is checked by default'));
		$this->assert_form_field_tag_equals('create_content_form','publish', 'checked', 'checked', t('Check if publish is checked by default'));
	}

	/**
	 * Check content create / delete / translate..
	 */
	public function check_create_content() {
		$this->post_values['create_content_form_submit'] = $this->csrf_token;

		$this->do_post('/admin/content/create/webtest_content_type', array(
			'create_content_form_submit' => $this->csrf_token,
		));
		$this->assert_web_regexp('/The field "title" is required./', t('Check if title is required'));
		$this->do_post('/admin/content/create/webtest_content_type', $this->post_values);
		$this->assert_web_regexp('/<li>Page created<\/li>/', t('Check if page was created'));

		$this->do_get('/' . UrlAliasObj::get_alias_string('test create content') . '.html');

		$this->assert_web_not_regexp('/test create content/', t('Check if alias was not created'));

		// Create an alias.
		$this->post_values['create_alias'] = '1';
		$this->do_post('/admin/content/edit/1', $this->post_values);


		$this->do_get('/' . UrlAliasObj::get_alias_string('test create content') . '.html');

		foreach ($this->post_values AS $k => $value) {
			if ($k == 'create_content_form_submit' || $k == 'create_alias') {
				continue;
			}
			$this->assert_web_regexp('/' . preg_quote($value, '/') . '/', t('Check if content is available: @type', array('@type' => $k)));
		}


		$this->assert_true((preg_match("/\/admin\/content\/view\/([0-9]+)/", $this->content, $matches) !== false), t('Check for view link'));
		if ($this->assert_true(isset($matches[1]), t('Check if view link really exist'))) {
			$this->nid = (int)$matches[1];
		}
		$this->assert_true((preg_match('/"\/admin\/content\/edit\/' . $this->nid . '"/', $this->content, $matches) !== false), t('Check for edit link'));
		$this->assert_true((preg_match('/"\/admin\/content\/translate_list\/' . $this->nid . '"/', $this->content, $matches) !== false), t('Check for translation list link'));
		$this->assert_true((preg_match('/"\/admin\/content\/revision_list\/' . $this->nid . '"/', $this->content, $matches) !== false), t('Check for revision list link'));
	}

	/**
	 * Check if the revision list is correct for previous created content.
	 */
	public function check_content_valid_revisions() {
		$this->do_get('/admin/content/revision_list/' . $this->nid);

		$this->assert_web_regexp('/revision overview: test create content/', t('Check for valid revision list page'));
		$this->assert_web_regexp('/\/admin\/content\/view\/' . $this->nid . '\/1/', t('Check if created revision is listed'));
	}

	/**
	 * Check changeing a content.
	 */
	public function check_content_change_content() {
		$this->post_edit_values['create_content_form_submit'] = $this->csrf_token;

		$this->do_get('/admin/content/edit/' . $this->nid);
		$this->assert_form_field_tag_equals('create_content_form', 'title', 'value', 'test create content', t('Check if form field is prefilled with the correct values: title'));
		$this->assert_form_field_tag_exist_not('create_content_form', 'publish', 'checked', t('Check if form field is prefilled with the correct values: publish'));
		$this->assert_form_field_tag_exist('create_content_form', 'create_alias', 'checked', t('Check if form field is prefilled with the correct values: create alias'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_link_0_text', 'value', 'this is a link', t('Check if form field is prefilled with the correct values: link text'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_link_0_link', 'value', 'http://www.google.de', t('Check if form field is prefilled with the correct values: link url'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_list_0_list', 'value', 'list element', t('Check if form field is prefilled with the correct values: list'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_textfield_0_text', 'value', 'textfield element', t('Check if form field is prefilled with the correct values: textfield element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_text_0_text', 'value', 'testarea field', t('Check if form field is prefilled with the correct values: testarea element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_wysiwyg_0_text', 'value', 'wysiwyg testarea field', t('Check if form field is prefilled with the correct values: wysiwyg element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_textfield_0_text', 'value', 'testarea2 field', t('Check if form field is prefilled with the correct values: textfield2 element'));

		$this->do_post('/admin/content/edit/' . $this->nid, $this->post_edit_values);

		$this->assert_web_regexp('/<li>Page saved, new revision created<\/li>/', t('Check if page was saved'));

		$this->do_get('/' . UrlAliasObj::get_alias_string('test 2create content') . '.html');

		foreach ($this->post_edit_values AS $k => $value) {
			if ($k == 'create_content_form_submit' || $k == 'create_alias') {
				continue;
			}
			$this->assert_web_regexp('/' . preg_quote($value, '/') . '/', t('Check if edited content is available: @type', array('@type' => $k)));
		}
	}

	/**
	 * Check if publishing works.
	 */
	public function check_content_publishing() {

		$this->do_get('/user/logout');
		$this->do_get('/' . UrlAliasObj::get_alias_string('test 2create content') . '.html');

		$this->assert_web_regexp('/<li>No such page<\/li>/', t('Check if page was not published'));

		$this->login('admin_create');

		$this->do_get('/admin/content/edit/' . $this->nid . '/1');

		$this->assert_form_field_tag_equals('create_content_form', 'title', 'value', 'test create content', t('Revision: Check if form field is prefilled with the correct values: title'));
		$this->assert_form_field_tag_exist_not('create_content_form', 'publish', 'checked', t('Revision: Check if form field is prefilled with the correct values: publish'));
		$this->assert_form_field_tag_exist('create_content_form', 'create_alias', 'checked', t('Revision: Check if form field is prefilled with the correct values: create alias'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_link_0_text', 'value', 'this is a link', t('Revision: Check if form field is prefilled with the correct values: link text'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_link_0_link', 'value', 'http://www.google.de', t('Revision: Check if form field is prefilled with the correct values: link url'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_list_0_list', 'value', 'list element', t('Revision: Check if form field is prefilled with the correct values: list'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_textfield_0_text', 'value', 'textfield element', t('Revision: Check if form field is prefilled with the correct values: textfield element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_text_0_text', 'value', 'testarea field', t('Revision: Check if form field is prefilled with the correct values: testarea element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_field_group_wysiwyg_0_text', 'value', 'wysiwyg testarea field', t('Revision: Check if form field is prefilled with the correct values: wysiwyg element'));
		$this->assert_form_field_tag_equals('create_content_form', 'webtest_textfield_0_text', 'value', 'testarea2 field', t('Revision: Check if form field is prefilled with the correct values: textfield2 element'));

		// Publish.
		$this->post_edit_values['publish'] = '1';
		$this->do_post('/admin/content/edit/' . $this->nid, $this->post_edit_values);
		$this->do_get('/user/logout');
		$this->do_get('/' . UrlAliasObj::get_alias_string('test 2create content') . '.html');

		foreach ($this->post_edit_values AS $k => $value) {
			if ($k == 'create_content_form_submit' || $k == 'create_alias' || $k == 'publish') {
				continue;
			}
			$this->assert_web_regexp('/' . preg_quote($value, '/') . '/', t('Check if published content is available: @type', array('@type' => $k)));
		}

	}

	/**
	 * Checks deleting content.
	 */
	public function check_content_delete() {
		$this->login('admin_create');
		// Mark the content as deleted.
		$this->post_edit_values['delete'] = 'delete';
		$this->do_post('/admin/content/edit/' . $this->nid, $this->post_edit_values);
		$this->assert_web_regexp('/<li>page deleted<\/li>/', t('Check page is deleted (mark as deleted)'));

		$this->do_get('/user/logout');
		$this->do_get('/' . UrlAliasObj::get_alias_string('test 2create content') . '.html');

		$this->assert_web_not_regexp('/test 2create content/', t('Check if we can not reach the deleted content as anonymous user'));

		$this->login('admin_create');
		$this->do_get('/' . UrlAliasObj::get_alias_string('test 2create content') . '.html');
		$this->assert_web_not_regexp('/test 2create content/', t('Check if alias is removed'));

		$this->do_get('/admin/content/view/1');
		$this->assert_web_regexp('/test 2create content/', t('Check if content is present with direct code and admin permissions'));

		$this->do_get('/admin/content/revision_list/' . $this->nid);
		$this->assert_web_regexp('/\/admin\/content\/edit\/1\/1/', t('Check if first revision is present'));
		$this->assert_web_regexp('/\/admin\/content\/edit\/1\/2/', t('Check if second revision is present'));

		$this->post_edit_values['really_delete'] = 'delete (really delete)!!!!';
		$this->do_post('/admin/content/edit/' . $this->nid, $this->post_edit_values);
		$this->assert_web_regexp('/<li>page deleted<\/li>/', t('Check page is deleted (really)'));

		$this->do_get('/admin/content/view/1');
		$this->assert_web_not_regexp('/test 2create content/', t('Check if content is really deleted'));
	}
}

