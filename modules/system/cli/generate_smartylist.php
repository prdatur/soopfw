<?php

/**
 * Provide cli commando (clifs) to re-generate the smartylist database (all allowed template dirs)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category CLI
 */
class cli_generate_smartylist extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help.
	 *
	 * @var string
	 */
	protected $description = "Re-create Smarty secure template directory list (It will scan all directories under SITEPATH and add directories which named 'templates')";

	/**
	 * Execute the command.
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		if (!$this->create_smarty_sdi()) {
			$this->core->message("config/smarty.php is not writeable", Core::MESSAGE_TYPE_ERROR);
		}
		return true;
	}

	/**
	 * Overrides CLICommand::on_success.
	 *
	 * callback for on_success
	 */
	public function on_success() {
		$this->core->message('Smartylists generated', Core::MESSAGE_TYPE_SUCCESS);
	}

	/**
	 * Create the smarty secure directory index, only templates in this directories can be used within smarty.
	 *
	 * @return boolean true if smartylist could be written successfully, else false.
	 */
	public function create_smarty_sdi() {
		//If the config file is not writeable, return false
		if (!is_writable(SITEPATH . "/config/smarty.php")) {
			return false;
		}
		//Init dir array with default template directory
		$secure_dir = array(SITEPATH . "/templates");

		//Get all templates directories within modules
		$secure_dir = array_merge($secure_dir, $this->get_template_dirs("modules"));

		//Build the secure directory variable
		$content = "<?php\n\$secure_dir = " . var_export($secure_dir, true) . ";";

		//Store the file
		$fp = fopen(SITEPATH . "/config/smarty.php", "w+");
		$return = ( fwrite($fp, $content) !== false) ? true : false;
		fclose($fp);
		return $return;
	}

	/**
	 * Returns all directories which are template directories.
	 *
	 * @param string $current_dir
	 *   current / start directory.
	 *
	 * @return array a list of all path which has a templates directory.
	 */
	private function get_template_dirs($current_dir) {
		$tmp_array = array();
		$dir = new Dir($current_dir);
		$dir->just_dirs();
		$dir->file_regexp('.*templates');
		foreach ($dir AS $entry) {
			$tmp_array[] = $entry->path;
		}
		return $tmp_array;
	}

}


