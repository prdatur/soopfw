<?php

/**
 * Test content module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class ContentTest extends WebUnitTest implements UnitTestInterface
{

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
	 * Checks main database functionality.
	 *
	 */
	public function check_content_type() {
		$this->login('admin_create');

		$this->do_get('/admin/content');
		$this->assert_web_regexp('/add content type/', t('Find "add content type" button'));

		$this->do_get('/admin/content/manage_content_type.ajax_html');
		$this->assert_web_regexp('/form_id_form_content_types_content_type/', t('Find content type add form'));

		$content_type = 'webtest_content_type';
		$content_type_description = 'Content type description';

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => $content_type_description,
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('The field "content type" is required.', $this->content['desc'], t('Check for valid missing param: content type'));
		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => $content_type,
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => '',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('The field "description" is required.', $this->content['desc'], t('Check for valid missing param: description'));

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => 'Content type description',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('content type added', $this->content['desc'], t('Check if content was successfully added'));

		$this->do_ajax_post('/admin/content/manage_content_type/webtest_content_type.ajax_html', array(
			'save' => 'save',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => 'Content type description changed',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('content type changed', $this->content['desc'], t('Check if content was successfully saved'));

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => 'Content type description',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->assert_equals('Could not insert content type, content type already exists', $this->content['desc'], t('Check for correct duplicated content type error message'));



		$this->do_ajax_post('/admin/content/delete_content_type.ajax', array(
			'content_type' => 'webtest_ct_delete_not_exist',
		));
		$this->assert_equals(405, $this->content['code'], t('Check if not existing content type was not deleted'));
		$this->assert_equals('no such content type', $this->content['desc'], t('Check if not existing content type was not deleted (on description)'));

		$this->do_ajax_post('/admin/content/manage_content_type.ajax_html', array(
			'content_type' => 'webtest_content_type_check_delete',
			'add' => 'add',
			'permission' => '',
			'create_alias' => 'yes',
			'description' => 'Content type description for delete',
			'form_content_types_submit' => $this->csrf_token,
		));

		$this->do_get('/admin/content/list_content_types');
		$this->assert_web_regexp('/Content type description changed/',t('Check for valid saved content type'));

		$this->do_get('/admin/content/manage_content_type_fields/' . $content_type);

		$this->assert_web_regexp('/"' . preg_quote($content_type, '/') . '" fields/',t('Check valid content type field manage page'));
		$this->assert_web_regexp('/>add new field</',t('Check valid add content type field button'));
		$this->assert_web_regexp('/>save new order</',t('Check if save new order button is found'));

		$this->do_get('/admin/content/change_content_type_field/' . $content_type . '.ajax_html');
		$this->assert_web_regexp('/form_id_form_content_types_field_groups_add/', t('Find content type field add form'));

		$default_field_types = array(
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
		foreach ($default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find default field type: @type', array('@type' => $type)));
		}


		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
		));
		$this->assert_equals('The field "id" is required.', $this->content['desc'], t('Check for valid missing param: id'));

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_textfield',
		));
		$this->assert_equals('The field "field type" is required.', $this->content['desc'], t('Check for valid missing param: field type'));

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_textfield',
			'field_group' => 'FieldGroupText',
		));
		$this->assert_equals('The field "name" is required.', $this->content['desc'], t('Check for valid missing param: name'));

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_textfield',
			'field_group' => 'FieldGroupText',
			'name' => 'Webtest Textfield',
		));
		$this->assert_equals('The field "required" is required.', $this->content['desc'], t('Check for valid missing param: required'));

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_textfield',
			'field_group' => 'FieldGroupText',
			'name' => 'Webtest Textfield',
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
		foreach ($default_field_types AS $type => $name) {
			$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
				'form_content_types_field_groups_submit' => $this->csrf_token,
				'add' => 'add',
				'id' => $name['id'],
				'field_group' => $type,
				'name' => $name['name'],
				'required' => 'no',
			));
		}

		$this->do_get('/admin/content/manage_content_type_fields/' . $content_type);

		// Check if default field types were added.
		foreach ($default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find added default field type: @type', array('@type' => $type)));
		}

		// Check save field
		$this->do_get('/admin/content/change_content_type_field/' . $content_type . '/webtest_field_group_text.ajax_html');
		$this->assert_web_regexp('/Save field/', t('Check save form'));
		$this->assert_web_regexp('/FieldGroupText"\s+selected/', t('Check field type: type'));
		$this->assert_web_regexp('/name="name"\s+value="Web test field text"/', t('Check field type: name'));
		$this->assert_web_regexp('/name="max_value"\s+value="1"/', t('Check field type: max_value'));
		$this->assert_web_regexp('/option\s+value="no"\s+selected/', t('Check field type: required'));
		$this->assert_web_regexp('/name="save"\s+id="form_id_form_content_types_field_groups_save"/', t('Check field edit save button'));

		$default_field_types['FieldGroupText']['name'] = 'Web test field text changed';
		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type . '/webtest_field_group_text.ajax_html', array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'save' => 'save',
			'field_group' => 'FieldGroupText',
			'name' => $default_field_types['FieldGroupText']['name'],
			'required' => 'no',
			'max_value' => '1',
		));
		$this->assert_equals('field changed', $this->content['desc'], t('Check if field was saved'));

		// Check again all default fields with changed field "webtest_field_group_text".
		$this->do_get('/admin/content/manage_content_type_fields/' . $content_type);

		// Check if default field types were added.
		foreach ($default_field_types AS $type => $name) {
			$this->assert_web_regexp('/' . preg_quote($type, '/') . '/', t('Find saved default field type: @type', array('@type' => $type)));
		}

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
			'form_content_types_field_groups_submit' => $this->csrf_token,
			'add' => 'add',
			'id' => 'webtest_field_group_text',
			'field_group' => 'FieldGroupText',
			'name' => 'duplicated',
			'required' => 'no',
		));
		$this->assert_equals('Could not insert field, field already exists', $this->content['desc'], t('Check if duplicated field error found'));

		$this->do_ajax_post('/admin/content/change_content_type_field/' . $content_type, array(
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
}

