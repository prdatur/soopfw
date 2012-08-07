<?php

/**
 * Provide cli commando (clifs) to install a module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_install_module extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Installs a module. If it runs for the first time it will really install the module, on a second run it will update the module";

	/**
	 * Execute the command
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $argv;
		$system = new system();
		//$options_array = getopt('m:', array("module:"));
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
			consoleLog('Module not specified, after --install_module you need to provide the module name like ./clifs --install_module user', Core::MESSAGE_TYPE_ERROR);
			return false;
		}
		$system->install($module);
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		consoleLog('Module installed or updated.', 'ok');
	}

}

?>
