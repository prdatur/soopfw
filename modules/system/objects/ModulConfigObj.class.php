<?php

/**
 * The modul config, this holds the information about a modul
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class ModulConfigObj extends AbstractDataManagement
{
	/**
	 * Define constances
	 */
	const TABLE = "modul_config";

	/**
	 *
	 * @param string $modul
	 *   the modul name (optional, default = '')
	 * @param boolean $force_db
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($modul = "", $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key(array("modul"));
		$this->db_struct->add_field("modul", t("Module"), PDT_STRING, '', 50);
		$this->db_struct->add_field("current_version", t("Current version"), PDT_INT, 1);
		$this->db_struct->add_field("enabled", t("Enabled"), PDT_INT, 1);

		if (!empty($modul)) {
			if (!$this->load(array($modul), $force_db)) {
				return false;
			}
		}
	}

	/**
	 * Save the given Data and also set the static cached variable within core.
	 *
	 * @param boolean $save_if_unchanged
	 *   Save this object even if no changes to it's values were made (optional, default = false)
	 *
	 * @return boolean true on success, else false
	 */
	public function save($save_if_unchanged = false) {

		$was_enabled = $this->old_values['enabled'];
		if (parent::save($save_if_unchanged)) {
			// We access for performance the values direct.
			$this->core->module_enabled($this->values['modul'], $this->values['enabled']);

			// We disabled the module and it was previous enabled so remove all permissions.
			if ($this->values['enabled'] == 0 && $was_enabled != $this->values['enabled']) {
				$this->delete_rights();
			}
			return true;
		}
		return false;
	}

	/**
	 * Delete the given data
	 *
	 * @return boolean true on success, else false
	 */
	public function delete() {
		$module = $this->values['module'];
		if (parent::delete()) {
			// We deleted the module so remove all permissions.
			$this->delete_rights($module);
			return true;
		}

		return false;
	}

	/**
	 * Delete all rights which the module has provided.
	 *
	 * @param string $module
	 *   the module, if not provided it will try to get the current one.
	 *
	 * @return boolean returns true on success, else false
	 */
	private function delete_rights($module = "") {
		if (empty($module)) {
			$module = $this->values['modul'];
		}

		if (empty($module)) {
			return false;
		}
		$permissions = SystemHelper::get_module_permissions($module, true);
		if (!empty($permissions) && count($permissions) > 0) {
			$where = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
			foreach ($permissions AS $permission) {
				$where->add_where('right', $permission);
			}

			$filter = DatabaseFilter::create(CoreRightObj::TABLE)
				->add_where($where);

			return $filter->delete();
		}
		return true;
	}


}

