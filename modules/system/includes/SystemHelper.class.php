<?php

/**
 * Provides helper methods for system operations.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Module
 */
class SystemHelper extends Object {

	/**
	 * Define consts.
	 */
	const DEPENDENCY_UNAVAILABLE = 0;
	const DEPENDENCY_ENABLED = 1;
	const DEPENDENCY_DISABLED = 2;

	const DEPENDENCY_FILTER_ALL = 0;
	const DEPENDENCY_FILTER_ENABLED = 1;
	const DEPENDENCY_FILTER_DISABLED = 2;

	/**
	 * Returns a list of modules which depends on the given one.
	 *
	 * @param string $module_name
	 *   the module name to be checked.
	 * @param boolean $recrusive
	 *   if set to true it will also get all dependet modules from the current
	 *   dependet module
	 *   it will not return a tree, only a flat list
	 *   (optional, default = false)
	 * @param int $filter
	 *   Only get the dependencies back which have the given state
	 *   Please use one of SystemHelper::DEPENDENCY_FILTER_*
	 *   (optional, default = 0)
	 *
	 * @return array returns an array with all modules which depends on the
	 *   given one
	 */
	public function get_dependet_modules($module_name, $recrusive = false, $filter = self::DEPENDENCY_FILTER_ALL) {

		$modules = array();

		foreach ($this->core->modules AS $module) {

			// Endless loop detection.
			if (isset($modules[$module])) {
				continue;
			}

			// If the module has no info file it is not a valid one.
			if (($info = SystemHelper::get_module_info($module)) === false) {
				continue;
			}

			// If the module does not depend on modules we can skip it.
			if (empty($info['depends']) || !is_array($info['depends'])) {
				continue;
			}

			// If the module depends the one $module_name.
			if (in_array($module_name, $info['depends'])) {

				// Only add it to the result if the provided filter matches.
				if (($filter === self::DEPENDENCY_FILTER_ALL) ||
					($filter === self::DEPENDENCY_FILTER_ENABLED && $this->core->module_enabled($module)) ||
					($filter === self::DEPENDENCY_FILTER_DISABLED && !$this->core->module_enabled($module))
				) {
					$modules[$module] = $info;
				}

				// If we recrusive want to scan, do it.
				if ($recrusive === true) {
					$modules = array_merge($modules, $this->get_dependet_modules($module, $recrusive));
				}
			}
		}

		return $modules;
	}

	/**
	 * Returns all dependencies and there state for the given module.
	 *
	 * @param string $module_name
	 *   the pure module name.
	 * @param boolean $recrusive
	 *   if set to true it will also get all dependencies from dependet modules
	 *   (optional, default = false)
	 * @param boolean $sort
	 *   this param makes only sense if $resrusive is set to true
	 *
	 *   if set to true it will reduce the recrusive array to a flat list
	 *   where the dependency modules which have the higheset depth will be at
	 *   the first position
	 *   (optional, default = false)
	 * @param array $loop_detection
	 *   This array will be INTERNAL ONLY used to detect endless loops, please
	 *   do NOT provide ANYTHING here (optional, default = array()
	 * @param int $filter
	 *   Only get the dependencies back which have the given state
	 *   Please use one of SystemHelper::DEPENDENCY_FILTER_*
	 *   (optional, default = 0)
	 *
	 * @return array returns an array which holds all dependency modules
	 *   (the pure name, not the name defined within the info file) and as
	 *   the value it holds an array with keys:
	 *     state = the state of the dependency as an integer based up on SystemHelper::DEPENDENCY_
	 *	   name = the name within the info file
	 *
	 *   If the state is SystemHelper::DEPENDENCY_UNAVAILABLE the name is also the pure module name.
	 *
	 *   if $recrusive is set to true and $sort to false each entry will have an additional key
	 *		dependencies = another array which holds the dependencies for this module
	 *
	 *   or boolean false if checked module does not exist,
	 */
	public function get_module_dependencies($module_name, $recrusive = false, $sort = false, $filter = self::DEPENDENCY_FILTER_ALL, $loop_detection = array()) {

		// Endless loop detection.
		$loop_detection[$module_name] = true;

		if (($info = SystemHelper::get_module_info($module_name)) === false) {
			return false;
		}

		if (empty($info['depends']) || !is_array($info['depends'])) {
			return array();
		}

		$results = array();
		foreach ($info['depends'] AS $dependency) {

			// Endless loop detection.
			if (isset($loop_detection[$dependency])) {
				echo $module_name . " = Endless loop detected for " . $dependency. ", skipping\n";
				continue;
			}

			// Check if it has the module info file.
			if (!file_exists(SITEPATH . '/modules/' . $dependency. '/' . $dependency . '.info')) {
				$results[$dependency] = array(
					'name' => $dependency,
					'description' => '-',
					'state' => self::DEPENDENCY_UNAVAILABLE
				);
				continue;
			}

			// Get the info for the current dependency
			if (($info_depends = SystemHelper::get_module_info($dependency)) === false) {
				continue;
			}

			// If module is system it will always be enabled.
			if ($dependency === 'system') {
				$results[$dependency] = array(
					'name' => $info_depends['name'],
					'description' => $info_depends['description'],
					'state' => self::DEPENDENCY_ENABLED
				);
				continue;
			}

			$value = array(
				'name' => $info_depends['name'],
				'description' => $info_depends['description'],
			);

			// Check if module is enabled.
			if ($this->core->module_enabled($dependency)) {
				$value['state'] = self::DEPENDENCY_ENABLED;
			}
			else {
				$value['state'] = self::DEPENDENCY_DISABLED;
			}

			// If recrusion is set to true, scan recrusive for dependet module dependencies.
			if ($recrusive === true) {
				$return = $this->get_module_dependencies($dependency, $recrusive, false, $loop_detection);
				$value['dependencies'] = (!is_array($return)) ? array() : $return;
			}
			if (($filter === self::DEPENDENCY_FILTER_ALL) ||
				($filter === self::DEPENDENCY_FILTER_ENABLED && $this->core->module_enabled($dependency)) ||
				($filter === self::DEPENDENCY_FILTER_DISABLED && !$this->core->module_enabled($dependency))
			) {
				$results[$dependency] = $value;
			}
		}

		if ($sort === true) {
			return $this->sort_dependencies($results);
		}

		return $results;
	}

	/**
	 * Sorts the dependency tree.
	 *
	 * @param array $array
	 *   the array tree of dependencies to be sorted.
	 *
	 * @return array the sorted flat array list
	 *   the values will be an array like you would get back from get_module_dependencies
	 *   without dependencies recrusive key, just name and state.
	 */
	private function sort_dependencies($array) {
		$return = array();
		foreach ($array AS $module => $val) {

			if (!empty($val['dependencies'])) {
				$return = $this->sort_dependencies($val['dependencies']);
			}

			if (isset($return[$module])) {
				continue;
			}

			if (isset($val['dependencies'])) {
				unset($val['dependencies']);
			}

			$return[$module] = $val;
		}

		return $return;
	}

	/**
	 * Returns all objects from the given module which needs an update.
	 *
	 * @param string $module
	 *   the module name.
	 *
	 * @return array returns an array with all objects which needs an update
	 *   the array values are CoreModelObjectObj's.
	 */
	public static function get_updateable_objects($module) {

		// Scan all module objects.
		$dir = new Dir("modules/" . $module . "/objects");

		$objects = array();
		foreach ($dir AS $entry) {

			// Validate the filename.
			if (preg_match("/(.*)\.class\.php$/", $entry->filename, $matches)) {
				$obj = $matches[1];

				// Try to load the object.
				$model_info = new CoreModelObjectObj($obj);

				// If the module exist and modify time has not changed we can skip it.
				if ($model_info->load_success() && $model_info->last_modified == filemtime($entry->path)) {
					continue;
				}
				$objects[$obj] = $model_info;
			}
		}
		return $objects;
	}

	/**
	 * Returns the info data for the given module.
	 *
	 * @staticvar array $cache
	 *   cached return values.
	 *
	 * @param string $module
	 *   the module name.
	 *
	 * @return array the parsed info data or if info file is not found boolean false
	 */
	public static function get_module_info($module) {
		static $cache = array();

		if (!isset($cache[$module])) {
			$info_file = SITEPATH . '/modules/' . $module . '/' . $module . '.info';
			if (!file_exists($info_file)) {
				$cache[$module] = false;
			}

			$info = parse_ini_file($info_file, true);
			if (empty($info)) {
				$cache[$module] = false;
			}
			$cache[$module] = $info;
		}

		return $cache[$module];
	}

	/**
	 * Returns an array with the permissions for the module.
	 *
	 * @static array $cache
	 *   will cache the results.
	 *
	 * @param string $module
	 *   the module.
	 *
	 * @param boolean $just_rights
	 *   if set to true we get no descriptions just an array with the permission
	 *   for the key and the value.
	 *
	 * @return array the permission array for the module
	 */
	public static function get_module_permissions($module, $just_rights = false) {
		static $cache = array();

		if (!isset($cache[$module . "|" . $just_rights])) {
			$info = SystemHelper::get_module_info($module);
			$rights = array();
			if (!empty($info['rights'])) {
				$rights = $info['rights'];
				if ($just_rights === true) {
					foreach ($rights AS $permission => &$description) {
						if (((int)$permission) . "" !== $permission . "") {
							$description = $permission;
						}
					}
				}
				else {
					$new_rights = array();
					foreach ($rights AS $permission => $description) {
						if (((int)$permission) . "" === $permission . "") {
							$permission = $description;
							$description = "";
						}
						$new_rights[$permission] = $description;
					}
					$rights = $new_rights;
					unset($new_rights);
				}
			}

			$cache[$module . "|" . $just_rights] = $rights;
		}

		return $cache[$module . "|" . $just_rights];
	}

	/**
	 * Removes all provided permissions from the database.
	 *
	 * This will only remove the information that the permission exist, it will not remove it from groups or users
	 * where the permissions are maybe configured.
	 * It is not necessary to delete this, because if we can not find the permission within our "exist" permission info
	 * the maybe "allowed" permission will be invalid at any rate.
	 *
	 * @param array $permissions
	 *   The permission which should be removed.
	 *
	 * @return boolean Returns true on success, else false.
	 */
	public static function delete_permissions(Array $permissions) {
		if (empty($permissions)) {
			return true;
		}

		// Generate the filter.
		$filter = DatabaseFilter::create(CoreRightObj::TABLE);

		// Add the conditions.
		$or = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
		foreach ($permissions AS $perm) {
			$or->add_where('right', $perm);
		}

		$filter->add_where($or);

		// Try to delete.
		return $filter->delete();
	}

	/**
	 * Inserts a log entry.
	 *
	 * @param string $message
	 *   the log message.
	 * @param string $type
	 *   the type, can be any string to identify this log entry better. (optional, default = 'default')
	 * @param int $log_level
	 *   the log level, use one of SystemLogObj::LEVEL_*
	 *   default is LEVEL_NORMAL (2) (optional, default = 2)
	 */
	public static function audit($message, $type = 'default', $log_level = 2) {
		if (class_exists('SystemLogObj')) {
			$log = new SystemLogObj();
			$log->type = $type;
			$log->message = $message;
			$log->log_level = $log_level;
			$log->insert();
		}
	}
}

