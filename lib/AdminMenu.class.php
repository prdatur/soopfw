<?php

/**
 * Provides a class to handle the administration menu.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Core
 */
class AdminMenu extends Object
{
	/**
	 * Define menu categories.
	 */
	const CATEGORY_STYLE = 'style';
	const CATEGORY_SECURITY = 'security';
	const CATEGORY_CONTENT = 'content';
	const CATEGORY_STRUCTURE = 'structure';
	const CATEGORY_AUTHENTICATION = 'auth';
	const CATEGORY_SYSTEM = 'system';
	const CATEGORY_OTHER = 'other';

	/**
	 * Get the admin menu.
	 */
	public function get() {
		// Only do something if we have the permission.
		if ($this->right_manager->has_perm("admin.user.show_admin_menu", false)) {

			// Prefill menu categories.
			$menu = array(

				// Content.
				self::CATEGORY_CONTENT => array(
					'#id' => 'soopfw_adminmenu_content',
					'#title' => t("Content"),
					'#childs' => array(),
				),

				// Style.
				self::CATEGORY_STYLE => array(
					'#id' => 'soopfw_adminmenu_style',
					'#title' => t("Style"),
					'#childs' => array(),
				),

				// Structure.
				self::CATEGORY_STRUCTURE => array(
					'#id' => 'soopfw_adminmenu_structure',
					'#title' => t("Structure"),
					'#childs' => array(),
				),

				// Security.
				self::CATEGORY_SECURITY => array(
					'#id' => 'soopfw_adminmenu_security',
					'#title' => t("Security"),
					'#childs' => array(),
				),

				// Authentication.
				self::CATEGORY_AUTHENTICATION => array(
					'#id' => 'soopfw_adminmenu_auth',
					'#title' => t("Authentication"),
					'#childs' => array(),
				),

				// Other.
				self::CATEGORY_OTHER => array(
					'#id' => 'soopfw_adminmenu_other',
					'#title' => t("Other"),
					'#childs' => array(),
				),

				// System.
				self::CATEGORY_SYSTEM => array(
					'#id' => 'soopfw_adminmenu_system',
					'#title' => t("System"),
					'#childs' => array(),
				),
			);
			$modules = $this->core->modules;

			// System module is always present.
			$modules[] = "system";


			/**
			 * Provides hook: admin_menu
			 *
			 * Returns an array which includes all links and childs for the admin menu.
			 * There are some special categories in which the module can be injected.
			 * The following categories are current supported:
			 *   style, security, content, structure, authentication, system, other
			 *
			 * @return array the menu
			 */
			foreach ($this->core->hook('admin_menu') AS $admin_menu) {
				foreach ($admin_menu AS $section => $menus) {
					if (isset($menu[$section])) {
						$menu[$section]['#childs'][] = $menus;
					}
					else {
						$menu[$section] = $menus;
					}

				}
			}
			
			// Unset all entries where we do not have permission.
			$this->unset_disallowed_entries($menu);

			// Assign the menu to smarty.
			$this->smarty->assign_by_ref("admin_menu", $menu);
		}
	}

	/**
	 * Recrusive method to unset all menu entries where we do not have the permission.
	 *
	 * @param array &$menu_entries
	 *   The menu entries.
	 */
	private function unset_disallowed_entries(&$menu_entries) {
		// Do nothing if &menu_entries are empty.
		if (empty($menu_entries)) {
			return;
		}

		// Only check if we have provided an array.
		if (is_array($menu_entries)) {

			// Go through all menu entries.
			foreach ($menu_entries AS $k => &$entry) {

				// If #perm is set check perms, unset the menu if we are not logged in or we not have the permission.
				if (isset($entry['#perm']) && (!$this->get_session()->is_logged_in() || !$this->right_manager->has_perm($entry['#perm']))) {
					unset($menu_entries[$k]);
					// We do not need to check for childs because we would not have the permission to show the child parent (this one).
					continue;
				}

				// If childs exist, check them too.
				if (isset($entry['#childs'])) {
					$this->unset_disallowed_entries($entry['#childs']);
				}
			}
		}
	}
}