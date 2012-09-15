<?php

/**
 * Provide cli commando (clifs) to re-generate the classlist database
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
		$this->generate_classlist();
		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		console_log('Classlists generated', 'ok');
	}

	/**
	 * Generate the classlist for spl auto class loader
	 */
	public function generate_classlist() {
		$argv = array(null,
			SITEPATH . '/', // root directory
			'true', // recursive?
			SITEPATH . '/config/classes.php', // filename or 'false' to display results
			'classes'   // variable name of Array
		);

		$argc = count($argv);

		$_SERVER['argc'] = $argc;
		$_SERVER['argv'] = $argv;

		require_once(SITEPATH . '/plugins/classlist_autogenerator.php');
		$this->core->load_classlist(true, true);
	}

}


