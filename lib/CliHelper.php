<?php

/**
 * Provides a class to help command line apps
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Cli
 */
class CliHelper
{

	/**
	 * Reads a string from the command line.
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @return string
	 * 	 The entered string
	 */
	public static function get_line($question) {
		echo $question;
		return trim(fgets(STDIN));
	}

	/**
	 * Reads the user input and validate it against a "yes" input.
	 *
	 * The following values will be return true:
	 * 1,y,yes,z,zes,j,ja,true
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @param string $default
	 *   the default value which will be used if the user just press enter
	 * @return boolean
	 * 	 return true if the user entered a "true" (yes,y,j,ja,true,1)
	 *   special behaviour on z,zes for keyboard layout english/german
	 */
	public static function get_boolean_input($question, $default = false) {
		$line = strtolower(self::get_line($question . ' [' . (($default) ? 'y' : 'n') . ']:'));
		if (empty($line)) {
			$line = $default;
		}
		switch ($line) {
			case '1':
			case 'y':
			case 'yes':
			case 'z':
			case 'zes':
			case 'j':
			case 'ja':
			case 'true':
				return true;
		}
		return false;
	}

	/**
	 * Reads the user input and returns the value
	 *
	 * @param string $question
	 * 	 the message to show in front of the waiting input line
	 * @param string $default
	 *   the default value which will be used if the user just press enter
	 * @return string
	 * 	 returns the entered string, if left empty, default will returned
	 */
	public static function get_string_input($question, $default = '') {
		$line = self::get_line($question . ' [' . $default . ']:');
		if (empty($line)) {
			$line = $default;
		}
		return $line;
	}

	/**
	 * Returns the entered string but the input will not be visible to the user.
	 *
	 * @param string $prompt
	 *   The message to show
	 *
	 * @return string
	 * 	 The entered string
	 */
	public static function get_silent_string_input($prompt = "Enter Password:") {
		if (preg_match('/^win/i', PHP_OS)) {
			$vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
			file_put_contents(
					$vbscript, 'wscript.echo(InputBox("' . addslashes($prompt) . '", "", "password here"))');
			$command = "cscript //nologo " . escapeshellarg($vbscript);
			$password = rtrim(shell_exec($command));
			unlink($vbscript);
			return $password;
		}
		else {
			$command = "/usr/bin/env bash -c 'echo OK'";
			if (rtrim(shell_exec($command)) !== 'OK') {
				trigger_error("Can't invoke bash");
				return;
			}
			$command = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($prompt) . "\" mypassword && echo \$mypassword'";
			$password = rtrim(shell_exec($command));
			echo "\n";
			return $password;
		}
	}

}

