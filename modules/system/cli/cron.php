<?php

/**
 * Provide cli commando (clifs) to run the cron
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_cron extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 * @var string
	 */
	protected $description = "Run the cron.";

	/**
	 * Execute the command
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {

		$cron = new Cron();

		/**
		 * Provides hook: cron
		 *
		 * Allow other modules to run cron's
		 *
		 * @param Cron $cron
		 *   A cron object.
		 *   So we don't need to initialize this object within every hook
		 *   to use it.
		 *   Its just a helper for performance
		 */
		$this->core->hook('cron', array(&$cron));

		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
	}

}

?>