<?php

/**
 * Provide cli commando (clifs) to disable a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class cli_disable_module extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Disables a module.";

	/**
	 * Execute the command
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $argv;

		unset($argv[0]);
		$module = "";
		if (!empty($argv)) {
			foreach ($argv AS $param) {
				if (preg_match("/^-/", $param)) {
					continue;
				}
				$module = $param;
				break;
			}
		}
		if (empty($module)) {
			CliHelper::console_log('Module not specified, after --disable_module you need to provide the module name like ./clifs --disable_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}
		$module_conf = new ModulConfigObj($module);
		if (!$module_conf->load_success()) {
			CliHelper::console_log('Module configuration not found, please install it first with ./clifs --install_module ' . $module, Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		$system_helper = new SystemHelper();
		$dependencies = $system_helper->get_dependet_modules($module, true, SystemHelper::DEPENDENCY_FILTER_ENABLED);
		if (!empty($dependencies)) {
			$msg = t("The following module depends on the module you want to disabled, they will be also enabled/installed") . "\n";
			foreach ($dependencies AS $mod) {
				$msg .= " - " . $mod['name']. "\n   (" . $mod['description'] . ")\n";
			}
			echo $msg . "\n";
			if (!CliHelper::get_boolean_input(t("Proceed with module disabling?"))) {
				CliHelper::console_log(t('Module disabling aborted'), 'ok');
				return false;
			}
		}

		if (!empty($dependencies)) {
			foreach ($dependencies AS $mod => $val) {
				$dep_module_conf = new ModulConfigObj($mod);
				if (!$dep_module_conf->load_success()) {
					continue;
				}
				$dep_module_conf->enabled = 0;
				$dep_module_conf->save();
				$permissions = SystemHelper::get_module_permissions($mod, true);
				if (!empty($permissions)) {
					CliHelper::console_log(t("The following rights were removed:\n!rights", array("!rights" => implode("\n", $permissions))), 'ok');
				}
				CliHelper::console_log(t('Module "@module" disabled', array("@module" => $val['name'])), 'ok');
			}
		}

		$module_conf->enabled = 0;
		$module_conf->save();

		$permissions = SystemHelper::get_module_permissions($module, true);
		if (!empty($permissions)) {
			CliHelper::console_log(t("The following rights were removed:\n!rights", array("!rights" => implode("\n", $permissions))), 'ok');
		}
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		CliHelper::console_log('Module disabled.', 'ok');
	}

}


