<?php

/**
 * Provide cli commando (drush) to re-generate the classlist database
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_generate_classlist extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 *
	 * @var string
	 */
	protected $description = "Recreate class list (you needs to do this each time you add a new class)";

	/**
	 * Execute the command
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $classes;
		$this->core->generate_classlist();
		include(SITEPATH.'/config/classes.php');
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		consoleLog('Classlists generated', 'ok');
	}

}

?>
