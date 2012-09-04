<?php

/**
 * Provide cli commando (clifs) to get the hashed value for a string
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_hash_password extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 *
	 * @var string
	 */
	protected $description = "Returns the hash for a password";

	/**
	 * Execute the command
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $classes;
		$hash_check = new PasswordHash();
		consoleLog('Hash: ' . $hash_check->hash_password(get_string_input('Plaintext:')));
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
