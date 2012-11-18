<?php

/**
 * Provide cli commando (clifs) to disable a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category CLI
 */
class cli_disable_module extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help.
	 *
	 * @var string
	 */
	protected $description = "Disables a module.";

	/**
	 * Execute the command.
	 * 
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $argv;

		// Unset the first argument which is the filename.
		unset($argv[0]);
		$module = "";

		// Check if we have provided some arguments.
		if (!empty($argv)) {
			foreach ($argv AS $param) {
				// Skip option arguments.
				if (preg_match("/^-/", $param)) {
					continue;
				}

				// Add the first valid "module" param.
				$module = $param;
				break;
			}
		}

		// If we did not provide a module, display error and return.
		if (empty($module)) {
			$this->core->message('Module not specified, after --disable_module you need to provide the module name like ./clifs --disable_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		// Check that the module exist.
		$module_conf = new ModulConfigObj($module);
		if (!$module_conf->load_success()) {
			$this->core->message('Module configuration not found, please install it first with ./clifs --install_module ' . $module, Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		$system_helper = new SystemHelper();

		// Get all modules which depends on the module what should be disabled.
		$dependencies = $system_helper->get_dependet_modules($module, true, SystemHelper::DEPENDENCY_FILTER_ENABLED);

		// If we have some dependencies show warning message and wait for user input if we really want to disable the
		// module and the depended one's.
		if (!empty($dependencies)) {
			$msg = t("The following modules depends on the module you want to disabled, they also will be disabled") . "\n";
			foreach ($dependencies AS $mod) {
				$msg .= " - " . $mod['name']. "\n      (" . $mod['description'] . ")\n";
			}

			// Display the confirmation message.
			$this->core->message($msg . "\n", Core::MESSAGE_TYPE_NOTICE);

			// Read the user input.
			if (!CliHelper::get_boolean_input(t("Proceed with module disabling?"))) {
				$this->core->message(t('Module disabling aborted'), Core::MESSAGE_TYPE_SUCCESS);
				return false;
			}

			// Disable all depended modules first.
			foreach ($dependencies AS $mod => $val) {
				$dep_module_conf = new ModulConfigObj($mod);
				if (!$dep_module_conf->load_success()) {
					continue;
				}
				$dep_module_conf->enabled = 0;
				$dep_module_conf->save();

				// Delete all permissions for this module.
				$permissions = SystemHelper::get_module_permissions($mod, true);
				if (!empty($permissions) && SystemHelper::delete_permissions($permissions)) {
					$this->core->message(t("The following rights were removed:\n!rights", array("!rights" => implode("\n", $permissions))), Core::MESSAGE_TYPE_SUCCESS);
				}
				$this->core->message(t('Module "@module" disabled', array("@module" => $val['name'])), Core::MESSAGE_TYPE_SUCCESS);
			}
		}


		// Finally disable the module and remove all provided permissions.
		$module_conf->enabled = 0;
		$module_conf->save();

		$permissions = SystemHelper::get_module_permissions($module, true);
		if (!empty($permissions) && SystemHelper::delete_permissions($permissions)) {
			$this->core->message(t("The following rights were removed:\n!rights", array("!rights" => implode("\n", $permissions))), Core::MESSAGE_TYPE_SUCCESS);
		}
		return true;
	}

	/**
	 * Overrides CLICommand::on_success.
	 *
	 * callback for on_success
	 */
	public function on_success() {
		$this->core->message('Module disabled.', Core::MESSAGE_TYPE_SUCCESS);
	}

}


