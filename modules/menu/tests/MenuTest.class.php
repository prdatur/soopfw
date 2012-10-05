<?php

/**
 * Test menu module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Testing
 */
class MenuTest extends WebUnitTest implements UnitTestInterface
{

	/**
	 * Define test variables which are used on other tests also.
	 */
	private $created_menu_id = "first_test_menu";


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
	}

	/**
	 * Checks menu overview
	 *
	 */
	public function check_menu_overview() {
		$this->login('admin_create');
		$this->do_get('/admin/menu/overview');
		$this->assert_web_regexp('/>add menu</', t('Check if we are on the correct menu page.'));
	}

	/**
	 * Checks menu overview
	 *
	 */
	public function check_menu_create() {
		// Check create menu form.
		$this->do_get('/admin/menu/change_menu.ajax_html');
		$this->assert_form_field_exist('form_menu_menu', 'menu_id', t('Check if form field is present: form_menu_menu/menu_id'));
		$this->assert_form_field_exist('form_menu_menu', 'title', t('Check if form field is present: form_menu_menu/title'));
		$this->assert_form_field_exist('form_menu_menu', 'add', t('Check if form field is present: form_menu_menu/submit'));
		$this->assert_form_field_exist('form_menu_menu', 'form_menu_menu_submit', t('Check if form field is present: form_menu_menu/csrf security'));

		// Check if menu id is required.
		$this->do_ajax_post('/admin/menu/change_menu.ajax_html', array(
			'form_menu_menu_submit' => $this->csrf_token,
		));
		$this->assert_ajax_description("The field \"menu id\" is required.
The field \"title\" is required.", t('Check if menu id is required'));

		// Check if menu title is required.
		$this->do_ajax_post('/admin/menu/change_menu.ajax_html', array(
			'menu_id' => $this->created_menu_id . $this->special_char_string,
			'title' => 'First menu ' . $this->special_char_string,
			'form_menu_menu_submit' => $this->csrf_token,
		));
		$this->assert_ajax_description("A menu_id can only have alphanumeric chars [a-z] and underlines. Spaces and numbers are not allowed.", t('Check if menu id has valid chars'));

		// Check if menu was created.
		$this->do_ajax_post('/admin/menu/change_menu.ajax_html', array(
			'menu_id' => $this->created_menu_id,
			'title' => 'First menu ' . $this->special_char_string,
			'form_menu_menu_submit' => $this->csrf_token,
		));
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if menu id was created (code)'));
		$this->assert_ajax_description("menu added", t('Check if menu id was created (description)'));

		$this->do_get('/admin/menu/overview');
		$this->assert_web_regexp("/" . preg_quote('First menu -._~!$&#039;&quot;()*@[]?&amp;+%#,;=:%26%2B%2F%3Féøïвβ中國', '/') . "/", t('Check if created menu is listed within the overview list'));
	}

	/**
	 * Checks for correct menu changes.
	 */
	public function check_menu_change() {
		// Check change menu.
		$this->do_get('/admin/menu/change_menu/' . $this->created_menu_id . '.ajax_html');
		$this->assert_form_field_exist('form_menu_menu', 'menu_id', t('Check if form field is present: form_menu_menu/menu_id'));
		$this->assert_form_field_exist('form_menu_menu', 'title', t('Check if form field is present: form_menu_menu/title'));
		$this->assert_form_field_exist('form_menu_menu', 'save', t('Check if form field is present: form_menu_menu/save'));
		$this->assert_form_field_exist('form_menu_menu', 'form_menu_menu_submit', t('Check if form field is present: form_menu_menu/csrf security'));

		$this->assert_form_field_tag_equals('form_menu_menu', 'menu_id', 'value', $this->created_menu_id, t('Check if field is correct prefilled: form_menu_menu/menu id'));
		$this->assert_form_field_tag_equals('form_menu_menu', 'title', 'value', 'First menu -._~!$\'&quot;()*@[]?&amp;+%#,;=:%26%2B%2F%3Féøïвβ中國', t('Check if field is correct prefilled: form_menu_menu/title'));

		// Change the title with invalid menu id.
		$values = $values_correct = $this->form_parser->get_form_values('form_menu_menu');
		$values['menu_id'] = 'new title' . $this->special_char_string;
		$values['title'] = $values_correct['title'] = 'new title';
		$this->do_ajax_post('/admin/menu/change_menu/' . $this->created_menu_id . '.ajax_html', $values);
		$this->assert_ajax_description("A menu_id can only have alphanumeric chars [a-z] and underlines. Spaces and numbers are not allowed.", t('Check if menu id has valid chars'));

		// Change the menu now.
		$this->do_ajax_post('/admin/menu/change_menu/' . $this->created_menu_id . '.ajax_html', $values_correct);
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if menu id was changed (code)'));
		$this->assert_ajax_description("menu changed", t('Check if menu id was changed (description)'));
	}

	/**
	 * Check menu delete.
	 */
	public function check_menu_delete() {
		// Create menu which can be deleted.
		$this->do_ajax_post('/admin/menu/change_menu.ajax_html', array(
			'menu_id' => 'delete_menu',
			'title' => 'delete_menu',
			'form_menu_menu_submit' => $this->csrf_token,
		));

		// Check if menu id param is required.
		$this->do_ajax_post('/admin/menu/delete_menu.ajax');
		$this->assert_ajax_code(AjaxModul::ERROR_MISSING_PARAMETER, t('Check if params are correct missing (menu_id)'));

		// Check if menu is deleted.
		$this->do_ajax_post('/admin/menu/delete_menu.ajax', array(
			'menu_id' => 'delete_menu',
		));
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if menu is deleted'));

		// Validate deleted menu within overview.
		$this->do_get('/admin/menu/overview');
		$this->assert_web_not_regexp("/delete_menu/", t('Check if deleted menu does not appear within overview list'));
	}

	/**
	 * Checks creating menu entries.
	 */
	public function check_menu_entry_add() {

		$this->do_get('admin/menu/entries/' . $this->created_menu_id);
		$this->assert_web_regexp('/>add menu entry</', t('Check for correct entry list'));

		// Check create form.
		$this->do_get('/admin/menu/change_menu_entry/' . $this->created_menu_id . '.ajax_html');
		$this->assert_form_exist('form_menu_entry_translation', t('Check if add menu entry form exists'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'language',  t('Check if add menu entry form field exists: language'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'title',  t('Check if add menu entry form field exists: title'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'perm',  t('Check if add menu entry form field exists: perm'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'always_open',  t('Check if add menu entry form field exists: always_open'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'destination',  t('Check if add menu entry form field exists: destination'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'active',  t('Check if add menu entry form field exists: active'));
		$languages = $this->form_parser->get_form_field('form_menu_entry_translation', 'language');
		$this->assert_true(isset($languages['options']['de']), t('Check if language german is present in select field'));
		$this->assert_true(isset($languages['options']['en']), t('Check if language english is present in select field'));

		// Check required messages.

		// Check field: language
		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '.ajax_html', array(
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));
		$this->assert_ajax_code(AjaxModul::ERROR_DEFAULT, t('Check if ajax error code is present for missing param'));
		$this->assert_ajax_description('The field "Language" is required.
The field "title" is required.
The field "destination" is required.
The field "active" is required.', t('Check for missing create menu entry params'));

		// Check if menu is created.
		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '.ajax_html', array(
			'language' => 'en',
			'title' => 'Menu entry 1',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if create menu entry result code is success'));
		$this->assert_ajax_description('menu entry added', t('Check if return ajax description was a success'));

		// Check if created menu entry is really created and listed in the menu entry list.
		$this->do_get('admin/menu/entries/' . $this->created_menu_id);
		$this->assert_web_regexp('/Menu entry 1/', t('Check if menu entry was really created'));
	}

	/**
	 * Checks changing a menu entry.
	 */
	public function check_menu_entry_change() {

		// Check change form.
		$this->do_get('/admin/menu/change_menu_entry/' . $this->created_menu_id . '/1.ajax_html');
		$this->assert_form_exist('form_menu_entry_translation', t('Check if change menu entry form exists'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'language',  t('Check if add menu entry form field exists: language'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'title',  t('Check if add menu entry form field exists: title'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'perm',  t('Check if add menu entry form field exists: perm'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'always_open',  t('Check if add menu entry form field exists: always_open'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'destination',  t('Check if add menu entry form field exists: destination'));
		$this->assert_form_field_exist('form_menu_entry_translation', 'active',  t('Check if add menu entry form field exists: active'));
		$languages = $this->form_parser->get_form_field('form_menu_entry_translation', 'language');
		$this->assert_true(isset($languages['options']['de']), t('Check if language german is present in select field'));
		$this->assert_true(isset($languages['options']['en']), t('Check if language english is present in select field'));

		// Check missing params
		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '/1.ajax_html', array(
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));

		$this->assert_ajax_code(AjaxModul::ERROR_DEFAULT, t('Check if ajax error code is present for missing params: menu entry change'));
		$this->assert_ajax_description('The field "Language" is required.
The field "title" is required.
The field "destination" is required.
The field "active" is required.', t('Check for missing param error messages: menu entry change'));

		// Check if menu entry is created.
		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '/1.ajax_html', array(
			'language' => 'en',
			'title' => 'Menu entry 1 changed',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if change menu entry result code is success'));
		$this->assert_ajax_description('menu entry changed', t('Check if return ajax description was a success'));

		// Check if created menu entry is really created and listed in the menu entry list.
		$this->do_get('admin/menu/entries/' . $this->created_menu_id);
		$this->assert_web_regexp('/Menu entry 1 changed/', t('Check if menu entry was really changed'));
	}

	public function check_menu_entry_delete() {

		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '.ajax_html', array(
			'language' => 'en',
			'title' => 'Menu entry 1 delete',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));
		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if create menu entry result code is success'));
		$this->assert_ajax_description('menu entry added', t('Check if return ajax description was a success'));

		$this->do_ajax_post('/admin/menu/delete_menu_entry_translation.ajax', array(
			'entry_id' => $this->content['data']['entry_id'],
		));

		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if menu entry is deleted.'));

		$this->do_get('admin/menu/entries/' . $this->created_menu_id);
		$this->assert_web_not_regexp('/Menu entry 1 delete/', t('Verify that menu is deleted.'));
	}

	public function check_menu_entry_translation() {

		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '.ajax_html', array(
			'language' => 'de',
			'title' => 'Not translated',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));
		$entry_id = $this->content['data']['entry_id'];
		$this->do_get('admin/menu/entries/' . $this->created_menu_id);
		$this->assert_web_regexp('/Not translated.*class\s*=\s*"\s*ui-icon\s*ui-icon-plus\s*"/s', t('Check if translateable entry exist'));

		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '/' . $entry_id . '.ajax_html', array(
			'language' => 'de',
			'title' => 'is translated',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));

		$this->assert_ajax_code(AjaxModul::ERROR_DEFAULT, t('Check if menu could not created by return code (already exists)'));
		$this->assert_ajax_description('Could not save menu entry', t('Check if menu could not created by return desc (already exists)'));

		$this->do_ajax_post('/admin/menu/change_menu_entry/' . $this->created_menu_id . '/' . $entry_id . '.ajax_html', array(
			'language' => 'en',
			'title' => 'is translated',
			'destination' => '/',
			'active' => 'yes',
			'form_menu_entry_translation_submit' => $this->csrf_token,
		));

		$this->assert_ajax_code(AjaxModul::SUCCESS, t('Check if menu could be translated'));
		$this->assert_ajax_description('menu entry added', t('Check if menu could be translated'));

		$this->do_get('admin/menu/entries/' . $this->created_menu_id);


		$this->assert_web_not_regexp('/Not translated.*class\s*=\s*"\s*ui-icon\s*ui-icon-plus\s*"/s', t('Check if translateable entry does not exist anymore'));
		$this->assert_web_regexp('/is translated/s', t('Verify that translation exist'));
	}
}