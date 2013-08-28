<?php

/**
 * Test main web functionality of the Framework.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Testing
 */
class DefaultWebSystemTest extends WebUnitTest implements UnitTestInterface
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
	 * Checks if all web/ajax calls are correct.
	 */
	public function check_module_actions() {
		// Get all modulese.
		$dir = new Dir('modules', false);
		$dir->just_dirs();

		foreach ($dir AS $entry) {

			// Verify module info and entry file-
			$module_name = $entry->filename;
			$this->assert_true(file_exists($entry->path . '/' . $module_name . '.info'), t('Check for valid module info file: @module', array('@module' => $module_name)));
			$this->assert_true(file_exists($entry->path . '/' . $module_name . '.php'), t('Check for valid module entry file: @module', array('@module' => $module_name)));

			// Check if the module entry class exist.
			$module_class = WebAction::generate_classname($module_name);
			$this->assert_true(class_exists($module_class), t('Check if module class exist: @module', array('@module' => $module_class)));

			// Verify all ajax request files.
			$ajax_dir = new Dir('modules/' . $module_name . '/ajax', false);
			$ajax_dir->just_files();
			$ajax_dir->file_extension('php');

			foreach ($ajax_dir AS $ajax_entry) {
				// Remvoe the extension so we get the pure filename.
				$action = preg_replace("/\.php$/", "", $ajax_entry->filename);

				// Get the parsed class which is needed.
				$ajax_class = WebAction::generate_classname('ajax_' . $module_name . '_' . $action);

				// Check if the class exist.
				if ($this->assert_true(class_exists($ajax_class), t('Check if ajax class exist: @ajax', array('@ajax' => $ajax_class)))) {

					// Generate the ajax call class object to check if it extends the needed class.
					$obj = new $ajax_class();

					$this->assert_true(($obj instanceof AjaxModul), t('Check if ajax class extends AjaxModul: @ajax', array('@ajax' => $ajax_class)));

					// Check if the run method was implemented, so an ajax call can be executed.
					$this->assert_true(method_exists($obj, 'run'), t('Check if ajax class implements run method: @ajax', array('@ajax' => $ajax_class)));
				}
			}

			// Validate web actions directory.
			$web_action_dir = new Dir('modules/' . $module_name . '/actions', false);
			$web_action_dir->just_files();
			$web_action_dir->file_extension('php');

			foreach ($web_action_dir AS $web_action_entry) {
				// Remvoe the extension so we get the pure filename.
				$action = preg_replace("/\.php$/", "", $web_action_entry->filename);

				// Get the parsed class which is needed.
				$web_action_class = WebAction::generate_classname($module_name . '_' . $action);

				// Check if the class exist.
				if ($this->assert_true(class_exists($web_action_class), t('Check if action class exist: @class', array('@class' => $web_action_class)))) {

					// Generate the action call class object to check if it extends the needed class.
					$obj = new $web_action_class();

					$this->assert_true(($obj instanceof ActionModul), t('Check if "@class" class extends ActionModul', array('@class' => $web_action_class)));

					// Check if the run method was implemented, so an action call can be executed.
					$this->assert_true(method_exists($obj, $action), t('Check if "@class" class implements the "@action" method', array(
						'@class' => $web_action_class,
						'@action' => $action,
					)));
				}
			}
		}
	}

	/**
	 * Check if we can log in.
	 */
	public function check_root() {
		$this->do_get('/user/login.html');
		$this->assert_true((preg_match("/type=\"submit\"\s+value=\"([^\"]+)\"\s+name=\"soopfw_login\"/", $this->content, $matches) == 1), t('Validate user login form'));
		$this->do_post('/user/login.html', array(
			'soopfw_login' => $matches[1],
		), true);

		$this->assert_web_regexp('/You have entered a wrong username and\/or password\./', t('Check invalid login'));

		$this->do_post('/user/login.html', array(
			'soopfw_login' => $matches[1],
			'user' => 'admin_create',
			'pass' => 'admin',
		), true);

		$this->assert_web_regexp('/>My account<\/a>/', t('Check valid login'));
	}
}

