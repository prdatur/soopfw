<?php

/**
 * Provide cli commando (clifs) to enable a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_enable_module extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Enables a module.";

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
			console_log('Module not specified, after --enable_module you need to provide the module name like ./clifs --enable_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		$system_helper = new SystemHelper();
		$dependencies = $system_helper->get_module_dependencies($module, true, true, SystemHelper::DEPENDENCY_FILTER_DISABLED);
		if (!empty($dependencies)) {
			$msg = t("The module depends on the following modules, they will be also enabled/installed") . "\n";
			foreach ($dependencies AS $mod) {
				$msg .= " - " . $mod['name']. "\n   (" . $mod['description'] . ")\n";
			}
			echo $msg . "\n";
			if (!CliHelper::get_boolean_input(t("Proceed with module installation?"))) {
				console_log(t('Module installation aborted'), 'ok');
				return false;
			}
		}

		$system = new system();
		if (!empty($dependencies)) {
			foreach ($dependencies AS $mod => $val) {
				$system->install_module($mod);
				console_log(t('Module "@module" enabled', array("@module" => $val['name'])), 'ok');
			}
		}

		$system->install_module($module);
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		console_log('Module enabled.', 'ok');
	}

}

?>
