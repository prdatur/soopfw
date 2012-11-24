<?php

/**
 * Provide cli commando (clifs) to enable a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category CLI
 */
class cli_enable_module extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help.
	 *
	 * @var string
	 */
	protected $description = "Enables or updates a module.";

	/**
	 * Execute the command.
	 *
	 * @return boolean return true if no errors occured, else false.
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
			$this->core->message('Module not specified, after --enable_module you need to provide the module name like ./clifs --enable_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		$system_helper = new SystemHelper();

		// Get all dependencies which the current module needs. They need also be enabled if they are not already.
		$dependencies = $system_helper->get_module_dependencies($module, true, true, SystemHelper::DEPENDENCY_FILTER_DISABLED);
		if (!empty($dependencies)) {
			$msg = t("The module depends on the following modules, they will be also enabled/installed") . "\n";
			foreach ($dependencies AS $mod) {
				$msg .= " - " . $mod['name']. "\n   (" . $mod['description'] . ")\n";
			}
			// Display the confirmation message.
			$this->core->message($msg . "\n", Core::MESSAGE_TYPE_NOTICE);

			// Read the user input.
			if (!CliHelper::get_boolean_input(t("Proceed with module installation?"))) {
				$this->core->message(t('Module installation aborted'), Core::MESSAGE_TYPE_SUCCESS);
				return false;
			}
		}

		// Enable all dependencies.
		$system = new System();
		if (!empty($dependencies)) {
			foreach ($dependencies AS $mod => $val) {
				$system->install_module($mod);
				$this->core->message(t('Module "@module" enabled/updated', array("@module" => $val['name'])), Core::MESSAGE_TYPE_SUCCESS);
			}
		}

		// Enable the module.
		$system->install_module($module);
		return true;
	}

	/**
	 * Overrides CLICommand::on_success.
	 *
	 * callback for on_success
	 */
	public function on_success() {
		$this->core->message(t('Module enabled/updated.'), Core::MESSAGE_TYPE_SUCCESS);
	}

}


