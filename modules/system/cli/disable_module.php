<?php

/**
 * Provide cli commando (clifs) to disable a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
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
		foreach ($argv AS $param) {
			if (preg_match("/^-/", $param)) {
				continue;
			}
			$module = $param;
			break;
		}
		if (empty($module)) {
			consoleLog('Module not specified, after --disable_module you need to provide the module name like ./clifs --disable_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}
		$module_conf = new ModulConfigObj($module);
		if (!$module_conf->load_success()) {
			consoleLog('Module configuration not found, please install it first with ./clifs --install_module ' . $module, Core::MESSAGE_TYPE_ERROR);
			return false;
		}

		$module_conf->enabled = 0;
		$module_conf->save();
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		consoleLog('Module enabled.', 'ok');
	}

}

?>
