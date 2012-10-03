<?php

/**
 * Provide cli commando (clifs) to run unit tests.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_run_tests extends CLICommand
{

	/**
	 * Overrides CLICommand::description
	 * The description for help
	 *
	 * @var string
	 */
	protected $description = "Run all or a specific unit test (no parameter will list all available tests, provide 'all' to run all tests.";

	/**
	 * Execute the command
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		global $argv;
		$cmds = array();

		$classes = Core::get_classlist();

		//Search cli commands and setup long options array
		$c = 1;
		foreach ($classes['classes'] AS $class => $v) {
			if (in_array('UnitTestInterface', $v['implements'])) {
				if (preg_match('/^\/modules\/([^\/]+)\//', $v['path'], $matches)) {

					// Skip module if not enabled.
					if (!$this->core->module_enabled($matches[1])) {
						continue;
					}
				}
				$cmds[$c++] = $class;
			}

		}



		//Display help information if no argument supplied
		if (!isset($argv[2])) {

			echo "Options:\n";
			echo " -h, --help\t\tDisplay this Help\n";
			echo "\nTests available:\n";
			echo "all (runs all available tests)\n";
			foreach ($cmds AS $cmd) {
				echo $cmd . "\n";
			}
			echo "\nExample:\n";
			echo "php -f clifs.php --run_tests all\n";
			echo "./clifs.php -run_tests all\n";
		}

		//Run tests
		else {
			$test = $argv[2];
			if ($test == 'all') {
				$test = $cmds;
			}

			if (!is_array($test)) {
				$test = array($test);
			}

			$tester = new UnitTestRunner();
			$logs = $tester->run_tests($test);

			CliHelper::console_log(t('Complete tests executed: @num', array('@num' => $tester->failed_tests+$tester->passed_tests)), Core::MESSAGE_TYPE_SUCCESS);
			CliHelper::console_log(t('Tests passed: @num', array('@num' => $tester->passed_tests)), Core::MESSAGE_TYPE_SUCCESS);
			if ($tester->failed_tests > 0) {
				CliHelper::console_log(t('Tests failed: @num', array('@num' => $tester->failed_tests)), Core::MESSAGE_TYPE_ERROR);
				CliHelper::console_log('---------------------------------------------------------------------------------', Core::MESSAGE_TYPE_ERROR);
			}

			foreach ($logs AS $entry) {
				/* @var $entry UnitTestLog */
				if ($entry->passed !== true) {
					CliHelper::console_log('At file: ' . $entry->file . ', line: ' . $entry->line, Core::MESSAGE_TYPE_ERROR);
					CliHelper::console_log('Test: ' . $entry->class. '->' . $entry->function . '()', Core::MESSAGE_TYPE_ERROR);
					echo "\n";
					CliHelper::console_log($entry->description . ': ', Core::MESSAGE_TYPE_ERROR);
					CliHelper::console_log($entry->message, Core::MESSAGE_TYPE_ERROR);
					CliHelper::console_log('---------------------------------------------------------------------------------', Core::MESSAGE_TYPE_ERROR);
					echo "\n";
				}
			}
		}

		return true;
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		CliHelper::console_log('Test complete', 'ok');
	}

}


