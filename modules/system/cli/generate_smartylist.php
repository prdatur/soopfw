<?php

/**
 * Provide cli commando (clifs) to re-generate the smartylist database (all allowed template dirs)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_generate_smartylist extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Re-create Smarty secure template directory list (It will scan all directories under SITEPATH and add directories which named 'templates')";

	/**
	 * Execute the command
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		if (!$this->core->create_smarty_sdi()) {
			consoleLog("config/smarty.php is not writeable", 'error');
		}
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		consoleLog('Smartylists generated', 'ok');
	}

}

?>
