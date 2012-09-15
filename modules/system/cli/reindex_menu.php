<?php

/**
 * Provide cli commando (clifs) to re-generate the menu index
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_reindex_menu extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Reindex the menu url alias, this must be done if you change something within any menu method";

	/**
	 * Execute the command
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		$this->core->reindex_menu();
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		console_log('Menu reindexed', 'ok');
	}

}

?>
